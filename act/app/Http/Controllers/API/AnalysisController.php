<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Record;
use App\Models\Survey;
use App\Objects\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\Exportable;
use App\Models\Test;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class AnalysisController extends Controller
{
    private function getVariables($project, $url_array)
    {
        $variables = $project->variables;
        $vars_result = [];
        foreach (generator($variables) as $variable) {
            $criteria = $variable->config['criteria'];
            foreach (generator($criteria) as $key => $cr) {
                $test = criteria_iterator($cr['conditions'], $url_array);
                if (true === $test) {
                    $vars_result[] = [
                        'variable' => $variable->name,
                        'value' => $cr['value'],
                        'name' => $cr['label'],
                        'key' => $key + 1,
                        'equivalent' => $cr['equivalent'] ?? ''
                    ];
                }
            }
        }
        return $vars_result;
    }

    private function extractCustomQuery($query_string_array)
    {
        $variables = [];
        collect($query_string_array)->each(function($v) use(&$variables) {
            $variables[$v['variable']] = $v['value'];
        });
        return $variables;
    }

    private function formatTargetIds($ids, $survey_id, $components)
    {
        if (!in_array($survey_id, $components)) return false;
        return $ids != '' ? collect(explode("\n", trim($ids)))->map(fn ($v) => explode(",", $v))->toArray() : false;
    }

    public function start_caching(Request $request, $survey_id)
    {
        // Get the ACT tests and filter out practice trials and description trials
        $options = $request->all();       
        $survey = Survey::find($survey_id);
        $tests = $survey->tests->filter(function($test) use($request) {
            return $test->config['main_trial'] == true && count($test->primes) > 0;
        })->values();

        // Format the filter IDs
        $ids = collect(explode(",", $request->get('ids')))->map(function($val) {
            return ['id' => $val];
        })->toArray();

        $target_ids = $this->formatTargetIds($request->target_ids, $survey_id, $options['components_with_target_ids']);

        // Generate temporary database table to store the filter IDs
        $temp_table = generateTemporaryTable();

        // Insert filter IDs to temporary table
        DB::table($temp_table)->insert($ids);

        // Get the records using the filter IDs stored in temporary table
        $records = Record::where('survey_id', $survey->id)->where(function($query) use($temp_table) {
            $query->whereIn('participant_id', function($q) use($temp_table) {
                $q->select('id')->from($temp_table);
            });
        })->orderBy('id', 'asc')->get();


        // Detect the variable presence
        $variables = [];
        if ($request->has('variables') && count($request->get('variables')) > 0) {
            $project = $survey->group->project;
            $vrs = collect($request->get('variables'))->mapWithKeys(function($v) {
                return [$v['variable'] => $v['value']];
            });
            $variables = $this->getVariables($project, $vrs);
            $query_array = $this->extractCustomQuery($request->variables);
            $records = $records->filter(function($record) use($query_array) {
                $url_components = parse_url($record->info['url']);
                parse_str($url_components['query'], $url_array);
                return count(array_intersect_assoc($query_array, $url_array)) > 0;
            });
        }

        // Exclude repeating IDs
        $records = $records->unique('participant_id');
        if ($records->count() <= ($request->get('minimum_sample') ?? 0)) {
            return response()->json([
                'key' => null
            ]);
        }
        

        $respondents_info = [];
        $responses = [];
        
        if(!$records->isEmpty())
        {
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

                    $targets = collect($test->targets)->filter(function($t) use($options, $record) {
                        if ($options['exclude_unselect']) {
                            if (isset($options['exclude_unselect_components']) && count($options['exclude_unselect_components']) === 0) {
                                return !isset($t['can_unselect']) || (isset($t['can_unselect']) && $t['can_unselect'] === false);
                            } else {
                                return in_array($record->survey_id, $options['exclude_unselect_components']) ? !isset($t['can_unselect']) || (isset($t['can_unselect']) && $t['can_unselect'] === false) : true;
                            }
                        } else {
                            return true;
                        }
                        
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
                                    'windsor1' => null,
                                    'windsor2' => null
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

                        $wrThres = $options['threshold'] ?? 0;
                        $prime_response['data'] = collect($prime_response['data'])->map(function($res) use($wrThres) {
                            $res['windsor1'] = !$res['selected'] ? null : ($res['rt'] > $wrThres ? $wrThres : $res['rt']);
                            return $res;
                        });                    
                        $avg_wr1 = collect($prime_response['data'])->pluck('windsor1')->avg();
                        $prime_response['data'] = collect($prime_response['data'])->map(function($res) use($avg_wr1) {
                            $res['windsor2'] = !$res['selected'] || $res['windsor1'] == 0 ? 0 : (100*($avg_wr1/$res['windsor1']));
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
        }

        $key = Uuid::uuid4();
        Cache::put($key, [
            'records' => $records->pluck('id'),
            'tests' => $tests->pluck('id'),
            'data' => $responses,
            'survey' => $survey->toArray(),
            'filter' => $request->get('filter_name'),
        ]);
        return response()->json([
            'key' => $key,
            'variables' => $variables
        ]);
    }

    private function temporary_rank($val)
    {
        if ($val == 1) {
            return 1;
        } else if ($val == 2) {
            return 0.5;
        } else if ($val == 3) {
            return 0.3;
        } else {
            return 0.2;
        }
    }

    public function exportData(Request $request)
    {        
        $cache_key = $request->get('key');
        $cache = Cache::get($cache_key);
        $options = $request->all();
        $data = [];
        $counts = [];
        $ranks = [];
        $ranks_modified = [];
        $rts = [];
        $rts2 = [];
        $windsorise = [];
        $windsorise2 = [];
        // $options['columns'] = json_decode($options['columns']);
        // $options['export'] = json_decode($options['export']);

        $tmp_headers = collect([
            'Survey ID' => in_array('survey_id', $options['columns']),
            'ID' => true,
            'URL' => in_array('url', $options['columns']),
            'Device' => in_array('device', $options['columns']),
            'IP' => in_array('ip', $options['columns']),
            'Started' => in_array('start', $options['columns']),
            'Ended' => in_array('end', $options['columns'])
        ])->filter()->keys();

        $rank_averages = [];

        $selected_group_headers = [];

        if (!is_null($cache)) {

            foreach (generator($cache['tests']) as $test) {
                $responses = collect($cache['data'])->filter(function($res) use($test) {
                    return $res->test_id === $test;
                });

                $_test = Test::find($test);
                $macros = collect($_test->config['macros'] ?? [])->filter(fn ($v) => $v['type'] === 'respondent')->toArray();

                if (count($macros) > 0) {
                    foreach (generator($_test->primes) as $pr) {

                        foreach (generator($macros) as $macro) {
                            $selected_group_headers[] = $macro['placeholder'];
                        } 
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
                $headers = collect(array_fill(0, ($options['minimal_respondent_level'] ? 1 : 7), null))->merge($prs2);
                $headers2 = $tmp_headers->merge($selected_group_headers ?? $prs);
            } else {
                $headers = collect(array_fill(0, ($options['minimal_respondent_level'] ? 1 : 7), null))->merge($prs2)->merge([null])->merge($prs2)->merge([null])->merge($prs2)->merge([null])->merge(count($rts2) == 0 ? [] : $prs2);
                $headers2 = collect($tmp_headers);
                if (in_array('selection', $options['export'])) {
                    $headers2 = $headers2->merge(empty($selected_group_headers) ? $prs : $selected_group_headers)->merge([null]);
                }
                if (in_array('rt', $options['export'])) {
                    $headers2 = $headers2->merge($prs)->merge([null]);
                }
                if (in_array('rank', $options['export'])) {
                    $headers2 = $headers2->merge($prs)->merge([null]);
                }
                if (in_array('modified_rank', $options['export'])) {
                    $headers2 = $headers2->merge(count($rts2) == 0 ? [] : $prs);
                }
                if (isset($options['windsorise']) && $options['windsorise']) {
                    $headers2 = $headers2->merge($prs)->merge([null]);
                }
                if (isset($options['windsorise']) && $options['windsorise']) {
                    $headers2 = $headers2->merge($prs)->merge([null]);
                }
                //$headers2 = $tmp_headers->merge(empty($selected_group_headers) ? $prs : $selected_group_headers)->merge([null])->merge($prs)->merge([null])->merge($prs)->merge([null])->merge(count($rts2) == 0 ? [] : $prs);
            }

            $rank_count = 0;
            foreach ($cache['records'] as $record_id) {
                $info = collect($cache['data'])->filter(function($v) use($record_id) {
                    return $v->record_id == $record_id;
                })->first()->info;
                $count = collect($counts[$record_id])->flatten(3);

                // default response time
                $rt = collect($rts[$record_id])->flatten(3);

                // windsorise
                if (isset($options['windsorise']) && $options['windsorise']) {
                    $threshold = $options['threshold'] ?? 0;
                    $windsorise = collect($rts[$record_id])->flatten(3)
                    ->map(function ($item) use ($threshold) {
                        if (is_null($item)) {
                            return null;
                        }
                        return $item > $threshold ? $threshold : $item;
                    });

                    // computes for windsorise 2
                    $nonNullResponse = $windsorise->filter();
                    $sumWR = $nonNullResponse->sum();
                    $countWR = $nonNullResponse->count();
                    $rt_avgWR = $countWR ? $sumWR / $countWR : 0;

                    $windsorise2 = $windsorise->map(function ($val) use ($rt_avgWR) {
                        return $val === null ? 0 : round((100 * $rt_avgWR) / $val, 2);
                    });
                }

                $rt2 = $options['compute_by'] === 'new_algo1' ? collect($rts2[$record_id])->flatten(3) : [];

                // old ranking before null rank to 0
                // $rank = collect($ranks[$record_id])->flatten(3);

                // this turns null rank to 0
                $rank = collect($ranks[$record_id])->flatten(3)
                        ->map(function ($item) {
                            return $item === null ? 0 : $item;
                        });

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
                        $tmp_data = collect([]);
                        $tmp_data->when(in_array('survey_id', $options['columns']), function (Collection $tmp_data) use($info) {
                            return $tmp_data->push($info['survey_id']);
                        });
                        $tmp_data->push($info['participant_id']);
                        $tmp_data->when(in_array('url', $options['columns']), function (Collection $tmp_data) use($info) {
                            return $tmp_data->push($info['url']);
                        });
                        $tmp_data->when(in_array('device', $options['columns']), function (Collection $tmp_data) use($info) {
                            return $tmp_data->push($info['device']);
                        });
                        $tmp_data->when(in_array('ip', $options['columns']), function (Collection $tmp_data) use($info) {
                            return $tmp_data->push($info['ip']);
                        });
                        $tmp_data->when(in_array('start', $options['columns']), function (Collection $tmp_data) use($info) {
                            return $tmp_data->push($info['started']);
                        });
                        $tmp_data->when(in_array('end', $options['columns']), function (Collection $tmp_data) use($info) {
                            return $tmp_data->push($info['ended']);
                        });
                        if (in_array('selection', $options['export'])) {
                            $tmp_data = $tmp_data->merge($count)->merge([null]);
                        }
                        if (in_array('rt', $options['export'])) {
                            $tmp_data = $tmp_data->merge($rt)->merge([null]);
                        }
                        if (in_array('rank', $options['export'])) {
                            $tmp_data = $tmp_data->merge($rank)->merge([null]);                        
                        }
                        if (in_array('modified_rank', $options['export'])) {
                            $tmp_data = $tmp_data->merge($rt2);
                        }
                        if (isset($options['windsorise']) && $options['windsorise']) {
                            $tmp_data = $tmp_data->merge($windsorise)->merge([null]);
                        }
                        if (isset($options['windsorise']) && $options['windsorise']) {
                            $tmp_data = $tmp_data->merge($windsorise2);
                        }
                        $data[] = $tmp_data->toArray();
                        //$data[] = $data->merge($count)->merge([null])->merge($rt)->merge([null])->merge($rank)->merge([null])->merge($rt2)->toArray();
                    }
                    
                }
            }
            if ($options['modified_rank'] === true) {
                $data[] = [];
                $data[] = collect(array_fill(0, 7, null))->merge($rank_averages)->toArray();
            }

        } // end of IF

        if (!isset($headers2)) {
            $headers2 = collect($tmp_headers);
        }

        // Prepend the headers
        $data = collect($data)->prepend($headers2)->toArray();
        //Cache::forget($cache_key); // destroy the cache
        return response()->json($data, 200);

        // Check if directory exists - if not then create directory
        // if (!Storage::exists('/exportables/' . $cache['survey']['id'])) {
        //     Storage::makeDirectory('exportables/' . $cache['survey']['id']);
        // }

        // Assign the filename
        // $filename = $options['part_name'] .  '.xlsx';
        // if (isset($options['variable_name'])) {
        //     $v_name = collect($options['variables'])->filter(function($vs) use($options) {
        //         return $vs['variable'] == $options['variable_name'];
        //     })->first();
        //     $filename .= "-" . ($v_name['name'] ?? null);
        // }

        // $hashName = (string) Uuid::uuid4() . '.xlsx';
        // (new FastExcel($data))->withoutHeaders()->export(storage_path("app/exportables/" . $cache['survey']['id'] . '/' . $hashName));
        // $exportable = Exportable::create([
        //     'user_id' => $request->user()->id,
        //     'user_id' => 1,
        //     'survey_id' => $cache['survey']['id'],
        //     'file' => $filename,
        //     'source' => $hashName,
        //     'file_type' => 'Responses'
        // ]);
        // $exportable->load('user:name,id');
        // Cache::forget($cache_key); // destroy the cache
        // return response()->json($exportable);
    }

    public function getScores(Request $request)
    {
        $key = $request->key;
        $type = $request->compute_by;
        // $export_results = $request->export_results;
        $options = $request->all();

        $cache = Cache::get($key);
        $tests = $cache['tests'];
        $survey = $cache['survey'];
        $target_ids = $this->formatTargetIds($options['target_ids'], $survey['id'], $options['components_with_target_ids']);
        $observation_size = collect($cache['data'])->unique('record_id')->count();
        $samples = collect(collect($cache['data'])->pluck('info'))->pluck('participant_id')->unique();
        $sample_size = $samples->count();

        $data = [];
        $tt = collect([]);
        foreach (generator($tests) as $test) {
            $test_model = Test::find($test);
            
            // $targets = collect($test_model->targets)->filter(function ($t) {
            //     return !isset($t['can_unselect']) || (isset($t['can_unselect']) && $t['can_unselect'] === false);
            // })->pluck('index')->toArray();

            $targets = collect($test_model->targets)->filter(function($t) use($options, $survey) {
                if ($options['exclude_unselect']) {
                    if (isset($options['exclude_unselect_components']) && count($options['exclude_unselect_components']) === 0) {
                        return !isset($t['can_unselect']) || (isset($t['can_unselect']) && $t['can_unselect'] === false);
                    } else {
                        return in_array($survey['id'], $options['exclude_unselect_components']) ? !isset($t['can_unselect']) || (isset($t['can_unselect']) && $t['can_unselect'] === false) : true;
                    }
                } else {
                    return true;
                }
                
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
                                'new_algo1' => $res['new_algo1'],
                                'windsor1' => $res['windsor1'],
                                'windsor2' => $res['windsor2']
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
                            $_count = !empty($target_ids) ? $samples->intersect($target_ids[$target_id - 1])->count() : $counts[$test_id][$prime_id];
                            // $_count = collect($rts)->reject(fn ($value) => is_null($value['selected']))->count();
                            $score = $_count <= $options['minimum_sample'] ? null : round((collect($rts)->filter(fn($v) => $v['selected'] === true)->count() / $_count) * 100, 1);
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
                        $score = round(collect($rts)->filter(fn ($v) => $v['selected'] === true)->pluck('rt')->median(), 1);
                    } else if ($type === 'windsor') {
                        $score = $counts[$test_id][$prime_id] <= $options['minimum_sample'] ? null : round(collect($rts)->pluck('windsor2')->avg(), 1);
                    }
                    $scores[$test_id][$prime_id][$target_id] = $score;
                }
            }
        }
        Cache::forget($key);

        $result = [
            'count' => $observation_size,
            'sample' => $sample_size,
            'overall' => round(collect($scores)->flatten(2)->avg(), 1),
            'scores' => $scores,
            // 'hasExports' => $export_results,
            'survey' => $cache['survey']['id'],
            'filter' => $cache['filter'],
            'survey_name' => $cache['survey']['name']
        ];
        $cache_key = Uuid::uuid4();
        Cache::put($cache_key, $result);
        $result['key'] = $cache_key;
        // if ($export_results) {
        //     $exportable = $this->exportScores($request, $result);
        //     $result['export'] = $exportable;
        // }
        return response()->json($result);
    }


    public function computeTabularIQScores(Request $request)
    {
        $options = $request->all();
        $keys = $request->keys;
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

        $average = round($scores->avg(), 1);
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

        // $filename = $cache['survey_name'] . '_' . Str::random(5) . '.xlsx';
        // if (!empty($request->tabular_name)) {
        //     $filename = $request->tabular_name;
        //     if ($options['variable_name']) {
        //         $v_name = collect($options['variables'])->filter(function($vs) use($options) {
        //             return $vs['variable'] == $options['variable_name'];
        //         })->first();
        //         $filename .= "-" . ($v_name['name'] ?? null);
        //     }
        //     if ($options['modified_rank'] === true) {
        //         $filename .= "-Inverse";
        //     }
        //     $filename .= '.xlsx';
        // }
        // $hashName = (string) Uuid::uuid4() . '.xlsx';
        // $exportable = Exportable::create([
        //     'user_id' => $request->user()->id,
        //     'survey_id' => $cache['survey'],
        //     'file' => $filename,
        //     'source' => $hashName,
        //     'file_type' => 'Tabular'
        // ]);
        // $exportable->load('user:name,id');


        // $sheets = new SheetCollection([
        //     'Computed Scores' => $raw_data,
        //     'IQ Scores' => $iq_data,
        //     'Counts' => [$filters, collect($filters_f)->map(function($v) use($filter_counts) {
        //         return $filter_counts[$v] ?? null;
        //     })->toArray()]
        // ]);
        // if (!Storage::exists('exportables/' . $cache['survey'])) {
        //     Storage::makeDirectory('exportables/' . $cache['survey']);
        // }
        // (new FastExcel($sheets))->withoutHeaders()->export(storage_path("app/exportables/" . $cache['survey'] . '/' . $hashName));
        // return $exportable;
        $data = [
            'percentages' => $raw_data,
            'iqs' => $iq_data,
            'counts' => [$filters, collect($filters_f)->map(function($v) use($filter_counts) {
                return $filter_counts[$v] ?? null;
            })->toArray()]
        ];
        return response()->json([
            'table' => $data,
            'key' => Uuid::uuid4()
        ]);
    }


    // Private functions
    private function transformScores($collections, $average = null)
    {
        $results = collect($collections)->map(function($v) use($average) {
            return collect($v['scores'])->map(function($p) use($average) {
                return collect($p)->map(function($r) use($average) {
                    return collect($r)->map(function($s) use($average) {
                        return (is_null($average) || $average === 0) ? $s : round(($s / $average) * 100, 1);
                    })->toArray();
                })->toArray();
            })->toArray();
        })->toArray();
        return $results;
    }

    private function mapVariablesWithKeys($variables = [])
    {
        return false === empty($variables) ? collect($variables)->mapWithKeys(function($item, $key) {
            return [$item['variable'] => $item['key']];
        })->toArray() : [];
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
    

    public function references(Survey $survey)
    {
        $array = [['Key', 'Value', 'Description']];
        $config = $survey->config;
        // if ($config['forward_response'] === false) {
        //     $array[] = [null, null, $survey->name];
        //     return $array;
        // } else {
            $tests = $survey->tests->filter(fn ($value) => !empty($value->targets) && !empty($value->primes))->values();
            $var = $config['forward_response'] === false ? 'ast_' : $config['response_var'];
            foreach (generator($tests) as $key => $test) {
                $primes = collect($test->primes)->sortBy('index')->values()->toArray();
                foreach (generator($primes) as $key2 => $prime) {
                    $array[] = [$var . $key . '_' . $prime['index'], null, $prime['equivalent']];
                    foreach (generator($test['targets']) as $target) {
                        $array[] = [
                            null,
                            $target['value'],
                            $target['equivalent']
                        ];
                    }
                }
            }
        // }
        return response()->json($array, 200);
    }
}
