<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Project;
use App\Models\Record;
use App\Models\Survey;
use App\Models\Variable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

class EngineController extends Controller
{
    public function renderSurvey(Request $request) {
        try {
            /**
             * Get the survey data
             */
            $response = $this->getLiveProjectData2($request);
            $data = $response->getData();

            // Apply saved styles from the survey's config to the live engine data.
            if (!empty($data->surveys[0]->survey_id)) {
                $survey = Survey::find($data->surveys[0]->survey_id);
                if ($survey && !empty($survey->config['styles'])) {
                    if (!isset($data->config)) {
                        $data->config = new \stdClass();
                    }
                    $data->config->styles = $survey->config['styles'];
                }
            }
    
            /**
             * Check if the respondent is missing some information e.g. 'id'
             * if missing, then it will show the database error page
             */
            $this->checkRespondentInfo($request);
            
            /**
             * Check if the responded is done taking the survey
             * if done, then the status is complete
             * if complete, it will not take the survey again
             * thus it will redirect to the next part
             */
            $status = $this->checkRecordStatus($request);
            
            /**
             * Check if preview is from a specific test
             * if so, then it will filter the tests to only include the specific test
             */
            $data = $this->getSurveyTest($request, $data);
    
            /**
             * Render the survey test page
             * on query => it will exclude the p_tid
             */
            return Inertia::render('Engine/EngineTest', [
                'status' => $status,
                'query' => $request->except(['p_tid', 'simulate']),
                'surveyData' => $data,
            ]);
        } catch (\Exception $e) {
            /**
             * If database connection fails, show fallback page
             */
            if (str_contains($e->getMessage(), 'getaddrinfo') || 
                str_contains($e->getMessage(), 'Connection') ||
                str_contains($e->getMessage(), 'SQLSTATE[HY000]')) {
                
                return Inertia::render('Engine/DatabaseError', [
                    'error' => 'Database connection failed',
                    'message' => 'The survey system is temporarily unavailable. Please try again later.',
                    'retryUrl' => $request->fullUrl()
                ]);
            }
            
            /**
             * Re-throw other exceptions
             */
            throw $e;
        }
    }

    private function checkRespondentInfo($request)
    {
        $respondentId = $request->input('id');
        if (empty($respondentId)) {
            abort(
                Inertia::render('Engine/DatabaseError', [
                    'error' => 'Missing Information',
                    'message' => 'We were unable to retrieve the requested data. Please ensure you have the correct link and try again.',
                    'retryUrl' => $request->fullUrl()
                ])
            );
        }
    }

    private function getSurveyTest($request, $data)
    {
        if (isset($request->p_tid) && $request->p_tid) {
            $p_tid = $request->p_tid;
            $filteredTests = collect($data->surveys[0]->tests)->filter(function ($test) use ($p_tid) {
                return $test->id == $p_tid;
            });
            $data->surveys[0]->tests = $filteredTests->values()->toArray();
        }
        return $data;
    }

    private function checkRecordStatus($request)
    {
        $respondentId = $request->input('id');
        $surveyId = $request->input('survey_id');
        $status = 'not_started';

        // Guard clause for id test
        if ($respondentId === 'test') {
            return $status;
        }

        // Check for the record only after validation
        $records = Record::where('participant_id', $respondentId)->where('survey_id', $surveyId)->get();
        
        if ($records->isEmpty()) {
            return $status;
        }

        // Extract URL list_index parameters
        $recordListIndices = [];
        foreach ($records as $record) {
            if (isset($record->info['url'])) {
                $recordUrl = parse_url($record->info['url'], PHP_URL_QUERY);
                if ($recordUrl) {
                    $params = [];
                    parse_str($recordUrl, $params);
                    $listIndex = $params['list_index'] ?? null;
                    if ($listIndex !== null) {
                        $recordListIndices[] = $listIndex;
                    }
                }
            }
        }       

        $recordListIndices = collect($recordListIndices);
        $queryListIndex = $request->input('list_index') ?? null;

        // Determine status based on list index values
        if ($recordListIndices->isEmpty() && $queryListIndex === null) {
            $status = 'complete';
        } elseif ($recordListIndices->contains($queryListIndex)) {
            $status = 'complete';
        } else {
            // If the record exists but the list index doesn't match
            $status = 'not_started';
        }

        return $status;
    }

    private function transformPrime($prime, $url_array)
    {
        $status = false;
        $conditions = $prime['config']['conditions'] ?? [];
        if (count($conditions) == 0) {
            return true;
        }
        $process = $this->processCriteria($url_array, $conditions);
        do {
            $process = $this->verifyCriteria($process);
        } while (count($process) > 1);
        if ($status === false) {
            $status = $process[0]['status'];
        }
        return !$status;
    }

    private function isTestIncluded($test, $url_array, $editor = false)
    {
        if ($editor) {
            return true;
        }
        
        $status = false;

        /**
         * start
         * prac_switch and dummy_trial
         */
        if ($test->config['type'] == 'trial') {
            // trial
            if (isset($test->config['practice_trial']) && $test->config['practice_trial']) {
                if (isset($url_array['prac_switch']) && $url_array['prac_switch'] == 0) {
                    return false;
                }
            }
        } else {
            // descriptive
            if (isset($test->config['dummy_trial']) && $test->config['dummy_trial']) {
                if (isset($url_array['dummy_switch']) && $url_array['dummy_switch'] == 0) {
                    return false;
                }
            }
        }
         /** end */

        $conditions = $test->config['conditions'] ?? [];
        if (count($conditions) === 0) {
            return true;
        }
        $process = $this->processCriteria($url_array, $conditions);
        do {
            $process = $this->verifyCriteria($process);
        } while (count($process) > 1);
        if($status === false) {
            $status = $process[0]['status'];
        }
        return !$status;
    }

    private function chunkTargets($targets, $display_by)
    {
        dd($targets, $display_by);
        $sizes = explode(",", $display_by);
        if (count($sizes) === 1) {
            return collect($targets)->chunk($display_by);
        } else {
            $counter = 0;
            $res = [];
            foreach ($sizes as $size) {
                $res[] = array_slice($targets, $counter, $size);
                $counter += $size;
            }
            return collect($res);
        }
    }

    public function transformTests($tests, $url_array, $variables = [], $editor = false)
    {
        $trials = [];
        
        foreach ($tests as $test) {
            // check skip logic for each test, if false then not included in the test, included otherwise
            if ($this->isTestIncluded($test, $url_array, $editor) === false) {
                continue;
            }
            $_test = $test->toArray();
            $config = $test->config;
            $config['title'] = textReplacer($variables, $config['title']);
            $config['main_header'] = textReplacer($variables, $config['main_header']);
            $config['bottom_text'] = textReplacer($variables, $config['bottom_text']);
            $config['empty_selection'] = textReplacer($variables, $config['empty_selection']);
            $_test['config'] = $config;
            $primes = $config['random_prime'] === true ? collect($test->primes)->shuffle()->toArray() : $test->primes;

            // Chunking targets, replace the old one with custom chunk sizes delimited by comma (reverted)
            $targets = ($config['random'] === true ? collect($test->targets)->shuffle() : $test->targets)->chunk($config['display_by']);
            // $targets = $this->chunkTargets(($config['random'] === true ? collect($test->targets)->shuffle() : $test->targets)->toArray(), $config['display_by']);
            
            /**
             * start 
             * If trial_unselect is true, then generate a none of these target
             * it will be added to the targets array
             * this is used to handle the trial_unselect_label
             */
            if (isset($config['trial_unselect']) && $config['trial_unselect']) {
                $targetsCount = count($test->targets);
                $noneOfThese = $this->generateNoneOfThese($targetsCount + 1, $config['trial_unselect_label']);
                $targets = $targets->map(function ($targetCollection) use ($noneOfThese) {
                    $targetCollection = $targetCollection->values();
                    $targetCollection->push($noneOfThese);                    
                    return $targetCollection;
                });
            }
            /**
             * end
             */

            $_trials = [];
            foreach ($primes as $prime) {
                $display = $this->transformPrime($prime, $url_array);
                $prime['display'] = $display;
                $prime['prime'] = textReplacer($variables, $prime['prime']);
                $prime['equivalent'] = textReplacer($variables, $prime['equivalent']);
                unset($prime['config']);
                foreach ($targets as $kk => $target) {
                    $_trials[] = [
                        'prime' => $prime,
                        'targets' => $target->values()->map(function($t) use($kk, $variables) {
                            $t['target'] = textReplacer($variables, $t['target']);
                            $t['equivalent'] = textReplacer($variables, $t['equivalent']);
                            $t['batch'] = $kk + 1;
                            return $t;
                        })->toArray(),
                    ];
                }
            }
            unset($_test['primes']);
            unset($_test['targets']);
            if ($config['random_prime'] && isset($config['random_prime_subsets'])) {
                if ($config['random_prime_subsets'] == true) {
                    $_trials = collect($_trials)->shuffle()->values()->toArray();
                }
            }
            $_test = collect($_test)->merge([
                'trials' => $_trials
            ])->toArray();
            $trials[] = $_test;
        }
        return $trials;
    }

    public function getLiveProjectData2(Request $request): JsonResponse
    {
        $url_array = $request->all();
        if ($request->has('survey_id')) {
            $request->validate([
                'survey_id' => 'required'
            ]);
            $survey = Survey::find($request->survey_id);
            if ($survey->parent_survey->is_not_live && !isset($url_array['preview'])) {
                return response()->json(['success' => false, 'message' => 'Survey already completed or paused.'], 403);
            }
            $project = $survey->group->project;
            $variables = $this->getVariables($project, $url_array);
            $surveys = collect([$survey])->map(function ($s) use($url_array, $variables) {
                return [
                    'survey_id' => $s->id,
                    'tests' => $this->transformTests($s->tests, $url_array, $variables),
                    'name' => $s->name,
                    'completed' => false,
                    'config' => $s->config
                ];
            })->values()->toArray();

            $config = $project->config;
            if (count($surveys) === 1) {
                if (isset($surveys[0]['config']['theme'])) {
                    if ($surveys[0]['config']['theme'] != 0) {
                        $config['theme'] = $surveys[0]['config']['theme'];
                    }
                }
                if (isset($surveys[0]['config']['buttons']) && $surveys[0]['config']['buttons']['next'] != '') {
                    $config['buttons']['next'] = $surveys[0]['config']['buttons']['next'];
                }
                if (isset($surveys[0]['config']['buttons']) && $surveys[0]['config']['buttons']['final'] != '') {
                    $config['buttons']['final'] = $surveys[0]['config']['buttons']['final'];
                }
                if (isset($surveys[0]['config']['texts']) && $surveys[0]['config']['texts']['saving'] != '') {
                    $config['texts']['saving'] = $surveys[0]['config']['texts']['saving'];
                }
                if (isset($surveys[0]['config']['texts']) && $surveys[0]['config']['texts']['redirecting'] != '') {
                    $config['texts']['redirecting'] = $surveys[0]['config']['texts']['redirecting'];
                }
            }
          
            return response()->json([
                'token' => 0,
                'total' => $survey->tests->count(),
                'config' => $config,
                'surveys' => $surveys,
                'variables' => $this->mapVariablesWithKeys($variables)
            ]);
        } else {
            $request->validate([
                'code' => 'required'
            ]);
            $project = Project::whereCode($request->code)->first();

            if (!$project) {
                throw ValidationException::withMessages([
                    'project' => ['Invalid project']
                ]);
            }
            $groups = $project->groups->filter(function ($group) use ($url_array) {
                $status = false;
                $conditions = $group->config['conditions'];
                if (count($conditions) == 0)
                    return true;
                $process = $this->processCriteria($url_array, $conditions);
                do {
                    $process = $this->verifyCriteria($process);
                } while (count($process) > 1);
                if ($status === false) {
                    $status = $process[0]['status'];
                }
                return $status;
            });
            $count = 0;
            $surveys = $groups->map(function ($group) use (&$count, $project, $url_array) {
                $config = $group->config;
                $surveys = $group->surveys;
                if ($config['random'] === true) {
                    $surveys = $surveys->shuffle();
                }

                return $surveys->map(function ($survey) use (&$count, $project, $url_array) {
                    $count += $survey->tests->count();
                    return [
                        'survey_id' => $survey->id,
                        'tests' => $this->transformTests($survey->tests, $url_array),
                        'name' => $survey->name,
                        'completed' => $project->config['progress']['continue'] ? $this->surveyHasRecord($survey->id, $url_array['id']) : false,
                        'config' => $survey->config
                    ];
                });
            })->flatten(1)->values()->toArray(); 
            return response()->json([
                'token' => generateToken(),
                'total' => $count,
                'config' => $project->config,
                'surveys' => $surveys
            ]);
        }
    }

    private function generateNoneOfThese($index, $label)
    {
         return [
            'target' => $label,
            'equivalent' => $label,
            'index' => $index,
            'can_unselect' => true,
            'colors' => [
                'normal' => null,
                'hover' => null,
                'active' => null
            ],
            'properties' => [
                'bg' => null
            ],
            'value' => $index,
            'rawTargetElement' => $label
        ];
    }


    public function getLiveProjectData(Request $request)
    {
        
        $url_array = $request->all();
        
        if ($request->has('survey_id')) {
            $request->validate([
                'survey_id' => 'required'
            ]);
            $survey = Survey::find($request->survey_id);
            $project = $survey->group->project;
            $surveys = collect([$survey])->map(function($s) {
                return [
                    'survey_id' => $s->id,
                    'tests' => $s->tests->toArray(),
                    'name' => $s->name,
                    'completed' => false,
                    'config' => $s->config
                ];
            })->values()->toArray();
            return response()->json([
                'total' => $survey->tests->count(),
                'config' => $project->config,
                'surveys' => $surveys
            ]);
        } else {
            $request->validate([
                'code' => 'required'
            ]);
            $project = Project::whereCode($request->code)->first();

            if (!$project) {
                throw ValidationException::withMessages([
                    'project' => ['Invalid project']
                ]);
            }
            $groups = $project->groups->filter(function($group) use($url_array) {
                $status = false;
                $conditions = $group->config['conditions'];
                if (count($conditions) == 0)
                    return true;
                $process = $this->processCriteria($url_array, $conditions);
                do {
                    $process = $this->verifyCriteria($process);
                } while (count($process) > 1);
                if ($status === false) {
                    $status = $process[0]['status'];
                }
                return $status;
            });
            $count = 0;
            $surveys = $groups->map(function($group) use(&$count, $project, $url_array) {
                $config = $group->config;
                $surveys = $group->surveys;
                if ($config['random'] === true) {
                    $surveys = $surveys->shuffle();
                }
                
                return $surveys->map(function ($survey) use(&$count, $project, $url_array) {
                    $count += $survey->tests->count();
                    return [
                        'survey_id' => $survey->id,
                        'tests' => $survey->tests->toArray(),
                        'name' => $survey->name,
                        'completed' => $project->config['progress']['continue'] ? $this->surveyHasRecord($survey->id, $url_array['id']) : false,
                        'config' => $survey->config
                    ];
                });
            })->flatten(1)->values()->toArray();

            return response()->json([
                'total' => $count,
                'config' => $project->config,
                'surveys' => $surveys
            ]);
        }
    }

    private function surveyHasRecord($survey_id, $participant_id)
    {
        return Record::where('survey_id', $survey_id)
        ->where('participant_id', $participant_id)
        ->where('status', 'complete')
        ->exists();
    }

    private function processOperator($request, $condition): bool
    {
        $status = false;
        if (!isset($request[$condition['var']])) {
            return $status;
        }
        switch ($condition['operator']) {
            case '==':
                if ($request[$condition['var']] == $condition['val']) {
                    $status = true;
                }
                break;
            case '!=':
                if ($request[$condition['var']] <> $condition['val']) {
                    $status = true;
                }
                break;
            case '>':
                if ($request[$condition['var']] > $condition['val']) {
                    $status = true;
                }
                break;
            case '<':
                if ($request[$condition['var']] < $condition['val']) {
                    $status = true;
                }
                break;
            case '>=':
                if ($request[$condition['var']] >= $condition['val']) {
                    $status = true;
                }
                break;
            case '<=':
                if ($request[$condition['var']] <= $condition['val']) {
                    $status = true;
                }
                break;
            default:
                dd("something went wrong");
                break;
        }

        return $status;

    }

    private function verifyCriteria($results): array
    {
        $j = 1;
        $new_result = [];
        for ($i = 0; $i < count($results); $i += $j) {
            if (isset($results[$i]['conditions'])) {
                if (count($results[$i]['conditions']) > 1) {
                    $new_result[] = ['conditions' => $this->verifyCriteria($results[$i]['conditions']), 'left_operator' => $results[$i]['left_operator']];
                } else {
                    $new_result[] = ['left_operator' => $results[$i]['left_operator'], 'status' => $results[$i]['conditions'][0]['status']];
                }
                $j = 1;
            } else {
                if (isset($results[$i + 1])) {
                    if (isset($results[$i + 1]['conditions'])) {
                        $new_result[] = $results[$i];
                        $j = 1;
                    } else {
                        $status = $this->processLeftOperator([$results[$i], $results[$i + 1]]);
                        $new_result[] = ['status' => $status, 'left_operator' => $results[$i]['left_operator']];
                        $j = 2;
                    }
                } else {
                    $new_result[] = $results[$i];
                    $j = 1;
                }
            }
        }
        return $new_result;
    }

    private function processLeftOperator($results): bool
    {
        $status = false;
        switch ($results[1]['left_operator']) {
            case 'and':
                $status = $results[0]['status'] == true && $results[1]['status'] == true;
                break;
            case 'or':
                $status = $results[0]['status'] == true || $results[1]['status'] == true;
                break;
            default:
                dd("process operator wrong");
                break;
        }
        return $status;
    }

    private function processCriteria($request, $conditions): array
    {
        $result = [];

        foreach (generator($conditions) as $key => $condition) {
            if ($condition['method'] == 'segment') {
                $result[$key]['conditions'] = $this->processCriteria($request, $condition['conditions']);
            } else {
                $result[$key]['status'] = $this->processOperator($request, $condition);
            }
            $result[$key]['left_operator'] = $condition['left_operator'];
        }

        return $result;
    }

    public function goWhere(Request $request)
    {        
        if (!isset($request->code) && !isset($request->survey_id)) {
            throw ValidationException::withMessages([
                'url' => ['Project code or Survey ID is missing']
            ]);
        }
        if ($request->has('code')) {
            $project = Project::where('code', $request->code)->first();
        } else if ($request->has('survey_id')) {
            $survey = Survey::find($request->survey_id);
            $project = $survey->group->project;

            # get all tests->singele_select config
            $testsData = $survey->tests->map(function ($test) {
                return [
                    'id' => $test->id,
                    'single_select' => $test->config['single_select'],
                ];
            })->all();
            $testsDataLookup = [];
            foreach ($testsData as $testItem) {
                $testsDataLookup[$testItem['id']] = $testItem['single_select'];
            }
        }

        $url_data = $request->except(['survey_id', 'code']);
        $response_array = [];
        if ($survey && (!$request->has('preview'))) {
            if ($survey->config['forward_response'] === true) {
                $record = Record::where('survey_id', $request->survey_id)
                    ->where('participant_id', $request->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                $response_array = $this->formatForwardingResponse($record->responses, $survey->config['response_var'], ($survey->config['response_type'] ?? 'selection'));
            }
        }

        $redirect = $project->config['redirects']['complete'];
        if (isset($survey->config['redirect']) && !empty($survey->config['redirect'])) {
            $redirect = $survey->config['redirect'];
        }
        return response()->json([
            'external' => true,
            'link' => $redirect . (count($response_array) > 0 ? (http_build_query($response_array) . '&') : '') . http_build_query($url_data)
        ]);
    }

    private function formatForwardingResponse($data, $response_var, $response_type)
    {
        $result = [];
        $variable = $response_var;
        $transformed_data = [];
        foreach ($data as $key => $trial) {
            $primes = collect($trial['primes'])->sortBy('index')->values()->toArray();
            foreach ($primes as $key2 => $prime) {
                // $response = collect($prime['data'])->filter(fn($v) => $v['selected'] === true);
                //$result[$response_var . ($key + 1) . "_" . ($key2 + 1)] =  $response->count() > 1 ? implode(",", $response->pluck('value')->toArray()) : $response->first()['value'];
                // $result[$response_var . ($key + 1) . "_" . $prime['index']] =  $response->count() > 1 ? implode(",", $response->pluck('value')->toArray()) : ($response->first()['value'] ?? 0);

                $windrorised = collect($prime['data'])->map(function($values) {
                    $values['wind'] = ($values['selected'] === true) ? min($values['rt'], 10000) : 0;
                    return $values;
                });
                

                $mean = $windrorised->avg('wind');
                $stdev = $this->stdev_sample($windrorised->pluck('wind')->toArray());
                $log_mean = $this->calculateLogMean($mean, $stdev);
                $log_stdev = $this->calculateLogSD($mean, $stdev);
                $max = collect($windrorised)->max('wind');
                $min = collect($windrorised)->min('wind');

                $minmax = $windrorised->pluck('wind')->map(function($item) use ($max, $min) {
                    if ($max - $min == 0) {
                        return 0;
                    }
                    return $item === null ? 0 : round(100 * ($max - $item) / ($max - $min), 2);
                });


                $synthetic = $windrorised->pluck('rt')->map(function($value) use ($log_mean, $log_stdev) {
                    $random = mt_rand() / mt_getrandmax(); 
                    return $this->lognormInv($random, $log_mean, $log_stdev);
                });

                $obfuscated = $synthetic->map(function ($val) {
                    return $this->calculateObfuscatedRT($val);
                });

                $min_obfuscated_rt = $obfuscated->min();
                $max_obfuscated_rt = $obfuscated->max();

                $coded = $obfuscated->map(function($val) use ($min_obfuscated_rt, $max_obfuscated_rt) {
                    return round(100 * ($max_obfuscated_rt - $val) / ($max_obfuscated_rt - $min_obfuscated_rt), 2);
                })->toArray();

                $transformed_data[] = [
                    'primary' => $windrorised,
                    'coded' => $coded,
                    'minmax' => $minmax,
                    'stdev' => $stdev,
                    'log_mean' => $log_mean,
                    'log_stdev' => $log_stdev,
                    'var' => $variable . '_' . ($key + 1) . '_' . $prime['index'],
                ];
            }
        }

        $result = collect($transformed_data)->map(function($item) use ($response_type) {
            $primary = $item['primary'];
            $coded = $item['coded'];
            $var = $item['var'];
            $minmax = $item['minmax'];
            if ($response_type == 'selection') {
                return collect($primary)->map(function($value) use($var) {
                    return [
                        'var' => $var . '_' . $value['index'],
                        'value' => $value['selected'] ? 1 : 0
                    ];
                })->toArray();
            } else if ($response_type == 'rt') {
                return collect($primary)->map(function($value) use($var) {
                    return [
                        'var' => $var . '_' . $value['index'],
                        'value' => $value['rt'] ?? 0
                    ];
                })->toArray();
            } else if ($response_type == 'rank') {
                return collect($primary)->map(function($value) use($var) {
                    return [
                        'var' => $var . '_' . $value['index'],
                        'value' => $value['rank'] ?? 0
                    ];
                })->toArray();
            } else if ($response_type == 'coded') {
                return collect($coded)->map(function($value, $index) use($var) {
                    return [
                        'var' => $var . '_' . ($index + 1),
                        'value' => $value ?? 0
                    ];
                })->toArray();
            } else if ($response_type == 'refined') {
                return collect($minmax)->map(function($value, $index) use($var) {
                    return [
                        'var' => $var . '_' . ($index + 1),
                        'value' => $value ?? 0
                    ];
                })->toArray();
            }
        })->flatten(1)->mapWithKeys(function($value) {
            return [$value['var'] => $value['value']];
        })->toArray();
        return $result;
    }

    private function stdev_sample(array $values): float|null
    {
        $n = count($values);
        if ($n == 0) {
            return null;
        }
        if ($n < 2) {
            // throw new \InvalidArgumentException("At least two data points are required for sample standard deviation.");
            return 0.1;
        }
    
        $mean = array_sum($values) / $n;
        $sumOfSquares = 0.0;
    
        foreach ($values as $value) {
            $sumOfSquares += pow($value - $mean, 2);
        }
    
        return sqrt($sumOfSquares / ($n - 1));
    }

    private function calculateLogMean(?float $mean, ?float $stdDev): float
    {
        // Handle edge cases
        if (($mean == 0 || is_null($mean)) && ($stdDev == 0 || is_null($stdDev))) {
            return 0;
        }
        
        if ($mean == 0) {
            // If mean is 0, the formula becomes 1.1*LN(0 / SQRT(stdDev^2 + 0^2)) = 1.1*LN(0) = -infinity
            // Return a very large negative number or throw an exception
            throw new \InvalidArgumentException("Mean cannot be zero when calculating logarithmic transformation.");
        }
        
        // Calculate BW^2 (mean squared)
        $meanSquared = pow($mean, 2);
        
        // Calculate BX^2 (standard deviation squared)
        $stdDevSquared = pow($stdDev, 2);
        
        // Calculate SQRT(BX^2 + BW^2)
        $denominator = sqrt($stdDevSquared + $meanSquared);
        
        // Avoid division by zero
        if ($denominator == 0) {
            throw new \InvalidArgumentException("Denominator cannot be zero in logarithmic transformation calculation.");
        }
        
        // Calculate BW^2 / SQRT(BX^2 + BW^2)
        $fraction = $meanSquared / $denominator;
        
        // Calculate LN(fraction)
        $logValue = log($fraction);
        
        // Multiply by 1.1
        return 1.1 * $logValue;
    }

    private function calculateLogSD(?float $mean, ?float $stdDev): float
    {
        // Handle edge cases
        if (($mean == 0 || is_null($mean)) && ($stdDev == 0 || is_null($stdDev))) {
            return 0;
        }
        
        if ($mean == 0) {
            // If mean is 0, the formula becomes 1.1*SQRT(LN(1 + (stdDev^2 / 0^2))) = 1.1*SQRT(LN(1 + infinity)) = infinity
            throw new \InvalidArgumentException("Mean cannot be zero when calculating logarithmic standard deviation.");
        }
        
        // Calculate BX^2 (standard deviation squared)
        $stdDevSquared = pow($stdDev, 2);
        
        // Calculate BW^2 (mean squared)
        $meanSquared = pow($mean, 2);
        
        // Calculate BX^2 / BW^2
        $ratio = $stdDevSquared / $meanSquared;
        
        // Calculate LN(1 + ratio)
        $logValue = log(1 + $ratio);
        
        // Calculate SQRT(logValue)
        $sqrtValue = sqrt($logValue);
        
        // Multiply by 1.1
        return 1.1 * $sqrtValue;
    }

    private function lognormInv($p, $mu, $sigma)
    {
        if ($p <= 0.0 || $p >= 1.0) {
            throw new \InvalidArgumentException("Probability must be between 0 and 1 (exclusive).");
        }

        return exp($mu + $sigma * $this->inverseStandardNormal($p));
    }

    private function inverseStandardNormal($p)
    {
        $a = [-3.969683028665376e+01, 2.209460984245205e+02,
              -2.759285104469687e+02, 1.383577518672690e+02,
              -3.066479806614716e+01, 2.506628277459239e+00];

        $b = [-5.447609879822406e+01, 1.615858368580409e+02,
              -1.556989798598866e+02, 6.680131188771972e+01,
              -1.328068155288572e+01];

        $c = [-7.784894002430293e-03, -3.223964580411365e-01,
              -2.400758277161838e+00, -2.549732539343734e+00,
              4.374664141464968e+00, 2.938163982698783e+00];

        $d = [7.784695709041462e-03, 3.224671290700398e-01,
              2.445134137142996e+00, 3.754408661907416e+00];

        $plow = 0.02425;
        $phigh = 1 - $plow;

        if ($p < $plow) {
            $q = sqrt(-2 * log($p));
            return ((((( $c[0]*$q + $c[1])*$q + $c[2])*$q + $c[3])*$q + $c[4])*$q + $c[5]) /
                   (((( $d[0]*$q + $d[1])*$q + $d[2])*$q + $d[3])*$q + 1);
        } elseif ($p > $phigh) {
            $q = sqrt(-2 * log(1 - $p));
            return -((((( $c[0]*$q + $c[1])*$q + $c[2])*$q + $c[3])*$q + $c[4])*$q + $c[5]) /
                     (((( $d[0]*$q + $d[1])*$q + $d[2])*$q + $d[3])*$q + 1);
        } else {
            $q = $p - 0.5;
            $r = $q * $q;
            return ((((( $a[0]*$r + $a[1])*$r + $a[2])*$r + $a[3])*$r + $a[4])*$r + $a[5]) * $q /
                   ((((( $b[0]*$r + $b[1])*$r + $b[2])*$r + $b[3])*$r + $b[4])*$r + 1);
        }
    }

    private function calculateObfuscatedRT(?float $baseValue): int|null
    {
        if ($baseValue === null) return null;
        // Generate random number between 0 and 1 (equivalent to RAND())
        $rand1 = mt_rand() / mt_getrandmax();
        
        // Calculate RAND()-0.5
        $randMinusHalf = $rand1 - 0.5;
        
        // Calculate SIGN(RAND()-0.5)
        $sign = $randMinusHalf > 0 ? 1 : ($randMinusHalf < 0 ? -1 : 0);
        
        // Generate another random number for the second RAND() in the formula
        $rand2 = mt_rand() / mt_getrandmax();
        
        // Calculate ABS(RAND()-0.5)
        $absRandMinusHalf = abs($rand2 - 0.5);
        
        // Calculate 1-2*ABS(RAND()-0.5)
        $logArgument = 1 - 2 * $absRandMinusHalf;
        
        // Calculate LN(1-2*ABS(RAND()-0.5)) with error handling (IFERROR equivalent)
        $logValue = 0;
        if ($logArgument > 0) {
            $logValue = log($logArgument);
        }
        // If logArgument <= 0, logValue remains 0 (equivalent to IFERROR returning 0)
        
        // Calculate 200*SIGN(RAND()-0.5)*LN(1-2*ABS(RAND()-0.5))
        $multiplier = 200 * $sign * $logValue;
        
        // Calculate CA2 + multiplier
        $result = $baseValue + $multiplier;
        
        // Return INT(result)
        return (int) $result;
    }



    public function getVariables($project, $url_array)
    {
        $variables = $project->variables;
        $vars_result = [];
        foreach (generator($variables) as $variable) {
            $criteria = $variable->config['criteria'];
            foreach (generator($criteria) as $key => $cr) {
                $test = $this->criteria_iterator($cr['conditions'], $url_array);
                if (true === $test) {
                    $vars_result[] = [
                        'variable' => $variable->name,
                        'value' => $cr['value'],
                        'name' => $cr['label'],
                        'key' => $key + 1
                    ];
                }
            }
        }
        return $vars_result;
    }

    private function criteria_iterator($conditions, $url_array)
    {
        $status = false;
        if (count($conditions) == 0) {
            return true;
        }
        $process = $this->processCriteria($url_array, $conditions);
        do {
            $process = $this->verifyCriteria($process);
        } while (count($process) > 1);
        if ($status === false) {
            $status = $process[0]['status'];
        }
        return $status;
    }

    public function test()
    {
        return urldecode('Rosa%20bettridge');
        return Carbon::parse('Tue, 06 Jul 2021 07:05:04 GMT')->toDateTimeString();
        // return gmdate('d.m.Y H:i', strtotime('Tue, 06 Jul 2021 07:05:04 GMT'));
        $data = [
            'survey_id' => 9,
            'url' => 'https://test.test?code=998&a1=1',
            'responses' => [
                [ // array of test trials
                    'id' => 1, //test id,
                    'primes' => [
                        [
                            'prime' => '1st Prime here',
                            'index' => 0,
                            'equivalent' => 'prime equivalent',
                            'data' => [
                                [
                                    'index' => 0,
                                    'target' => 'target here',
                                    'equivalent' => 'target equivalent here',
                                    'rt' => 900,
                                    'rank' => 1,
                                    'selected' => true
                                ],
                                [
                                    'index' => 0,
                                    'target' => 'target2 here',
                                    'equivalent' => 'target2 equivalent here',
                                    'rt' => 890,
                                    'rank' => 1,
                                    'selected' => false
                                ]
                            ]
                        ],
                        [
                            'prime' => 'second Prime here',
                            'index' => 0,
                            'equivalent' => 'prime equivalent',
                            'data' => [
                                [
                                    'index' => 0,
                                    'target' => 'target here',
                                    'equivalent' => 'target equivalent here',
                                    'rt' => 900,
                                    'rank' => 1,
                                    'selected' => true
                                ],
                                [
                                    'index' => 0,
                                    'target' => 'target2 here',
                                    'equivalent' => 'target2 equivalent here',
                                    'rt' => 890,
                                    'rank' => 1,
                                    'selected' => false
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        return response()->json($data);
    }

    public function testing()
    {
        $variables = Variable::all();
        $url = "https://ast.splitsecondsurveys.co.uk/act-engine?id=test&survey_id=34&brc=3";
        parse_str($url, $url_array);
        $vars_result = [];
        foreach (generator($variables) as $variable) {
            $criteria = $variable->config['criteria'];
            foreach (generator($criteria) as $key => $cr) {
                $test = $this->criteria_iterator($cr['conditions'], $url_array);
                if (true === $test) {
                    $vars_result[] = [
                        'variable' => $variable->name,
                        'value' => $cr['value'],
                        'name' => $cr['label'],
                        'key' => $key + 1
                    ];
                    break;
                }
            }
        }
        dump($vars_result);
        $t = collect($vars_result)->mapWithKeys(function($item, $key) {
            return [$item['variable'] => $item['key']];
        })->toArray();
        $text = "The brand is {{ brand_name }} with an image of {{ brand_image }}";
        $new_text = textReplacer($vars_result, $text);
        return $new_text;
    }

    private function mapVariablesWithKeys($variables = [])
    {
        return false === empty($variables) ? collect($variables)->mapWithKeys(function($item, $key) {
            return [$item['variable'] => $item['key']];
        })->toArray() : [];
    }
}
