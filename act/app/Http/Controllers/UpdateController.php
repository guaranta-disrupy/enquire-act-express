<?php

namespace App\Http\Controllers;

use App\Models\Exportable;
use App\Models\Filter;
use App\Models\Group;
use App\Models\Project;
use App\Models\Survey;
use App\Models\Team;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UpdateController extends Controller
{

    public function index()
    {
        // $projects = Project::all();
        // foreach (generator($projects) as $project) {
        //     $config = collect($project->config)->merge([
        //         'css' => '',
        //         'js' => ""
        //     ])->toArray();
        //     $project->update([
        //         'config' => $config
        //     ]);
        // }
        // $tests = Test::all();
        // foreach (generator($tests) as $test) {
        //     $config = collect($test->config)->merge([
        //         'single_select' => false,
        //     ])->toArray();
        //     //unset($config['single_select']);
        //     // $targets = collect($test->targets)->map(function($target) {
        //     //     $target['colors'] = [
        //     //         'normal' => null,
        //     //         'hover' => null,
        //     //         'active' => null
        //     //     ];
        //     //     return $target;
        //     // });
        //     $test->update([
        //         'config' => $config,
        //         //'targets' => $targets
        //     ]);
        // }
        // $surveys = Survey::all();
        // foreach (generator($surveys) as $survey) {
        //     $config = $survey->config;
        //     if (!collect($config)->has('css')) {
        //         $config = collect($config)->merge([
        //             'css' => '',
        //             'js' => ''
        //         ]);
        //     }
        //     $new_config = [
        //         'forward_response' => false,
        //         'response_var' => 'ast'
        //     ];
        //     $survey->update([
        //         'config' => collect($config)->merge($new_config)->toArray()
        //     ]);
        // }
        // $tests = Test::all();
        // foreach (generator($tests) as $test) {
        //     $config = $test->config;
        //     if (!collect($config)->has('random_prime')) {
        //         $config = collect($config)->merge([
        //             'random_prime' => false
        //         ])->toArray();
        //     }
        //     $test->update([
        //         'config' => $config
        //     ]);
        // }
        // $surveys = Survey::all();
        // foreach (generator($surveys) as $survey) {
        //     foreach (generator($survey->tests) as $key => $test) {
        //         $test->update([
        //             'part' => $key + 1
        //         ]);
        //     }
        // }
        $additional_config = [
            // 'left_key' => "f",
            // 'right_key' => "j",
            // 'click_instructions' => 'Click the YES or NO.'
            // 'keypress_instructions' => "Press <strong>F</strong> if you disagree or <strong>J</strong> otherwise."
            'keypress_click' => false
        ];
        // foreach (generator($surveys) as $survey) {
        //     $survey->update([
        //         'config' => collect($survey->config)->merge($additional_config)->toArray()
        //     ]);
        // }
        /*$teams_map = [
            5 => 18,
            8 => 22,
            17 => 29,
            18 => 31,
            20 => 34,
            29 => 42,
            32 => 44,
            33 => 43,
            34 => 45,
            35 => 46,
            37 => 47,
            38 => 30,
            6 => 8,
            7 => 5,
            9 => 10,
            10 => 13,
            11 => 14,
            13 => 16,
            14 => 3,
            15 => 9,
            16 => 7,
            19 => 25,
            22 => 28,
            25 => 39,
            26 => 38,
            27 => 40,
            28 => 41,
            2 => 1,
        ];
        foreach ($teams_map as $key => $value) {
            $team = Team::find($key);
            if ($team) {
                $team->forceFill([
                    'fluent_id' => $value
                ])->save();
            }
        }
        return 'ok';*/
        // AST team ID => Fluent team ID
        $teams = [
            2 => 1,
            7 => 5,
            16 => 7,
            6 => 8,
            15 => 9,
            9 => 10,
            10 => 13,
            21 => 15,
            13 => 16,
            5 => 18,
            19 => 24,
            22 => 28,
            17 => 29,
            38 => 30,
            18 => 31,
            20 => 34,
            39 => 37,
            28 => 41,
            29 => 42,
            33 => 43,
            32 => 44,
            34 => 45,
            35 => 46,
            37 => 47,
            8 => 22,
            1 => 36,
            3 => 3,
            14 => 3,
            4 => 27,
            23 => 36
        ];
        $users = [
            1 => 1,
            2 => 2,
            9 => 3,
            5 => 5,
            13 => 7,
            23 => 7,
            4 => 8,
            12 => 9,
            6 => 10,
            7 => 12,
            8 => 13,
            10 => 14,
            3 => 17,
            14 => 18,
            19 => 20,
            16 => 21,
            15 => 22,
            17 => 23,
            18 => 24
        ];
        DB::beginTransaction();
        $projects = Project::withTrashed()->get();
        foreach ($projects as $project) {
            if (isset($teams[$project->team_id]) && isset($users[$project->user_id])) {
                $project->update([
                    'team_id' => $teams[$project->team_id],
                    'user_id' => $users[$project->user_id]
                ]);
            }

        }
        $exportables = Exportable::all();
        foreach ($exportables as $exportable) {
            if (isset($users[$exportable->user_id])) {
                $exportable->update([
                    'user_id' => $users[$exportable->user_id]
                ]);
            }
        }
        $filters = Filter::withTrashed()->get();
        foreach ($filters as $filter) {
            if (isset($users[$filter->user_id])) {
                $filter->update([
                    'user_id' => $users[$filter->user_id]
                ]);
            }
        }
        $groups = Group::withTrashed()->get();
        foreach ($groups as $group) {
            if (isset($users[$group->user_id])) {
                $group->update([
                    'user_id' => $users[$group->user_id]
                ]);
            }
        }
        $surveys = Survey::withTrashed()->get();
        foreach ($surveys as $survey) {
            if (isset($users[$survey->user_id])) {
                $survey->update([
                    'user_id' => $users[$survey->user_id]
                ]);
            }
        }
        $tests = Test::withTrashed()->get();
        foreach ($tests as $test) {
            if (isset($users[$test->user_id])) {
                $test->update([
                    'user_id' => $users[$test->user_id]
                ]);
            }
        }
        DB::commit();
        return 'ok';
    }
}
