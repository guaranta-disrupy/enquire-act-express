<?php

namespace App\Http\Controllers;

use App\Models\Exportable;
use App\Models\Filter;
use App\Models\Group;
use App\Models\MainSurvey;
use App\Models\Record;
use App\Models\Survey;
use App\Models\Test;
use App\Objects\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use Inertia\Inertia;
use Ramsey\Uuid\Uuid;
use Rap2hpoutre\FastExcel\FastExcel;
use Rap2hpoutre\FastExcel\SheetCollection;
use Illuminate\Support\Str;

class AnalysisController extends Controller
{

    public function index(Survey $survey)
    {
        $surveys = DB::table('surveys')->select(['id', 'name'])->where('group_id', $survey->group->id)->whereNull('deleted_at')->get()->toArray();
        $project = $survey->group->project;
        $variables = $project->variables;
        $didUseVariables = $this->surveyHasVariables($survey);
        return Inertia::render('Analysis/Analysis', [
            'variables' => $variables,
            'didUseVariables' => $didUseVariables,
            'pre_selected_variable' => $didUseVariables ? $variables->filter(fn($v) => $v->name === $didUseVariables)->first() : false,
            'project' => $project,
            'filters' => $project->filters,
            'survey' => $survey,
            'allSurveys' => $surveys,
            'projects' => projectsList(),
            'exportables' => $survey->exportables()->with(['user:id,name'])->orderBy('id', 'desc')->get(),
            'tests' => collect($survey->tests->toArray())->map(function ($y) {
                unset($y['config']);
                $y['isTrial'] = count($y['targets']) > 0;
                unset($y['targets']);
                unset($y['primes']);
                return $y;
            }),
        ]);
    }

    public function index2(Survey $survey)
    {
        $main_survey = MainSurvey::where('uuid', $survey->config['survey_id'])->first();
        $surveys = $survey->group->surveys;
        $project = $survey->group->project;
        $variables = $project->variables;
        $didUseVariables = $this->surveyHasVariables($survey);
        return Inertia::render('Analysis/Analysis_light', [
            'variables' => $variables,
            'didUseVariables' => $didUseVariables,
            'pre_selected_variable' => $didUseVariables ? $variables->filter(fn($v) => $v->name === $didUseVariables)->first() : false,
            'project' => $project,
            'filters' => Filter::where('survey_id', $main_survey->id)->get(),
            'survey' => $survey,
            'surveys' => $surveys,
            'projects' => projectsList(),
            'exportables' => $survey->exportables()->with(['user:id,name'])->orderBy('id', 'desc')->get(),
            'tests' => collect($survey->tests->toArray())->map(function ($y) {
                unset($y['config']);
                $y['isTrial'] = count($y['targets']) > 0;
                unset($y['targets']);
                unset($y['primes']);
                return $y;
            })
        ]);
    }

    private function surveyHasVariables($survey)
    {
        $tests = $survey->tests;
        $variables = $survey->group->project->variables;
        if ($variables->count() === 0) {
            return false;
        }
        $variables = $variables->pluck('name')->toArray();
        foreach (generator($tests) as $test) {
            $targets = json_encode($test->targets);
            if (($needle = strposa($targets, $variables)) !== false) return $needle;

            $primes = json_encode($test->primes);
            if (($needle = strposa($primes, $variables)) !== false) return $needle;

            $config = json_encode($test->config);
            if (($needle = strposa($config, $variables)) !== false) return $needle;
        }
        return false;
    }

    public function advance(Survey $survey)
    {
        $group_ids = DB::table('groups')->select('id')
            ->where('project_id', $survey->group->project_id)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->toArray();

        $surveys = DB::table('surveys')
            ->select(['id', 'name'])
            ->whereIn('group_id', $group_ids)
            ->whereNull('deleted_at')
            ->orderBy('group_id', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->toArray();

        $project = $survey->group->project;
        return Inertia::render('Analysis/AnalysisAdvance', [
            'project' => $project,
            'filters' => $project->filters,
            'survey' => $survey,
            'allSurveys' => $surveys,
            'projects' => projectsList(),
            'exportables' => $survey->exportables()->with(['user:id,name'])->orderBy('id', 'desc')->get()
        ]);
    }

    private function formatTargetIds($ids)
    {
        return $ids != '' ? collect(explode("\n", trim($ids)))->map(fn ($v) => explode(",", $v))->toArray() : false;

    }

    public function start_caching(Request $request, Survey $survey, Filter $filter)
    {
        $selected_tests = $request->selected_tests;
        $tests = $survey->tests->filter(function($test) use($request, $selected_tests) {
            return $test->config['main_trial'] == true && count($test->primes) > 0 && in_array($test->id, $selected_tests);
        })->values();

        $filter_ids = $filter->data['ids'] ?? $filter->details['ids'];
        $filter_operator = 'in';

        $ids = collect(explode(",", $filter_ids))->map(function($val) {
            return ['id' => $val];
        })->toArray();

        $target_ids = $this->formatTargetIds($request->target_ids);

        $temp_table = generateTemporaryTable();

        DB::table($temp_table)->insert($ids);

        $records = Record::where('survey_id', $survey->id)->when($filter_operator === 'in', function($query) use($temp_table) {
            return $query->whereIn('participant_id', function($q) use($temp_table) {
                $q->select('id')->from($temp_table);
            });
        }, function($query) use($temp_table) {
            return $query->whereNotIn('participant_id', function ($q) use ($temp_table) {
                $q->select('id')->from($temp_table);
            });
        // })->when($request->variable_name, function($query) use($request) {
        //     return $query->whereJsonContains('info->' . $request->variable_name, $request->variable_value);
        // })->orderBy('id', 'asc')->get()->unique('participant_id');
        })->orderBy('id', 'asc')->get();
        if ($request->variable_name) {
            $project = $survey->group->project;
            $records = $records->filter(function($record) use($project, $request) {
                $url_components = parse_url($record->info['url']);
                parse_str($url_components['query'], $url_array);
                $variables = $this->getVariables($project, $url_array);
                $mapped_vars = $this->mapVariablesWithKeys($variables);
                return isset($mapped_vars[$request->variable_name]) && $mapped_vars[$request->variable_name] == $request->variable_value;
            });
        }
        $records = $records->unique('participant_id');

        if ($records->count() <= ($request->minimum_sample ?? 0)) {
            return response()->json([
                'key' => null
            ]);
        }

        $respondents_info = [];
        $responses = [];
        foreach (generator($records) as $record) {
            $respondents_info = [
                'survey_id' => $record->survey_id,
                'participant_id' => $record->participant_id,
                'ip' => $record->info['ip'],
                'device' => $record->info['device'],
                'created' => $record->created_at->toDateTimeString(),
                'started' => $record->started_at->toDateTimeString(),
                'ended' => $record->ended_at->toDateTimeString(),
                'url' => $record->info['url']
            ];
            $participant_id = $record->participant_id;
            foreach (generator($tests) as $test) {
                $display_by = $test->config['display_by'];
                $max_rt = $display_by * 1500;
                $min_rt = 300;
                $primes = $test->primes;
                $targets = collect($test->targets)->filter(function($t) {
                    return !isset($t['can_unselect']) || (isset($t['can_unselect']) && $t['can_unselect'] === false);
                })->toArray();
                foreach (generator($primes) as $prime) {
                    $response = collect($record->responses)->filter(function($res) use($test) {
                        return $res['id'] == $test->id;
                    })->first();

                    $prime_response = collect($response['primes'] ?? [])->filter(function($res) use($prime) {
                        return $res['index'] == $prime['index'];
                    })->first();
                    
                    $target_indeces = collect($targets)->pluck('index')->toArray();
                    if (!is_null($prime_response)) {
                        $target_response = collect($prime_response['data'])->filter(function ($res) use ($target_indeces) {
                            return in_array($res['index'], $target_indeces);
                        })->unique('index')->sortBy('index')->map(function($v) use($target_ids, $participant_id) {
                            if ($target_ids) {
                                if (isset($target_ids[$v['index'] - 1])) {
                                    if (!in_array($participant_id, $target_ids[$v['index'] - 1]) && $v['selected'] == true) {
                                        $v['selected'] = null;
                                        return $v;
                                    } else {
                                        return $v;
                                    }
                                } else {    
                                    return $v;
                                }
                            } else {
                                return $v;
                            }
                        });
                    } else {
                        $target_response = collect($targets)->map(function($target, $key) {
                            return [
                                'index' => $target['index'],
                                'target' => remove_special_chars($target['target'], $target['equivalent']),
                                'equivalent' => $target['equivalent'],
                                'rt' => 0,
                                'rank' => 0,
                                'selected' => null,
                                'slot' => $target['index'],
                                'value' => $target['value']
                            ];
                        })->sortBy('index');
                    }
                    $prime_response['data'] = $target_response->map(function($res) use($max_rt, $min_rt) {
                        $rt2 = null;
                        if ($res['selected']) {
                            $res['rt2'] = $res['rt'];
                            if ($res['rt'] > $max_rt) {
                                $res['rt2'] = $max_rt;
                            }
                            if ($res['rt'] < $min_rt) {
                                $res['rt2'] = $min_rt;
                            }
                        } else {
                            $res['rt2'] = null;
                        }
                        //$res['rank2'] = $this->whatRecodedRank($res['rank']);
                        $res['rank2'] = $res['rank'] === 0 ? 0 : 2/($res['rank']*$res['rank']*$res['rank']);
                        //$res['new_algo1'] = is_null($res['rt2']) ? 0 : ($res['rt2'] * $res['rank2']);
                        return $res;
                    });
                    $avg_rt = collect($prime_response['data'])->pluck('rt2')->avg();

                    $prime_response['data'] = collect($prime_response['data'])->map(function($res) use($avg_rt) {
                        $res['rt3'] = is_null($res['rt2']) ? 0 : (100*($avg_rt/$res['rt2']));
                        $res['new_algo1'] = is_null($res['rt2']) ? 0 : ($res['rt3'] * $res['rank2']);
                        return $res;
                    });
                    $has_prime_response = collect($target_response)->filter(fn($vv) => $vv['selected'])->count() > 0;
                    $responses[] = new Response([
                        'test_id' => $test->id,
                        'index' => $prime['index'],
                        'info' => $respondents_info,
                        'rts' => $prime_response['data'],
                        'average_rt' => $avg_rt,
                        'record_id' => $record->id,
                        'prime' => $prime['equivalent'],
                        'is_taken' => $has_prime_response
                    ]);
                }
            }
        }
        $key = Uuid::uuid4();
        Cache::put($key, [
            'records' => $records->pluck('id'),
            'tests' => $tests->pluck('id'),
            'data' => $responses,
            'survey' => $survey->toArray(),
            'filter' => $filter->name
        ]);
        return response()->json([
            'key' => $key
        ]);
    }

    private function whatRecodedRank($rank)
    {
        if ($rank == 0) {
            return 0;
        }
        $table = [
            1 => 2.000,
            2 => 0.250,
            3 => 0.074,
            4 => 0.031,
            5 => 0.016,
            6 => 0.009,
            7 => 0.006,
            8 => 0.002
        ];
        return $table[$rank];
    }

    // Exprt and merge surveys using the new computation
    public function merge(Request $request)
    {
        $keys = $request->keys;
        $counts = [];
        $ranks = [];
        $rts = [];
        $headers = collect(['Survey ID', 'Survey Name', 'Participant ID', 'URL', 'Device', 'IP', 'Started', 'Ended']);
        $options = $request->all();
        $surveys = [];
        $url_headers = collect([]);
        foreach (generator($keys) as $key) {
            $cache = Cache::get($key);
            if (!isset($surveys[$cache['survey']['id']])) {
                $surveys[$cache['survey']['id']] = Survey::find($cache['survey']['id']);
            }
            foreach (generator($cache['records']) as $rec_id) {
                $info = collect($cache['data'])->filter(function($v) use($rec_id) {
                    return $v->record_id == $rec_id;
                })->first()->info;
                $url_headers = $url_headers->merge(query_parser($info['url']));
            }
            foreach (generator($cache['tests']) as $test) {

                $responses = collect($cache['data'])->filter(function($res) use($test) {
                    return $res->test_id === $test;
                });

                $prime_indeces = collect($responses)->pluck('index')->unique()->sort();
                foreach (generator($prime_indeces) as $index) {
                    $prime_responses = $responses->filter(function($res) use($index) {
                        return $res->index === $index;
                    });
                    foreach (generator($prime_responses) as $response) {
                        $counts[$cache['survey']['id']][$response->record_id][$test][$index] = collect($response->rts)->pluck('selected')->map(function ($s) {
                            return $s === true ? 1 : null;
                        })->toArray();
                        $ranks[$cache['survey']['id']][$response->record_id][$test][$index] = collect($response->rts)->pluck('rank')->map(function ($s) {
                            return $s !== 0 ? $s : null;
                        })->toArray();
                        $rts[$cache['survey']['id']][$response->record_id][$test][$index] = collect($response->rts)->pluck('rt')->map(function ($s) {
                            return $s !== 0 ? $s : null;
                        })->toArray();
                    }
                }
            }
        }
        $revised_rts = [];
        $medians = [];
        foreach (generator($rts) as $rt) {
            foreach (generator($rt) as $record_id => $idv) {
                $median = collect($idv)->flatten(2)->median();
                $medians[$record_id] = $median;
                $revised_rts[$record_id] = collect($idv)->flatten(2)->map(function($rr) use($median) {
                    return is_null($rr) ? 0 : 100 * $median / $rr;
                })->toArray();
            }
        }
        $overall_average = collect($revised_rts)->flatten(1)->avg();
        $revised_rts2 = collect($revised_rts)->map(function($rts) use($overall_average) {
            return collect($rts)->map(function($rt) use($overall_average) {
                return round(100 * $rt / $overall_average);
            })->toArray();
        })->toArray();

        $lastID = collect($cache['records'])->last();
        $prs = collect($cache['data'])->filter(function ($g) use ($lastID) {
            return $g->record_id === $lastID;
        })->map(function ($g) use($options) {
            $t = $options['use_attributes'] == 'true' ? collect($g->rts)->pluck('target')->map(fn($x) => remove_special_chars($x))->toArray() : collect($g->rts)->pluck('equivalent')->toArray();
            if (collect($t)->filter(fn($x) => !is_null($x))->count() === 0) {
                $t = collect($g->rts)->pluck('equivalent')->toArray();
            }
            return $t;
        })->flatten(1);

        $exclude_from_url = ['id', 'survey_id', 'list_index', 'status_index'];
        $url_headers = $url_headers->except($exclude_from_url)->keys()->toArray();

        $headers = $headers->merge($url_headers)->merge($prs)->merge([null])->merge($prs)->merge(['MEDIAN', null])->merge($prs)->merge(['AVERAGE', null])->merge($prs);

        foreach (generator($keys) as $key) {
            $cache = Cache::get($key);
            foreach ($cache['records'] as $record_id) {
                $info = collect($cache['data'])->filter(function($v) use($record_id) {
                    return $v->record_id == $record_id;
                })->first()->info;
                $count = collect($counts[$cache['survey']['id']][$record_id])->flatten(2);
                $rt = collect($rts[$cache['survey']['id']][$record_id])->flatten(2);
                $rts1 = collect($revised_rts[$record_id])->toArray();
                $rts2 = collect($revised_rts2[$record_id])->toArray();

                $extracted_url = $this->extractQueryArrayFromBaseHeaders($url_headers, query_parser($info['url']), $exclude_from_url);
                $data[] = collect([
                    $info['survey_id'],
                    $surveys[$info['survey_id']]->name,
                    $info['participant_id'],
                    $info['url'],
                    $info['device'],
                    $info['ip'],
                    $info['started'],
                    $info['ended']
                ])->merge($extracted_url)->merge($count)->merge([null])->merge($rt)->merge([$medians[$record_id], null])->merge($rts1)->merge([$overall_average, null])->merge($rts2)->toArray();
            }
        }
        $data = collect($data)->prepend($headers)->toArray();
        if (!Storage::exists('/exportables/' . $cache['survey']['id'])) {
            Storage::makeDirectory('exportables/' . $cache['survey']['id']);
        }
        $filename = "Merged.xlsx";
        if (!empty($request->responses_name)) {
            $keywords = ['$count$', '$filter$'];
            $replace = [$request->input('count'), $cache['filter']];
            $filename = remove_special_chars(str_replace($keywords, $replace, $request->responses_name) . '.xlsx');
        }
        $hashName = (string) Uuid::uuid4() . '.xlsx';
        (new FastExcel($data))->withoutHeaders()->export(storage_path("app/exportables/" . $cache['survey']['id'] . '/' . $hashName));
        $exportable = Exportable::create([
            'user_id' => $request->user()->id,
            'survey_id' => $cache['survey']['id'],
            'file' => $filename,
            'source' => $hashName,
            'file_type' => 'Responses'
        ]);
        $exportable->load('user:name,id');

        // destroy the cache data
        collect($keys)->each(function($key) {
            Cache::forget($key);
        });
        return response()->json($exportable);

    }

    private function extractQueryArrayFromBaseHeaders($base_array, $query_array, $exclusions = []): array
    {
        $results = [];
        foreach (generator($base_array) as $base) {
            if (!collect($exclusions)->contains($base))
                $results[$base] = $query_array[$base] ?? '-';
        }
        return $results;
    }

    //export data
    public function exportData(Request $request): \Illuminate\Http\JsonResponse
    {
        $cache_key = $request->key;
        $cache = Cache::get($cache_key);
        $options = $request->all();
        $data = [];
        $counts = [];
        $ranks = [];
        $rts = [];
        $rts2 = [];
        $tmp_headers = $options['minimal_respondent_level'] ?
                collect(['ID']) :
                collect(['Survey ID', 'ID', 'URL', 'Device', 'IP', 'Started', 'Ended']);
        $rank_averages = [];

        $selected_group_headers = [];
        $selected_group_headers_primes = [];

        foreach (generator($cache['tests']) as $test) {
            $responses = collect($cache['data'])->filter(function($res) use($test) {
                return $res->test_id === $test;
            });
            $_test = Test::find($test);
            $macros = collect($_test->config['macros'] ?? [])->filter(fn ($v) => $v['type'] === 'respondent')->toArray();
        

            if (count($macros) > 0) {
                foreach (generator($_test->primes) as $pr) {

                    $tmpp = array_fill(0, count($macros), null);
                    $tmpp[0] = strip_tags(textReplacer($options['variables'], $pr['prime'], $options['use_attributes']));

                    $selected_group_headers_primes[] = $tmpp;

                    foreach (generator($macros) as $macro) {
                        $selected_group_headers[] = $macro['placeholder'];
                    } 
                }
            } else {
                foreach (generator($_test->primes) as $pr) {
                    $tmpp = array_fill(0, count($_test->targets), null);
                    $tmpp[0] = strip_tags(textReplacer($options['variables'], $pr['prime'], $options['use_attributes']));

                    $selected_group_headers_primes[] = $tmpp;
                }
            }

            $prime_indeces = collect($responses)->pluck('index')->unique()->sort();
            foreach (generator($prime_indeces) as $index) {
                $prime_responses = $responses->filter(function($res) use($index) {
                    return $res->index === $index;
                });
                
                foreach (generator($prime_responses) as $response) {
                    $_macros = $macros;
                    if (count($macros) > 0) {
                        foreach (generator($_macros) as $m_key => $macro) {
                            $_macros[$m_key]['formula'] = Str::replace(
                                collect($response->rts)->map(fn ($v) => 't' . $v['index'])->toArray(), 
                                collect($response->rts)->map(fn ($v) => $v['selected'] === true ? 'true': 'false')->toArray(),
                                $macro['formula']
                            );
                        }
                        // foreach (generator($response->rts) as $_rts) {
                        //     //info($_rts);
                        //     dd(1);
                        //     foreach (generator($_macros) as $m_key => $macro) {
                        //         $_macros[$m_key]['formula'] = Str::replace('{{target' . $_rts['index'] . '}}', $_rts['selected'] === true ? 'true' : 'false', $macro['formula']);
                        //     }
                        // }
                        $counts[$response->record_id][$test][$index][] = collect($_macros)->map(fn ($v) => !$response->is_taken ? null : evaluate_eval($v['formula']));
                    } else {
                        $counts[$response->record_id][$test][$index][] = collect($response->rts)->pluck('selected')->map(function ($s) {
                            return $s === true ? 1 : ($s === false ? 0 : null);
                        })->toArray();
                    }
                    
                    if ($options['compute_by'] === 'new_algo1') {
                        $ranks_rts = collect($response->rts)->pluck('rank2')->map(function ($s) use($options) {
                            return $s !== 0
                                ? ($options['modified_rank'] ? round(1/$s, 5) : $s)
                                : ($options['modified_rank'] ? 0 : 0);
                        })->toArray();
                    } else {
                        $ranks_rts = collect($response->rts)->pluck('rank')->map(function ($s) use($options) {
                            return $s !== 0
                                ? ($options['modified_rank'] ? round(1/$s, 5) : $s)
                                : ($options['modified_rank'] ? 0 : null);
                        })->toArray();
                    }
                    
                    $ranks[$response->record_id][$test][$index][] = $ranks_rts;
                    if ($options['modified_rank'] === true) {
                        foreach (generator($ranks_rts) as $key => $value) {
                            $rank_averages[$test][$index][$key][] = $value;
                        }
                    }
                    if ($options['compute_by'] === 'new_algo1') {
                        $rts[$response->record_id][$test][$index][] = collect($response->rts)->pluck('rt3')->map(function ($s) {
                            return $s;
                        })->toArray();
                        $rts2[$response->record_id][$test][$index][] = collect($response->rts)->pluck('new_algo1')->map(function ($s) {
                            return $s;
                        })->toArray();
                    } else {
                        $rts[$response->record_id][$test][$index][] = collect($response->rts)->pluck('rt')->map(function ($s) {
                            return $s !== 0 ? $s : null;
                        })->toArray();
                    }
                    
                }
            }
        }

        $selected_group_headers_primes = collect($selected_group_headers_primes)->flatten(1)->toArray();

        // dd($rank_averages);
        if ($options['modified_rank'] === true) {
            $rank_averages = collect($rank_averages)->map(function($vals) {
                return collect($vals)->map(fn($vs) => collect($vs)->map(fn($vv) => collect($vv)->avg() ));
            })->flatten(2);
        }

        $lastID = collect($cache['records'])->last();
        
        $prs = collect($cache['data'])->filter(function ($g) use ($lastID) {
            return $g->record_id === $lastID;
        })->map(function ($g) use($options) {
            $t = $options['use_attributes'] == 'true' ? collect($g->rts)->pluck('target')->map(fn($x) => remove_special_chars($x))->toArray() : collect($g->rts)->pluck('equivalent')->toArray();
            if (collect($t)->filter(fn($x) => !is_null($x))->count() === 0) {
                $t = collect($g->rts)->pluck('equivalent')->toArray();
            }
            return $t;
        })->flatten(1);
        $prs2 = collect($cache['data'])->filter(function ($g) use ($lastID) {
            return $g->record_id === $lastID;
        })->map(function ($g) use($options) {
            $t = $options['use_attributes'] == 'true' ? collect($g->rts)->pluck('target')->map(fn($x) => remove_special_chars($x))->toArray() : collect($g->rts)->pluck('equivalent')->toArray();
            if (collect($t)->filter(fn($x) => !is_null($x))->count() === 0) {
                $t = collect($g->rts)->pluck('equivalent')->toArray();
            }
            $tmp_array = array_fill(0, count($t), null);
            $tmp_array[0] = strip_tags(textReplacer($options['variables'], $g->prime, $options['use_attributes']));
            return $tmp_array;
        })->flatten(1);
        if ($options['modified_rank'] === true) {
            $headers = collect(array_fill(0, ($options['minimal_respondent_level'] ? 1 : 7), null))->merge($selected_group_headers_primes);
            $headers2 = $tmp_headers->merge($selected_group_headers ?? $prs);
        } else {
            $headers = collect(array_fill(0, ($options['minimal_respondent_level'] ? 1 : 7), null))->merge($selected_group_headers_primes)->merge([null])->merge($prs2)->merge([null])->merge($prs2)->merge([null])->merge(count($rts2) == 0 ? [] : $selected_group_headers_primes);

            $headers2 = $tmp_headers->merge(empty($selected_group_headers) ? $prs : $selected_group_headers)->merge([null])->merge($prs)->merge([null])->merge($prs)->merge([null])->merge(count($rts2) == 0 ? [] : $prs);
        }

        $rank_count = 0;
        foreach ($cache['records'] as $record_id) {
            $info = collect($cache['data'])->filter(function($v) use($record_id) {
                return $v->record_id == $record_id;
            })->first()->info;
            $count = collect($counts[$record_id])->flatten(3);
            $rt = collect($rts[$record_id])->flatten(3);
            $rt2 = $options['compute_by'] === 'new_algo1' ? collect($rts2[$record_id])->flatten(3) : [];
            $rank = collect($ranks[$record_id])->flatten(3);
            $rank_count = $rank->count();
            if ($options['modified_rank'] === true) {
                if ($options['minimal_respondent_level']) {
                    $data[] = collect([
                        $info['participant_id'],
                    ])->merge($rank)->toArray();
                } else {
                    $data[] = collect([
                        $info['survey_id'],
                        $info['participant_id'],
                        $info['url'],
                        $info['device'],
                        $info['ip'],
                        $info['started'],
                        $info['ended']
                    ])->merge($rank)->toArray();
                }
                
            } else {
                if ($options['minimal_respondent_level']) {
                    $data[] = collect([
                        $info['participant_id'],
                    ])->merge($count)->merge([null])->merge($rt)->merge([null])->merge($rank)->merge([null])->merge($rt2)->toArray();
                } else {
                    $data[] = collect([
                        $info['survey_id'],
                        $info['participant_id'],
                        $info['url'],
                        $info['device'],
                        $info['ip'],
                        $info['started'],
                        $info['ended']
                    ])->merge($count)->merge([null])->merge($rt)->merge([null])->merge($rank)->merge([null])->merge($rt2)->toArray();
                }
                
            }
        }
        if ($options['modified_rank'] === true) {
            $data[] = [];
            $data[] = collect(array_fill(0, 7, null))->merge($rank_averages)->toArray();
        }
        $data = collect($data)->prepend($headers2)->toArray();
        if (!$options['minimal_respondent_level'])
            $data = collect($data)->prepend($headers)->toArray();
        if (!Storage::exists('/exportables/' . $cache['survey']['id'])) {
            Storage::makeDirectory('exportables/' . $cache['survey']['id']);
        }
        $filename = $cache['filter'] . '_' . Str::random(5) . '.xlsx';
        if (!empty($request->responses_name)) {
            $keywords = ['$count$', '$filter$'];
            $replace = [$request->input('count'), $cache['filter']];
            $filename = str_replace($keywords, $replace, $request->responses_name);
            if ($options['variable_name']) {
                $v_name = collect($options['variables'])->filter(function($vs) use($options) {
                    return $vs['variable'] == $options['variable_name'];
                })->first();
                $filename .= ($v_name['name'] ?? null);
            }
            if ($options['modified_rank'] === true) {
                $filename .= '-Inverse';
            }
        }
        $filename = remove_special_chars(stripslashes($filename)) . '.xlsx';
        $hashName = (string) Uuid::uuid4() . '.xlsx';
        (new FastExcel($data))->withoutHeaders()->export(storage_path("app/exportables/" . $cache['survey']['id'] . '/' . $hashName));
        $exportable = Exportable::create([
            'user_id' => $request->user()->id,
            'survey_id' => $cache['survey']['id'],
            'file' => $filename,
            'source' => $hashName,
            'file_type' => 'Responses'
        ]);
        $exportable->load('user:name,id');
        Cache::forget($cache_key); // destroy the cache
        return response()->json($exportable);

        // directly download the excel file
        // (new FastExcel($data))->withoutHeaders()->download($cache['survey']['name'] . '.xlsx');
    }

    public function getScores(Request $request)
    {
        $key = $request->key;
        $type = $request->compute_by;
        $export_results = $request->export_results;
        // $export_results = true;
        // $key = "354c41d2-e7b2-4796-9103-b94431907b2a";
        // $type = "count";
        $options = $request->all();
        $target_ids = $this->formatTargetIds($options['target_ids']);
        //info($target_ids);
        $cache = Cache::get($key);
        $tests = $cache['tests'];
        $observation_size = collect($cache['data'])->unique('record_id')->count();
        $samples = collect(collect($cache['data'])->pluck('info'))->pluck('participant_id')->unique();
        $sample_size = $samples->count();
        $counts = [];

        $data = [];
        $tt = collect([]);
        foreach (generator($tests) as $test) {
            $test_model = Test::find($test);
            $targets = collect($test_model->targets)->filter(function ($t) {
                return !isset($t['can_unselect']) || (isset($t['can_unselect']) && $t['can_unselect'] === false);
            })->pluck('index')->toArray();
            $responses = collect($cache['data'])->filter(fn($v) => $v->test_id === $test);
            $primes = collect($responses)->pluck('index')->unique();
            foreach (generator($primes) as $prime) {
                //$data[$test][$prime][]
                
                $prime_responses = $responses->filter(fn($v) => $v->index === $prime);
                $counts[$test][$prime] = collect($prime_responses)->filter(fn($vv) => $vv->is_taken === true)->count();
                foreach (generator($prime_responses) as $prime_response) {
                    foreach (generator($prime_response->rts) as $res) {
                        if (in_array($res['index'], $targets)) {
                            $data[$test][$prime][$res['index']][] = [
                                'selected' => $res['selected'],
                                'rank' => $res['rank'],
                                'rt' => $res['rt'],
                                'new_algo1' => $res['new_algo1']
                            ];
                        }
                    }
                }

            }
        }
        $scores = [];
        foreach (generator($data) as $test_id => $primes_data) {
            foreach (generator($primes_data) as $prime_id => $responses) {
                $target_count = count($responses);
                foreach (generator($responses) as $target_id => $rts) {
                    if ($type === 'count' || $type === 'new_algo1') {
                        // $count = 
                        //info($counts[$test_id][$prime_id]);
                        //$score = round((collect($rts)->filter(fn($v) => $v['selected'] === true)->count() / $observation_size) * 100, 0);
                        if ($type === 'new_algo1') {
                            //info(collect($rts)->pluck('new_algo1')->toArray());
                            $score = $counts[$test_id][$prime_id] <= $options['minimum_sample'] ? null : round(collect($rts)->pluck('new_algo1')->avg(), 0);
                            //info($rts);
                        } else {
                            // info($target_id);
                            $_count = !empty($target_ids) ? $samples->intersect($target_ids[$target_id - 1])->count() : $counts[$test_id][$prime_id];
                            // $_count = collect($rts)->reject(fn ($value) => is_null($value['selected']))->count();
                            $score = $_count <= $options['minimum_sample'] ? null : round((collect($rts)->filter(fn($v) => $v['selected'] === true)->count() / $_count) * 100, 0);
                        }
                        
                    } else if ($type === 'rank') {
                        if ($options['modified_rank']) {
                            $score = round(collect($rts)->pluck('rank')->map(function($s) use($options) {
                                return $s !== 0
                                    ? ($options['modified_rank'] ? round(1/$s, 5) : $s)
                                    : ($options['modified_rank'] ? 0 : null);
                            })->avg() * 100, 0);
                        } else {
                            $rank_average = collect($rts)->filter(fn($v) => $v['selected'] === true)->pluck('rank')->avg();
                            $score = round(100 * (($target_count + 1) - $rank_average) / ($target_count + 1), 0);
                        }

                    } else if ($type === 'rt') {
                        $score = round(collect($rts)->filter(fn ($v) => $v['selected'] === true)->pluck('rt')->median(), 0);
                    }
                    $scores[$test_id][$prime_id][$target_id] = $score;
                }
            }
        }
        Cache::forget($key);

        $result = [
            'count' => $observation_size,
            'sample' => $sample_size,
            'overall' => round(collect($scores)->flatten(2)->avg(), 0),
            'scores' => $scores,
            'hasExports' => $export_results,
            'survey' => $cache['survey']['id'],
            'filter' => $cache['filter'],
            'survey_name' => $cache['survey']['name']
        ];
        $cache_key = Uuid::uuid4();
        Cache::put($cache_key, $result);
        $result['key'] = $cache_key;
        if ($export_results) {
            $exportable = $this->exportScores($request, $result);
            $result['export'] = $exportable;
        }
        return response()->json($result);
    }

    private function exportScores(Request $request, $result)
    {
        $data = [];
        $counter = 0;
        $options = $request->all();
        foreach (generator($result['scores']) as $test_id => $sub_scores) {
            $counter++;
            $test = Test::find($test_id);
            $primes = $test->primes;
            $targets = $test->targets;
            $tmp = [];
            $tmp[] = ['Number of obversavations', $result['count']];
            $tmp[] = ['Sample n', $result['sample']];
            $tmp[] = ['Overall Effect'];
            $tmp[] = [$result['overall']];
            $tmp[] = [null];
            $tmp[] = [null];
            $tmp[] = ['Attributes'];
            $tmps = [];
            foreach (generator($sub_scores) as $prime_id => $scores) {
                $prime = collect($primes)->filter(fn($v) => $v['index'] === $prime_id)->first();
                $tmp2 = [];
                $tmp3 = [];
                $tmp4 = [];
                $tmp5 = [];
                $tmp6 = [];
                foreach (generator($scores) as $target_id => $score) {
                    $target = collect($targets)->filter(fn($v) => $v['index'] === $target_id)->first();
                    $tmp2[] = textReplacer($options['variables'], $options['use_attributes'] == 'true' ? ($prime['prime'] ?? $prime['equivalent']) : $prime['equivalent'], $options['use_attibutes']);
                    $tmp3[] = $options['use_attributes'] == 'true' ? (strip_tags($target['target']) ?? $target['equivalent']) : $target['equivalent'];
                    $tmp4[] = 'Positive';
                    $tmp5[] = $score;
                    $tmp6[] = percentStat($score);
                }
                $tmps['primes'][] = $tmp2;
                $tmps['targets'][] = $tmp3;
                $tmps['valence'][] = $tmp4;
                $tmps['scores'][] = $tmp5;
                $tmps['stats'][] = $tmp6;
            }
            $tmp[] = collect($tmps['primes'])->flatten(1)->toArray();
            $tmp[] = collect($tmps['targets'])->flatten(1)->toArray();
            $tmp[] = collect($tmps['valence'])->flatten(1)->toArray();
            $tmp[] = collect($tmps['scores'])->flatten(1)->toArray();
            $tmp[] = collect($tmps['stats'])->flatten(1)->toArray();
            $data['Trial ' . $counter] = $tmp;
        }

        $filename = $result['filter'] . '_' . Str::random(5) . '.xlsx';
        if (!empty($request->computed_name)) {
            $keywords = ['$count$', '$filter$'];
            $replace = [$request->input('count'), $result['filter']];
            $filename = str_replace($keywords, $replace, $request->computed_name);
            if ($options['variable_name']) {
                $v_name = collect($options['variables'])->filter(function($vs) use($options) {
                    return $vs['variable'] == $options['variable_name'];
                })->first();
                $filename .= "-" . ($v_name['name'] ?? null);
            }
        }
        $filename = stripslashes(remove_special_chars($filename)) . '.xlsx';
        $hashName = (string) Uuid::uuid4() . '.xlsx';
        $exportable = Exportable::create([
            'user_id' => $request->user()->id,
            'survey_id' => $result['survey'],
            'file' => $filename,
            'source' => $hashName,
            'file_type' => 'Computed'
        ]);
        $exportable->load('user:name,id');
        $sheets = new SheetCollection($data);
        if (!Storage::exists('/exportables/' . $result['survey'])) {
            Storage::makeDirectory('exportables/' . $result['survey']);
        }
        (new FastExcel($sheets))->withoutHeaders()->export(storage_path("app/exportables/" . $result['survey'] . '/' . $hashName));
        return $exportable;
    }

    public function computeTabularIQScores(Request $request)
    {
        $options = $request->all();
        //$keys = ['30da1c2f-610b-40f2-92ed-4ce450f9ba85', 'd7b8b469-8dd0-4b1d-9466-f0940845c87c'];
        $keys = $request->cache_keys;
        $scores = collect([]);
        $scores_collection = collect([]);
        $filter_names = [];
        $filter_counts = [];
        foreach (generator($keys) as $key) {
            $cache = Cache::get($key);
            $scores = $scores->merge(collect($cache['scores'])->flatten(2));
            $scores_collection->push($cache);
            $filter_names[] = $cache['filter'];
            $filter_counts[] = $cache['count'];
            Cache::forget($key);
        }
        $filters = $options['filters'];
        $filter_names_reversed = collect($filter_names)->mapWithKeys(function($item, $key) {
            return [$item => $key];
        });
        $filters_f = collect($filters)->map(function($v) use($filter_names_reversed) {
            return $filter_names_reversed[$v] ?? null;
        });

        $average = round($scores->avg(), 0);
        $iq_scores = $this->transformScores($scores_collection, $average);
        $raw_scores = $this->transformScores($scores_collection);

        $headers = collect(['Order', 'Part', 'Trial', 'Category', 'Dimensions', 'Attributes'])->merge($filters);
        $iq_data = [];
        $raw_data = [];

        $formattedIQScores = $this->exportIQScores($iq_scores, $options);
        $left = $formattedIQScores['left'];
        $parallel = $formattedIQScores['data'];

        $left = collect($left)->flatten(1)->toArray();

        $parallel = collect($parallel)->flatten(2);
        foreach (generator($left) as $key => $values) {
            $p_data = $parallel[$key];
            $iq_data[$key] = collect($values)->merge(collect($filters_f)->map(function($v) use($p_data) {
                return $p_data[$v] ?? null;
            })->toArray());
        }
        $iq_data = collect($iq_data)->prepend($headers)->toArray();

        $formattedRawSscores = $this->exportIQScores($raw_scores, $options);
        $r_left = $formattedRawSscores['left'];
        $r_parallel = $formattedRawSscores['data'];
        $r_left = collect($r_left)->flatten(1)->toArray();
        $r_parallel = collect($r_parallel)->flatten(2);
        foreach (generator($r_left) as $key => $values) {
            $r_data = $r_parallel[$key];
            $raw_data[$key] = collect($values)->merge(collect($filters_f)->map(function($v) use($r_data) {
                return $r_data[$v] ?? null;
            })->toArray());
        }
        $raw_data = collect($raw_data)->prepend($headers)->toArray();

        //$formattedMacroScores = $this->exportIQScoresMacros($raw_scores, $options);

        $filename = $cache['survey_name'] . '_' . Str::random(5) . '.xlsx';
        if (!empty($request->tabular_name)) {
            $filename = $request->tabular_name;
            // $keywords = ['$count$', '$filter$'];
            // $replace = [$request->input('count'), $result['filter']];
            // $filename = str_replace($keywords, $replace, $request->computed_name);
            if ($options['variable_name']) {
                $v_name = collect($options['variables'])->filter(function($vs) use($options) {
                    return $vs['variable'] == $options['variable_name'];
                })->first();
                $filename .= ($v_name['name'] ?? null);
            }
            if ($options['modified_rank'] === true) {
                $filename .= "-Inverse";
            }
        }
        $filename = remove_special_chars(stripslashes($filename)) . '.xlsx';
        $hashName = (string) Uuid::uuid4() . '.xlsx';
        $exportable = Exportable::create([
            'user_id' => $request->user()->id,
            'survey_id' => $cache['survey'],
            'file' => $filename,
            'source' => $hashName,
            'file_type' => 'Tabular'
        ]);
        $exportable->load('user:name,id');


        $sheets = new SheetCollection([
            'Computed Scores' => $raw_data,
            'IQ Scores' => $iq_data,
            'Counts' => [$filters, collect($filters_f)->map(function($v) use($filter_counts) {
                return $filter_counts[$v] ?? null;
            })->toArray()]
        ]);
        if (!Storage::exists('exportables/' . $cache['survey'])) {
            Storage::makeDirectory('exportables/' . $cache['survey']);
        }
        (new FastExcel($sheets))->withoutHeaders()->export(storage_path("app/exportables/" . $cache['survey'] . '/' . $hashName));
        return $exportable;
    }

    private function exportIQScores($iq_scores, $options): array
    {
        $parallel = [];
        $left = [];
        $count = 1;
        $once = true;
        foreach (generator($iq_scores) as $key => $tests) {
            foreach (generator($tests) as $test_id => $tst) {
                $test = Test::find($test_id);
                $primes = $test->primes;
                $targets = $test->targets;
                $macros = collect($test->config['macros'] ?? [])->filter(fn ($v) => $v['type'] === 'tabular')->toArray();
                
                foreach (generator($tst) as $prime_index => $prime_scores) {
                    $_macros = $macros;
                    $prime = collect($primes)->filter(fn ($vv) => $vv['index'] == $prime_index)->first();
                    
                    foreach (generator($prime_scores) as $target_id => $score) {
                        if (count($macros) > 0) {
                            foreach (generator($_macros) as $key => $macro) {
                                
                                $_macros[$key]['formula'] = Str::replace('t' . $target_id, $score, $macro['formula']);
                            }
                        } else {
                            $target = collect($targets)->filter(fn ($vv) => $vv['index'] == $target_id)->first();
                            if ($once)
                                $left[$test_id][] = [
                                    $count,
                                    null,
                                    $target_id,
                                    null,
                                    cleanString(textReplacer($options['variables'], $options['use_attributes'] == 'true' ? $prime['prime'] : $prime['equivalent'], $options['use_attributes']), $prime['equivalent'], $options['use_attributes']),
                                    cleanString($options['use_attributes'] == 'true' ? $target['target'] : $target['equivalent'], $target['equivalent']) 
                                ];
                            $parallel[$test_id][$prime_index][$target_id][] = $score;
                            $count++;
                        }
                    }
                    
                    if (count($_macros) > 0) {
                        foreach (generator($_macros) as $key => $macro) {
                            if ($once) {
                                $left[$test_id][] = [
                                    $count,
                                    null,
                                    $key + 1,
                                    null,
                                    cleanString(textReplacer($options['variables'], $options['use_attributes'] == 'true' ? $prime['prime'] : $prime['equivalent'], $options['use_attributes']), $prime['equivalent'], $options['use_attributes']),
                                    $macro['placeholder']
                                ];
                            }
                            $parallel[$test_id][$prime_index][$key + 1][] = evaluate_eval($macro['formula']);
                            $count++;
                        }
                    }
                }
            }
            $once = false;
        }

        return  [
            'data' => $parallel,
            'left' => $left
        ];
    }

    private function transformScores($collections, $average = null)
    {
        $results = collect($collections)->map(function($v) use($average) {
            return collect($v['scores'])->map(function($p) use($average) {
                return collect($p)->map(function($r) use($average) {
                    return collect($r)->map(function($s) use($average) {
                        return is_null($average) ? $s : round(($s / $average) * 100, 0);
                    })->toArray();
                })->toArray();
            })->toArray();
        })->toArray();
        return $results;
    }


    private function getVariables($project, $url_array)
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

    private function mapVariablesWithKeys($variables = [])
    {
        return false === empty($variables) ? collect($variables)->mapWithKeys(function($item, $key) {
            return [$item['variable'] => $item['key']];
        })->toArray() : [];
    }

}
