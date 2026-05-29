<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\Activity_Log;
use App\Services\DistinctQuestionGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;

class AiExecutionController extends Controller
{
    public function execute(Request $request, string $actionableId): JsonResponse
    {
        Log::info('[AI EXECUTION DEBUG] Legacy execute method called', [
            'actionable_id' => $actionableId,
            'message' => 'This method is deprecated. Use direct execution methods instead.'
        ]);
        
        return response()->json([
            'message' => 'This execution method is no longer supported. Please use the new confirmation flow.',
        ], 410);
    }

    public function executeCreateQuestion(Request $request, array $parameters, ?string $scope = null): JsonResponse
    {
        Log::info('[AI EXECUTION DEBUG] Starting executeCreateQuestion', [
            'scope' => $scope,
            'parameters' => $parameters,
            'request_questionnaire_code' => $request->input('questionnaire_code'),
            'parameters_questionnaire_code' => $parameters['questionnaire_code'] ?? 'NOT_FOUND'
        ]);
        
        try {
            $questionnaireCode = $request->input('questionnaire_code')
                ?: ($parameters['questionnaire_code'] ?? null);
                
            Log::info('[AI EXECUTION DEBUG] Questionnaire code resolution', [
                'from_request' => $request->input('questionnaire_code'),
                'from_parameters' => $parameters['questionnaire_code'] ?? null,
                'final_questionnaire_code' => $questionnaireCode
            ]);
            
            if (!$questionnaireCode) {
                Log::error('[AI EXECUTION DEBUG] No questionnaire code found', [
                    'request_input' => $request->input('questionnaire_code'),
                    'parameters' => $parameters
                ]);
                return response()->json([
                    'message' => 'Questionnaire context required for question creation.',
                ], 400);
            }

            $questionnaire = Questionnaire::whereCode($questionnaireCode)->first();
            
            Log::info('[AI EXECUTION DEBUG] Questionnaire lookup', [
                'questionnaire_code' => $questionnaireCode,
                'questionnaire_found' => !is_null($questionnaire),
                'questionnaire_id' => $questionnaire?->id,
                'questionnaire_name' => $questionnaire?->name,
                'parent_survey_id' => $questionnaire?->parent_survey?->id
            ]);
            
            if (!$questionnaire) {
                Log::error('[AI EXECUTION DEBUG] Questionnaire not found in database', [
                    'questionnaire_code' => $questionnaireCode
                ]);
                return response()->json([
                    'message' => 'Questionnaire not found.',
                ], 404);
            }

            Log::info('[AI EXECUTION DEBUG] Checking authorization', [
                'questionnaire_id' => $questionnaire->id,
                'parent_survey_id' => $questionnaire->parent_survey?->id,
                'user_id' => optional($request->user())->id
            ]);
            
            try {
                Gate::authorize('update', $questionnaire->parent_survey);
                Log::info('[AI EXECUTION DEBUG] Authorization passed');
            } catch (\Exception $e) {
                Log::error('[AI EXECUTION DEBUG] Authorization failed', [
                    'error' => $e->getMessage(),
                    'user_id' => optional($request->user())->id,
                    'questionnaire_id' => $questionnaire->id
                ]);
                throw $e;
            }

            $user = Auth::user();
            Log::info('[AI EXECUTION DEBUG] User authentication check', [
                'user_authenticated' => !is_null($user),
                'user_id' => $user?->id,
                'user_email' => $user?->email,
                'user_name' => $user?->name
            ]);
            
            if (!$user) {
                Log::error('[AI EXECUTION DEBUG] User not authenticated');
                return response()->json([
                    'message' => 'Authentication required.',
                ], 401);
            }

            // If questions_count > 1, loop: generate distinct question via AI → create → repeat
            $questionsCount = (int) ($request->input('questions_count') ?? 1);
            $questionsCount = max(1, min($questionsCount, 20));

            $topic = $this->deriveTopicForDistinctGeneration($request->input('user_message'), $parameters);
            $scopeForSchema = $this->normalizeScopeForSchema($scope);

            $createdQuestions = [];
            $existingQuestionTexts = [];
            $count = $questionnaire->questions()->count();
            $distinctGenerator = app(DistinctQuestionGeneratorService::class);

            for ($i = 0; $i < $questionsCount; $i++) {
                // First question: use preview_data; subsequent: generate distinct via AI
                if ($i === 0) {
                    $currentParameters = $parameters;
                } else {
                    $currentParameters = $distinctGenerator->generate($scopeForSchema, $topic, $existingQuestionTexts);
                    if (!$currentParameters) {
                        $currentParameters = $distinctGenerator->generate($scopeForSchema, $topic, $existingQuestionTexts);
                    }
                    if (!$currentParameters) {
                        Log::warning('[AI EXECUTION] Distinct question generation failed, skipping', [
                            'iteration' => $i + 1,
                            'scope' => $scopeForSchema,
                        ]);
                        continue;
                    }
                }

                $questionData = $this->mapAiParametersToQuestionData($currentParameters, $scope);
                $currentOrder = $count + 1;
                $question = $questionnaire->questions()->create([
                    'order' => $currentOrder,
                    'question_type_id' => $questionData['question_type_id'],
                    'question' => $questionData['question'],
                    'setup' => $questionData['setup'],
                    'choices' => $questionData['choices'] ?? [],
                    'rows' => $questionData['rows'] ?? [],
                    'columns' => $questionData['columns'] ?? [],
                    'items' => $questionData['items'] ?? [],
                    'item1' => $questionData['item1'] ?? ($questionData['question_type_id'] == 21 ? [] : null),
                    'item2' => $questionData['item2'] ?? ($questionData['question_type_id'] == 21 ? [] : null),
                    'values' => $questionData['values'] ?? [],
                    'questionnaire_id' => $questionnaire->id,
                    'updated_by' => $user->id,
                ]);
                $count++;
                $createdQuestions[] = $question;
                $existingQuestionTexts[] = strip_tags($questionData['question'] ?? '');
            }

            $createdCount = count($createdQuestions);
            if ($createdCount === 0) {
                return response()->json([
                    'message' => 'Could not create any questions. Distinct generation failed for all attempts.',
                ], 500);
            }

            Activity_Log::create([
                'user_id' => $user->id,
                'action' => "add",
                'details' => "User " . $user->name . " added {$createdCount} question(s) via Chatbot in " . $questionnaire->name . " questionnaire code: " . $questionnaire->code,
            ]);

            $lastQuestion = end($createdQuestions) ?: null;
            
            return response()->json([
                'message' => $createdCount > 1
                    ? "Created {$createdCount} distinct questions successfully!"
                    : 'Question created successfully!',
                'question_id' => $lastQuestion?->id,
                'route' => route('questions.index', ['questionnaire_code' => $questionnaireCode])
            ]);

        } catch (\Throwable $e) {
            Log::error('Question creation via AI failed', [
                'parameters' => $parameters,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Failed to create question. Please try again.',
            ], 500);
        }
    }

    // Execute survey creation (multiple questions)
    public function executeCreateSurvey(Request $request, array $surveyData, ?string $questionnaireCode = null): JsonResponse
    {
        Log::info('[AI EXECUTION DEBUG] Starting executeCreateSurvey', [
            'survey_data' => $surveyData,
            'questionnaire_code' => $questionnaireCode,
        ]);

        try {
            $questionnaireCode = $questionnaireCode ?? $request->input('questionnaire_code');
            
            if (!$questionnaireCode) {
                return response()->json([
                    'message' => 'Questionnaire context required for survey creation.',
                ], 400);
            }

            $questionnaire = Questionnaire::whereCode($questionnaireCode)->first();
            
            if (!$questionnaire) {
                return response()->json([
                    'message' => 'Questionnaire not found.',
                ], 404);
            }

            try {
                Gate::authorize('update', $questionnaire->parent_survey);
            } catch (\Exception $e) {
                Log::error('Authorization failed for survey creation', ['error' => $e->getMessage()]);
                throw $e;
            }

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'message' => 'Authentication required.',
                ], 401);
            }

            $questions = $surveyData['questions'] ?? [];
            if (empty($questions) || !is_array($questions)) {
                return response()->json([
                    'message' => 'No questions found in survey data.',
                ], 400);
            }

            $createdQuestions = [];
            $failedQuestions = [];
            $count = $questionnaire->questions()->count();

            foreach ($questions as $questionData) {
                try {
                    $questionType = $questionData['question_type'] ?? 'descriptive';
                    $titleAndQuestion = $questionData['title_and_question_text'] ?? [];
                    $questionText = $titleAndQuestion['question_text'] ?? '';
                    
                    $mappedData = $this->mapSurveyQuestionToQuestionData($questionData, $questionType);

                    // Skip matrix questions that still have no rows or columns after mapping
                    if ($questionType === 'matrix') {
                        $mappedRows = $mappedData['rows'] ?? [];
                        $mappedColumns = $mappedData['columns'] ?? [];
                        if (empty($mappedRows) || empty($mappedColumns)) {
                            $failedQuestions[] = $titleAndQuestion['title'] ?? $titleAndQuestion['name'] ?? $questionText ?: 'Matrix question';
                            continue;
                        }
                    }

                    $question = $questionnaire->questions()->create([
                        'order' => ++$count,
                        'question_type_id' => $mappedData['question_type_id'],
                        'question' => $questionText,
                        'setup' => $mappedData['setup'],
                        'choices' => $mappedData['choices'] ?? [],
                        'rows' => $mappedData['rows'] ?? [],
                        'columns' => $mappedData['columns'] ?? [],
                        'items' => $mappedData['items'] ?? [],
                        'item1' => $mappedData['item1'] ?? ($mappedData['question_type_id'] == 21 ? [] : null),
                        'item2' => $mappedData['item2'] ?? ($mappedData['question_type_id'] == 21 ? [] : null),
                        'values' => $mappedData['values'] ?? [],
                        'questionnaire_id' => $questionnaire->id,
                        'updated_by' => $user->id,
                    ]);

                    $createdQuestions[] = $question->id;
                    
                    Log::info('Question created in survey', [
                        'question_id' => $question->id,
                        'question_type' => $questionType,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Failed to create question in survey', [
                        'question_data' => $questionData,
                        'error' => $e->getMessage(),
                    ]);
                    $failedQuestions[] = $questionData['question_text'] ?? 'Unknown question';
                }
            }

            Activity_Log::create([
                'user_id' => $user->id,
                'action' => "add",
                'details' => "User " . $user->name . " created a complete survey with " . count($createdQuestions) . " questions via Chatbot in " . $questionnaire->name . " questionnaire code: " . $questionnaire->code,
            ]);

            $message = 'Survey created successfully! ';
            $message .= count($createdQuestions) . ' question(s) created.';
            
            if (!empty($failedQuestions)) {
                $message .= ' ' . count($failedQuestions) . ' question(s) failed to create.';
            }

            $lastQuestionId = !empty($createdQuestions) ? end($createdQuestions) : null;

            return response()->json([
                'message' => $message,
                'created_questions' => count($createdQuestions),
                'failed_questions' => count($failedQuestions),
                'question_id' => $lastQuestionId,
                'route' => route('questions.index', ['questionnaire_code' => $questionnaireCode])
            ]);
        } catch (\Throwable $e) {
            Log::error('Survey creation via AI failed', [
                'survey_data' => $surveyData,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Failed to create survey. Please try again.',
            ], 500);
        }
    }

    // Map survey question data to question data format
    private function mapSurveyQuestionToQuestionData(array $questionData, string $questionType): array
    {
        $questionTypeId = $this->determineQuestionTypeId([], $questionType);
        
        $titleAndQuestion = $questionData['title_and_question_text'] ?? [];
        if (isset($titleAndQuestion['question text']) && !isset($titleAndQuestion['question_text'])) {
            $titleAndQuestion['question_text'] = $titleAndQuestion['question text'];
            unset($titleAndQuestion['question text']);
        }
        if (empty($titleAndQuestion)) {
            $titleAndQuestion = [
                'name' => null,
                'question_text' => ''
            ];
        }
        
        if (empty($titleAndQuestion['name']) || strtolower(trim($titleAndQuestion['name'])) === 'untitled question') {
            $titleAndQuestion['name'] = $this->generateQuestionTitle($titleAndQuestion);
        }

        $setup = $this->buildProperSetup($questionData, $questionTypeId, $titleAndQuestion);
        
        $mappedData = [
            'question_type_id' => $questionTypeId,
            'question' => $titleAndQuestion['question_text'] ?? '',
            'setup' => $setup,
        ];

        $mappedData = array_merge($mappedData, $this->buildSurveyQuestionSpecificData($questionData, $questionTypeId, $questionType));

        return $mappedData;
    }

    /**
     * Build type-specific data for survey questions
     */
    private function buildSurveyQuestionSpecificData(array $questionData, int $questionTypeId, string $questionType): array
    {
        $data = [];

        switch ($questionTypeId) {
            case 2: // Text Selection
                if (isset($questionData['text_options']) && is_array($questionData['text_options'])) {
                    $data['choices'] = $this->formatTextOptions($questionData['text_options']);
                }
                break;
            case 3: // Image Selection
                if (isset($questionData['text_options']) && is_array($questionData['text_options'])) {
                    $data['choices'] = $this->formatImageOptions($questionData['text_options']);
                }
                break;
            case 8: // Slider
                if (isset($questionData['items']) && is_array($questionData['items'])) {
                    $data['items'] = $this->formatSliderItems($questionData['items']);
                }
                if (isset($questionData['choices']) && is_array($questionData['choices'])) {
                    $data['values'] = $this->formatSliderValues($questionData['choices']);
                }
                break;
            case 9: // Ranking
                if (isset($questionData['items']) && is_array($questionData['items'])) {
                    $data['items'] = $this->formatRankingItems($questionData['items']);
                }
                break;
            case 10: // Star Rating
                if (isset($questionData['items']) && is_array($questionData['items'])) {
                    $data['items'] = $this->formatStarRatingItems($questionData['items']);
                }
                break;
            case 15: // Button Rating
                if (isset($questionData['items']) && is_array($questionData['items'])) {
                    $data['items'] = $this->formatButtonRatingItems($questionData['items']);
                }
                if (isset($questionData['choices']) && is_array($questionData['choices'])) {
                    $data['values'] = $this->formatButtonRatingValues($questionData['choices']);
                }
                // When no items are provided (single "rate this" question), use one default row so we get one question with one row of scale buttons.
                if (empty($data['items']) && !empty($questionData['choices'])) {
                    $data['items'] = $this->formatButtonRatingItems([['text' => 'Rating', 'equivalent' => 'Rating']]);
                }
                if (isset($questionData['choices']) && is_array($questionData['choices']) && !empty($questionData['choices'])) {
                    $normalizedChoices = $this->normalizeSurveyButtonRatingChoices($questionData['choices']);
                    $data['values'] = $this->formatButtonRatingValues($normalizedChoices);
                }
                break;
            case 5: // Multiple Textbox
                if (isset($questionData['textboxes']) && is_array($questionData['textboxes']) && !empty($questionData['textboxes'])) {
                    $textboxes = array_map(function($textbox) {
                        $selectType = $textbox['select_type'] ?? [];
                        return [
                            'text' => $textbox['text'] ?? '',
                            'equivalent' => $textbox['equivalent'] ?? null,
                            'select_type' => [
                                'select_type_option' => $selectType['select_type_option'] ?? 'open text',
                                'minimum_character' => $selectType['minimum_character'] ?? 0,
                                'maximum_character' => $selectType['maximum_character'] ?? 255,
                            ],
                        ];
                    }, $questionData['textboxes']);
                    $data['items'] = $this->formatMultipleTextboxItems($textboxes);
                }
                elseif (isset($questionData['text_options']) && is_array($questionData['text_options'])) {
                    $textboxes = array_map(function($textOption) {
                        return [
                            'text' => $textOption['text'] ?? $textOption['value'] ?? '',
                            'equivalent' => $textOption['equivalent'] ?? null,
                            'select_type' => [
                                'select_type_option' => 'open text', // Default
                                'minimum_character' => $textOption['write_in']['minimum_character'] ?? 0,
                                'maximum_character' => $textOption['write_in']['maximum_character'] ?? 255,
                            ],
                        ];
                    }, $questionData['text_options']);
                    $data['items'] = $this->formatMultipleTextboxItems($textboxes);
                }
                elseif (isset($questionData['items']) && is_array($questionData['items']) && !empty($questionData['items'])) {
                    $formattedItems = [];
                    foreach ($questionData['items'] as $index => $item) {
                        $text = $item['text'] ?? $item['name'] ?? '';
                            $equivalent = $item['equivalent'] ?? null;
                            $name = $item['name'] ?? $text;
                            if (!empty($name) && !str_starts_with(trim($name), '<p>')) {
                                $name = '<p>' . $name . '</p>';
                            }
                            
                            $formattedItems[] = [
                                'name' => $name ?: '<p></p>',
                                'equivalent' => $equivalent,
                                'validate' => [
                                    'type' => 1,
                                    'min' => 0,
                                    'max' => 255,
                                    'errorMsg' => 'This question requires an answer',
                                    'dateFormat' => 'mm-dd-yyyy',
                                    'timeFormat' => '12-hour',
                                    'datetimeFormat' => 'dd-mmm-yyyy hh:mm',
                                    'currencyFormat' => 'USD',
                                ],
                                'CUUID' => 'C' . substr(md5(uniqid() . $index), 0, 7),
                                'index' => $item['index'] ?? ($index + 1),
                            ];
                        }
                        $data['items'] = $formattedItems;
                    }
                    break;
            case 11: // Matrix
                $matrixRowsSource = $questionData['rows'] ?? $questionData['items'] ?? [];
                if (is_array($matrixRowsSource) && !empty($matrixRowsSource)) {
                    $normalizedRows = array_map(function ($row) {
                        $name = $row['text'] ?? $row['name'] ?? '';
                        $equivalent = strip_tags($name);
                        
                        return [
                            'name' => $name,
                            'equivalent' => $equivalent,
                            'write_in' => $row['write_in'] ?? null,
                            'optional_row' => $row['optional_row'] ?? null,
                        ];
                    }, $matrixRowsSource);
                    $data['rows'] = $this->formatMatrixRows($normalizedRows);
                }

                $matrixColumnsSource = $questionData['columns'] ?? $questionData['choices'] ?? [];
                if (is_array($matrixColumnsSource) && !empty($matrixColumnsSource)) {
                    $normalizedColumns = array_map(function ($column) {
                        $width = $column['width'] ?? $column['style']['width'] ?? null;
                        $name = $column['text'] ?? $column['name'] ?? '';
                        $equivalent = strip_tags($name);
                        
                        return [
                            'name' => $name,
                            'equivalent' => $equivalent,
                            'width' => $width,
                            'random_choice' => $column['random_choice'] ?? null,
                        ];
                    }, $matrixColumnsSource);
                    $data['columns'] = $this->formatMatrixColumns($normalizedColumns);
                        
                }

                $data['selections'] = [
                    'apply_select_all' => (bool)($questionData['selections']['apply_select_all'] ?? false),
                    'apply_unselect_all' => (bool)($questionData['selections']['apply_unselect_all'] ?? false),
                ];
                $data['single_answer_per_column'] = (bool)($questionData['single_answer_per_column'] ?? false);
                $data['random_choices'] = (bool)($questionData['random_choices'] ?? false);
                break;
        }

        $fieldsToCopy = [
            'random_items',
            'random_values',
            'random_choices',
            'number_of_rating_stars',
            'mode',
            'rank_mode',
            'sort_mode',
            'item_fixed_height',
            'selections',
            'single_answer_per_column',
            'track_reaction_time',
            'hide_instruction',
            'header',
            'footer',
        ];

        foreach ($fieldsToCopy as $field) {
            if (!array_key_exists($field, $data) && array_key_exists($field, $questionData)) {
                $data[$field] = $questionData[$field];
            }
        }

        if ($questionTypeId === 10 && empty($data['number_of_rating_stars'])) {
            $data['number_of_rating_stars'] = count($questionData['items'] ?? []) ?: 5;
        }

        if ($questionTypeId === 9) {
            $data['mode'] = $data['mode'] ?? ($questionData['mode'] ?? 'rank');
            $data['rank_mode'] = $data['rank_mode'] ?? [
                'display_position' => $questionData['rank_mode']['display_position'] ?? 'right',
                'maximum_item_selection' => $questionData['rank_mode']['maximum_item_selection'] ?? 0,
                'minimum_item_selection' => $questionData['rank_mode']['minimum_item_selection'] ?? 3,
                'limit_error_message' => $questionData['rank_mode']['limit_error_message'] ?? 'Select at least {{count}} item(s)',
            ];
            $data['sort_mode'] = $data['sort_mode'] ?? [
                'number_of_columns' => $questionData['sort_mode']['number_of_columns'] ?? 2,
            ];
            $data['random_items'] = $data['random_items'] ?? (bool)($questionData['random_items'] ?? false);
            $data['item_fixed_height'] = $data['item_fixed_height'] ?? ($questionData['item_fixed_height'] ?? null);
        }

        if ($questionTypeId === 15) {
            $data['random_values'] = $data['random_values'] ?? (bool)($questionData['random_values'] ?? false);
            $data['track_reaction_time'] = $data['track_reaction_time'] ?? (bool)($questionData['track_reaction_time'] ?? false);
            $data['hide_instruction'] = $data['hide_instruction'] ?? (bool)($questionData['hide_instruction'] ?? false);
            $data['header'] = $data['header'] ?? ($questionData['header'] ?? null);
            $data['footer'] = $data['footer'] ?? ($questionData['footer'] ?? null);
        }

        return $data;
    }

    // Execute question update
    public function executeUpdateQuestion(Request $request, array $parameters, ?string $scope = null): JsonResponse
    {
        // Implementation for updating questions via AI
        return response()->json([
            'message' => 'Question update via AI not yet implemented.',
        ], 501);
    }

    // Execute question options update
    public function executeUpdateQuestionOptions(Request $request, array $parameters, ?string $scope = null): JsonResponse
    {
        Log::info('[AI EXECUTION DEBUG] Starting executeUpdateQuestionOptions', [
            'scope' => $scope,
            'parameters' => $parameters,
            'request_questionnaire_code' => $request->input('questionnaire_code'),
        ]);

        try {
            $questionnaireCode = $request->input('questionnaire_code')
                ?: ($parameters['questionnaire_code'] ?? null);

            if (!$questionnaireCode) {
                Log::error('[AI EXECUTION DEBUG] No questionnaire code found');
                return response()->json([
                    'message' => 'Questionnaire context required for question options update.',
                ], 400);
            }

            $questionnaire = Questionnaire::whereCode($questionnaireCode)->first();
            
            if (!$questionnaire) {
                Log::error('[AI EXECUTION DEBUG] Questionnaire not found', [
                    'questionnaire_code' => $questionnaireCode
                ]);
                return response()->json([
                    'message' => 'Questionnaire not found.',
                ], 404);
            }

            try {
                Gate::authorize('update', $questionnaire->parent_survey);
            } catch (\Exception $e) {
                Log::error('[AI EXECUTION DEBUG] Authorization failed', [
                    'error' => $e->getMessage(),
                    'user_id' => optional($request->user())->id,
                ]);
                throw $e;
            }

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'message' => 'Authentication required.',
                ], 401);
            }

            $questionId = $parameters['question_id'] ?? null;
            $questionOrder = $parameters['question_order'] ?? null;
            $questionNumber = $parameters['question_number'] ?? null;
            $optionType = $parameters['option_type'] ?? null;
            $optionValue = $parameters['option_value'] ?? true;
            $multiSelectType = $parameters['multi_select_type'] ?? null;

            if (!$optionType) {
                return response()->json([
                    'message' => 'Option type is required.',
                ], 400);
            }

            // Find question
            $question = null;
            if ($questionId) {
                $question = Question::where('id', $questionId)
                    ->where('questionnaire_id', $questionnaire->id)
                    ->first();
            } elseif ($questionOrder) {
                $question = Question::where('questionnaire_id', $questionnaire->id)
                    ->where('order', $questionOrder)
                    ->first();
            }

            if (!$question) {
                return response()->json([
                    'message' => $questionNumber ? "Question{$questionNumber} not found." : 'Question not found.',
                ], 404);
            }

            // Get current setup
            $setup = $question->setup ?? [];
            if (is_string($setup)) {
                $setup = json_decode($setup, true) ?? [];
            }
            if (!is_array($setup)) {
                $setup = [];
            }

            // Update setup based on option type
            switch ($optionType) {
                case 'required':
                    if (!isset($setup['validate'])) {
                        $setup['validate'] = [];
                    }
                    $setup['validate']['required'] = $optionValue ? 1 : 0;
                    break;

                case 'random_choices':
                    if ($optionValue) {
                        // When enabling random choices:
                        // - Root level randomChoices should be an array of choice IDs [1,2,3,4]
                        // - validate.randomChoices should be a boolean flag true
                        $choiceIds = [];
                        if ($question->choices && is_array($question->choices)) {
                            foreach ($question->choices as $index => $choice) {
                                // Use choice index (1-based) or choice.id if available
                                $choiceId = $choice['index'] ?? ($index + 1);
                                $choiceIds[] = (int)$choiceId;
                            }
                        }
                        $setup['randomChoices'] = $choiceIds; // Array of choice IDs
                        if (!isset($setup['validate'])) {
                            $setup['validate'] = [];
                        }
                        $setup['validate']['randomChoices'] = true; // Boolean flag
                    } else {
                        // When disabling, set root to false and remove validate flag
                        $setup['randomChoices'] = false;
                        if (isset($setup['validate']['randomChoices'])) {
                            unset($setup['validate']['randomChoices']);
                        }
                    }
                    break;

                case 'multiple_selection':
                    $setup['selectMultiple'] = $optionValue ? 1 : 0;
                    if ($optionValue && isset($setup['textSelection'])) {
                        if ($multiSelectType) {
                            $setup['textSelection']['multiSelect_type'] = $multiSelectType;
                            // Set default min/max for Range and Fixed types
                            if ($multiSelectType == 2 || $multiSelectType == 3) {
                                if (!isset($setup['textSelection']['min_selection'])) {
                                    $setup['textSelection']['min_selection'] = 1;
                                }
                                if (!isset($setup['textSelection']['max_selection'])) {
                                    $setup['textSelection']['max_selection'] = $multiSelectType == 3 ? 5 : 10;
                                }
                            }
                        } else {
                            $setup['textSelection']['multiSelect_type'] = 1; // Default to Unlimited
                        }
                    }
                    break;

                case 'dropdown':
                    $setup['isDropdown'] = $optionValue ? 1 : 0;
                    break;

                case 'select_all':
                    $setup['selectAll'] = $optionValue ? [] : null;
                    break;

                case 'unselect_all':
                    $setup['unSelectAll'] = $optionValue ? [] : null;
                    break;

                case 'scoring':
                    $setup['enablePoint'] = $optionValue ? 1 : 0;
                    break;

                default:
                    return response()->json([
                        'message' => "Unknown option type: {$optionType}",
                    ], 400);
            }

            // Save updated question
            $question->setup = $setup;
            $question->updated_by = $user->id;
            $question->save();

            Log::info('[AI EXECUTION DEBUG] Question options updated successfully', [
                'question_id' => $question->id,
                'question_number' => $questionNumber,
                'option_type' => $optionType,
                'option_value' => $optionValue,
            ]);

            $optionDisplayName = ucfirst(str_replace('_', ' ', $optionType));
            $valueDisplay = $optionValue ? 'enabled' : 'disabled';
            
            return response()->json([
                'message' => "Question {$questionNumber} updated: {$optionDisplayName} {$valueDisplay}.",
                'question_id' => $question->id,
                'question_number' => $questionNumber,
                'route' => route('questions.index', ['questionnaire_code' => $questionnaireCode])
            ]);
        } catch (\Throwable $e) {
            Log::error('[AI EXECUTION DEBUG] Question options update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'parameters' => $parameters
            ]);
            
            return response()->json([
                'message' => 'Failed to update question options. Please try again.',
            ], 500);
        }
    }

    // Execute question choice update (add/edit/remove)
    public function executeUpdateQuestionChoice(Request $request, array $parameters, ?string $scope = null): JsonResponse
    {
        Log::info('[AI EXECUTION DEBUG] Starting executeUpdateQuestionChoice', [
            'scope' => $scope,
            'parameters' => $parameters,
            'request_questionnaire_code' => $request->input('questionnaire_code'),
        ]);

        try {
            $questionnaireCode = $request->input('questionnaire_code')
                ?: ($parameters['questionnaire_code'] ?? null);

            if (!$questionnaireCode) {
                Log::error('[AI EXECUTION DEBUG] No questionnaire code found');
                return response()->json([
                    'message' => 'Questionnaire context required for choice update.',
                ], 400);
            }

            $questionnaire = Questionnaire::whereCode($questionnaireCode)->first();
            
            if (!$questionnaire) {
                Log::error('[AI EXECUTION DEBUG] Questionnaire not found', [
                    'questionnaire_code' => $questionnaireCode
                ]);
                return response()->json([
                    'message' => 'Questionnaire not found.',
                ], 404);
            }

            try {
                Gate::authorize('update', $questionnaire->parent_survey);
            } catch (\Exception $e) {
                Log::error('[AI EXECUTION DEBUG] Authorization failed', [
                    'error' => $e->getMessage(),
                    'user_id' => optional($request->user())->id,
                ]);
                throw $e;
            }

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'message' => 'Authentication required.',
                ], 401);
            }

            $questionId = $parameters['question_id'] ?? null;
            $questionOrder = $parameters['question_order'] ?? null;
            $questionNumber = $parameters['question_number'] ?? null;
            $action = $parameters['action'] ?? 'add';
            $choiceText = $parameters['choice_text'] ?? null;
            $oldChoiceText = $parameters['old_choice_text'] ?? null;

            if (!$choiceText) {
                return response()->json([
                    'message' => 'Choice text is required.',
                ], 400);
            }

            // Find question
            $question = null;
            if ($questionId) {
                $question = Question::where('id', $questionId)
                    ->where('questionnaire_id', $questionnaire->id)
                    ->first();
            } elseif ($questionOrder) {
                $question = Question::where('questionnaire_id', $questionnaire->id)
                    ->where('order', $questionOrder)
                    ->first();
            }

            if (!$question) {
                return response()->json([
                    'message' => $questionNumber ? "Question {$questionNumber} not found." : 'Question not found.',
                ], 404);
            }

            // Validate question type supports choices
            $choiceSupportedTypes = [2, 3, 16, 20]; // Multiple Choice, Image Selection, Segmentation, Best Worst Scale
            if (!in_array($question->question_type_id, $choiceSupportedTypes)) {
                return response()->json([
                    'message' => "This question type does not support choices.",
                ], 400);
            }

            // Get current choices
            $choices = $question->choices ?? [];
            if (!is_array($choices)) {
                $choices = [];
            }

            $maxIndex = 0;
            if (!empty($choices)) {
                foreach ($choices as $choice) {
                    if (isset($choice['index']) && $choice['index'] > $maxIndex) {
                        $maxIndex = $choice['index'];
                    }
                }
            }

            switch ($action) {
                case 'add':
                    // Add new choice
                    $newChoice = [
                        'name' => '<p>' . htmlspecialchars($choiceText, ENT_QUOTES, 'UTF-8') . '</p>',
                        'equivalent' => $choiceText,
                        'enable_wr' => 0,
                        'CUUID' => 'C' . substr(md5(uniqid()), 0, 7),
                        'index' => $maxIndex + 1,
                        'score' => $maxIndex + 1,
                        'points' => 0,
                        'checkboxScore' => [
                            'active' => 1,
                            'inactive' => 0
                        ]
                    ];
                    $choices[] = $newChoice;
                    $message = "Choice '{$choiceText}' added to Question {$questionNumber}.";
                    break;

                case 'edit':
                    // Edit existing choice
                    if (!$oldChoiceText) {
                        return response()->json([
                            'message' => 'Old choice text is required for editing.',
                        ], 400);
                    }
                    
                    $found = false;
                    foreach ($choices as $index => $choice) {
                        $choiceName = strip_tags($choice['name'] ?? $choice['equivalent'] ?? '');
                        if (stripos($choiceName, $oldChoiceText) !== false || stripos($oldChoiceText, $choiceName) !== false) {
                            $choices[$index]['name'] = '<p>' . htmlspecialchars($choiceText, ENT_QUOTES, 'UTF-8') . '</p>';
                            $choices[$index]['equivalent'] = $choiceText;
                            $found = true;
                            break;
                        }
                    }
                    
                    if (!$found) {
                        return response()->json([
                            'message' => "Choice '{$oldChoiceText}' not found in Question {$questionNumber}.",
                        ], 404);
                    }
                    $message = "Choice updated in Question {$questionNumber}: '{$oldChoiceText}' → '{$choiceText}'.";
                    break;

                case 'remove':
                    // Remove choice
                    $found = false;
                    foreach ($choices as $index => $choice) {
                        $choiceName = strip_tags($choice['name'] ?? $choice['equivalent'] ?? '');
                        if (stripos($choiceName, $choiceText) !== false || stripos($choiceText, $choiceName) !== false) {
                            array_splice($choices, $index, 1);
                            $found = true;
                            break;
                        }
                    }
                    
                    if (!$found) {
                        return response()->json([
                            'message' => "Choice '{$choiceText}' not found in Question {$questionNumber}.",
                        ], 404);
                    }
                    
                    // Reindex remaining choices
                    foreach ($choices as $index => $choice) {
                        $choices[$index]['index'] = $index + 1;
                        $choices[$index]['score'] = $index + 1;
                    }
                    
                    $message = "Choice '{$choiceText}' removed from Question {$questionNumber}.";
                    break;

                default:
                    return response()->json([
                        'message' => "Unknown action: {$action}",
                    ], 400);
            }

            // Save updated question
            $question->choices = $choices;
            $question->updated_by = $user->id;
            $question->save();

            Log::info('[AI EXECUTION DEBUG] Question choice updated successfully', [
                'question_id' => $question->id,
                'question_number' => $questionNumber,
                'action' => $action,
                'choice_text' => $choiceText,
            ]);
            
            return response()->json([
                'message' => $message,
                'question_id' => $question->id,
                'question_number' => $questionNumber,
                'route' => route('questions.index', ['questionnaire_code' => $questionnaireCode])
            ]);
        } catch (\Throwable $e) {
            Log::error('[AI EXECUTION DEBUG] Question choice update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'parameters' => $parameters
            ]);
            
            return response()->json([
                'message' => 'Failed to update question choice. Please try again.',
            ], 500);
        }
    }

    // Execute question deletion
    public function executeDeleteQuestion(Request $request, array $parameters, ?string $scope = null): JsonResponse
    {
        // Implementation for deleting questions via AI
        return response()->json([
            'message' => 'Question deletion via AI not yet implemented.',
        ], 501);
    }

    /** Derive topic for distinct question generation from user message or first question. */
    private function deriveTopicForDistinctGeneration(?string $userMessage, array $parameters): string
    {
        $msg = trim($userMessage ?? '');
        if (!empty($msg)) {
            // Try to extract topic from patterns like "create N questions about X" or "add questions about X"
            if (preg_match('/\b(?:about|on|regarding)\s+(.+?)(?:\s*\.|$)/i', $msg, $m)) {
                return trim($m[1]);
            }
            return $msg;
        }
        $titleAndQuestion = $parameters['title_and_question_text'] ?? [];
        $questionText = $titleAndQuestion['question_text'] ?? $titleAndQuestion['question text'] ?? '';
        if (!empty($questionText)) {
            return strip_tags($questionText);
        }
        return 'survey topic';
    }

    /** Normalize scope for schema lookup (e.g. best_worst_scale -> best-worst_scale). */
    private function normalizeScopeForSchema(?string $scope): string
    {
        $scope = $scope ?? 'descriptive';
        $scope = str_replace('-', '_', $scope);
        if ($scope === 'best_worst_scale' || $scope === 'best_worst_scale2') {
            return 'best-worst_scale';
        }
        return $scope;
    }

    // Map AI parameters to question data format
    private function mapAiParametersToQuestionData(array $parameters, ?string $scope = null): array
    {
        $titleAndQuestion = $parameters['title_and_question_text'] ?? [];
        if (isset($titleAndQuestion['question text']) && !isset($titleAndQuestion['question_text'])) {
            $titleAndQuestion['question_text'] = $titleAndQuestion['question text'];
            unset($titleAndQuestion['question text']);
        }
        $questionTypeId = $this->determineQuestionTypeId($parameters, $scope);
        $setup = $this->buildProperSetup($parameters, $questionTypeId, $titleAndQuestion);

        $questionData = [
            'question_type_id' => $questionTypeId,
            'question' => $titleAndQuestion['question_text'] ?? '',
            'setup' => $setup,
        ];

        $questionData = array_merge($questionData, $this->buildTypeSpecificData($parameters, $questionTypeId));

        return $questionData;
    }

    // Generate a meaningful title from question text if title is missing or generic
    private function generateQuestionTitle(array $titleAndQuestion): string
    {
        if (isset($titleAndQuestion['question text']) && !isset($titleAndQuestion['question_text'])) {
            $titleAndQuestion['question_text'] = $titleAndQuestion['question text'];
        }

        $title = $titleAndQuestion['name'] ?? null;
        $questionText = $titleAndQuestion['question_text'] ?? '';
        
        if ($title && trim($title) !== '' && strtolower(trim($title)) !== 'untitled question') {
            return trim($title);
        }
        
        if (empty($questionText)) {
            return 'Question';
        }
        
        $cleanText = strip_tags($questionText);
        $cleanText = preg_replace('/\s+/', ' ', trim($cleanText));
        $cleanText = preg_replace('/^(what|how|when|where|why|who|which|do|does|did|are|is|can|could|would|will)\s+/i', '', $cleanText);
        $cleanText = rtrim($cleanText, '?');
        
        $words = explode(' ', $cleanText);
        $words = array_filter($words, function($word) {
            return strlen(trim($word)) > 0;
        });
        
        $titleWords = [];
        $maxWords = 8;
        $maxLength = 60;
        
        foreach ($words as $word) {
            if (count($titleWords) >= $maxWords) {
                break;
            }
            
            if (preg_match('/[.!?;:]/', $word)) {
                $word = preg_replace('/[.!?;:].*$/', '', $word);
                if (!empty($word)) {
                    $titleWords[] = $word;
                }
                break;
            }
            
            $potentialTitle = implode(' ', array_merge($titleWords, [$word]));
            if (strlen($potentialTitle) > $maxLength && count($titleWords) >= 3) {
                break;
            }
            
            $titleWords[] = $word;
        }
        
        if (count($titleWords) < 3 && count($words) >= 3) {
            $titleWords = array_slice($words, 0, min(8, count($words)));
        }
        
        $generatedTitle = implode(' ', $titleWords);
        $generatedTitle = ucwords(strtolower($generatedTitle));
        
        if (empty($generatedTitle) || strlen($generatedTitle) < 3) {
            return 'Question';
        }
        
        return $generatedTitle;
    }

    // Build proper setup object based on question type
    private function buildProperSetup(array $parameters, int $questionTypeId, array $titleAndQuestion): array
    {
        $baseSetup = [
            'boxStyle' => [
                'size' => 'md',
                'max-width' => '70vmin',
                'max-width-tab' => '100%',
                'max-width-mobile' => '100%',
                'max-width_enabler' => false,
            ],
            'style' => [
                'max-width' => null,
                'width' => '100%',
                'height' => '200px',
            ],
            'layout' => 1,
            'layout_img' => [
                'src' => null,
                'width' => '40vmin',
                'height' => '40vmin',
                'image_dimension' => '50vmin',
                'image_dimension_tab' => '50vmin',
                'image_dimension_mobile' => '50vmin',
                'image_dimension_enabler' => false,
            ],
            'layout_border_enabler' => false,
            'btn_delay' => 0,
            'btn_autoNext' => 0,
            'btn_delay_enabled' => false,
            'btn_autoNext_enabled' => false,
            'advanceSkiplogic' => [
                'enter' => [],
                'leave' => [],
            ],
            'advanceSkiplogic2' => [
                'enter' => [],
                'leave' => [],
            ],
            'dataType' => [],
            'questionName' => null,
            'chartTitle' => $this->generateQuestionTitle($titleAndQuestion),
            'piping' => null,
        ];

        switch ($questionTypeId) {
            case 1: // Descriptive
                $baseSetup['layout'] = 1;
                break;
            case 2: // Multiple Choice (Text Selection)
                $baseSetup['validate'] = [
                    'required' => $parameters['required'] ?? 1,
                    'min' => 0,
                    'max' => 0,
                    'errorMsg' => 'This question requires an answer',
                ];
                $baseSetup['skiplogic'] = [];
                $baseSetup['customSkiplogic'] = [
                    'enter' => '',
                    'leave' => '',
                ];
                $baseSetup['mobile_layout'] = 1;
                $baseSetup['selectMultiple'] = !empty($parameters['select_multiple']) ? 1 : 0;
                $baseSetup['isDropdown'] = $parameters['dropdown'] ?? false;
                $baseSetup['textSelection'] = [
                    'layout_switch' => 0,
                    'layout_desktop' => 1,
                    'layout_mobile' => 1,
                    'layout_tab' => 1,
                    'multiSelect_type' => 1,
                    'min_selection' => $baseSetup['selectMultiple'] ? 0 : 1,
                    'max_selection' => $baseSetup['selectMultiple'] ? 0 : 1,
                    'grid' => $parameters['edit_columns'] ?? 1,
                ];
                $baseSetup['enablePoint'] = $parameters['enable_scoring'] ?? 0;
                $baseSetup['randomChoices'] = $parameters['random_choices'] ?? false;
                $baseSetup['selections'] = $parameters['selections'] ?? 1;
                $baseSetup['displayChoices'] = false;
                $baseSetup['unSelectAll'] = null;
                break;
            case 3: // Image Selection
                $baseSetup['validate'] = [
                    'required' => $parameters['required'] ?? 1,
                    'min' => 0,
                    'max' => 0,
                    'errorMsg' => 'This question requires an answer',
                ];
                $baseSetup['skiplogic'] = [];
                $baseSetup['customSkiplogic'] = [
                    'enter' => '',
                    'leave' => '',
                ];
                $baseSetup['mobile_layout'] = 1;
                $baseSetup['selectMultiple'] = !empty($parameters['select_multiple']) ? 1 : 0;
                $baseSetup['isDropdown'] = false;
                $baseSetup['textSelection'] = [
                    'layout_switch' => 0,
                    'layout_desktop' => 1,
                    'layout_mobile' => 1,
                    'layout_tab' => 1,
                    'multiSelect_type' => 1,
                    'min_selection' => $baseSetup['selectMultiple'] ? 0 : 1,
                    'max_selection' => $baseSetup['selectMultiple'] ? 0 : 1,
                    'grid' => $parameters['edit_columns'] ?? 1,
                ];
                $baseSetup['enablePoint'] = $parameters['enable_scoring'] ?? 0;
                $baseSetup['randomChoices'] = $parameters['random_choices'] ?? false;
                $baseSetup['selections'] = $parameters['selections'] ?? 1;
                $baseSetup['displayChoices'] = false;
                $baseSetup['unSelectAll'] = null;
                break;
            case 4: // Single Textbox
                $selectType = $parameters['select_type']['select_type_option'] ?? 'open text';
                $selectTypeMap = [
                    'open text' => 1,
                    'letters only' => 8,
                    'alphanumeric' => 9,
                    'whole number' => 2,
                    'decimal number' => 3,
                    'email address' => 4,
                    'date' => 5,
                    'time' => 6,
                    'date time' => 7,
                    'pricing' => 10,
                ];
                $validateType = $selectTypeMap[strtolower(trim($selectType))] ?? 1;
                
                $baseSetup['textBox'] = [
                    'layout_switch' => 0,
                    'layout_desktop' => 1,
                    'layout_mobile' => 1,
                    'layout_tab' => 1,
                    'textbox_dimension_switch' => 0,
                    'textbox_dimension' => '20vmin',
                    'textbox_dimension_tab' => '20vmin',
                    'textbox_dimension_mobile' => '20vmin',
                ];
                
                $validate = [
                    'required' => $parameters['required'] ?? 1,
                    'type' => $validateType,
                    'min' => $parameters['select_type']['minimum_character'] ?? '',
                    'max' => $parameters['select_type']['maximum_character'] ?? '',
                    'errorMsg' => 'This question requires an answer',
                ];
                
                if (in_array($validateType, [5, 6, 7])) {
                    $validate['dateFormat'] = $parameters['select_type']['date_format'] ?? 'mm-dd-yyyy';
                    $validate['timeFormat'] = $parameters['select_type']['time_format'] ?? '12-hour';
                    $validate['datetimeFormat'] = $parameters['select_type']['datetime_format'] ?? 'dd-mmm-yyyy hh:mm';
                }
                if ($validateType === 10) {
                    $validate['currencyFormat'] = $parameters['select_type']['currency_format'] ?? 'USD';
                }
                if ($validateType === 4) {
                    $validate['emailProvider'] = $parameters['select_type']['email_provider'] ?? null;
                }
                
                $baseSetup['validate'] = $validate;
                $baseSetup['appendString'] = $parameters['after_textbox'] ?? '';
                $baseSetup['prependString'] = $parameters['before_textbox'] ?? '';
                $baseSetup['skiplogic'] = [];
                $baseSetup['customSkiplogic'] = [
                    'enter' => '',
                    'leave' => '',
                ];
                $baseSetup['textBox_dimension_switch'] = 0;
                $baseSetup['textBox_dimension'] = '20vmin';
                $baseSetup['textBox_dimension_tab'] = '20vmin';
                $baseSetup['textBox_dimension_mobile'] = '20vmin';
                break;
            case 5: // Multiple Textbox
                $baseSetup['boxStyle']['max-width_enabler'] = true;
                $baseSetup['style']['max-width'] = '100%';
                
                $baseSetup['textBox'] = [
                    'layout_switch' => 0,
                    'layout_desktop' => 1,
                    'layout_mobile' => 1,
                    'layout_tab' => 1,
                    'textbox_dimension_switch' => 0,
                    'textbox_dimension' => '20vmin',
                    'textbox_dimension_tab' => '20vmin',
                    'textbox_dimension_mobile' => '20vmin',
                ];
                $baseSetup['validate'] = [
                    'required' => $parameters['required'] ?? 1,
                    'min' => 0,
                    'max' => 0,
                    'errorMsg' => 'This question requires an answer',
                    'type' => 1,
                    'dateFormat' => 'mm-dd-yyyy',
                    'timeFormat' => '12-hour',
                    'datetimeFormat' => 'dd-mmm-yyyy hh:mm',
                    'currencyFormat' => 'USD',
                ];
                $baseSetup['skiplogic'] = [];
                $baseSetup['customSkiplogic'] = [
                    'enter' => null,
                    'leave' => null,
                ];
                $baseSetup['layout_desktop'] = 1;
                $baseSetup['layout_tab'] = 1;
                $baseSetup['layout_mobile'] = 1;
                $baseSetup['detectGibberish'] = 0;
                $baseSetup['appendString'] = null;
                $baseSetup['prependString'] = null;
                $baseSetup['textBox_dimension_switch'] = 0;
                $baseSetup['textBox_dimension'] = '20vmin';
                $baseSetup['textBox_dimension_tab'] = '20vmin';
                $baseSetup['textBox_dimension_mobile'] = '20vmin';
                $baseSetup['multipleTextSelection'] = [
                    'layout_switch' => 0,
                    'grid' => null,
                    'grid_tab' => null,
                    'grid_mobile' => null,
                    '_grid_backup' => 1,
                    '_grid_tab_backup' => 3,
                    '_grid_mobile_backup' => 2,
                ];
                $baseSetup['textSelection'] = [
                    'layout_switch' => 0,
                    'grid' => null,
                    'grid_tab' => null,
                    'grid_mobile' => null,
                    'multiSelect_type' => 1,
                    'multiSelect_max' => null,
                    'multiSelect_min' => null,
                    'multiSelect_errorMsg_type2' => 'Please select between @min_select to @max_select items',
                    'multiSelect_errorMsg_type3' => 'You must select @max_select items',
                    '_grid_backup' => 1,
                    '_grid_tab_backup' => 1,
                    '_grid_mobile_backup' => 1,
                ];
                $baseSetup['searchPlaceholder'] = 'Search for';
                $baseSetup['selectedPlaceholder'] = 'option(s) selected';
                $baseSetup['selectMultiple'] = 0;
                $baseSetup['enablePoint'] = 0;
                $baseSetup['randomChoices'] = $parameters['random_choices'] ?? [];
                $baseSetup['unSelectAll'] = [];
                $baseSetup['selectAll'] = [];
                $baseSetup['isDropdown'] = 0;
                $baseSetup['placeholder'] = 'Select Choice';
                $baseSetup['grid'] = $parameters['edit_columns'] ?? 1;
                $baseSetup['grid_tab'] = $parameters['edit_columns'] ?? 1;
                $baseSetup['grid_mobile'] = $parameters['edit_columns'] ?? 1;
                break;
            case 8: // Slider
                $baseSetup['default'] = $parameters['set_default_slider_position'] ?? 3;
                $randomItemsArray = [];
                if (($parameters['random_items'] ?? false) === true && isset($parameters['items']) && is_array($parameters['items'])) {
                    foreach ($parameters['items'] as $index => $item) {
                        $randomItemsArray[] = $index + 1; 
                    }
                }
                $baseSetup['randomItems'] = $randomItemsArray;
                $baseSetup['validate'] = [
                    'required' => $parameters['required'] ?? 1,
                    'errorMsg' => 'This question requires an answer',
                ];
                $baseSetup['skiplogic'] = [];
                $baseSetup['customSkiplogic'] = [
                    'enter' => '',
                    'leave' => '',
                ];
                break;
            case 6: // Open-ended
                $minChar = $parameters['minimum_character'] ?? 1;
                $maxChar = $parameters['maximum_character'] ?? 200;
                
                $baseSetup['validate'] = [
                    'filter_domain' => null,
                    'required' => $parameters['required'] ?? 1,
                    'min' => $minChar,
                    'max' => $maxChar,
                    'errorMsg' => "Please input a value in {$minChar} - {$maxChar}",
                ];
                $baseSetup['detectGibberish'] = 0;
                $baseSetup['skiplogic'] = [];
                $baseSetup['customSkiplogic'] = [
                    'enter' => '',
                    'leave' => '',
                ];
                $baseSetup['textBox'] = [
                    'layout_switch' => 0,
                    'layout_desktop' => 1,
                    'layout_mobile' => 1,
                    'layout_tab' => 1,
                    'textbox_dimension_switch' => 0,
                    'textbox_dimension' => '20vmin',
                    'textbox_dimension_tab' => '20vmin',
                    'textbox_dimension_mobile' => '20vmin',
                ];
                $baseSetup['textBox_dimension_switch'] = 0;
                $baseSetup['textBox_dimension'] = '20vmin';
                $baseSetup['textBox_dimension_tab'] = '20vmin';
                $baseSetup['textBox_dimension_mobile'] = '20vmin';
                break;
            case 19: // Pairwise Comparison
                $labels = $parameters['labels'] ?? [];
                $labelA = $labels['statement_[A]_label'] ?? $labels['statementA'] ?? 'Statement A';
                $labelB = $labels['statement_[B]_label'] ?? $labels['statementB'] ?? 'Statement B';
                
                $baseSetup['labels'] = [
                    'statementA' => str_starts_with($labelA, '<p>') ? $labelA : "<p>{$labelA}</p>",
                    'statementB' => str_starts_with($labelB, '<p>') ? $labelB : "<p>{$labelB}</p>",
                ];
                
                $footer = $parameters['footer'] ?? null;
                if ($footer) {
                    $footer = str_replace('\\n', '', $footer);
                    if (!str_starts_with($footer, '<p>')) {
                        $footer = "<p>{$footer}</p>";
                    }
                } else {
                    $footer = '<p>Click <strong>Statement A</strong> or <strong>Statement B</strong> to choose your preference</p>';
                }
                $baseSetup['footer'] = $footer;
                
                $baseSetup['validate'] = [
                    'required' => $parameters['required'] ?? 1,
                    'errorMsg' => 'This question requires an answer',
                ];
                $baseSetup['skiplogic'] = [];
                $baseSetup['customSkiplogic'] = [
                    'enter' => null,
                    'leave' => null,
                ];
                break;
            case 7: // Pie Chart
                $slider = $parameters['slider'] ?? [];
                $fixedValue = $parameters['fixed_value'] ?? [];
                $displayTotal = $parameters['display_total'] ?? [];
                
                $baseSetup['boxStyle']['max-width_enabler'] = true;
                $baseSetup['default'] = isset($parameters['default_value_per_item']) && $parameters['default_value_per_item'] !== null ? $parameters['default_value_per_item'] : 3;
                $baseSetup['randomItems'] = [];
                
                $baseSetup['validate'] = [
                    'required' => $parameters['required'] ?? 1,
                    'errorMsg' => 'This question requires an answer',
                ];
                $baseSetup['skiplogic'] = [];
                $baseSetup['customSkiplogic'] = [
                    'enter' => null,
                    'leave' => null,
                ];
                $baseSetup['fontStyle'] = [
                    'items' => '2vmin',
                    'values' => '2vmin',
                ];
                
                $baseSetup['selectMultiple'] = 1;
                $randomChoices = $parameters['random_choices'] ?? [];
                $baseSetup['randomChoices'] = is_array($randomChoices) ? $randomChoices : [];
                $baseSetup['enableDecimal'] = $parameters['allow_decimal'] ?? false ? 1 : 0;
                $baseSetup['prefix'] = $parameters['prefix'] ?? null;
                $baseSetup['suffix'] = $parameters['suffix'] ?? '%';
                $baseSetup['enableChart'] = 1;
                $baseSetup['enableSlider'] = 1;
                $baseSetup['chart'] = [
                    'enable' => 1,
                    'noDataLabel' => null,
                ];
                $baseSetup['range'] = [
                    'min' => $slider['minimum_range'] ?? 0,
                    'max' => $slider['maximum_range'] ?? 100,
                    'origin' => 0,
                ];
                
                $totalLabelText = $displayTotal['label'] ?? 'Total';
                if (!str_starts_with($totalLabelText, '<p>')) {
                    $totalLabelText = '<p>' . $totalLabelText . '</p>';
                }
                $baseSetup['totalLabel'] = [
                    'enable' => ($displayTotal['display_total_enabled'] ?? true) ? 1 : 0,
                    'text' => $totalLabelText,
                ];
                
                $baseSetup['fixedValue'] = ($fixedValue['fixed_value_enabled'] ?? true) ? 1 : 0;
                
                $validate2ErrorMsg = $parameters['error_message_minimum'] ?? 'Each value must be greater than @min';
                if (!str_starts_with($validate2ErrorMsg, '<p>')) {
                    $validate2ErrorMsg = '<p>' . $validate2ErrorMsg . '</p>';
                }
                $baseSetup['validate2'] = [
                    'minValue' => $parameters['minimum_value_per_item'] ?? 0,
                    'errorMsg' => $validate2ErrorMsg,
                ];
                
                $validate3ErrorMsg = $fixedValue['error_message_maximum'] ?? 'Total must be @total%';
                $baseSetup['validate3'] = [
                    'total' => $fixedValue['total_value'] ?? 100,
                    'errorMsg' => $validate3ErrorMsg,
                ];
                
                break;
            case 21: // Bar Chart
                $slider = $parameters['slider'] ?? [];
                $fixedValue = $parameters['fixed_value'] ?? [];
                $displayTotal = $parameters['display_total'] ?? [];                
                $baseSetup['boxStyle']['max-width_enabler'] = false;
                $baseSetup['selectMultiple'] = 1;
                $randomChoices = $parameters['random_choices'] ?? [];
                $baseSetup['randomChoices'] = is_array($randomChoices) ? $randomChoices : [];
                $baseSetup['enableDecimal'] = $parameters['allow_decimal'] ?? false ? 1 : 0;
                $baseSetup['prefix'] = $parameters['prefix'] ?? null;
                $baseSetup['suffix'] = $parameters['suffix'] ?? '%';
                $baseSetup['enableChart'] = 1;
                $baseSetup['enableSlider'] = 1;          
                $baseSetup['barchart'] = [
                    'x' => 'x axis',
                    'y' => 'y axis',
                ];               
                $baseSetup['chart'] = [
                    'enable' => 1,
                    'noDataLabel' => null,
                ];
                $baseSetup['range'] = [
                    'min' => $slider['minimum_range'] ?? 0,
                    'max' => $slider['maximum_range'] ?? 100,
                    'origin' => 0,
                ];               
                $totalLabelText = $displayTotal['label'] ?? 'Total';
                if (!str_starts_with($totalLabelText, '<p>')) {
                    $totalLabelText = '<p>' . $totalLabelText . '</p>';
                }
                $baseSetup['totalLabel'] = [
                    'enable' => ($displayTotal['display_total_enabled'] ?? true) ? 1 : 0,
                    'text' => $totalLabelText,
                ];
                
                $baseSetup['fixedValue'] = ($fixedValue['fixed_value_enabled'] ?? true) ? 1 : 0;
                
                $baseSetup['validate'] = [
                    'required' => $parameters['required'] ?? 1,
                    'errorMsg' => 'This question requires an answer',
                ];
                
                $validate2ErrorMsg = $parameters['error_message_minimum'] ?? 'Each value must be greater than @min';
                if (!str_starts_with($validate2ErrorMsg, '<p>')) {
                    $validate2ErrorMsg = '<p>' . $validate2ErrorMsg . '</p>';
                }
                $baseSetup['validate2'] = [
                    'minValue' => $parameters['minimum_value_per_item'] ?? 0,
                    'errorMsg' => $validate2ErrorMsg,
                ];
                
                $validate3ErrorMsg = $fixedValue['error_message_maximum'] ?? 'Total must be @total%';
                if (!str_starts_with($validate3ErrorMsg, '<p>')) {
                    $validate3ErrorMsg = '<p>' . $validate3ErrorMsg . '</p>';
                }
                $baseSetup['validate3'] = [
                    'total' => $fixedValue['total_value'] ?? 100,
                    'errorMsg' => $validate3ErrorMsg,
                ];
                
                $baseSetup['skiplogic'] = [];
                $baseSetup['customSkiplogic'] = [
                    'enter' => null,
                    'leave' => null,
                ];
                
                break;
            case 9: // Ranking
                $rankMode = $parameters['rank_mode'] ?? [];
                $sortMode = $parameters['sort_mode'] ?? [];
                $mode = $parameters['mode'] ?? 'sort';
                
                $baseSetup['boxStyle']['max-width_enabler'] = true;
                $baseSetup['default'] = 3;
                $baseSetup['randomItems'] = [];
                
                $baseSetup['validate'] = [
                    'required' => $parameters['required'] ?? 1,
                    'errorMsg' => 'This question requires an answer',
                ];
                $baseSetup['skiplogic'] = [];
                $baseSetup['customSkiplogic'] = [
                    'enter' => null,
                    'leave' => null,
                ];
                $baseSetup['fontStyle'] = [
                    'items' => '2vmin',
                    'values' => '2vmin',
                ];
                
                $randomItemsArray = [];
                if (($parameters['random_items'] ?? false) === true && isset($parameters['items']) && is_array($parameters['items'])) {
                    foreach ($parameters['items'] as $index => $item) {
                        $randomItemsArray[] = $index + 1; 
                    }
                }
                $baseSetup['randomChoices'] = $randomItemsArray;
                $baseSetup['mode'] = ($mode === 'rank') ? 0 : 1;
                
                $displayPosition = $rankMode['display_position'] ?? 'right';
                $positionMap = [
                    'right' => 1,
                    'left' => 2,
                    'bottom' => 3,
                    'top' => 4,
                ];
                $baseSetup['position'] = $positionMap[$displayPosition] ?? 1;
                $baseSetup['limit'] = $rankMode['maximum_item_selection'] ?? 0;
                $baseSetup['numberOfColumns'] = $sortMode['number_of_columns'] ?? 2;
                $baseSetup['numberOfColumns_tab'] = 1;
                $baseSetup['numberOfColumns_mobile'] = 1;
                
                $minValue = $rankMode['minimum_item_selection'] ?? 3;
                $baseSetup['validate2'] = [
                    'minValue' => $minValue,
                    'errorMsg' => $rankMode['limit_error_message'] ?? 'Select at least {{count}} item(s)',
                ];
                
                $baseSetup['fixedHeight'] = $parameters['item_fixed_height'] ?? '50vmin';
                $baseSetup['itemDimension'] = [
                    'item_dimension_switch' => 0,
                    'item_dimension' => '20vmin',
                    'item_dimension_tab' => '20vmin',
                    'item_dimension_mobile' => '20vmin',
                ];
                
                break;
            case 10: // Star Rating
                $baseSetup['boxStyle']['max-width_enabler'] = true;
                $baseSetup['default'] = 3;
                $baseSetup['randomItems'] = [];
                
                $baseSetup['validate'] = [
                    'required' => $parameters['required'] ?? 1,
                    'errorMsg' => 'This question requires an answer',
                ];
                $baseSetup['skiplogic'] = [];
                $baseSetup['customSkiplogic'] = [
                    'enter' => null,
                    'leave' => null,
                ];
                $baseSetup['fontStyle'] = [
                    'items' => '2vmin',
                    'values' => '2vmin',
                ];
                
                $baseSetup['size'] = $parameters['number_of_rating_stars'] ?? 5;
                $baseSetup['design'] = [
                    'selected' => null,
                    'notSelected' => null,
                ];
                
                break;
            case 11: // Matrix
                $baseSetup['boxStyle'] = [
                    'size' => 'md',
                    'max-width' => '70%',
                    'max-width-tab' => '100%',
                    'max-width-mobile' => '100%',
                    'max-width_enabler' => true,
                ];
                $baseSetup['style'] = [
                    'max-width' => null,
                    'width' => '100%',
                    'height' => '200px',
                ];
                $baseSetup['layout'] = 1;
                $baseSetup['layout_img'] = [
                    'src' => null,
                    'width' => '40vmin',
                    'height' => '40vmin',
                    'image_dimension' => '50vmin',
                    'image_dimension_tab' => '50vmin',
                    'image_dimension_mobile' => '50vmin',
                    'image_dimension_enabler' => false,
                ];
                $baseSetup['layout_border_enabler'] = false;
                $baseSetup['btn_delay'] = 0;
                $baseSetup['btn_autoNext'] = 0;
                $baseSetup['btn_delay_enabled'] = false;
                $baseSetup['btn_autoNext_enabled'] = false;
                $baseSetup['advanceSkiplogic'] = ['enter' => [], 'leave' => []];
                $baseSetup['advanceSkiplogic2'] = ['enter' => [], 'leave' => []];
                $baseSetup['dataType'] = [];
                $baseSetup['questionName'] = null;
                $baseSetup['displayByRow'] = 0;
                $baseSetup['selectMultiple'] = 0;
                $baseSetup['random'] = 0;
                $baseSetup['optional'] = 0;
                $baseSetup['randomRows'] = [];
                $baseSetup['unSelectAll'] = [];
                $baseSetup['selectAll'] = [];
                $baseSetup['validate'] = [
                    'required' => $parameters['required'] ?? 1,
                    'min' => 1,
                    'max' => 3,
                    'errorMsg' => 'This question requires an answer',
                ];
                $baseSetup['writeinType'] = 1;
                $baseSetup['writeinMin'] = 0;
                $baseSetup['writeinMax'] = 1;
                $baseSetup['writeinRow'] = 0;
                $baseSetup['writeinRowChoice'] = 'Others (Please specify)';
                $baseSetup['skiplogic'] = [];
                $baseSetup['customSkiplogic'] = [
                    'enter' => null,
                    'leave' => null,
                ];
                $baseSetup['displayByColumn'] = 0;
                $baseSetup['piping'] = null;
                $baseSetup['edit_layout_width'] = $parameters['edit_layout_width'] ?? [
                    'desktop' => ['desktop width' => 100, 'units' => 'percentage'],
                    'tablet' => ['tablet width' => 100, 'units' => 'percentage'],
                    'mobile' => ['mobile width' => 100, 'units' => 'percentage']
                ];
                break;
            case 15: // Button Rating
                $baseSetup['boxStyle']['max-width_enabler'] = true;
                $baseSetup['default'] = 3;
                $baseSetup['randomItems'] = [];
                
                $baseSetup['validate'] = [
                    'required' => $parameters['required'] ?? 1,
                    'errorMsg' => 'This question requires an answer',
                ];
                $baseSetup['skiplogic'] = [];
                $baseSetup['customSkiplogic'] = [
                    'enter' => null,
                    'leave' => null,
                ];
                $baseSetup['fontStyle'] = [
                    'items' => '2vmin',
                    'values' => '2vmin',
                ];
                
                $baseSetup['header'] = $parameters['header'] ?? null;
                $baseSetup['footer'] = $parameters['footer'] ?? null;
                $baseSetup['randomValues'] = [];
                $baseSetup['hideQuestionFrame'] = $parameters['hide_instruction'] ?? false;
                $baseSetup['itemsLayout'] = 1;
                $baseSetup['valuesLayout'] = 1;
                $baseSetup['passRTToURL'] = $parameters['track_reaction_time'] ?? false ? 1 : 0;
                $baseSetup['analysis'] = [];
                
                break;
            case 16: // Segmentation
                $labels = $parameters['labels'] ?? [];
                
                $baseSetup['boxStyle']['max-width_enabler'] = true;
                $baseSetup['boxStyle']['max-width'] = '100%';
                $baseSetup['boxStyle']['max-width-tab'] = '100%';
                $baseSetup['boxStyle']['max-width-mobile'] = '100%';
                $baseSetup['default'] = 3;
                $baseSetup['randomItems'] = [];
                
                $baseSetup['validate'] = [
                    'required' => $parameters['required'] ?? 1,
                    'errorMsg' => 'This question requires an answer',
                ];
                $baseSetup['skiplogic'] = [];
                $baseSetup['customSkiplogic'] = [
                    'enter' => null,
                    'leave' => null,
                ];
                $baseSetup['fontStyle'] = [
                    'items' => '2vmin',
                    'values' => '2vmin',
                ];
                
                $labelA = $labels['statement_A'] ?? $labels['statementA'] ?? 'Statement A';
                $labelB = $labels['statement_B'] ?? $labels['statementB'] ?? 'Statement B';
                
                if (!str_starts_with($labelA, '<p>')) {
                    $labelA = '<p>' . $labelA . '</p>';
                }
                if (!str_starts_with($labelB, '<p>')) {
                    $labelB = '<p>' . $labelB . '</p>';
                }
                
                $baseSetup['labels'] = [
                    'statementA' => $labelA,
                    'statementB' => $labelB,
                ];
                
                break;
            case 20: // Best-Worst Scale
                $labels = $parameters['labels'] ?? [];
                
                $baseSetup['boxStyle']['max-width_enabler'] = false;
                $baseSetup['validate'] = [
                    'required' => $parameters['required'] ?? 1,
                    'errorMsg' => 'This question requires an answer',
                ];
                $baseSetup['skiplogic'] = [];
                $baseSetup['customSkiplogic'] = [
                    'enter' => null,
                    'leave' => null,
                ];
                
                $labelA = $labels['Label 1 (Best)']['label'] ?? 'Best';
                $labelAColor = $labels['Label 1 (Best)']['colour'] ?? '#4caf50';
                $labelB = $labels['Label 2 (Worst)']['label'] ?? 'Worst';
                $labelBColor = $labels['Label 2 (Worst)']['colour'] ?? '#f44336';
                
                if (!str_starts_with($labelA, '<p>')) {
                    $labelA = '<p>' . $labelA . '</p>';
                }
                if (!str_starts_with($labelB, '<p>')) {
                    $labelB = '<p>' . $labelB . '</p>';
                }
                
                $statementsCount = isset($parameters['statements']) && is_array($parameters['statements']) ? count($parameters['statements']) : 6;
                
                $baseSetup['bestWostScale'] = [
                    'labelA' => $labelA,
                    'labelAColor' => $labelAColor,
                    'labelB' => $labelB,
                    'labelBColor' => $labelBColor,
                    'sets' => 1,
                    'repetitionsPerStatement' => $parameters['repetitions'] ?? 1,
                    'itemsPerSet' => $statementsCount,
                    'sets_enabler' => true,
                ];
                
                break;
        }

        return $baseSetup;
    }

    // Determine question type ID from parameters
    private function determineQuestionTypeId(array $parameters, ?string $scope = null): int
    {
        $typeMapping = [
            'descriptive' => 1,
            'text_selection' => 2,
            'image_selection' => 3,
            'single_textbox' => 4,
            'multiple_textbox' => 5,
            'open_ended' => 6,
            'constant_sum' => 7,
            'pie_chart' => 7,
            'slider' => 8,
            'ranking' => 9,
            'star_rating' => 10,
            'matrix' => 11,
            'matrix_multi' => 12,
            'memory_test_desc' => 13,
            'memory_test_comparison' => 14,
            'button_rating' => 15,
            'segmentation' => 16,
            'video_player' => 18,
            'pairwise_comparison' => 19,
            'best_worst_scale' => 20,
            'bar_chart' => 21,
            'best_worst_scale2' => 22,
        ];
        
        $questionScope = $scope ?? 'descriptive';
        $questionScope = str_replace('-', '_', $questionScope);
        
        return $typeMapping[$questionScope] ?? 1;
    }

    // Build type-specific setup data
    private function buildTypeSpecificSetup(array $parameters, int $questionTypeId): array
    {
        $setup = [];

        switch ($questionTypeId) {
            case 1: // Descriptive
                if (isset($parameters['change_layout'])) {
                    $setup['change_layout'] = $parameters['change_layout'];
                }
                if (isset($parameters['button_delay'])) {
                    $setup['button_delay'] = $parameters['button_delay'];
                }
                break;
            case 2: // Multiple Choice
                if (isset($parameters['text_selection'])) {
                    $setup['text_selection'] = $parameters['text_selection'];
                }
                break;
        }

        return $setup;
    }

    // Build type-specific data (choices, rows, etc.)
    private function buildTypeSpecificData(array $parameters, int $questionTypeId): array
    {
        $data = [];

        switch ($questionTypeId) {
            case 2: // Multiple Choice (Text Selection)
                if (isset($parameters['text_options'])) {
                    $data['choices'] = $this->formatTextOptions($parameters['text_options']);
                } elseif (isset($parameters['choices'])) {
                    $data['choices'] = $this->formatChoices($parameters['choices']);
                }
                break;
            case 3: // Image Selection
                if (isset($parameters['image_options'])) {
                    $data['choices'] = $this->formatImageOptions($parameters['image_options']);
                } elseif (isset($parameters['choices'])) {
                    $data['choices'] = $this->formatChoices($parameters['choices']);
                }
                break;
            case 8: // Slider Question
                if (isset($parameters['items'])) {
                    $data['items'] = $this->formatSliderItems($parameters['items']);
                }
                if (isset($parameters['choices'])) {
                    $data['values'] = $this->formatSliderValues($parameters['choices']);
                }
                break;
            case 11: // Matrix Choice
                if (isset($parameters['columns'])) {
                    $data['columns'] = $this->formatMatrixColumns($parameters['columns']);
                }
                if (isset($parameters['rows'])) {
                    $data['rows'] = $this->formatMatrixRows($parameters['rows']);
                }
                break;
            case 16: // Segmentation
                if (isset($parameters['statements'])) {
                    $data['items'] = $this->formatSegmentationItems($parameters['statements']);
                }
                if (isset($parameters['choices'])) {
                    $data['values'] = $this->formatSegmentationValues($parameters['choices']);
                }
                break;
            case 5: // Multiple Textbox
                if (isset($parameters['textboxes'])) {
                    $data['items'] = $this->formatMultipleTextboxItems($parameters['textboxes']);
                }
                break;
            case 19: // Pairwise Comparison
                if (isset($parameters['items'])) {
                    $data['items'] = $this->formatPairwiseComparisonItems($parameters['items']);
                }
                break;
            case 7: // Pie Chart
                if (isset($parameters['chart_items'])) {
                    $data['items'] = $this->formatChartItems($parameters['chart_items'], $parameters);
                }
                break;
            case 21: // Bar Chart
                if (isset($parameters['chart_items'])) {
                    $data['items'] = $this->formatBarChartItems($parameters['chart_items']);
                }
                $data['item1'] = [];
                $data['item2'] = [];
                break;
            case 9: // Ranking
                if (isset($parameters['items'])) {
                    $data['items'] = $this->formatRankingItems($parameters['items']);
                }
                break;
            case 10: // Star Rating
                if (isset($parameters['items'])) {
                    $data['items'] = $this->formatStarRatingItems($parameters['items']);
                }
                break;
            case 15: // Button Rating
                if (isset($parameters['items'])) {
                    $data['items'] = $this->formatButtonRatingItems($parameters['items']);
                }
                if (isset($parameters['choices'])) {
                    $data['values'] = $this->formatButtonRatingValues($parameters['choices']);
                }
                break;
            case 20: // Best-Worst Scale
                if (isset($parameters['statements'])) {
                    $data['choices'] = $this->formatBestWorstScaleStatements($parameters['statements']);
                }
                break;
        }

        return $data;
    }

    // Format text options for multiple choice questions
    private function formatTextOptions(array $textOptions): array
    {
        $formattedChoices = [];
        foreach ($textOptions as $index => $option) {
            $formattedChoices[] = [
                'name' => '<p>' . ($option['text'] ?? $option['name'] ?? '') . '</p>',
                'equivalent' => $option['text'] ?? $option['name'] ?? '',
                'enable_wr' => 0,
                'CUUID' => 'C' . substr(md5(uniqid()), 0, 7),
                'index' => $index + 1,
                'score' => $index + 1,
                'points' => $option['points'] ?? 0,
                'checkboxScore' => [
                    'active' => 1,
                    'inactive' => 0
                ]
            ];
        }
        return $formattedChoices;
    }

    // Format image options for image selection questions
    private function formatImageOptions(array $imageOptions): array
    {
        $formattedChoices = [];
        foreach ($imageOptions as $index => $option) {
            $formattedChoices[] = [
                'name' => '<p>' . ($option['text'] ?? $option['name'] ?? '') . '</p>',
                'equivalent' => $option['text'] ?? $option['name'] ?? '',
                'enable_wr' => 0,
                'CUUID' => 'C' . substr(md5(uniqid()), 0, 7),
                'index' => $index + 1,
                'score' => $index + 1,
                'points' => $option['points'] ?? 0,
                'checkboxScore' => [
                    'active' => 1,
                    'inactive' => 0
                ]
            ];
        }
        return $formattedChoices;
    }

    // Format choices for multiple choice questions
    private function formatChoices(array $choices): array
    {
        $formattedChoices = [];
        foreach ($choices as $index => $choice) {
            $formattedChoices[] = [
                'name' => '<p>' . ($choice['name'] ?? '') . '</p>',
                'equivalent' => $choice['name'] ?? '',
                'enable_wr' => 0,
                'CUUID' => 'C' . substr(md5(uniqid()), 0, 7),
                'index' => $index + 1,
                'score' => $index + 1,
                'points' => 0,
                'checkboxScore' => [
                    'active' => 1,
                    'inactive' => 0
                ]
            ];
        }
        return $formattedChoices;
    }

    // Format matrix columns
    private function formatMatrixColumns(array $columns): array
    {
        $formattedColumns = [];
        foreach ($columns as $index => $column) {
            $columnIndex = $index + 1;
            $width = $column['width'] ?? '20%';
            
            $formattedColumns[] = [
                'index' => $columnIndex,
                'name' => $column['name'] ?? $column['text'] ?? 'no col receiveed',
                'equivalent' => $column['name'] ?? $column['text'] ?? 'no col receiveed',
                'value' => $columnIndex,
                'style' => [
                    'width' => $width
                ],
                'score' => $columnIndex,
                'random_choice' => $column['random_choice'] ?? false,
            ];
        }
        return $formattedColumns;
    }

    private function formatMatrixRows(array $rows): array
    {
        $formattedRows = [];
        foreach ($rows as $index => $row) {
            $rowIndex = $index + 1;
            
            $writeIn = $row['write_in'] ?? [
                'enabled' => false,
                'write_in_type' => 'character length',
                'minimum_character' => 1,
                'maximum_character' => 10,
                'placeholder' => null,
                'set_max_width' => null
            ];
            
            if (isset($row['write_in']) && is_array($row['write_in'])) {
                $writeIn = array_merge([
                    'enabled' => false,
                    'write_in_type' => 'character length',
                    'minimum_character' => 1,
                    'maximum_character' => 10,
                    'placeholder' => null,
                    'set_max_width' => null
                ], $row['write_in']);
            }
            
            $formattedRows[] = [
                'index' => $rowIndex,
                'name' => $row['text'] ?? $row['name'] ?? 'no row receiveed here',
                'equivalent' => $row['text'] ?? $row['name'] ?? 'no row receiveed',
                'value' => $rowIndex,
                'optional' => false,
                'enable_wr' => false,
                'write_in' => $writeIn,
                'optional_row' => $row['optional_row'] ?? false,
            ];
        }
        return $formattedRows;
    }

    // Format segmentation items (statement pairs)
    private function formatSegmentationItems(array $statements): array
    {
        $formattedItems = [];
        foreach ($statements as $index => $statement) {
            $statementA = $statement['statement_A'] ?? '';
            $statementB = $statement['statement_B'] ?? '';
            
            if (!str_starts_with($statementA, '<p>')) {
                $statementA = '<p>' . $statementA . '</p>';
            }
            if (!str_starts_with($statementB, '<p>')) {
                $statementB = '<p>' . $statementB . '</p>';
            }
            
            $formattedItems[] = [
                'statementA' => $statementA,
                'statementB' => $statementB,
                'equivalentA' => null,
                'equivalentB' => null,
                'index' => $index + 1,
            ];
        }
        return $formattedItems;
    }

    // Format segmentation values (choices)
    private function formatSegmentationValues(array $choices): array
    {
        $formattedValues = [];
        foreach ($choices as $index => $choice) {
            $text = $choice['value'] ?? $choice['text'] ?? '';
            $color = $choice['colour'] ?? $choice['color'] ?? null;
            
            $name = $text;
            if (!str_starts_with($name, '<p>')) {
                $name = '<p>' . $name . '</p>';
            }
            
            $formattedValues[] = [
                'name' => $name,
                'equivalent' => null,
                'color' => $color,
                'index' => $index + 1,
                'score' => $index + 1,
            ];
        }
        return $formattedValues;
    }

    // Format best-worst scale statements (stored as choices)
    private function formatBestWorstScaleStatements(array $statements): array
    {
        $formattedChoices = [];
        foreach ($statements as $index => $statement) {
            $text = $statement['text'] ?? '';
            
            $name = $text;
            if (!str_starts_with($name, '<p>')) {
                $name = '<p>' . $name . '</p>';
            }
            
            $formattedChoices[] = [
                'name' => $name,
                'equivalent' => $statement['equivalent'] ?? $text,
                'enable_wr' => 0,
                'CUUID' => 'C' . substr(md5(uniqid()), 0, 7),
                'index' => $index + 1,
                'score' => $index + 1,
                'points' => 0,
                'checkboxScore' => [
                    'active' => 1,
                    'inactive' => 0
                ]
            ];
        }
        return $formattedChoices;
    }

    // Format pairwise comparison items
    private function formatPairwiseComparisonItems(array $items): array
    {
        $formattedItems = [];
        foreach ($items as $item) {
            $formattedItems[] = [
                'statementA' => $item['statementA'] ?? '',
                'statementB' => $item['statementB'] ?? '',
                'valueA' => $item['valueA'] ?? 0,
                'valueB' => $item['valueB'] ?? 0,
                'index' => $item['index'] ?? 0,
                'equivalentA' => $item['equivalentA'] ?? '',
                'equivalentB' => $item['equivalentB'] ?? '',
            ];
        }
        return $formattedItems;
    }

    private function formatSliderItems(array $items): array
    {
        $formattedItems = [];
        foreach ($items as $index => $item) {
            $name = $item['name'] ?? $item['text'] ?? '';
            $equivalent = $item['equivalent'] ?? strip_tags($name);
            $equivalent = strip_tags($equivalent) ?: strip_tags($name);
            
            $formattedItems[] = [
                'index' => $index + 1,
                'name' => $name,
                'equivalent' => $equivalent,
                'random_item' => $item['random_item'] ?? false,
            ];
        }
        return $formattedItems;
    }

    /** Default gradient colors for slider scale: low (red) -> mid (orange) -> high (green) */
    private const SLIDER_DEFAULT_COLORS = [
        '#b71c1c', '#ef5350', '#ff9800', '#ffc107', '#8bc34a', '#4caf50', '#2e7d32',
    ];

    private function formatSliderValues(array $choices): array
    {
        $formattedValues = [];
        $colorPalette = self::SLIDER_DEFAULT_COLORS;
        $numColors = count($colorPalette);
        $numChoices = count($choices);
        foreach ($choices as $index => $choice) {
            $raw = $choice['value'] ?? $choice['text'] ?? $choice['name'] ?? '';
            $name = str_replace('_', ' ', $raw);
            $name = ucwords(mb_strtolower($name));
            if (!str_starts_with($name, '<p>')) {
                $name = '<p>' . $name . '</p>';
            }
            $equivalent = strip_tags($name) ?: (string)($index + 1);
            $color = $choice['colour'] ?? $choice['color'] ?? null;
            if ($color === null || $color === '') {
                $colorIndex = $numChoices <= 1
                    ? 0
                    : (int) round($index * ($numColors - 1) / ($numChoices - 1));
                $colorIndex = min(max(0, $colorIndex), $numColors - 1);
                $color = $colorPalette[$colorIndex];
            }
            $formattedValues[] = [
                'name' => $name,
                'equivalent' => $equivalent,
                'color' => $color,
                'index' => $index + 1,
                'score' => $index + 1,
            ];
        }
        return $formattedValues;
    }

    // Format chart items for pie chart and bar chart questions
    private function formatChartItems(array $chartItems, array $parameters = []): array
    {
        $formattedItems = [];
        $slider = $parameters['slider'] ?? [];
        $maxValue = $slider['maximum_range'] ?? 100;
        
        foreach ($chartItems as $index => $item) {
            $text = $item['text'] ?? '';
            
            $name = $text;
            if (!str_starts_with($name, '<p>')) {
                $name = '<p>' . $name . '</p>';
            }
            
            $formattedItems[] = [
                'name' => $name,
                'equivalent' => null,
                'validate' => [
                    'type' => 1,
                    'min' => 0,
                    'max' => $maxValue,
                    'errorMsg' => 'This question requires an answer',
                ],
                'index' => $index + 1,
            ];
        }
        return $formattedItems;
    }

    // Format chart items for bar chart questions (simpler structure than pie chart)
    private function formatBarChartItems(array $chartItems): array
    {
        $formattedItems = [];
        
        foreach ($chartItems as $index => $item) {
            $text = $item['text'] ?? '';
            
            $name = $text;
            if (!str_starts_with($name, '<p>')) {
                $name = '<p>' . $name . '</p>';
            }
            
            $formattedItems[] = [
                'name' => $name,
                'equivalent' => null,
                'index' => $index + 1,
            ];
        }
        return $formattedItems;
    }

    // Format ranking items
    private function formatRankingItems(array $items): array
    {
        $formattedItems = [];
        foreach ($items as $index => $item) {
            $text = $item['text'] ?? '';
            $equivalent = $item['equivalent'] ?? strip_tags($text);
            $equivalent = strip_tags($equivalent) ?: strip_tags($text);
            
            $name = $text;
            if (!str_starts_with($name, '<p>')) {
                $name = '<p>' . $name . '</p>';
            }
            
            $formattedItems[] = [
                'name' => $name,
                'equivalent' => $equivalent,
                'enable_wr' => 0,
                'index' => $index + 1,
                'score' => $index + 1,
            ];
        }
        return $formattedItems;
    }

    // Format star rating items
    private function formatStarRatingItems(array $items): array
    {
        $formattedItems = [];
        foreach ($items as $index => $item) {
            $text = $item['text'] ?? '';
            $equivalent = $item['equivalent'] ?? strip_tags($text);
            $equivalent = strip_tags($equivalent) ?: strip_tags($text);
            
            $name = $text;
            if (!str_starts_with($name, '<p>')) {
                $name = '<p>' . $name . '</p>';
            }
            
            $formattedItems[] = [
                'name' => $name,
                'equivalent' => $equivalent,
                'index' => $index + 1,
            ];
        }
        return $formattedItems;
    }

    // Format button rating items
    /**
     * Normalize survey payload for button_rating items: accept strings or objects with text/name/label/equivalent.
     * Mirrors what single-question creation expects (text, equivalent).
     */
    private function normalizeSurveyButtonRatingItems($source): array
    {
        if (!is_array($source)) {
            return [];
        }
        $out = [];
        foreach ($source as $item) {
            if ($item === null || $item === '') {
                continue;
            }
            if (is_string($item)) {
                $out[] = ['text' => $item, 'equivalent' => $item];
                continue;
            }
            if (is_array($item)) {
                $text = $item['text'] ?? $item['name'] ?? $item['label'] ?? '';
                if ((string) $text !== '') {
                    $out[] = [
                        'text' => $text,
                        'equivalent' => $item['equivalent'] ?? strip_tags($text),
                    ];
                }
            }
        }
        return $out;
    }

    /**
     * Normalize survey payload for button_rating choices (scale values): accept strings or objects with value/text/name and colour/color.
     * Mirrors what single-question creation expects (value, colour) for formatButtonRatingValues.
     */
    private function normalizeSurveyButtonRatingChoices($source): array
    {
        if (!is_array($source)) {
            return [];
        }
        $out = [];
        foreach ($source as $choice) {
            if ($choice === null || $choice === '') {
                continue;
            }
            if (is_string($choice)) {
                $out[] = ['value' => $choice, 'colour' => '#666666'];
                continue;
            }
            if (is_array($choice)) {
                $value = $choice['value'] ?? $choice['text'] ?? $choice['name'] ?? '';
                if ((string) $value !== '') {
                    $out[] = [
                        'value' => $value,
                        'colour' => $choice['colour'] ?? $choice['color'] ?? '#666666',
                    ];
                }
            }
        }
        return $out;
    }

    private function formatButtonRatingItems(array $items): array
    {
        $formattedItems = [];
        foreach ($items as $index => $item) {
            $text = $item['text'] ?? '';
            $equivalent = $item['equivalent'] ?? strip_tags($text);
            $equivalent = strip_tags($equivalent) ?: strip_tags($text);
            
            $name = $text;
            if (!str_starts_with($name, '<p>')) {
                $name = '<p>' . $name . '</p>';
            }
            
            $formattedItems[] = [
                'name' => $name,
                'equivalent' => $equivalent,
                'enable_wr' => 0,
                'optional' => 0,
                'index' => $index + 1,
            ];
        }
        return $formattedItems;
    }

    // Format button rating values (choices)
    private function formatButtonRatingValues(array $choices): array
    {
        $formattedValues = [];
        foreach ($choices as $index => $choice) {
            $text = $choice['value'] ?? $choice['text'] ?? '';
            $text = str_replace('_', ' ', $text);
            $text = ucwords(mb_strtolower($text));
            $color = $choice['colour'] ?? $choice['color'] ?? null;

            $name = $text;
            if (!str_starts_with($name, '<p>')) {
                $name = '<p>' . $name . '</p>';
            }

            $formattedValues[] = [
                'name' => $name,
                'equivalent' => $text,
                'color' => $color,
                'index' => $index + 1,
                'score' => $index + 1,
            ];
        }
        return $formattedValues;
    }

    // Format multiple textbox items according to the guide structure
    private function formatMultipleTextboxItems(array $textboxes): array
    {
        $formattedItems = [];
        foreach ($textboxes as $index => $textbox) {
            $text = $textbox['text'] ?? '';
            $equivalent = $textbox['equivalent'] ?? null;
            
            $selectType = $textbox['select_type']['select_type_option'] ?? 'open text';
            $selectTypeMap = [
                'open text' => 1,
                'letters only' => 8,
                'alphanumeric' => 9,
                'whole number' => 2,
                'decimal number' => 3,
                'email address' => 4,
                'date' => 5,
                'time' => 6,
                'date time' => 7,
                'pricing' => 10,
            ];
            $validateType = $selectTypeMap[strtolower(trim($selectType))] ?? 1;
            
            $validate = [
                'type' => $validateType,
                'min' => $textbox['select_type']['minimum_character'] ?? 0,
                'max' => $textbox['select_type']['maximum_character'] ?? 5,
                'errorMsg' => 'This question requires an answer',
                'dateFormat' => 'mm-dd-yyyy',
                'timeFormat' => '12-hour',
                'datetimeFormat' => 'dd-mmm-yyyy hh:mm',
                'currencyFormat' => 'USD',
            ];
            
            $name = $text;
            if (!empty($text) && !str_starts_with(trim($text), '<p>')) {
                $name = '<p>' . $text . '</p>';
            }
            
            $formattedItems[] = [
                // 'name' => $name,
                'name' => $name ?? $text,
                'equivalent' => $equivalent,
                'validate' => $validate,
                'CUUID' => 'C' . substr(md5(uniqid() . $index), 0, 7),
                'index' => $index + 1,
            ];
        }
        return $formattedItems;
    }
}
