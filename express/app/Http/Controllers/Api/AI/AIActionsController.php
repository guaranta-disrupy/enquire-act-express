<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\AI\Traits\ChatControllerHelpers;
use App\Services\ChatService;
use App\Services\PromptService;
use App\Services\Neo4JService;
use App\Services\ConversationMemoryService;
use App\Services\ConversationStorageService;
use App\Services\TokenManagerService;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\Activity_Log;
use App\Services\DistinctQuestionGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;

class AIActionsController extends Controller
{
    use ChatControllerHelpers;

    public function __construct(
        ChatService $chatService,
        Neo4JService $neo4jService,
        ConversationMemoryService $memoryService,
        ConversationStorageService $conversationStorage,
        TokenManagerService $tokenManager
    ) {
        $this->chatService = $chatService;
        $this->neo4jService = $neo4jService;
        $this->memoryService = $memoryService;
        $this->conversationStorage = $conversationStorage;
        $this->tokenManager = $tokenManager;
        
        $this->memoryService->setConversationStorage($conversationStorage);
    }

    public function confirmAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|string',
            'scope' => 'required|string',
            'intent' => 'required|string',
            'preview_data' => 'nullable|array',
            'survey_data' => 'nullable|array',
            'questionnaire_code' => 'nullable|string',
            'questions_count' => 'nullable|integer|min:1|max:20',
            'user_message' => 'nullable|string|max:500',
        ]);

        Log::info('CHAT CONTROLLER DEBUG == Action confirmation received', [
            'action' => $request->action,
            'scope' => $request->scope,
            'intent' => $request->intent,
            'questionnaire_code' => $request->questionnaire_code,
            'questions_count' => $request->questions_count,
            'user_id' => optional($request->user())->id,
        ]);

        try {
            $executionRequest = new Request();
            $executionRequest->merge([
                'questionnaire_code' => $request->questionnaire_code,
                'questions_count' => $request->questions_count,
                'user_message' => $request->user_message,
            ]);

            $aiExecutionController = new \App\Http\Controllers\Api\AiExecutionController();
            
            switch ($request->action) {
                case 'express_survey_create':
                    return $aiExecutionController->executeCreateSurvey(
                        $executionRequest,
                        $request->survey_data ?? $request->preview_data,
                        $request->questionnaire_code
                    );
                case 'express_question_create':
                case 'create_question':
                case 'express_text_selection_create':
                case 'express_image_ lection_create':
                case 'express_descriptive_create':
                case 'express_single_textbox_create':
                case 'express_multiple_textbox_create':
                case 'express_open_ended_create':
                case 'express_slider_create':
                case 'express_ranking_create':
                case 'express_star_rating_create':
                case 'express_button_rating_create':
                case 'express_segmentation_create':
                case 'express_video_player_create':
                case 'express_pairwise_comparison_create':
                case 'express_best_worst_scale_create':
                case 'express_best-worst_scale_create':
                case 'express_pie_chart_create':
                case 'express_bar_chart_create':
                case 'express_matrix_create':
                    return $aiExecutionController->executeCreateQuestion(
                        $executionRequest, 
                        $request->preview_data, 
                        $request->scope
                    );
                case 'express_question_update':
                case 'update_question':
                    return $aiExecutionController->executeUpdateQuestion(
                        $executionRequest, 
                        $request->preview_data, 
                        $request->scope
                    );
                case 'express_question_delete':
                case 'delete_question':
                    return $aiExecutionController->executeDeleteQuestion(
                        $executionRequest, 
                        $request->preview_data, 
                        $request->scope
                    );
                case 'express_question_move':
                case 'move_question':
                    return $this->moveQuestion($request);
                case 'express_question_options_update':
                case 'update_question_options':
                    return $aiExecutionController->executeUpdateQuestionOptions(
                        $executionRequest,
                        $request->preview_data,
                        $request->scope
                    );
                case 'express_question_choice_update':
                case 'update_question_choice':
                case 'add_question_choice':
                case 'edit_question_choice':
                case 'remove_question_choice':
                    return $aiExecutionController->executeUpdateQuestionChoice(
                        $executionRequest,
                        $request->preview_data,
                        $request->scope
                    );
                default:
                    Log::error('CHAT CONTROLLER DEBUG === Unknown action type in confirmation', [
                        'action' => $request->action,
                    ]);
                    return response()->json([
                        'message' => 'Unknown action type: ' . $request->action,
                    ], 400);
            }
        } catch (\Throwable $e) {
            Log::error('Action confirmation failed', [
                'action' => $request->action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Failed to execute action. Please try again.',
            ], 500);
        }
    }

    public function cancelAction(Request $request): JsonResponse
    {
        Log::info('[CHAT CONTROLLER DEBUG] Action cancelled', [
            'user_id' => optional($request->user())->id,
        ]);

        return response()->json([
            'message' => 'Action cancelled. Is there anything else I can help you with?',
        ]);
    }

    /**
     * Generate N distinct questions for preview (used when user asks for multiple questions of same type).
     * Returns questions in survey_data format for display in the confirmation list.
     */
    public function previewMultipleQuestions(Request $request): JsonResponse
    {
        $request->validate([
            'scope' => 'required|string',
            'topic' => 'required|string',
            'first_question' => 'required|array',
            'count' => 'required|integer|min:2|max:20',
            'questionnaire_code' => 'nullable|string',
        ]);

        $scope = $request->input('scope');
        $topic = trim($request->input('topic'));
        $firstQuestion = $request->input('first_question');
        $count = (int) $request->input('count');
        $questionnaireCode = $request->input('questionnaire_code');

        $scopeForSchema = str_replace('-', '_', $scope);
        if ($scopeForSchema === 'best_worst_scale' || $scopeForSchema === 'best_worst_scale2') {
            $scopeForSchema = 'best-worst_scale';
        }

        $questions = [];
        $firstQ = $this->toSurveyQuestionFormat($firstQuestion, $scope);
        $questions[] = $firstQ;
        $existingTexts = [strip_tags($firstQ['title_and_question_text']['question_text'] ?? $firstQ['title_and_question_text']['question text'] ?? '')];

        $distinctGenerator = app(DistinctQuestionGeneratorService::class);

        for ($i = 1; $i < $count; $i++) {
            $generated = $distinctGenerator->generate($scopeForSchema, $topic, $existingTexts);
            if (!$generated) {
                $generated = $distinctGenerator->generate($scopeForSchema, $topic, $existingTexts);
            }
            if (!$generated) {
                Log::warning('[previewMultipleQuestions] Failed to generate question', ['index' => $i + 1]);
                break;
            }
            $q = $this->toSurveyQuestionFormat($generated, $scope);
            $questions[] = $q;
            $existingTexts[] = strip_tags($q['title_and_question_text']['question_text'] ?? $q['title_and_question_text']['question text'] ?? '');
        }

        return response()->json([
            'success' => true,
            'questions' => $questions,
            'questionnaire_code' => $questionnaireCode,
        ]);
    }

    private function toSurveyQuestionFormat(array $questionData, string $scope): array
    {
        $titleAndQuestion = $questionData['title_and_question_text'] ?? [];
        if (isset($titleAndQuestion['question text']) && !isset($titleAndQuestion['question_text'])) {
            $titleAndQuestion['question_text'] = $titleAndQuestion['question text'];
        }

        $formatted = [
            'question_type' => str_replace('-', '_', $scope),
            'title_and_question_text' => $titleAndQuestion,
        ];

        $fields = ['text_options', 'image_options', 'textboxes', 'choices', 'items', 'values', 'rows', 'columns'];
        foreach ($fields as $field) {
            if (isset($questionData[$field])) {
                $formatted[$field] = $questionData[$field];
            }
        }

        return $formatted;
    }

    protected function detectCreationQuestion(string $message): ?array
    {
        $patterns = config('ai_detection.creation_question_patterns', []);
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                if (preg_match('/(question|survey|folder|component)/i', $message, $matches)) {
                    return ['item' => strtolower($matches[1])];
                }
            }
        }
        
        return null;
    }

    public function duplicateQuestion(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'questionnaire_code' => 'required|string',
                'question_id' => 'required|integer'
            ]);

            $questionnaireCode = $request->input('questionnaire_code');
            $questionId = $request->input('question_id');

            $questionnaire = Questionnaire::where('code', $questionnaireCode)->first();
            if (!$questionnaire) {
                return response()->json([
                    'success' => false,
                    'message' => 'Questionnaire not found'
                ], 404);
            }

            Gate::authorize('update', $questionnaire->parent_survey);

            $questionToCopy = Question::where('id', $questionId)
                ->where('questionnaire_id', $questionnaire->id)
                ->first();

            if (!$questionToCopy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Question not found'
                ], 404);
            }

            $order = $questionnaire->questions()->count();
            $newQuestion = copyQuestions(['order' => $order + 1], $questionToCopy);

            return response()->json([
                'success' => true,
                'message' => 'Question duplicated successfully',
                'question_id' => $newQuestion->id,
                'route' => route('questions.index', ['questionnaire_code' => $questionnaireCode])
            ]);
        } catch (\Throwable $e) {
            Log::error('[ChatController] Failed to duplicate question', [
                'questionnaire_code' => $request->input('questionnaire_code'),
                'question_id' => $request->input('question_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate question: ' . $e->getMessage()
            ], 500);
        }
    }

    public function moveQuestion(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'questionnaire_code' => 'required|string',
                'preview_data' => 'required|array',
                'preview_data.source_order' => 'required|integer',
                'preview_data.target_order' => 'required|integer',
            ]);

            $questionnaireCode = $request->input('questionnaire_code');
            $sourceOrder = $request->input('preview_data.source_order');
            $targetOrder = $request->input('preview_data.target_order');
            $sourceQuestion = $request->input('preview_data.source_question');
            $targetQuestion = $request->input('preview_data.target_question');

            $questionnaire = Questionnaire::where('code', $questionnaireCode)->first();
            if (!$questionnaire) {
                return response()->json([
                    'success' => false,
                    'message' => 'Questionnaire not found'
                ], 404);
            }

            Gate::authorize('update', $questionnaire->parent_survey);

            $question = Question::where('questionnaire_id', $questionnaire->id)
                ->where('order', $sourceOrder)
                ->first();

            if (!$question) {
                return response()->json([
                    'success' => false,
                    'message' => 'Question not found'
                ], 404);
            }

            // Use the same logic as QuestionController::move_question
            $questions = $questionnaire->questions->toArray();
            
            // Take question to move
            $out = array_splice($questions, ($question->order - 1), 1);
            
            // Move question to target position
            array_splice($questions, ($targetOrder - 1), 0, $out);
            
            // Apply changes
            foreach ($questions as $key => $q) {
                $qquestion = Question::find($q['id']);
                $qquestion->order = $key + 1;
                $qquestion->save();
            }

            // Log activity
            Activity_Log::create([
                'user_id' => $request->user()->id,
                'action' => "move",
                'details' => "User " . $request->user()->name . " moved questions in " . $questionnaire->name . " questionnaire code: " . $questionnaireCode,
            ]);

            $variableStart = $questionnaire->setup['variable_start'] ?? 1;
            $newQuestionNumber = $targetOrder + $variableStart - 1;

            return response()->json([
                'success' => true,
                'message' => "Question moved successfully to Q{$newQuestionNumber}",
                'route' => route('questions.index', ['questionnaire_code' => $questionnaireCode])
            ]);
        } catch (\Throwable $e) {
            Log::error('[AIActionsController] Failed to move question', [
                'questionnaire_code' => $request->input('questionnaire_code'),
                'source_order' => $request->input('preview_data.source_order'),
                'target_order' => $request->input('preview_data.target_order'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to move question: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getQuestionsForSelection(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'questionnaire_code' => 'required|string'
            ]);

            $questionnaireCode = $request->input('questionnaire_code');
            $questionnaire = Questionnaire::where('code', $questionnaireCode)->first();

            if (!$questionnaire) {
                return response()->json([
                    'success' => false,
                    'message' => 'Questionnaire not found'
                ], 404);
            }

            Gate::authorize('view', $questionnaire->parent_survey);

            $questions = Question::where('questionnaire_id', $questionnaire->id)
                ->orderBy('order', 'asc')
                ->get();

            $variableStart = $questionnaire->setup['variable_start'] ?? 1;
            
            $formattedQuestions = $questions->map(function ($question) use ($variableStart) {
                $questionNumber = (int)$question->order + (int)$variableStart - 1;
                
                // Extract chartTitle from setup
                $chartTitle = '';
                $setup = $question->setup;
                if ($setup) {
                    if (is_string($setup)) {
                        $setup = json_decode($setup, true);
                    }
                    if (is_array($setup) && isset($setup['chartTitle'])) {
                        $chartTitle = $setup['chartTitle'];
                    }
                }
                
                // Map question type ID to type name
                $questionType = $this->getQuestionTypeName($question->question_type_id, $setup );
                
                return [
                    'id' => $question->id,
                    'order' => $question->order,
                    'number' => $questionNumber,
                    'title' => $chartTitle ?: ($question->title ?? ''),
                    'question_type' => $questionType,
                    'qtype' => $question->question_type_id,
                    'text' => $question->question ?? '',
                    'setup' => $setup,
                    'fullText' => "Q{$questionNumber}: " . ($chartTitle ?: ($question->question ?? ''))
                ];
            })->toArray();

            return response()->json([
                'success' => true,
                'questions' => $formattedQuestions
            ]);
        } catch (\Throwable $e) {
            Log::error('[ChatController] Failed to get questions for selection', [
                'questionnaire_code' => $request->input('questionnaire_code'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get questions: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function getQuestionTypeName(int $typeId, ?array $setup = null): string
    {
        $typeMap = [
            1 => 'Descriptive',
            2 => 'Multiple Choice',
            3 => 'Image Selection',
            4 => 'Single Textbox',
            5 => 'Multiple Textbox',
            6 => 'Open Ended',
            7 => 'Constant Sum',
            8 => 'Rating Slider',
            9 => 'Ranking',
            10 => 'Star Rating',
            11 => 'Matrix',
            12 => 'Matrix Multi',
            13 => 'Memory Test Desc',
            14 => 'Memory Test Comparison',
            15 => 'Button Rating',
            16 => 'Segmentation',
            18 => 'Video Player',
            19 => 'Pairwise Comparison',
            20 => 'Best Worst Scale',
            21 => 'Bar Chart',
            22 => 'Best Worst Scale 2',
        ];
        
        $baseType = $typeMap[$typeId] ?? 'Unknown';
        
        // Handle special cases
        if ($typeId == 2 && $setup && isset($setup['selectMultiple'])) {
            return $setup['selectMultiple'] ? 'Multiple Choice (Multiple Answer)' : 'Multiple Choice (Single Answer)';
        }
        
        if ($typeId == 9 && $setup && isset($setup['mode'])) {
            return $setup['mode'] ? 'Ranking (Mode B)' : 'Ranking (Mode A)';
        }
        
        if ($typeId == 11 && $setup && isset($setup['selectMultiple'])) {
            return $setup['selectMultiple'] ? 'Matrix (Multiple Answer)' : 'Matrix (Single Answer)';
        }
        
        return $baseType;
    }

    public function updateQuestionOptions(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'questionnaire_code' => 'required|string',
                'question_number' => 'required|integer',
                'option_type' => 'required|string',
                'option_value' => 'nullable',
                'multi_select_type' => 'nullable|integer',
            ]);

            $questionnaireCode = $request->input('questionnaire_code');
            $questionNumber = $request->input('question_number');
            $optionType = $request->input('option_type');
            $optionValue = $request->input('option_value', true);
            $multiSelectType = $request->input('multi_select_type');

            $questionnaire = Questionnaire::where('code', $questionnaireCode)->first();
            if (!$questionnaire) {
                return response()->json([
                    'success' => false,
                    'message' => 'Questionnaire not found'
                ], 404);
            }

            Gate::authorize('update', $questionnaire->parent_survey);

            // Calculate order from question number
            $variableStart = $questionnaire->setup['variable_start'] ?? 1;
            $order = $questionNumber - $variableStart + 1;

            $question = Question::where('questionnaire_id', $questionnaire->id)
                ->where('order', $order)
                ->first();

            if (!$question) {
                return response()->json([
                    'success' => false,
                    'message' => "Question Q{$questionNumber} not found"
                ], 404);
            }

            // Validate option is applicable for question type
            if (!$this->isOptionApplicable($question->question_type_id, $optionType)) {
                return response()->json([
                    'success' => false,
                    'message' => "Option '{$optionType}' is not applicable for this question type"
                ], 400);
            }

            // Route to execution controller
            $executionRequest = new Request();
            $executionRequest->merge([
                'questionnaire_code' => $questionnaireCode,
            ]);

            $previewData = [
                'question_id' => $question->id,
                'question_order' => $order,
                'question_number' => $questionNumber,
                'option_type' => $optionType,
                'option_value' => $optionValue,
                'multi_select_type' => $multiSelectType,
            ];

            $aiExecutionController = new \App\Http\Controllers\Api\AiExecutionController();
            return $aiExecutionController->executeUpdateQuestionOptions(
                $executionRequest,
                $previewData,
                'question_options'
            );
        } catch (\Throwable $e) {
            Log::error('[AIActionsController] Failed to update question options', [
                'questionnaire_code' => $request->input('questionnaire_code'),
                'question_number' => $request->input('question_number'),
                'option_type' => $request->input('option_type'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update question options: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function isOptionApplicable(int $questionTypeId, string $optionType): bool
    {
        $applicableOptions = [
            2 => ['required', 'random_choices', 'multiple_selection', 'dropdown', 'select_all', 'unselect_all', 'scoring'], // Multiple Choice
            3 => ['required', 'random_choices', 'multiple_selection', 'select_all', 'unselect_all', 'scoring'], // Image Selection (no dropdown)
            11 => ['required', 'random_choices', 'multiple_selection', 'scoring'], // Matrix
        ];

        // Default options for other question types
        $defaultOptions = ['required', 'scoring'];

        $options = $applicableOptions[$questionTypeId] ?? $defaultOptions;
        
        return in_array($optionType, $options);
    }
}

