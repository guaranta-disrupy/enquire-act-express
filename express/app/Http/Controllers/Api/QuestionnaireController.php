<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Questionnaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class QuestionnaireController extends Controller
{
    public function question(Questionnaire $questionnaire)
    {
        $questionnaire_config = $questionnaire->setup;
        $results = [];
        
        // Iterate through each question in the questionnaire
        foreach (Question::where('questionnaire_id', $questionnaire->id)->lazy() as $question) {
            $q_type = $question->question_type_id;
            $config = $question->setup;
            $new_q_type = $this->determineQuestionType($q_type, $config);
            $question_type = $this->question_types($new_q_type);
            $question_text = $question->question;
            $data = $new_q_type != '1' ? $this->pick_data($question, $new_q_type) : [];

            // Collect question data
            $results[] = collect([
                'type' => 'express',
                'question_id' => $question->id,
                'question_type_id' => $q_type,
                'question_type_code' => $question_type['question_type_code'],
                'question_type_name' => $question_type['question_type_name'],
                'question_reference_id' => $questionnaire_config['variable'] . ($questionnaire_config['variable_start'] + ($question->order - 1)),
                'question_text' => $question_text,
            ])->merge($data)->toArray();
        }
        return response()->json($results);
    }

    // Determine the question type based on configuration
    private function determineQuestionType($q_type, $config): string
    {
        if (in_array($q_type, [2, 9, 11])) {
            if ($q_type == 2 || $q_type == 11) {
                return $q_type . ($config['selectMultiple'] ? 'b' : 'a');
            } elseif ($q_type == 9) {
                return $q_type . ($config['mode'] ? 'b' : 'a');
            }
        }
        return (string) $q_type;
    }

    // Get question type details
    private function question_types($type): array
    {
        return [
            '1' => [
                'question_type_code' => 'description',
                'question_type_name' => 'Description',
            ],
            '2a' => [
                'question_type_code' => 'single_select',
                'question_type_name' => 'Multiple Choice - Single Answer',
            ],
            '2b' => [
                'question_type_code' => 'multiple_select',
                'question_type_name' => 'Multiple Choice - Multiple Answer',
            ],
            '3' => [
                'question_type_code' => 'single_select_image',
                'question_type_name' => 'Multiple Choice - Single Answer Image',
            ],
            '4' => [
                'question_type_code' => 'single_textbox',
                'question_type_name' => 'Single Textbox',
            ],
            '5' => [
                'question_type_code' => 'multiple_textbox',
                'question_type_name' => 'Multiple Textbox',
            ],
            '6' => [
                'question_type_code' => 'open_textbox',
                'question_type_name' => 'Open Textbox',
            ],
            '7' => [
                'question_type_code' => 'constant_sum',
                'question_type_name' => 'Constant Sum',
            ],
            '8' => [
                'question_type_code' => 'rating_slider',
                'question_type_name' => 'Rating Slider',
            ],
            '9a' => [
                'question_type_code' => 'rating_ranking_rank',
                'question_type_name' => 'Rating Ranking by Rank',
            ],
            '9b' => [
                'question_type_code' => 'rating_ranking_sort',
                'question_type_name' => 'Rating Ranking by Sort',
            ],
            '10' => [
                'question_type_code' => 'rating_star',
                'question_type_name' => 'Rating Star',
            ],
            '11a' => [
                'question_type_code' => 'matrix_single_select',
                'question_type_name' => 'Matrix Single Select',
            ],
            '11b' => [
                'question_type_code' => 'matrix_multiple_select',
                'question_type_name' => 'Matrix Multiple Select',
            ],
        ][$type];
    }

    // Pick additional data based on question type
    private function pick_data(Question $question, $type): array
    {
        $data = [
            'error_message' => $question->setup['validate']['errorMsg']
        ];
        switch ($type) {
            case '2a':
            case '2b':
                $data['choices'] = collect($question->choices)->map(fn($choice) => ['choice_text' => $choice['name']])->toArray();
                break;
            case '4':
                $data['append_string'] = $question->setup['appendString'];
                break;
            case '5':
                $data['items'] = collect($question->items)->map(fn($item) => [
                    'item_text' => $item['name'],
                    'error_message' => $item['validate']['errorMsg']
                ])->toArray();
                break;
            case '7':
                $data['minimum_value_error_message'] = $question->setup['validate2']['errorMsg'];
                $data['total_value_error_message'] = $question->setup['validate3']['errorMsg'];
                $data['prefix'] = $question->setup['prefix'];
                $data['suffix'] = $question->setup['suffix'];
                $data['items'] = collect($question->items)->map(fn($item) => ['item_text' => $item['name']])->toArray();
                break;
            case '8':
                $data['items'] = collect($question->items)->map(fn($item) => ['item_text' => $item['name']])->toArray();
                $data['values'] = collect($question->values)->map(fn($value) => ['value' => $value['name']])->toArray();
                break;
            case '9a':
            case '9b':
            case '10':
                $data['items'] = collect($question->items)->map(fn($item) => ['item_text' => $item['name']])->toArray();
                break;
            case '11a':
            case '11b':
                $data['rows'] = collect($question->rows)->map(fn($row) => ['row_text' => $row['name']])->toArray();
                $data['columns'] = collect($question->columns)->map(fn($column) => ['column_text' => $column['name']])->toArray();
                break;
        }
        return $data;
    }

    public function questionnaire(Questionnaire $questionnaire)
    {
        if (!$questionnaire) {
            return response()->json(['message' => 'Questionnaire not found'], 404);
        }
        $results = [
            'type' => 'express',
            'question_id' => $questionnaire->code,
            'question_type_id' => 0,
            'question_type_code' => 'express_questionnaire_instructions',
            'question_type_name' => 'Express Questionnaire Wide Instructions',
            'question_reference_id' => 'None',
            'title' => '',
            'messages_loading' => $questionnaire->setup['messages']['loading'],
            'messages_saving' => $questionnaire->setup['messages']['saving'],
            'messages_nextBtn' => $questionnaire->setup['messages']['nextBtn'],
            'messages_prevBtn' => $questionnaire->setup['messages']['prevBtn'],
            'messages_finalBtn' => $questionnaire->setup['messages']['finalBtn'],
            'modals_invalidProject_title' => $questionnaire->setup['modals']['invalidProject']['title'],
            'modals_invalidProject_content' => $questionnaire->setup['modals']['invalidProject']['content'],
            'modals_surveyClosed_title' => $questionnaire->setup['modals']['surveyClosed']['title'],
            'modals_surveyClosed_content' => $questionnaire->setup['modals']['surveyClosed']['content'],
            'modals_noIdAssigned_title' => $questionnaire->setup['modals']['noIdAssigned']['title'],
            'modals_noIdAssigned_content' => $questionnaire->setup['modals']['noIdAssigned']['content'],
            'modals_connectionLost_title' => $questionnaire->setup['modals']['connectionLost']['title'],
            'modals_connectionLost_content' => $questionnaire->setup['modals']['connectionLost']['content'],
            'redirects_complete_content' => $questionnaire->setup['redirects']['complete']['content'],
            'redirects_screenout_content' => $questionnaire->setup['redirects']['screenout']['content'],
            'redirects_qualityControl_content' => $questionnaire->setup['redirects']['qualityControl']['content'],
        ];
        return response()->json($results, 200);
    }

    // Function to update the database from JSON data
    public function updateFromJson(Request $request, bool $isValidation = false)
    {
        $item = $request->item;

        $validator = Validator::make($item, $this->getValidationRules($item['question_type_code']));
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        if ($isValidation && $validator->passes()) {
            return response()->json(['message' => 'Validation successful', 'success' => true]);
        }

        // return response()->json($item);
        // foreach ($data as $item) {
        if ($item['type'] === 'express') {
            if ($item['question_type_code'] === 'express_questionnaire_instructions') {
                $questionnaire = Questionnaire::where('code', $item['question_id'])->first();
                if (!$questionnaire || Gate::denies('update', $questionnaire->parent_survey)) {
                    return response()->json(['message' => 'Questionnaire not found or unauthorized'], 404);
                }
                $questionnaire->setup = $this->updateQuestionnaireSetup($questionnaire->setup, $item);
                $questionnaire->save();
            } else {
                $question = Question::find($item['question_id']);
                if (!$question || Gate::denies('update', $question->questionnaire->parent_survey)) {
                    return response()->json(['message' => 'Question not found or unauthorized'], 404);
                }
                $question->question = $item['question_text'];
                $question->setup = $this->updateSetup($question->setup, $item);
                $this->updateQuestionFields($question, $item);
                $question->save();
            }
        }
        // }

        return response()->json(['message' => 'Data updated successfully', 'success' => true]);
    }

    private function getValidationRules($questionTypeCode)
    {
        $rules = [
            'question_id' => 'required',
            'error_message' => 'sometimes|string',
        ];

        if ($questionTypeCode !== 'express_questionnaire_instructions') {
            $rules['question_text'] = 'required|string';
            $rules['question_id'] = ["required", "exists:questions,id", "integer"];
        } else {
            $rules['question_id'] = ["required", "exists:questionnaires,code", "string"];
        }

        switch ($questionTypeCode) {
            case 'single_select':
            case 'multiple_select':
                $rules['choices'] = 'required|array';
                $rules['choices.*.choice_text'] = 'required|string';
                break;
            case 'single_textbox':
                $rules['append_string'] = 'sometimes|string';
                break;
            case 'multiple_textbox':
                $rules['items'] = 'required|array';
                $rules['items.*.item_text'] = 'required|string';
                $rules['items.*.error_message'] = 'required|string';
                break;
            case 'constant_sum':
                $rules['minimum_value_error_message'] = 'sometimes|string';
                $rules['total_value_error_message'] = 'sometimes|string';
                $rules['prefix'] = 'sometimes|string|nullable';
                $rules['suffix'] = 'sometimes|string|nullable';
                $rules['items'] = 'required|array';
                $rules['items.*.item_text'] = 'required|string';
                break;
            case 'rating_slider':
                $rules['items'] = 'required|array';
                $rules['items.*.item_text'] = 'required|string';
                $rules['values'] = 'required|array';
                $rules['values.*.value'] = 'nullable|string';
                break;
            case 'rating_ranking_rank':
            case 'rating_ranking_sort':
            case 'rating_star':
                $rules['items'] = 'required|array';
                $rules['items.*.item_text'] = 'required|string';
                break;
            case 'matrix_single_select':
            case 'matrix_multiple_select':
                $rules['rows'] = 'required|array';
                $rules['rows.*.row_text'] = 'required|string';
                $rules['columns'] = 'required|array';
                $rules['columns.*.column_text'] = 'required|string';
                break;
        }

        return $rules;
    }

    // Function to update the setup field of the question
    private function updateSetup(array $setup, array $item): array
    {
        if (isset($setup['validate']['errorMsg']))
            $setup['validate']['errorMsg'] = $item['error_message'] ?? $setup['validate']['errorMsg'];

        switch ($item['question_type_code']) {
            case 'single_textbox':
                $setup['appendString'] = $item['append_string'] ?? $setup['appendString'];
                break;
            case 'constant_sum':
                $setup['validate2']['errorMsg'] = $item['minimum_value_error_message'] ?? $setup['validate2']['errorMsg'];
                $setup['validate3']['errorMsg'] = $item['total_value_error_message'] ?? $setup['validate3']['errorMsg'];
                $setup['prefix'] = $item['prefix'] ?? $setup['prefix'];
                $setup['suffix'] = $item['suffix'] ?? $setup['suffix'];
                break;
        }

        return $setup;
    }

    // Function to update additional fields of the question
    private function updateQuestionFields(Question $question, array $item)
    {
        switch ($item['question_type_code']) {
            case 'single_select':
            case 'multiple_select':
                $question->choices = collect($question->choices)->map(function ($val, $key) use ($item) {
                    $val['name'] = $item['choices'][$key]['choice_text'];
                    return $val;
                })->toArray();
                break;
            case 'multiple_textbox':
                $question->items = collect($question->items)->map(function ($val, $key) use ($item) {
                    $val['name'] = $item['items'][$key]['item_text'];
                    $val['validate']['errorMsg'] = $item['items'][$key]['error_message'];
                    return $val;
                })->toArray();
                break;
            case 'constant_sum':
                $question->items = collect($question->items)->map(function ($val, $key) use ($item) {
                    $val['name'] = $item['items'][$key]['item_text'];
                    return $val;
                })->toArray();
                break;
            case 'rating_slider':
                $question->items = collect($question->items)->map(function ($val, $key) use ($item) {
                    $val['name'] = $item['items'][$key]['item_text'];
                    return $val;
                })->toArray();
                $question->values = collect($question->values)->map(function ($val, $key) use ($item) {
                    $val['name'] = $item['values'][$key]['value'];
                    return $val;
                })->toArray();
                break;
            case 'matrix_single_select':
            case 'matrix_multiple_select':
                // Preserve optional property when updating rows
                // The optional property should override setup.validate.required for individual rows
                $question->rows = collect($question->rows)->map(function ($val, $key) use ($item) {
                    $val['name'] = $item['rows'][$key]['row_text'];
                    // Preserve optional property if it exists in the original data
                    // This allows individual rows to be optional even when the question is required
                    if (isset($val['optional'])) {
                        // Keep the existing optional value
                    } elseif (isset($item['rows'][$key]['optional'])) {
                        $val['optional'] = $item['rows'][$key]['optional'];
                    }
                    return $val;
                })->toArray();
                $question->columns = collect($question->columns)->map(function ($val, $key) use ($item) {
                    $val['name'] = $item['columns'][$key]['column_text'];
                    return $val;
                })->toArray();
                break;
            case 'rating_ranking_rank':
            case 'rating_ranking_sort':
            case 'rating_star':
                $question->items = collect($question->items)->map(function ($val, $key) use ($item) {
                    $val['name'] = $item['items'][$key]['item_text'];
                    return $val;
                })->toArray();
                break;
        }
    }

    // Function to update the setup field of the questionnaire
    private function updateQuestionnaireSetup(array $setup, array $item): array
    {
        $setup['messages']['loading'] = $item['messages_loading'] ?? $setup['messages']['loading'];
        $setup['messages']['saving'] = $item['messages_saving'] ?? $setup['messages']['saving'];
        $setup['messages']['nextBtn'] = $item['messages_nextBtn'] ?? $setup['messages']['nextBtn'];
        $setup['messages']['prevBtn'] = $item['messages_prevBtn'] ?? $setup['messages']['prevBtn'];
        $setup['messages']['finalBtn'] = $item['messages_finalBtn'] ?? $setup['messages']['finalBtn'];
        $setup['modals']['invalidProject']['title'] = $item['modals_invalidProject_title'] ?? $setup['modals']['invalidProject']['title'];
        $setup['modals']['invalidProject']['content'] = $item['modals_invalidProject_content'] ?? $setup['modals']['invalidProject']['content'];
        $setup['modals']['surveyClosed']['title'] = $item['modals_surveyClosed_title'] ?? $setup['modals']['surveyClosed']['title'];
        $setup['modals']['surveyClosed']['content'] = $item['modals_surveyClosed_content'] ?? $setup['modals']['surveyClosed']['content'];
        $setup['modals']['noIdAssigned']['title'] = $item['modals_noIdAssigned_title'] ?? $setup['modals']['noIdAssigned']['title'];
        $setup['modals']['noIdAssigned']['content'] = $item['modals_noIdAssigned_content'] ?? $setup['modals']['noIdAssigned']['content'];
        $setup['modals']['connectionLost']['title'] = $item['modals_connectionLost_title'] ?? $setup['modals']['connectionLost']['title'];
        $setup['modals']['connectionLost']['content'] = $item['modals_connectionLost_content'] ?? $setup['modals']['connectionLost']['content'];
        $setup['redirects']['complete']['content'] = $item['redirects_complete_content'] ?? $setup['redirects']['complete']['content'];
        $setup['redirects']['screenout']['content'] = $item['redirects_screenout_content'] ?? $setup['redirects']['screenout']['content'];
        $setup['redirects']['qualityControl']['content'] = $item['redirects_qualityControl_content'] ?? $setup['redirects']['qualityControl']['content'];

        return $setup;
    }
}
