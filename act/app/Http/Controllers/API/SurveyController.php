<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Project;
use App\Models\Survey;
use App\Models\Test;
use App\Models\Variable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class SurveyController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'user' => 'required',
            'survey' => 'required',
            'name' => 'required'
        ]);
        $config = [
            'development' => true,
            'redirects' => [
                'complete' => $request->survey['complete_link'],
                'quality_control' => '',
                'pass_variable' => false,
            ],
            'progress' => [
                'show' => true,
                'text' => '',
                'continue' => 'true'
            ],
            'buttons' => [
                'next' => 'NEXT',
                'final' => 'CONTINUE'
            ],
            'texts' => [
                'saving' => 'Please wait while we save your progress...',
                'redirecting' => 'Preparing the next part...',
                'error_start' => 'Something went wrong while we were loading the app. Please refresh the page.',
                'error_saving' => 'Something went wrong while we were saving your progress. We\'ll try again! If this keeps on happening please refresh the page.'
            ],
            'records' => [
                'show_completes' => false
            ],
            'theme' => 0,
            'js' => '',
            'css' => ''
        ];
        DB::beginTransaction();
        $project = Project::firstOrCreate([
            'code' => $request->survey['code']
        ], [
            'name' => $request->survey['name'],
            'team_id' => $user->currentTeam->id,
            'config' => $config,
            'user_id' => $user->id,
        ]);
        $group = Group::firstOrCreate([
            'project_id' => $project->id
        ], [
            'user_id' => $user->id,
            'name' => 'fluent',
            'config' => [
                'random' => false,
                'conditions' => []
            ]
        ]);
        $config2 = [
            'css' => "",
            'js' => "",
            'forward_response' => false,
            'response_var' => 'ast',
            'swipe' => false,
            'left_key' => 'f',
            'right_key' => 'j',
            'click_instructions' => 'Click Yes or No.',
            'swipe_instructions' => ' Or SWIPE <strong>LEFT</strong> or <strong>RIGHT</strong>',
            'keypress_instructions' => "Press <strong>F</strong> if you disagree or <strong>J</strong> if otherwise.",
            'keypress_click' => false,
            'fullscreen_text' => "For the best experience, please enter fullscreen.",
            'engine_bg_image' => null,
            'fullscreen_button' => "Enter Fullscreen",
            // 'dummy_trial' => false,
            'redirect' => $request->survey['complete_link'],
            'unselect_message' => 'Please confirm that none of these options apply.',
            'unselect_cancel' => 'Cancel',
            'unselect_submit' => 'Submit',
            'theme' => 0,
            'survey_id' => $request->survey['uuid'],
            'reference' => Str::random(8),
            'buttons' => [
                'next' => 'NEXT',
                'final' => 'CONTINUE',
                'button_delay' => 0,
                'is_countdown_visible' => false,
            ],
            'unselect_settings' => [
                'unselect_message' => 'Please confirm that none of these options apply.',
                'unselect_cancel' => 'Cancel',
                'unselect_submit' => 'Submit',
            ]
        ];
        $survey = Survey::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'config' => $config2,
            'name' => $request->name
        ]);
        DB::commit();
        return response()->json(['id' => $survey->id], 200);
    }

    public function copy(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'user' => 'required',
            'survey' => 'required',
            'name' => 'required',
            'component_id' => 'required',
            'team_id' => 'required'
        ]);
        $survey = Survey::find($request->input('component_id'));

        $old_survey_project = $survey->group->project;


        $config = [
            'development' => true,
            'redirects' => [
                'complete' => $request->survey['complete_link'],
                'quality_control' => '',
                'pass_variable' => false,
            ],
            'progress' => [
                'show' => true,
                'text' => '',
                'continue' => 'true'
            ],
            'buttons' => [
                'next' => 'NEXT',
                'final' => 'CONTINUE'
            ],
            'unselect_settings' => [
                'unselect_message' => 'Please confirm that none of these options apply.',
                'unselect_cancel' => 'Cancel',
                'unselect_submit' => 'Submit',
            ],
            'texts' => [
                'saving' => 'Please wait while we save your progress...',
                'redirecting' => 'Preparing the next part...',
                'error_start' => 'Something went wrong while we were loading the app. Please refresh the page.',
                'error_saving' => 'Something went wrong while we were saving your progress. We\'ll try again! If this keeps on happening please refresh the page.'
            ],
            'records' => [
                'show_completes' => false
            ],
            'theme' => 0,
            'js' => '',
            'css' => ''
        ];
        
        DB::beginTransaction();
        $project = Project::firstOrCreate([
            'code' => $request->survey['code']
        ], [
            'name' => $request->survey['name'],
            'team_id' => $request->input('team_id'),
            'config' => $config,
            'user_id' => $user->id,
        ]);

        // Copy variables if there are any from source survey
        if ($old_survey_project->variables()->exists() && $project->variables()->exists() === false) {
            $variables = $old_survey_project->variables;
            foreach (generator($variables) as $variable) {
                if (Variable::where('project_id', $project->id)->where('name', $variable->name)->exists() === false) {
                    $project->variables()->create([
                        'user_id' => $user->id,
                        'name' => $variable->name,
                        'config' => $variable->config
                    ]);
                }
            }
        }

        $group = Group::firstOrCreate([
            'project_id' => $project->id
        ], [
            'user_id' => $user->id,
            'name' => 'fluent',
            'config' => [
                'random' => false,
                'conditions' => []
            ]
        ]);
        $new_survey = $survey->replicate();
        $config2 = $new_survey->config;
        $config2['redirect'] = $request->survey['complete_link'];
        $config2['survey_id'] = $request->survey['uuid'];
        $new_survey->config = $config2;
        $new_survey->group_id = $group->id;
        $new_survey->user_id = $user->id;
        $new_survey->name = $request->name;
        $new_survey->save();
        $survey->tests->each(function($test) use($new_survey, $request) {
            $new_test = $test->replicate()->fill([
                'survey_id' => $new_survey->id,
                'user_id' => $request->user()->id
            ]);
            $new_test->save();
        });
        DB::commit();
        return response()->json(['id' => $new_survey->id], 200);
    }

    public function destroy(Survey $survey)
    {
        $survey->delete();
        return response()->json(['message' => 'ok'], 200);
    }

    public function update(Request $request, Survey $survey)
    {
        $request->validate([
            'name' => 'required'
        ]);
        $survey->update([
            'name' => $request->input('name')
        ]);
        return response()->json(['message' => 'ok'], 200);
    }

    public function tests(Survey $survey)
    {
        $variables = $survey->group->project->variables;
        $tests = $survey->tests->filter(function($test) {
            return count($test->targets) > 0 || count($test->primes) > 0;
        })->map(function($test) use($variables, $survey) {
            $prime_texts = collect($test->primes)->map(function($prime) {
                return $prime['prime'];
            })->implode(' ');
            $target_texts = collect($test->targets)->map(function($target) {
                return $target['target'];
            })->implode(' ');
            $test_texts = $test->config['main_header'] . $prime_texts . $target_texts;
            $var = collect($variables)->filter(fn ($item) => Str::contains($test_texts, "{{$item->name}}"))->first();
            return [
                'id' => $test->id,
                'name' => $test->name,
                'config' => [
                    'single_select' => $test->config['single_select'] || $test->config['auto_next'],
                    'hasVariables' => !empty($var),
                    'variables' => !empty($var) ? collect($var['config']['criteria'])->map(fn ($item) => ['val' => $item['conditions'][0]['val'], 'label' => $item['equivalent']])->values() : [],
                    'reference' => $survey->config['reference'] ?? Str::slug(Str::limit($survey->name, 15), '-')
                ],
                'targets' => $test->targets,
                'primes' => $test->primes
            ];
        })->values();
        return response()->json($tests, 200);
    }

    public function survey(Survey $survey)
    {
        $results = [];
        foreach (Test::where('survey_id', $survey->id)->lazy() as $test) {
            $q_type = $this->get_test_type($test);
            $tmp = [
                'type' => 'act',
                'question_id' => $test->id,
                'question_type_id' => 0,
                'question_type_code' => $q_type['question_type_code'],
                'question_type_name' => $q_type['question_type_name'],
                'question_reference_id' => 'None',
                'title' => $test->config['title'],
            ];
            if ($q_type['question_type_code'] == 'act_test') {
                $tmp['main_header'] = $test->config['main_header'];
                $tmp['empty_selection'] = $test->config['empty_selection'];
                $tmp['bottom_text'] = $test->config['bottom_text'];
                $tmp['primes'] = collect($test->primes)->map(fn ($prime) => ['prime_text' => $prime['prime']])->toArray();
                $tmp['targets'] = collect($test->targets)->map(fn ($target) => ['target_text' => $target['target']])->toArray();
            }
            $results[] = $tmp;
        }
        return response()->json($results, 200);
    }

    private function get_test_type($test)
    {
        $has_title = $test->config['title'] != '';

        return [
            'question_type_code' => $has_title ? 'act_description' : 'act_test',
            'question_type_name' => $has_title ? 'ACT Description' : 'ACT Test',
        ];
    }

    public function get_variables(Project $project): \Illuminate\Http\JsonResponse
    {
        $results = [];
        foreach (Variable::where('project_id', $project->id)->lazy() as $variable) {
            $tmp = [
                'type' => 'act',
                'question_id' => $variable->id,
                'question_type_id' => 0,
                'question_type_code' => 'act_variables',
                'question_type_name' => 'ACT Variables',
                'question_reference_id' => $variable->name,
                'title' => null,
            ];
            $were_string_content = collect($variable->config['criteria'])->map(fn ($item)  => strip_tags($item['value']))
                ->filter(fn ($item) => $item != '')
                ->count() > 0;
            if ($were_string_content) {
                $tmp['items'] = collect($variable->config['criteria'])->map(fn ($item) => ['item_text' => $item['value']])->toArray();
            } else {
                continue;
            }
            $results[] = $tmp;
        }
        return response()->json($results, 200);
    }

    public function get_survey_instructions(Survey $survey): \Illuminate\Http\JsonResponse
    {
        if (!$survey) {
            return response()->json(['message' => 'Survey not found'], 404);
        }
        $results = [
            'type' => 'act',
            'question_id' => $survey->id,
            'question_type_id' => 0,
            'question_type_code' => 'act_survey_instructions',
            'question_type_name' => 'ACT Survey Wide Instructions',
            'question_reference_id' => 'None',
            'title' => '',
        ];
        if ($survey->config['swipe'] == true) {
            $results['swipe_instructions'] = $survey->config['swipe_instructions'];
        }
        if ($survey->config['keypress_click'] == true) {
            $results['keypress_instructions'] = $survey->config['keypress_instructions'];
            $results['click_instructions'] = $survey->config['click_instructions'];
        }
        if (isset($survey->config['buttons'])) {
            $results['button_next'] = $survey->config['buttons']['next'];
            $results['button_final'] = $survey->config['buttons']['final'];
        }
        if (isset($survey->config['texts'])) {
            $results['text_saving'] = $survey->config['texts']['saving'];
            $results['text_redirecting'] = $survey->config['texts']['redirecting'];
        }
        if (count($results) == 7) $results = [];
        return response()->json($results, 200);
    }

    public function get_project_instructions(Project $project): \Illuminate\Http\JsonResponse
    {
        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }
        $results = [
            'type' => 'act',
            'question_id' => $project->id,
            'question_type_id' => 0,
            'question_type_code' => 'act_project_instructions',
            'question_type_name' => 'ACT Project Wide Instructions',
            'question_reference_id' => 'None',
            'title' => '',
        ];
        if (isset($project->config['buttons'])) {
            $results['button_next'] = $project->config['buttons']['next'];
            $results['button_final'] = $project->config['buttons']['final'];
        }
        if (isset($project->config['texts'])) {
            $results['text_saving'] = $project->config['texts']['saving'];
            $results['text_redirecting'] = $project->config['texts']['redirecting'];
            $results['text_error_start'] = $project->config['texts']['error_start'];
            $results['text_error_saving'] = $project->config['texts']['error_saving'];
        }
        if (count($results) == 7) $results = [];
        return response()->json($results, 200);
    }

    public function updateFromJson(Request $request, bool $isValidation = false)
    {
        $item = $request->item;

        $rules = [
            'question_id' => 'required|integer|exists:tests,id',
            'question_type_code' => 'required|string',
        ];

        switch ($item['question_type_code']) {
            case 'act_description':
                $rules['title'] = 'required|string';
                break;
            case 'act_test':
                $rules['main_header'] = 'required|string|nullable';
                $rules['empty_selection'] = 'required|string|nullable';
                $rules['bottom_text'] = 'string|nullable';
                $rules['primes'] = 'required|array';
                $rules['targets'] = 'required|array';
                break;
            case 'act_variables':
                $rules['question_id'] = ["required", "integer", "exists:variables,id"];
                $rules['items'] = 'required|array|nullable';
                break;
            case 'act_survey_instructions':
                $rules['question_id'] = ["required", "integer", "exists:surveys,id"];
                $rules['swipe_instructions'] = 'sometimes|string|nullable';
                $rules['keypress_instructions'] = 'sometimes|string|nullable';
                $rules['click_instructions'] = 'sometimes|string|nullable';
                $rules['button_next'] = 'required|string|nullable';
                $rules['button_final'] = 'required|string|nullable';
                $rules['text_saving'] = 'required|string|nullable';
                $rules['text_redirecting'] = 'required|string|nullable';
                break;
            case 'act_project_instructions':
                $rules['question_id'] = ["required", "integer", "exists:projects,id"];
                $rules['button_next'] = 'required|string';
                $rules['button_final'] = 'required|string';
                $rules['text_saving'] = 'required|string';
                $rules['text_redirecting'] = 'required|string';
                $rules['text_error_start'] = 'required|string|nullable';
                $rules['text_error_saving'] = 'required|string|nullable';
                break;
        }

        $validator = Validator::make($item, $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        if ($isValidation && $validator->passes()) {
            return response()->json(['message' => 'Validation successful', 'success' => true]);
        }

        $test = Test::find($item['question_id']);
        if (!$test || Gate::denies('update', $test->survey->parent_survey)) {
            return response()->json(['message' => 'Test not found or unauthorized'], 403);
        }
        $config = $test->config;

        if (in_array($item['question_type_code'], ['act_description', 'act_test'])) {
            if ($item['question_type_code'] == 'act_description') {
                $config['title'] = $item['title'];
            }
            if ($item['question_type_code'] == 'act_test') {
                $config['main_header'] = $item['main_header'];
                $config['empty_selection'] = $item['empty_selection'];
                $config['bottom_text'] = $item['bottom_text'];
                $test->primes = collect($test->primes)->map(function ($prime, $key) use($item) {
                    $prime['prime'] = $item['primes'][$key]['prime_text'] ?? '';
                    return $prime;
                })->toArray();
                $test->targets = collect($test->targets)->map(function ($target, $key) use($item)  {
                    $target['target'] = $item['targets'][$key]['target_text'] ?? '';
                    return $target;
                })->toArray();
                $test->config = $config;
            }
            $test->save();
        } else {
            if ($item['question_type_code'] == 'act_variables') {
                $variable = Variable::find($item['question_id']);
                if (!$variable || Gate::denies('update', $variable->project->groups()->first()->surveys()->first()->parent_survey)) {
                    return response()->json(['message' => 'Variable not found or unauthorized'], 403);
                }
                $config = $variable->config;
                $config['criteria'] = collect($config['criteria'])->map(function ($crit, $key) use($item)  {
                    $crit['value'] = $item['items'][$key]['item_text'] ?? '';
                    return $crit;
                })->toArray();
                $variable->config = $config;
                $variable->save();
            }
            if ($item['question_type_code'] == 'act_survey_instructions') {
                $survey = Survey::find($item['question_id']);
                if (!$survey || Gate::denies('update', $survey->parent_survey)) {
                    return response()->json(['message' => 'Survey not found or unauthorized'], 403);
                }
                $config = $survey->config;
                if ($config['swipe'] == true) {
                    $config['swipe_instructions'] = $item['swipe_instructions'];
                }
                if ($config['keypress_click'] == true) {
                    $config['keypress_instructions'] = $item['keypress_instructions'];
                    $config['click_instructions'] = $item['click_instructions'];
                }
                $config['buttons']['next'] = $item['button_next'];
                $config['buttons']['final'] = $item['button_final'];
                $config['texts']['saving'] = $item['text_saving'];
                $config['texts']['redirecting'] = $item['text_redirecting'];
                $survey->config = $config;
                $survey->save();
            }
            if ($item['question_type_code'] == 'act_project_instructions') {
                $project = Project::find($item['question_id']);
                if (!$project || Gate::denies('update', $project->groups()->first()->surveys()->first()->parent_survey)) {
                    return response()->json(['message' => 'Project not found or unauthorized'], 403);
                }
                $config = $project->config;
                $config['buttons']['next'] = $item['button_next'];
                $config['buttons']['final'] = $item['button_final'];
                $config['texts']['saving'] = $item['text_saving'];
                $config['texts']['redirecting'] = $item['text_redirecting'];
                $config['texts']['error_start'] = $item['text_error_start'];
                $config['texts']['error_saving'] = $item['text_error_saving'];
                $project->config = $config;
                $project->save();
            }
        }

        return response()->json(['message' => 'ok', 'success' => true], 200);
    }
}
