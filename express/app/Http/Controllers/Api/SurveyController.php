<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Questionnaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;



use App\Models\Filter;
use App\Models\Export;
use App\Models\Record;
use App\Models\Question;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class SurveyController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'user' => 'required',
            'survey' => 'required',
            'name' => 'required',
        ]);

        try {
            DB::beginTransaction();
            $project = Project::firstOrCreate([
                'code' => $request->survey['code']
            ], [
                'name' => $request->survey['name'],
                'team_id' => $user->currentTeam->id,
                'user_id' => $user->id,
            ]);
            $code = generateCode(10);
            $count = $project->questionnaires()->count();
            $setup = array(
            'design' =>
            array(
                'sb-color' => '#ededed',
                'tb-color' => '#ffffff',
                'cal-color' => '#ffffff',
                'isb-color' => '#ffffff',
                'radio-color' => '#ededed',
                'button-color' => '#e38733',
                'sb-txt-color' => '#2c3e50',
                'tb-lbl-color' => '#2c3e50',
                'tb-txt-color' => '#2c3e50',
                'cal-lbl-color' => '#2c3e50',
                'isb-txt-color' => '#2c3e50',
                'tbl-lbl-color' => '#2c3e50',
                'background-img' => 'https://express.splitsecondsurveys.co.uk/ide/gallery/yjNqj0sNfhu5atP3JVI2Sn5pEYsqYRqDQmAAcH9E.png',
                'checkbox-color' => '#ededed',
                'sb-hover-color' => '#ededed',
                'tb-error-color' => '#faddd5',
                'tbl-hint-color' => '#d3e7ff',
                'cal-hover-color' => '#ededed',
                'cal-today-color' => '#ededed',
                'isb-hover-color' => '#ededed',
                'radio-lbl-color' => '#2c3e50',
                'sb-border-color' => '#dbd7d7',
                'tb-border-color' => '#dbd7d7',
                'background-color' => '#ffffff',
                'button-txt-color' => '#ffffff',
                'cal-border-color' => '#dbd7d7',
                'choice-box-color' => '#ffffff',
                'slider-lbl-color' => '#2c3e50',
                'tbl-block-color1' => '#f1f1f1',
                'tbl-block-color2' => '#ffffff',
                'tbl-border-color' => '#ededed',
                'tbl-header-color' => '#1276be',
                'sb-selected-color' => '#0c5ba8',
                'slider-line-color' => '#dbd7d7',
                'warning-msg-color' => '#f15a2a',
                'button-hover-color' => '#e17126',
                'cal-selected-color' => '#1276be',
                'checkbox-lbl-color' => '#2c3e50',
                'isb-selected-color' => '#0c5ba8',
                'question-txt-color' => '#2c3e50',
                'radio-border-color' => '#dbd7d7',
                'sb-hover-txt-color' => '#2c3e50',
                'tb-error-txt-color' => '#2c3e50',
                'tbl-hint-lbl-color' => '#1276be',
                'isb-hover-txt-color' => '#2c3e50',
                'question-margin-top' => '14vmin',
                'slider-option-color' => '#dbd7d7',
                'question-margin-left' => '8vmin',
                'radio-selected-color' => '#1276be',
                'slider-default-color' => '#dbd7d7',
                'tbl-header-lbl-color' => '#ffffff',
                'cal-disable-lbl-color' => 'rgba(0,0,0,.5)',
                'checkbox-border-color' => '#dbd7d7',
                'question-margin-right' => '8vmin',
                'sb-selected-txt-color' => '#ffffff',
                'warning-msg-txt-color' => '#ffffff',
                'isb-selected-txt-color' => '#ffffff',
                'question-margin-bottom' => '14vmin',
                'checkbox-selected-color' => '#1276be',
                'choice-box-border-color' => '#ededed',
                'warning-msg-border-color' => '#b12b01',
                'background-img-brightness' => '100%',
                'slider-default-selected-color' => '#ffffff',
            ),
            'design_id' => 1,
            'random' => 0,
            'variable' => 'q',
            'variable_start' => 1,
            'hidePrevBtn' => 1,
            'showProgressBar' => 1,
            'display_logo' => 0,
            'theme' => 0,
            'display_asterisk' => 1,
            'messages' =>
            array(
                'loading' => 'Loading...',
                'saving' => 'Saving data...',
                'nextBtn' => 'NEXT',
                'prevBtn' => 'PREVIOUS',
                'finalBtn' => 'CONTINUE',

                "input1_warning_min" => "Please input at least @min characters",
                "input1_warning_max" => "Please input no more than @max characters",
                "input2_warning_minmax" => "Please input a number between @min and @max",
                "input2_warning_min" => "Please input a number greater than or equal to @min",
                "input2_warning_max" => "Please input a number less than or equal to @max",
                "input9_warning_alphanumeric" => "Please input alphanumeric characters only. Special symbols and spaces are not allowed.",
                "input8_warning_letters" => "Please input letters only. Numbers and special characters are not allowed.",
                "input4_warning_invalidemail" => "Please input a valid email address",
                "required_warning" => "This question requires an answer"

            ),
            'footer' =>
            array(
                'enable' => 1,
                'logo' => '',
            ),
            'customCSS' => '',
            'customJS' => '',
            'modals' =>
            array(
                'invalidProject' =>
                array(
                    'title' => 'Application Error',
                    'content' => 'Invalid Project.',
                ),
                'surveyClosed' =>
                array(
                    'title' => 'Application Error',
                    'content' => 'This survey is not yet available.',
                ),
                'noIdAssigned' =>
                array(
                    'title' => 'Application Error',
                    'content' => 'No ID assigned.',
                ),
                'connectionLost' =>
                array(
                    'title' => 'Application Error',
                    'content' => 'Unable to connect with server. Please check your internet connection.',
                ),
            ),
            'redirects' =>
            array(
                'complete' =>
                array(
                    'content' => 'Preparing the next part...',
                    'url' => $request->survey['complete_link'],
                    'duration' => 0,
                    'passExternalVars' => 1,
                    'passInternalVars' => 1,
                ),
                'screenout' =>
                array(
                    'content' => 'Please wait while we load the next step.',
                    'url' => $request->survey['so_link'],
                    'duration' => 0,
                    'passExternalVars' => 1,
                    'passInternalVars' => 1,
                ),
                'qualityControl' =>
                array(
                    'content' => 'Please wait while we load the next step.',
                    'url' => $request->survey['qc_link'],
                    'duration' => 0,
                    'passExternalVars' => 1,
                    'passInternalVars' => 1,
                ),
            ),
            'survey_id' => $request->survey['uuid']
        );
            $setup['variable'] = $request->input('variable') ?? chr(ord('a') + $count);
            $questionnaire = $project->questionnaires()->create([
                'name' => $request->input('name'),
                'details' => $request->input('details'),
                'user_id' => $user->id,
                'theme_id' => $setup['design_id'],
                'setup' => $setup,
                'code' => $code,
            ]);

            if (!$questionnaire) {
                DB::rollBack();
                return response()->json(['id' => null], 500);
            }

            DB::commit();
            return response()->json(['id' => $code], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['id' => null], 500);
        }
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

        $questionnaire = Questionnaire::where('code', $request->input('component_id'))->first();

        DB::beginTransaction();
        $project = Project::firstOrCreate([
            'code' => $request->survey['code']
        ], [
            'name' => $request->survey['name'],
            'team_id' => $request->input('team_id'),
            'user_id' => $user->id,
        ]);
        $code = generateCode(10);
        $new_questionnaire = $questionnaire->replicate();
        $setup = $new_questionnaire->setup;
        $setup['survey_id'] = $request->survey['uuid'];
        $setup['redirects']['complete']['url'] = $request->survey['complete_link'];
        $setup['redirects']['screenout']['url'] = $request->survey['so_link'];
        $setup['redirects']['qualityControl']['url'] = $request->survey['qc_link'];
        $new_questionnaire->fill([
            'code' => $code,
            'user_id' => $user->id,
            'project_id' => $project->id,
            'name' => $request->input('name'),
            'setup' => $setup
        ]);
        $new_questionnaire->save();
        $questionnaire->questions->each(function ($question) use ($new_questionnaire, $request) {
            $new_question = $question->replicate()->fill([
                'questionnaire_id' => $new_questionnaire->id
            ]);
            $new_question->save();
        });
        DB::commit();
        return response()->json(['id' => $code], 200);
    }

    public function destroy(Request $request, Questionnaire $questionnaire)
    {
        // info($questionnaire->code);
        // $questionnaire->delete();

        $data = Questionnaire::whereCode($request->code)->first();
        $data->delete();

        return response()->json(['message' => 'ok'], 200);
    }

    public function update(Request $request, Questionnaire $questionnaire)
    {
        $request->validate([
            'name' => 'required'
        ]);
        $questionnaire = Questionnaire::whereCode($request->code)->first();
        $setup = $questionnaire->setup;
        $setup['variable'] = $request->input('variable') ?? $setup['variable'];
        $questionnaire->update([
            'name' => $request->name,
            'details' => $request->details,
            'setup' => $setup,
        ]);
        return response()->json(['message' => 'ok'], 200);
    }

    public function q_lists_details($survey_code)
    {
        //$questionnaire = Questionnaire::where('code', $question_code)->first();
        //if ($questionnaire) {
        $project = Project::where('code', $survey_code)->first();
        //$project = $questionnaire->project;
        $questionnaires = $project->questionnaires->map(function ($q) {
            $questions = $q->questions;
            return $q;
        });
        return response()->json($questionnaires, 200);
        //}
    }

    public function q_lists_reference($questionnaire_code)
    {
        // $questionnaire = Questionnaire::where('code', $questionnaire_code)->first();
        // Gate::authorize('view', $questionnaire->parent_survey);

        $questionnaires = Questionnaire::where('code', $questionnaire_code)->get();

        $list_question = array();
        $list_question[] = ["Key", "Question", "Matrix", "Value", "Description"];
        foreach (generator($questionnaires) as $questionnaire) {
            $list_question = array_merge($list_question, $this->get_Question($questionnaire));
        }

        return response()->json($list_question, 200);
    }

    public function get_Question($quest)
    {
        $tmp_question = [];
        $var = $quest->setup;
        $count = 0;
        $storage = [];
        $array3 = [];
        $questions = Question::where(['questionnaire_id' => $quest->id])->get();
        $questions = $questions->sortBy(function ($question, $key) {
            return $question->order;
        });
        foreach ($questions as $question) {
            $tmp_question = [$var['variable'] . (string)($count + 1), cleanString($question->question)];
            array_push($storage, $this->question_type($question, $tmp_question, $var, $count, $question->question_type_id));
            $count++;
        }
        foreach ($storage as $store) {
            foreach ($store as $array1) {
                array_push($array3, $array1);
            }
        }
        return $array3;
    }

    public function question_type($question, $tmp_question, $var, $count, $q_type_id)
    {
        // dd($question->values);
        $storage = [];
        $countC = 0;
        $tmp_value = ["", "", "", "Text"];
        if ($q_type_id == 1) { // Descriptive =========================================== OKAY
            array_push($storage, $tmp_question, ["", "", "", "Descriptive"]);
        } else if ($q_type_id == 2 || $q_type_id == 3) { // Multiple choice & Image selection =========================================== OKAY
            // dd($question->choices);
            array_push($storage, $tmp_question);
            $countR = 0;
            if (!empty($question->choices)) {
                $isMultipleSelect = $question->setup;
                if ($isMultipleSelect['selectMultiple']) {
                    foreach ($question->choices as $choice) {
                        $tmp_value = [$var['variable'] . (string)($count + 1) . "_" . (string)($choice['index']), "", "", (string)($choice['checkboxScore']['active'] ?? 1), cleanString($choice['name']) ?? ''];
                        array_push($storage, $tmp_value);
                        $countC++;
                    }
                } else {
                    foreach ($question->choices as $choice) {
                        $tmp_value = [$var['variable'] . (string)($count + 1), "", "", $choice['score'] ?? $choice['index'] ?? ++$countR, cleanString($choice['name']) ?? ''];
                        array_push($storage, $tmp_value);
                        $countC++;
                    }
                }
            }
        } else if ($q_type_id == 4) { // Single textbox =========================================== OKAY
            array_push($storage, $tmp_question, ["", "", "", $this->textBox($question->setup['validate']['type'])]);
        } else if ($q_type_id == 5) { // Multiple textbox =========================================== OKAY
            array_push($storage, $tmp_question);
            if (!empty($question->items)) {
                foreach ($question->items as $item) {
                    $tmp_value = [$var['variable'] . (string)($count + 1) . "_" . (string)($item['index'] ?? ''), "", "", $this->textBox($item['validate']['type']), cleanString($item['name'])];
                    array_push($storage, $tmp_value);
                    $countC++;
                }
            }
        } else if ($q_type_id == 6) { // Open ended =========================================== OKAY
            array_push($storage, $tmp_question, $tmp_value);
        } else if ($q_type_id == 7) { // Constant sum =========================================== OKAY
            // dd($question->items);
            array_push($storage, $tmp_question);
            if (!empty($question->items)) {
                foreach ($question->items as $item) {
                    $tmp_value = [$var['variable'] . (string)($count + 1) . "_" . (string)($countC + 1), "", "", "Number", cleanString($item['name'])];
                    array_push($storage, $tmp_value);
                    $countC++;
                }
            }
        } else if ($q_type_id == 8) { // Slider =========================================== OKAY
            // dd($question->values);
            array_push($storage, $tmp_question);
            $countR = 0;
            if (!empty($question->items)) {
                foreach ($question->items as $item) {
                    $tmp_value = [$var['variable'] . (string)($count + 1) . "_" . (string)($item['index']), "", "", cleanString($item['name'])];
                    array_push($storage, $tmp_value);
                    if (!empty($question->values)) {
                        foreach ($question->values as $value) {
                            array_push($storage, ["", "", "", (string)($value['score'] ?? $value['index'] ?? ++$countR), cleanString($value['name'])]);
                        }
                    }
                    $countC++;
                }
            }
        } else if ($q_type_id == 9 || $q_type_id == 10) { // Ranking & Star rating ===========================================
            array_push($storage, $tmp_question);
            if (!empty($question->items)) {
                foreach ($question->items as $item) {
                    if ($q_type_id == 10) {
                        $tmp_value = [$var['variable'] . (string)($count + 1) . "_" . (string)($countC + 1), "", "", "Start rating", cleanString($item['name'])];
                    } else {
                        $tmp_value = [$var['variable'] . (string)($count + 1) . "_" . (string)($countC + 1), "", "", "Ranking", cleanString($item['name'])];
                    }

                    array_push($storage, $tmp_value);
                    $countC++;
                }
            }
        } else if ($q_type_id == 11) { // Matrix =========================================== DISPLAY-BY-COLUMN = PENDING
            // still not using item index
            $countR = 0;
            array_push($storage, $tmp_question);
            $displayByColumn = isset($question->setup['displayByColumn']) ? $question->setup['displayByColumn'] : 0;
            if ($displayByColumn == 1) {
                if (!empty($question->columns)) {
                    foreach ($question->columns as $column) {
                        $tmp_value_col = [$var['variable'] . (string)($count + 1) . "." . (string)($countC + 1), "", cleanString($column['name'])];
                        array_push($storage, $tmp_value_col);
                        if (!empty($question->rows)) {
                            foreach ($question->rows as $row) {
                                $tmp_value_row = ["", "", "", $countR + 1, cleanString($row['placeholder']) ?? cleanString($row['name'])];
                                array_push($storage, $tmp_value_row);
                                $countR++;
                            }
                            $countR = 0;
                        }
                        $countC++;
                    }
                }
            } else {
                if (!empty($question->rows)) {
                    foreach ($question->rows as $row) {
                        $matrixIsMultiple = $question->setup["selectMultiple"];

                        if ($matrixIsMultiple) { // multiple select =========================================== OKAY
                            $tmp_value = [$var['variable'] . (string)($count + 1) . "_" . (string)($countC + 1), "", cleanString($row['placeholder']) ?? cleanString($row['name'])];

                            array_push($storage, $tmp_value);
                            if (!empty($question->columns)) {
                                foreach ($question->columns as $column) {
                                    array_push($storage, [$var['variable'] . (string)($count + 1) . "_" . (string)($countC + 1) . "_" . $countR + 1, "", "", 1, cleanString($column['name'])]);
                                    $countR++;
                                }
                                $countR = 0;
                            }
                        } else { // single select =========================================== OKAY
                            $tmp_value = [$var['variable'] . (string)($count + 1) . "_" . (string)($countC + 1), "", cleanString($row['placeholder']) ?? cleanString($row['name'])];

                            array_push($storage, $tmp_value);
                            if (!empty($question->columns)) {
                                foreach ($question->columns as $column) {
                                    array_push($storage, ["", "", "", $countR + 1, cleanString($column['name'])]);
                                    $countR++;
                                }
                                $countR = 0;
                            }
                        }
                        $countC++;
                    }
                }
            }
        } else if ($q_type_id == 12) { // Matrix multi ===========================================
            $countR = 0;
            array_push($storage, $tmp_question);
            if (!empty($question->rows)) {
                foreach ($question->rows as $row) {
                    $tmp_value = [$var['variable'] . (string)($count + 1) . "." . (string)($countC + 1), "", cleanString($row['name'])];
                    array_push($storage, $tmp_value);
                    if (!empty($question->items)) {
                        foreach ($question->items as $item) {
                            array_push($storage, ["", "", "", $countR + 1, cleanString($item['title'])]);
                            $countR++;
                        }
                        $countR = 0;
                    }
                    $countC++;
                }
            }
        } else if ($q_type_id == 13) { // Memory test descriptive ===========================================
            array_push($storage, $tmp_question);
            if (!empty($question->items)) {
                foreach ($question->items as $item) {
                    $tmp_value = [$var['variable'] . (string)($count + 1) . "." . (string)($countC + 1), "", "", "Memory test descriptive", cleanString($item['name'])];
                    array_push($storage, $tmp_value);
                    $countC++;
                }
            }
        } else if ($q_type_id == 14) { // Memory Comparison =========================================== OKAY
            array_push($storage, $tmp_question, ["", "", "", "Memory Comparison"]);
        } else if ($q_type_id == 15) { // Button rating =========================================== OKAY
            // dd($question->items);
            array_push($storage, $tmp_question);
            $countR = 0;
            if (!empty($question->items)) {
                foreach ($question->items as $item) {
                    $tmp_value = [$var['variable'] . (string)($count + 1) . "_" . (string)($item['index']), "", "", cleanString($item['equivalent'] ?? $item['name'])];
                    array_push($storage, $tmp_value);
                    if (!empty($question->values)) {
                        foreach ($question->values as $value) {
                            array_push($storage, ["", "", "", (string)($value['score'] ?? $value['index'] ?? ++$countR), cleanString($value['name'])]);
                        }
                    }
                    $countC++;
                }
            }
        } else if ($q_type_id == 16) { // Segmentation =========================================== OKAY
            // dd($question->values);
            array_push($storage, $tmp_question);
            $countR = 0;
            if (!empty($question->items)) {
                foreach ($question->items as $item) {
                    $statementStr = $item['statementA'] . " -- " . $item['statementB'];
                    $tmp_value = [$var['variable'] . (string)($count + 1) . "_" . (string)($item['index']), "", "", cleanString($statementStr)];
                    array_push($storage, $tmp_value);
                    if (!empty($question->values)) {
                        foreach ($question->values as $value) {
                            array_push($storage, ["", "", "", (string)($value['score'] ?? $value['index'] ?? ++$countR), cleanString($value['name']) ?? "*"]);
                        }
                    }
                    $countC++;
                }
            }
        }
        return $storage;
    }

    public function textBox($type)
    {
        $list = array("Text", "Whole Number", "Decimal Number", "Email Address", "Date", "Time", "Date Time");
        $index =  (int)$type - 1;
        return $list[$index];
    }
    public function multiMetrixType($type)
    {
        $list = array("Dropdown", "Radio", "Textbox");
        $index =  (int)$type - 1;
        return $list[$index];
    }
}
