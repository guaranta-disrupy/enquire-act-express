<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Theme;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\Activity_Log;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Illuminate\Http\Request;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class QuestionnaireController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($project_code)
    {
        $project = Project::whereCode($project_code)->first();
        $projects = Project::orderBy("id", "desc")->get();
        $themes = Theme::all();
        $questionnaires = Questionnaire::whereProjectId($project->id)
            ->with("user:id,name")
            ->orderBy("id", "desc")
            ->paginate(10);

        dd($questionnaires);
        return Inertia::render("Questionnaires/Index", [
            "project" => $project,
            "projects" => $projects,
            "questionnaires" => $questionnaires,
            "themes" => $themes,
            "engine_url" => env("ENGN_URL", "http://no-ENGN_URL-in.env"),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $project_code)
    {
        $request->validate([
            "name" => ["required", "max:255"],
        ]);

        $user = Auth::user();
        $project = Project::whereCode($project_code)->first();
        $count = $project->questionnaires()->count();
        $setup = $request->setup;
        $setup["variable"] = chr(ord("a") + $count);
        
        $project->questionnaires()->create([
            "name" => $request->name,
            "details" => $request->details,
            "user_id" => $user->id,
            "theme_id" => $setup["design_id"],
            "setup" => $setup,
            "code" => generateCode(10),
        ]);

        Activity_Log::create([
            "user_id" => $user->id,
            "action" => "store",
            "details" =>
            "User " .
                $user->name .
                " added new questionnaire under project name " .
                $project->name,
        ]);

        return Redirect::back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $request->validate([
            "name" => ["required", "max:255"],
            "details" => ["required", "max:255"],
        ]);

        $questionnaire = Questionnaire::whereCode($request->code)->first();

        $questionnaire->update([
            "name" => $request->name,
            "details" => $request->details,
        ]);

        Activity_Log::create([
            "user_id" => Auth::user()->id,
            "action" => "update",
            "details" =>
            "User " .
                Auth::user()->name .
                " updated questionnaire " .
                Questionnaire::whereCode($questionnaire->code)->first()->name .
                " under project name " .
                $questionnaire->project->name,
        ]);

        return Redirect::back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($code)
    {
        $data = Questionnaire::whereCode($code)->first();
        $data->delete();

        Activity_Log::create([
            "user_id" => Auth::user()->id,
            "action" => "delete",
            "details" =>
            "User " .
                Auth::user()->name .
                " deleted " .
                $data->name .
                " questionnaire under project name " .
                Project::whereId($data->project_id)->first()->name,
        ]);

        return Redirect::back();
    }

    public function settings($questionnaire_code): \Inertia\Response | \Illuminate\Http\JsonResponse
    {
        $questionnaire = Questionnaire::whereCode($questionnaire_code)->first();
        Gate::authorize('update', $questionnaire->parent_survey);

        $themes = Theme::all();
        $parts = Questionnaire::whereProjectId(
            $questionnaire->project->id
        )->get();
        // return Inertia::render("Questionnaires/Settings", [
        //     "project" => $questionnaire->project,
        //     "questionnaire" => $questionnaire,
        //     "questions" => $questionnaire->questions,
        //     "parts" => $parts,
        //     "themes" => $themes,
        //     "pageSTR" => "questionnaires.settings",
        //     "engine_url" => env("ENGN_URL", "http://no-ENGN_URL-in.env"),
        // ]);

        $data = [
            "project" => $questionnaire->project,
            "questionnaire" => $questionnaire,
            "questions" => $questionnaire->questions,
            "parts" => $parts,
            "themes" => $themes,
            "pageSTR" => "questionnaires.settings",
            "engine_url" => env("ENGN_URL", "http://no-ENGN_URL-in.env"),
        ];

        $settingsData = $data['questionnaire'];

        return request()->wantsJson() ? response()->json(['success' => true, 'data' => $settingsData]) : Inertia::render('Questions/Setting', $data);
    }

    public function updateSettings(Request $request, $questionnaire_code): \Illuminate\Http\RedirectResponse | \Illuminate\Http\JsonResponse
    {
        $questionnaire = Questionnaire::whereCode($questionnaire_code)->first();
        Gate::authorize('update', $questionnaire->parent_survey);
        $questions = Question::whereQuestionnaireId($questionnaire->id)
            ->orderBy('order')
            ->get();

        // Only renumber skip logic if variable_start actually changed
        $oldVarStart = $questionnaire->setup["variable_start"] ?? 1;
        $newVarStart = $request->settings["setup"]["variable_start"] ?? $oldVarStart;
        
        \Log::info('Skip logic renumbering check:', [
            'old_var_start' => $oldVarStart,
            'new_var_start' => $newVarStart,
            'will_renumber' => $oldVarStart != $newVarStart
        ]);
        
        // Helpers to safely rebase variable strings like "Q2", "Q12_opt1" when variable_start changes
        $isNumeric = function ($str) {
            return is_numeric($str);
        };
        $getQuestionVar = function ($str) use ($isNumeric) {
            if (empty($str)) return null;
            $ch = explode('_', $str)[0];
            $q = preg_match('/^Q\d/', $ch) ? substr($ch, 1) : $ch;
            return $isNumeric($q) ? intval($q) : null;
        };
        $getVarSuffix = function ($str) {
            if (empty($str)) return '';
            $parts = explode('_', $str);
            if (count($parts) <= 1) return '';
            return '_' . implode('_', array_slice($parts, 1));
        };
        $rebaseVarString = function ($varStr, $oldStart, $newStart) use ($getQuestionVar, $getVarSuffix, $isNumeric) {
            $currentVarNum = $getQuestionVar($varStr);
            if (!$currentVarNum || !is_numeric($oldStart) || !is_numeric($newStart)) {
                return $varStr;
            }
            $suffix = $getVarSuffix($varStr);
            $targetOrder = $currentVarNum - intval($oldStart) + 1;
            if (!$isNumeric($targetOrder) || $targetOrder < 1) {
                return $varStr;
            }
            $newVarNum = intval($newStart) + $targetOrder - 1;
            return 'Q' . $newVarNum . $suffix;
        };
        $rebaseConditionsArray = function (&$conditions, $oldStart, $newStart) use (&$rebaseConditionsArray, $rebaseVarString) {
            if (!is_array($conditions)) return;
            foreach ($conditions as &$cond) {
                if (!is_array($cond)) continue;
                if ((isset($cond['method']) && $cond['method'] === 'process') && isset($cond['var']) && is_string($cond['var'])) {
                    $cond['var'] = $rebaseVarString($cond['var'], $oldStart, $newStart);
                }
                if (isset($cond['conditions']) && is_array($cond['conditions']) && count($cond['conditions'])) {
                    $rebaseConditionsArray($cond['conditions'], $oldStart, $newStart);
                }
            }
        };

        $questionsCollection = [];
        foreach ($questions as $question) {
            $questionCopy = $question->toArray();

            if ($oldVarStart != $newVarStart && isset($questionCopy['setup']['advanceSkiplogic'])) {
                // Rebase all vars by mapping oldStart->newStart using their logical order
                if (isset($questionCopy['setup']['advanceSkiplogic']['leave']) && is_array($questionCopy['setup']['advanceSkiplogic']['leave'])) {
                    foreach ($questionCopy['setup']['advanceSkiplogic']['leave'] as &$leave) {
                        if (isset($leave['conditions']) && is_array($leave['conditions'])) {
                            $rebaseConditionsArray($leave['conditions'], $oldVarStart, $newVarStart);
                        }
                    }
                }
                if (isset($questionCopy['setup']['advanceSkiplogic']['enter']) && is_array($questionCopy['setup']['advanceSkiplogic']['enter'])) {
                    foreach ($questionCopy['setup']['advanceSkiplogic']['enter'] as &$enter) {
                        if (isset($enter['conditions']) && is_array($enter['conditions'])) {
                            $rebaseConditionsArray($enter['conditions'], $oldVarStart, $newVarStart);
                        }
                    }
                }
            }

            $questionsCollection[] = $questionCopy;
        }

        // dd($questionsCollection,$questionsCollection[1]["setup"]["advanceSkiplogic"]["leave"][0]["conditions"][0]["var"]);

        

        // Get existing setup to preserve all current settings
        $existingSetup = $questionnaire->setup;
        $requestSetup = $request->settings["setup"];
        
        // Debug logging
        \Log::info('Setting.vue backend - preserving existing setup and only updating specific fields', [
            'request_fields' => array_keys($requestSetup),
            'existing_setup_keys' => array_keys($existingSetup),
            'nested_objects' => [
                'messages' => $requestSetup['messages'] ?? 'NOT_SET',
                'footer' => $requestSetup['footer'] ?? 'NOT_SET',
                'redirects' => $requestSetup['redirects'] ?? 'NOT_SET',
                'modals' => $requestSetup['modals'] ?? 'NOT_SET',
                'language' => isset($requestSetup['language']) ? array_keys($requestSetup['language']) : 'NOT_SET'
            ]
        ]);
        
        // Only update specific fields that Setting.vue manages, preserve everything else
        $updatedSetup = $existingSetup; // Start with existing setup
        
        // Update only the fields that Setting.vue should manage
        if (isset($requestSetup["design_id"])) {
            $set_design_id = $requestSetup["design_id"] ?: 1;
            $updatedSetup["design_id"] = $set_design_id;
            $updatedSetup["design"] = Theme::find($set_design_id)["setup"]["design"];
        }
        
        if (isset($requestSetup["variable_start"])) {
            $updatedSetup["variable_start"] = $requestSetup["variable_start"];
        }
        
        if (isset($requestSetup["customCSS"])) {
            $updatedSetup["customCSS"] = $requestSetup["customCSS"];
        }
        
        if (isset($requestSetup["customJS"])) {
            $updatedSetup["customJS"] = $requestSetup["customJS"];
        }
        
        // Add other specific fields that Setting.vue manages
        $settingsFields = [
            'randomize_questions', 'show_progress', 'allow_back', 'allow_skip',
            'show_question_numbers', 'auto_save', 'time_limit', 'redirect_url',
            'completion_message', 'loading_message', 'saving_message',
            'quality_control', 'screenout', 'pass_internal', 'pass_external',
            'duration', 'logo', 'background_color', 'text_color', 'button_color',
            'hidePrevBtn', 'hideNextBtn', 'hideFinishBtn', 'showProgressBar',
            'display_asterisk', 'rtl', 'variable', 'randomiseSQ'
        ];
        
        foreach ($settingsFields as $field) {
            if (isset($requestSetup[$field])) {
                $updatedSetup[$field] = $requestSetup[$field];
            }
        }
        
        // Handle nested messages object
        if (isset($requestSetup['messages']) && is_array($requestSetup['messages'])) {
            if (!isset($updatedSetup['messages'])) {
                $updatedSetup['messages'] = [];
            }
            foreach ($requestSetup['messages'] as $key => $value) {
                $updatedSetup['messages'][$key] = $value;
            }
        }
        
        // Handle nested footer object
        if (isset($requestSetup['footer']) && is_array($requestSetup['footer'])) {
            if (!isset($updatedSetup['footer'])) {
                $updatedSetup['footer'] = [];
            }
            foreach ($requestSetup['footer'] as $key => $value) {
                $updatedSetup['footer'][$key] = $value;
            }
        }
        
        // Handle nested redirects object
        if (isset($requestSetup['redirects']) && is_array($requestSetup['redirects'])) {
            if (!isset($updatedSetup['redirects'])) {
                $updatedSetup['redirects'] = [];
            }
            foreach ($requestSetup['redirects'] as $key => $value) {
                $updatedSetup['redirects'][$key] = $value;
            }
        }
        
        // Handle nested modals object
        if (isset($requestSetup['modals']) && is_array($requestSetup['modals'])) {
            if (!isset($updatedSetup['modals'])) {
                $updatedSetup['modals'] = [];
            }
            foreach ($requestSetup['modals'] as $key => $value) {
                $updatedSetup['modals'][$key] = $value;
            }
        }
        
        // Handle other nested objects that might exist
        $nestedObjects = ['redirects', 'modals', 'footer', 'messages'];
        foreach ($nestedObjects as $objectName) {
            if (isset($requestSetup[$objectName]) && is_array($requestSetup[$objectName])) {
                if (!isset($updatedSetup[$objectName])) {
                    $updatedSetup[$objectName] = [];
                }
                foreach ($requestSetup[$objectName] as $key => $value) {
                    $updatedSetup[$objectName][$key] = $value;
                }
            }
        }

        // Handle language translations object - preserve existing translations and merge new ones
        if (isset($requestSetup['language']) && is_array($requestSetup['language'])) {
            if (!isset($updatedSetup['language'])) {
                $updatedSetup['language'] = [];
            }
            // Merge language translations - preserve existing languages and update/add new ones
            foreach ($requestSetup['language'] as $langCode => $langData) {
                if (is_array($langData)) {
                    // If language already exists, merge the data; otherwise create new
                    if (isset($updatedSetup['language'][$langCode]) && is_array($updatedSetup['language'][$langCode])) {
                        $updatedSetup['language'][$langCode] = array_merge($updatedSetup['language'][$langCode], $langData);
                    } else {
                        $updatedSetup['language'][$langCode] = $langData;
                    }
                }
            }
            \Log::info('Setting.vue backend - Language translations saved', [
                'languages' => array_keys($updatedSetup['language']),
                'language_data' => $updatedSetup['language']
            ]);
        }

        $questionnaire->update([
            "name" => $request->settings["name"],
            "setup" => $updatedSetup,
        ]);
        
        // Final validation logging
        \Log::info('Setting.vue backend - Final save validation', [
            'saved_setup_keys' => array_keys($updatedSetup),
            'nested_objects_saved' => [
                'messages' => $updatedSetup['messages'] ?? 'NOT_SAVED',
                'footer' => $updatedSetup['footer'] ?? 'NOT_SAVED',
                'redirects' => $updatedSetup['redirects'] ?? 'NOT_SAVED',
                'modals' => $updatedSetup['modals'] ?? 'NOT_SAVED',
                'language' => isset($updatedSetup['language']) ? array_keys($updatedSetup['language']) : 'NOT_SAVED'
            ],
            'fields_updated_count' => count($updatedSetup),
            'request_fields_count' => count($requestSetup)
        ]);

        $question_ = [];

        foreach($questionsCollection as $question) {
            try {
                $result = Question::where('id', $question["id"])->update([
                    'setup' => $question["setup"]
                ]);
                
                if ($result === 0) {
                    \Log::error("Failed to update question {$question['id']}: No rows affected");
                } else {
                    \Log::info("Successfully updated question {$question['id']}: {$result} rows affected");
                }
                
                $db_question_ = Question::where('id', $question["id"])->first();
                $question_[] = $db_question_;
                
            } catch (\Exception $e) {
                \Log::error("Error updating question {$question['id']}: " . $e->getMessage());
                throw $e;
            }
        }


        Activity_Log::create([
            "user_id" => Auth::user()->id,
            "action" => "Update",
            "details" =>
            "User " .
                Auth::user()->name .
                " updated settings in " .
                $questionnaire->name .
                " questionnaire under project name " .
                $questionnaire->project->name,
        ]);

        return request()->wantsJson() ? response()->json(['success' => true, 'data' => $questionnaire]) : Redirect::back();
    }

    public function updateAdvanced(Request $request, $questionnaire_code): \Illuminate\Http\RedirectResponse | \Illuminate\Http\JsonResponse
    {
        $questionnaire = Questionnaire::whereCode($questionnaire_code)->first();
        Gate::authorize('update', $questionnaire->parent_survey);

        // Validate the request
        $request->validate([
            'customCSS' => 'nullable|string',
            'customJS' => 'nullable|string',
        ]);

        // Only update customCSS and customJS, preserve all other settings
        $existingSetup = $questionnaire->setup;
        $existingSetup['customCSS'] = $request->customCSS ?? '';
        $existingSetup['customJS'] = $request->customJS ?? '';

        $questionnaire->update([
            "setup" => $existingSetup,
        ]);

        Activity_Log::create([
            "user_id" => Auth::user()->id,
            "action" => "Update",
            "details" =>
            "User " .
                Auth::user()->name .
                " updated advanced settings (CSS/JS) in " .
                $questionnaire->name .
                " questionnaire under project name " .
                $questionnaire->project->name,
        ]);

        return request()->wantsJson() ? response()->json(['success' => true, 'data' => $questionnaire]) : Redirect::back();
    }

    public function copyQuestionnaire(Request $request)
    {
        $project = Project::whereCode($request->copyCodeTo)->first();
        $QnrtoCopy = Questionnaire::whereCode($request->codeToCopy)->first();

        $getCopy = copyQuestionnaire(
            [
                "project_id" => $project->id,
                "name" => $QnrtoCopy->name . " -Copy",
                "details" => $QnrtoCopy->details . " Copy",
                "user_id" => Auth::user()->id,
            ],
            $QnrtoCopy
        );

        $count = $project->questionnaires()->count();
        $setup = $getCopy->setup;
        $setup["variable"] = chr(ord("a") + ($count - 1));
        $getCopy->update([
            "setup" => $setup,
        ]);

        foreach ($QnrtoCopy->questions as $question) {
            copyQuestions(["questionnaire_id" => $getCopy->id], $question);
        }

        Activity_Log::create([
            "user_id" => Auth::user()->id,
            "action" => "Copy",
            "details" =>
            "User " .
                Auth::user()->name .
                " replicated Questionnaire" .
                $QnrtoCopy->name .
                " to Project " .
                $project->name,
        ]);
        return Redirect::back();
    }

    /* public function copyQuestions($from,$to)
    {
        $copiedQuestions = [];
        $questionToCopy = Question::whereQuestionnaireId($from)->get();

        $questionToCopy->each(function($item,$key) use ($to){
            $copiedQuestions[$key] = $item->replicate();
            $copiedQuestions[$key]->questionnaire_id = $to;
            $copiedQuestions[$key]->save();
        });
    } */

    public function getTrashed()
    {
        $qnrTrash = Questionnaire::onlyTrashed()
            ->orderBy("id", "desc")
            ->get();

        return Inertia::render("Pages/Activity_Logs", [
            "trash_questions" => $qnrTrash,
        ]);
    }

    public function restoreQnr($id)
    {
        $qnr = Questionnaire::onlyTrashed()->find($id);
        $qnr->restore();
    }
}
