<?php

namespace App\Http\Controllers\Api;

use App\LoggerHelpers;
use App\AI\Enums\Intent;
use App\AI\Enums\Scope;
use App\AI\Enums\Platform;
use App\AI\Enums\EXPRESSType;
use App\AI\Enums\AIModel;
use App\Http\Controllers\Controller;
use App\Services\ChatService;
use App\Services\PromptService;
use App\Services\Neo4JService;
use App\Services\ConversationMemoryService;
use App\Services\ConversationStorageService;
use App\Services\TokenManagerService;
use App\Services\GeminiFileAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\StreamedResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Prism;
use App\AI\Schemas\ConversationSchema;
use App\Models\AI\MongoTokenManager;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateBaseQuestionSchema;
use App\Models\Question;
use App\Models\Questionnaire;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\Api\AI\Traits\ChatControllerHelpers;
use Illuminate\Support\Str;
use MongoDB\BSON\ObjectId;
use Carbon\Carbon;

class ChatController extends Controller
{
    use ChatControllerHelpers;

    protected ChatService $chatService;
    protected PromptService $promptService;
    protected Neo4JService $neo4jService;
    protected ConversationMemoryService $memoryService;
    protected ConversationStorageService $conversationStorage;
    protected TokenManagerService $tokenManager;

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

    private function getUserTypeInfo(Request $request): array
    {
        $user = $request->user();
        if (!$user) {
            return ['type' => 'normal', 'firstName' => null];
        }
        $firstName = $user->name ? trim(explode(' ', $user->name)[0]) : null;
        return ['type' => 'normal', 'firstName' => $firstName];
    }

    private function buildSystemPrompt(string $basePrompt, string $userType, ?string $firstName, ?string $memoryContext = null): string
    {
        $prompt = $basePrompt;
        
        if ($firstName) {
            $prompt .= str_replace('{firstName}', $firstName, config('ai_prompts.name_addressing.normal', ''));
        }
        
        if ($memoryContext) {
            $prompt .= "\n\nContext:\n" . $memoryContext;
        }
        
        return $prompt;
    }

    private function storeUserMessage(Request $request, string $message, array $metadata = []): ?object
    {
        $user = $request->user();
        if (!$user) return null;

        try {
            $mongoConversation = $this->conversationStorage->getOrCreateConversation(
                $user->id,
                $request->input('conversation_id')
            );
            $this->conversationStorage->addUserMessage(
                $mongoConversation,
                $message,
                $request->input('websitePosition', 'dashboard'),
                $metadata
            );
            return $mongoConversation;
        } catch (\Throwable $e) {
            Log::warning('MongoDB user message storage failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function storeAssistantMessage(?object $mongoConversation, string $message, int $responseTime = 0, array $metadata = []): ?string
    {
        if (!$mongoConversation) return null;

        try {
            $result = $this->conversationStorage->addAssistantMessage(
                $mongoConversation,
                $message,
                $responseTime,
                $metadata
            );
            return $result['message_id'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('MongoDB assistant message storage failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function checkTokenLimit(Request $request): ?string
    {
        $user = $request->user();
        if (!$user) return null;

        try {
            $requestTokens = $this->estimateTokens($request->message);
            $needed = $requestTokens + 1000;
            
            if (!$this->hasTokenRemaining($user->id, $needed)) {
                return "You've reached your daily AI token limit. Please try again tomorrow.";
            }
        } catch (\Throwable $e) {
            Log::warning('Token check failed', ['error' => $e->getMessage()]);
        }
        return null;
    }

    private function buildCompleteSurveyQuestionSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'survey_generation',
            description: 'Generate a complete survey with multiple questions',
            properties: [
                new ArraySchema(
                    name: 'questions',
                    description: 'Array of question definitions',
                    items: new ObjectSchema(
                        name: 'question',
                        description: 'Question definition for survey - use the same schema structure as individual question creation. REQUIRED fields by type: text_selection/image_selection=text_options; multiple_textbox=textboxes; slider=items+choices; ranking=items; star_rating=items+number_of_rating_stars; button_rating=items+choices; matrix=rows+columns. Include all fields from the specific question type schema, setting unused fields to null.',
                        properties: [
                            new EnumSchema(
                                name: 'question_type',
                                description: 'Question type',
                                options: ['descriptive', 'text_selection', 'image_selection', 'single_textbox', 'multiple_textbox', 'open_ended', 'slider', 'ranking', 'star_rating', 'button_rating', 'segmentation', 'matrix', 'pairwise_comparison', 'best-worst_scale', 'pie_chart', 'bar_chart']
                            ),
                            (new CreateBaseQuestionSchema())->getSchema(),
                            new ArraySchema(
                                name: 'text_options',
                                description: 'Text options/choices for selection questions (text_selection, image_selection). Required for text_selection and image_selection. Set to null for other question types.',
                                items: (new \App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateTextSelectionChoiceSchema())->getSchema(),
                                nullable: true
                            ),
                            new ArraySchema(
                                name: 'textboxes',
                                description: 'REQUIRED for multiple_textbox only. The textbox labels/fields for each input (e.g., "First Name", "Last Name", "Email", "Phone Number"). Generate 2-5 contextually relevant textbox labels based on the question. Use CreateMultipleTextboxChoiceSchema format: text, equivalent. Set to null for other question types.',
                                items: (new \App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateMultipleTextboxChoiceSchema())->getSchema(),
                                nullable: true
                            ),
                            new ArraySchema(
                                name: 'items',
                                description: 'REQUIRED for slider, ranking, star_rating, and button_rating. CRITICAL: For SLIDER, "items" are the TOPICS/ASPECTS being rated (what respondents evaluate), NOT the scale labels. For RANKING: items are the options to rank (e.g., "Most important", "Second", "Least important" or brand names, product features). For STAR_RATING: items are the aspects to rate with stars (e.g., "Product quality", "Customer service", "Value for money"). For BUTTON_RATING: same as star_rating. Generate 3-6 items for ranking/star_rating/button_rating. Required for slider (CreateSliderItemSchema: index, name, equivalent, random_item), ranking (CreateRankingItemSchema: text, equivalent, random_item), star_rating (CreateStarRatingItemSchema: text, equivalent, random_item), button_rating (CreateButtonRatingItemSchema: text, equivalent, random_item). Set to null for other question types.',
                                items: new ObjectSchema(
                                    name: 'item',
                                    description: 'An item - structure varies by question type. For slider: include index (number), name (string with HTML), equivalent (string), random_item (boolean). IMPORTANT: Slider items must be topics/aspects being rated (e.g., "Product quality", "Customer service", "Price value"), NOT scale labels. For ranking/star_rating/button_rating: include text (string), equivalent (string), random_item (boolean).',
                                    properties: [
                                        new \Prism\Prism\Schema\NumberSchema(
                                            name: 'index',
                                            description: 'Index/order for slider items (1, 2, 3, etc.). Required for slider items, set to null for ranking/star_rating/button_rating items.',
                                            nullable: true
                                        ),
                                        new StringSchema(
                                            name: 'text',
                                            description: 'The item text (for ranking, star_rating, and button_rating items). Required for ranking/star_rating/button_rating, set to null for slider items.',
                                            nullable: true
                                        ),
                                        new StringSchema(
                                            name: 'name',
                                            description: 'The item name with HTML formatting (for slider items). MUST be a topic/aspect being rated, NOT a scale label. Examples: "<p>Atmosphere of the party</p>", "<p>Quality of food and drinks</p>", "<p>Entertainment provided</p>". WRONG examples: "<p>Not at all</p>", "<p>Slightly</p>", "<p>Very</p>" - these are scale labels and belong in "choices", not "items". Required for slider items, set to null for ranking/star_rating/button_rating items.',
                                            nullable: true
                                        ),
                                        new StringSchema(
                                            name: 'equivalent',
                                            description: 'The plain text version without HTML (required for all item types). MUST be the same as the item text/name but without HTML tags. For slider: plain text version of name (e.g., if name is "<p>Atmosphere of the party</p>", equivalent should be "Atmosphere of the party"). For ranking/star_rating/button_rating: plain text version of text (e.g., if text is "Starbucks", equivalent should be "Starbucks"). The equivalent value should always match the text/name value.',
                                            nullable: true
                                        ),
                                        new \Prism\Prism\Schema\BooleanSchema(
                                            name: 'random_item',
                                            description: 'Whether the item should be displayed randomly. Required for all item types.',
                                            nullable: true
                                        )
                                    ],
                                    requiredFields: ['index', 'text', 'name', 'equivalent', 'random_item']
                                ),
                                nullable: true
                            ),
                            new ArraySchema(
                                name: 'choices',
                                description: 'CRITICAL DISTINCTION: For SLIDER questions, "choices" are the SCALE VALUES/LABELS for the slider (the rating scale itself), NOT the topics being rated. PREFER TEXT-BASED SCALE LABELS over numeric values. Common text scale examples: "Not at all", "Slightly", "Moderately", "Very", "Extremely" OR "Strongly Disagree", "Disagree", "Neutral", "Agree", "Strongly Agree" OR "Poor", "Fair", "Good", "Very Good", "Excellent" OR "Never", "Rarely", "Sometimes", "Often", "Always". Generate 3-7 text-based scale labels that form a coherent rating scale appropriate for the question context. Use numeric values ("1", "2", "3", etc.) only if text labels are not suitable. DO NOT use topic names like "Atmosphere" or "Food quality" as choices - those belong in "items". Required for slider and button_rating. For slider: use CreateSliderChoiceSchema format (value, colour). For button_rating: choices ALWAYS depend on the question—use "Yes"/"No" for binary (2 choices), or TEXT scale labels (e.g. "Very satisfied", "Neutral") for rating; never numbers only. Use CreateButtonRatingChoiceSchema format (value, colour). Set to null for other question types.',
                                items: new ObjectSchema(
                                    name: 'choice',
                                    description: 'A choice - structure varies by question type. For slider: include value (string), colour (string). IMPORTANT: Slider choices must be text-based scale labels (e.g., "Not at all", "Slightly", "Moderately", "Very", "Extremely") or numeric values if text is not suitable. NOT topics being rated. For button_rating: include value (string), colour (string).',
                                    properties: [
                                        new StringSchema(
                                            name: 'value',
                                            description: 'The scale label/value for the choice. For slider: PREFER text-based labels like "Not at all", "Slightly", "Moderately", "Very", "Extremely" or similar common rating scales. Use numeric values ("1", "2", "3") only if text labels are not appropriate. The label should be a descriptive word or phrase that indicates the level of agreement, frequency, quality, or intensity. Required for slider and button_rating choices.',
                                            nullable: false
                                        ),
                                        new StringSchema(
                                            name: 'colour',
                                            description: 'The hexadecimal colour for the choice. Use color gradients that match the scale intensity: darker red (#b71c1c or #FF0000) for negative/low values, orange/yellow (#ff9800 or #FFA500) for neutral/middle values, green/teal (#8bc34a, #009688, or #00FF00) for positive/high values. Create a smooth gradient from negative to positive across the scale. Required for slider and button_rating choices.',
                                            nullable: false
                                        )
                                    ],
                                    requiredFields: ['value', 'colour']
                                ),
                                nullable: true
                            ),
                            new ArraySchema(
                                name: 'rows',
                                description: 'Rows for matrix questions. Required for matrix questions. Set to null for other question types.',
                                items: (new \App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateMatrixRowSchema())->getSchema(),
                                nullable: true
                            ),
                            new ArraySchema(
                                name: 'columns',
                                description: 'Columns for matrix questions. Required for matrix questions. Set to null for other question types.',
                                items: (new \App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateMatrixColumnSchema())->getSchema(),
                                nullable: true
                            ),
                            (new \App\AI\Schemas\Question\EXPRESS\Reusables\CreateRequiredSchema())->getSchema(),
                            (new \App\AI\Schemas\Question\EXPRESS\Reusables\CreateEditLayoutWidthSchema())->getSchema(),
                            new \Prism\Prism\Schema\BooleanSchema(
                                name: 'random_items',
                                description: 'Indicates whether items should be randomised. Required for ranking, star_rating, button_rating, and slider questions. Set to null for other question types.',
                                nullable: true
                            ),
                            new \Prism\Prism\Schema\BooleanSchema(
                                name: 'random_values',
                                description: 'Indicates whether values/choices should be randomised. Required for button_rating questions. Set to null for other question types.',
                                nullable: true
                            ),
                            new \Prism\Prism\Schema\BooleanSchema(
                                name: 'random_choices',
                                description: 'Indicates whether choices should be randomised. Required for matrix questions. Set to null for other question types.',
                                nullable: true
                            ),
                            new \Prism\Prism\Schema\NumberSchema(
                                name: 'number_of_rating_stars',
                                description: 'Number of stars for star rating questions. Required for star_rating questions. Set to null for other question types.',
                                nullable: true
                            ),
                            new ObjectSchema(
                                name: 'rank_mode',
                                description: 'Rank mode configuration for ranking questions. Provide values when mode is set to rank; otherwise set fields to null. Required for ranking questions.',
                                properties: [
                                    new EnumSchema(
                                        name: 'display_position',
                                        description: 'Position of the drop zone relative to the item list. Use "right", "left", "bottom", or "top".',
                                        options: ['right', 'left', 'bottom', 'top'],
                                        nullable: true
                                    ),
                                    new \Prism\Prism\Schema\NumberSchema(
                                        name: 'maximum_item_selection',
                                        description: 'Maximum number of items allowed in the drop zone. Set to null if not specified.',
                                        nullable: true
                                    ),
                                    new \Prism\Prism\Schema\NumberSchema(
                                        name: 'minimum_item_selection',
                                        description: 'Minimum number of items required in the drop zone. Set to null if not specified.',
                                        nullable: true
                                    ),
                                    new StringSchema(
                                        name: 'limit_error_message',
                                        description: 'Custom error message when the selection limit is not respected. Set to null to use the default message.',
                                        nullable: true
                                    ),
                                ],
                                requiredFields: ['display_position', 'maximum_item_selection', 'minimum_item_selection', 'limit_error_message'],
                                nullable: true
                            ),
                            new ObjectSchema(
                                name: 'sort_mode',
                                description: 'Sort mode configuration for ranking questions. Provide values when mode is set to sort; otherwise set fields to null. Required for ranking questions.',
                                properties: [
                                    new \Prism\Prism\Schema\NumberSchema(
                                        name: 'number_of_columns',
                                        description: 'Number of columns in the sort layout. Set to null to use the default value.',
                                        nullable: true
                                    ),
                                ],
                                requiredFields: ['number_of_columns'],
                                nullable: true
                            ),
                            new EnumSchema(
                                name: 'mode',
                                description: 'Ranking mode (rank or sort). Required for ranking questions. Set to null for other question types.',
                                options: ['rank', 'sort'],
                                nullable: true
                            ),
                            new StringSchema(
                                name: 'item_fixed_height',
                                description: 'Fixed item height for ranking questions. Set to null for other question types.',
                                nullable: true
                            ),
                            new \Prism\Prism\Schema\BooleanSchema(
                                name: 'single_answer_per_column',
                                description: 'Matrix-only flag indicating whether columns accept a single answer. Required for matrix questions. Set to null for other question types.',
                                nullable: true
                            ),
                            new ObjectSchema(
                                name: 'selections',
                                description: 'Selection behaviour for matrix questions. Required for matrix questions. Set to null for other question types.',
                                properties: [
                                    new \Prism\Prism\Schema\BooleanSchema(
                                        name: 'apply_select_all',
                                        description: 'If true, enables a select-all option.',
                                        nullable: true
                                    ),
                                    new \Prism\Prism\Schema\BooleanSchema(
                                        name: 'apply_unselect_all',
                                        description: 'If true, enables an unselect-all option.',
                                        nullable: true
                                    ),
                                ],
                                requiredFields: ['apply_select_all', 'apply_unselect_all'],
                                nullable: true
                            ),
                            new \Prism\Prism\Schema\BooleanSchema(
                                name: 'track_reaction_time',
                                description: 'Specifies whether to track reaction time. Required for button_rating questions. Set to null for other question types.',
                                nullable: true
                            ),
                            new \Prism\Prism\Schema\BooleanSchema(
                                name: 'hide_instruction',
                                description: 'Specifies whether to show the instruction page. Required for button_rating questions. Set to null for other question types.',
                                nullable: true
                            ),
                            new StringSchema(
                                name: 'header',
                                description: 'Header text for button_rating questions. Required for button_rating questions. Set to null for other question types.',
                                nullable: true
                            ),
                            new StringSchema(
                                name: 'footer',
                                description: 'Footer text for button_rating questions. Required for button_rating questions. Set to null for other question types.',
                                nullable: true
                            )
                        ],
                        requiredFields: ['question_type', 'title_and_question_text', 'text_options', 'textboxes', 'items', 'choices', 'rows', 'columns', 'required', 'edit_layout_width', 'random_items', 'random_values', 'random_choices', 'number_of_rating_stars', 'rank_mode', 'sort_mode', 'mode', 'item_fixed_height', 'single_answer_per_column', 'selections', 'track_reaction_time', 'hide_instruction', 'header', 'footer']
                    )
                )
            ],
            requiredFields: ['questions']
        );
    }

    private function estimateTokens(string $text): int
    {
        $length = mb_strlen($text ?? '');
        if ($length === 0) {
            return 0;
        }
        // Heuristic: ~4 chars per token on average for English-like text.
        return (int) ceil($length / 4);
    }

    private function recordTokenUsage(int $userId, int $tokens): void
    {
        if ($tokens <= 0) {
            return;
        }

        try {
            $this->conversationStorage->incrementMongoTokenUsage($userId, 'express', $tokens);
        } catch (\Throwable $e) {
            Log::warning('MongoDB token usage record failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);
        }
    }

    private function hasTokenRemaining(int $userId, int $neededTokens): bool
    {
        $dailyLimit = config('ai_chat.daily_token_limit', 25000);
        
        try {
            $record = MongoTokenManager::where('user_id', $userId)->first();
            if (!$record) {
                return $neededTokens <= $dailyLimit;
            }

            $currentUsage = (int) ($record->overall_token_used ?? 0);
           
            return ($currentUsage + $neededTokens) <= $dailyLimit;
        } catch (\Throwable $e) {
            Log::warning('Token check failed', ['error' => $e->getMessage()]);
            return true;
        }
    }

    private function markUserMessageUnanswered(Request $request): void
    {
        try {
            $mongoConversation = $request->input('_mongo_conversation');
            
            if (!$mongoConversation) {
                $user = $request->user();
                if ($user) {
                    $mongoConversation = $this->conversationStorage->getOrCreateConversation(
                        $user->id,
                        $request->input('conversation_id')
                    );
                }
            }
            
            if ($mongoConversation) {
                $this->conversationStorage->markLastUserMessageAsUnanswered($mongoConversation);
                Log::info('Marked user message as unanswered', [
                    'conversation_id' => $mongoConversation->conversation_id ?? 'unknown'
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to mark message as unanswered', ['error' => $e->getMessage()]);
        }
    }

    private function isOffTopicResponse(string $response): bool
    {
        $offTopicPhrases = config('ai_chat.off_topic_phrases', []);
        $lowerResponse = strtolower($response);

        foreach ($offTopicPhrases as $phrase) {
            if (str_contains($lowerResponse, strtolower($phrase))) {
                return true;
            }
        }
        
        return false;
    }

    public function updateActionResult(Request $request): JsonResponse
    {
        $request->validate([
            'conversation_id' => 'required|string',
            'message_id' => 'nullable|string',
            'result' => 'required|string', // 'success', 'failed', 'completed'
            'question_id' => 'nullable|integer',
            'questionnaire_code' => 'nullable|string',
        ]);

        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $conversation = $this->conversationStorage->getOrCreateConversation(
                $user->id,
                $request->input('conversation_id')
            );

            $this->conversationStorage->updateActionStatus(
                $conversation,
                $request->input('message_id', 'unknown'),
                $request->input('result'),
                $request->input('question_id'),
                $request->input('questionnaire_code')
            );

            return response()->json([
                'message' => 'Action result updated successfully',
                'conversation_id' => $conversation->conversation_id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to update action result in MongoDB', [
                'error' => $e->getMessage(),
                'conversation_id' => $request->input('conversation_id'),
            ]);

            return response()->json([
                'message' => 'Failed to update action result',
            ], 500);
        }
    }

    public function sendMessage(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'message' => 'required|string|max:1000',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Request validation failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'I\'m sorry, there was an issue with your message. Please make sure your message is not too long and try again.',
            ], 200);
        }

        Log::info('CHAT CONTROLLER == Incoming message', [
            'user_id' => optional($request->user())->id,
            'user_email' => optional($request->user())->email,
            'message_preview' => mb_substr($request->message, 0, 200),
            'platform' => $request->platform,
            'websitePosition' => $request->websitePosition,
            'folder_id' => $request->folder,
            'survey_id' => $request->survey,
            'survey_uuid' => $request->surveyuuid,
            'all_request_data' => $request->all()
        ]);

        if ($this->isBulkCreationOveruse($request->message)) {
            return response()->json([
                'message' => "It looks like you're asking to create a large number of items at once. For safety and to avoid mistakes, please limit your request to a smaller batch. Would you like me to proceed with a smaller number or a single item?",
            ], 200);
        }
        $platform = 'express';

        try {
            $user = $request->user();
            if ($user) {
                $requestTokens = $this->estimateTokens($request->message);
                $replyBudget = 1000;
                $needed = max(0, $requestTokens + $replyBudget);
                if (!$this->hasTokenRemaining($user->id, $needed)) {
                    return response()->json([
                        'message' => "You've reached your daily AI token limit. Please try again tomorrow.",
                    ], 200);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('TokenManager pre-check failed', ['error' => $e->getMessage()]);
        }

        $structuredResponse = [];
        try {
            $lastAssistantMessage = null;
            try {
                $user = $request->user();
                if ($user) {
                    $memoryContext = $this->memoryService->getMemoryContext($user->id, 'express');
                    $buffer = $memoryContext['buffer'] ?? [];
                    // Get the last assistant message from buffer
                    for ($i = count($buffer) - 1; $i >= 0; $i--) {
                        if ($buffer[$i]['role'] === 'assistant') {
                            $lastAssistantMessage = $buffer[$i]['content'] ?? null;
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to get last assistant message for detection', ['error' => $e->getMessage()]);
            }
            
            $simpleGreeting = $this->detectSimpleGreetings($request->message);
            if ($simpleGreeting) {
                $structuredResponse = $simpleGreeting;
            } else {
                $structuredResponse = $this->detectIntentAndScope($request->message, $lastAssistantMessage);
            }
            $structuredResponse['questionnaire_code'] = $request->input('questionnaire_code');
        } catch (\Throwable $e) {
            Log::warning('Detection failed', ['error' => $e->getMessage()]);
            $structuredResponse = ['scope' => null, 'intent' => null];
        }


        if (config('app.debug') || env('CONV_MEMORY_DEBUG')) {
            Log::info('[ChatController] detection', $structuredResponse);
        }

        if (empty($structuredResponse['scope']) || empty($structuredResponse['intent'])) {
            return response()->json([
                'message' => 'I\'m sorry, I\'m not quite sure what you\'re asking for! I can only help you with questions related to EXPRESS, our survey platform. Could you please rephrase your request or ask about something related to EXPRESS?',
                'type' => 'chat'
            ], 200);
        }
        
        try {
            $this->promptService = new PromptService(
                $request->user(),
                $platform,
                $structuredResponse['scope'],
                $structuredResponse['intent'],
                [],
                null,
                $this->neo4jService
            );
        } catch (\Throwable $e) {
            Log::error('Failed to create PromptService', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'I\'m sorry, I encountered an error while processing your request. Please try again or rephrase your question about EXPRESS.',
            ], 200);
        }

        $mongoConversation = null;
        $startTime = microtime(true);
        try {
            $user = $request->user();
            if ($user) {
                $this->memoryService->appendMessage(
                    $user->id,
                    $platform,
                    'user',
                    $request->message
                );
                
                try {
                    $mongoConversation = $this->conversationStorage->getOrCreateConversation(
                        $user->id,
                        $request->input('conversation_id')
                    );
                    $this->conversationStorage->addUserMessage(
                        $mongoConversation,
                        $request->message,
                        $request->input('websitePosition', 'dashboard'),
                        [
                            'intent' => $structuredResponse['intent'] ?? 'conversation',
                            'scope' => $structuredResponse['scope'] ?? 'conversation',
                        ]
                    );
                } catch (\Throwable $e) {
                    Log::warning('MongoDB conversation storage failed (user message)', ['error' => $e->getMessage()]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Conversation memory pre-append failed', ['error' => $e->getMessage()]);
        }

        try {
            // Check if intent is an action - if action, handle locally
            // If not an action (conversation/guide), route to centralized helpbot
            $detectedIntent = $structuredResponse['intent'] ?? null;
            $isAction = $this->isActionIntent($detectedIntent);
            
            if ($isAction) {
                // Handle actions locally
                $response = match(true) {
                    $this->promptService->scope === 'survey' && $this->promptService->intent === 'create' => $this->handleSurveyGeneration($request),
                    default => $this->handleAction($request)
                };
            } else {
                // Route non-action intents (conversation/guide) to centralized helpbot
                $response = $this->routeToHelpbot($request);
                
                // COMMENTED OUT: Local handling of conversation/guide intents - now using centralized helpbot
                // $response = match(true) {
                //     $this->promptService->isForConversation() => $this->handleConversation($request),
                //     $this->promptService->isForGuide() => $this->handleGuide($request),
                //     $this->promptService->scope === 'survey' && $this->promptService->intent === 'create' => $this->handleSurveyGeneration($request),
                //     default => $this->handleAction($request)
                // };
            }
        } catch (\Throwable $e) {
            Log::error('Handler execution failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'I\'m sorry, I encountered an error while processing your request. Please try again or ask me something else about EXPRESS.',
            ], 200);
        }

        try {
            $payload = $response->getData(true);
            if (!empty($payload['message'])) {
                $user = $request->user();
                if ($user) {
                    $assistantMessage = is_string($payload['message']) ? $payload['message'] : json_encode($payload['message']);
                    
                    $this->memoryService->appendMessage(
                        $user->id,
                        $platform,
                        'assistant',
                        $assistantMessage
                    );
                    if (config('app.debug') || env('CONV_MEMORY_DEBUG')) {
                        Log::info('[ChatController] assistant_reply', [
                            'message_preview' => is_string($payload['message']) ? mb_substr($payload['message'], 0, 200) : '[json payload]'
                        ]);
                    }
                    $mongoConversationForSummary = $mongoConversation;
                    if (!$mongoConversationForSummary) {
                        try {
                            $mongoConversationForSummary = $this->conversationStorage->getOrCreateConversation(
                                $user->id,
                                $request->input('conversation_id')
                            );
                        } catch (\Throwable $e) {
                            Log::warning('Failed to get conversation for summary', ['error' => $e->getMessage()]);
                        }
                    }
                    $this->memoryService->summarizeIfNeeded($user->id, $platform, null, $mongoConversationForSummary);
                
                    if ($mongoConversation) {
                        try {
                            $responseTime = (int)((microtime(true) - $startTime) * 1000);
                            $result = $this->conversationStorage->addAssistantMessage(
                                $mongoConversation,
                                $assistantMessage,
                                $responseTime,
                                [
                                    'action_type' => $payload['action_type'] ?? null,
                                    'action_result' => $payload['action_result'] ?? null,
                                    'created_survey_id' => $payload['created_survey_id'] ?? null,
                                ]
                            );
                            $payload['message_id'] = $result['message_id'] ?? null;
                        } catch (\Throwable $e) {
                            Log::warning('MongoDB conversation storage failed (assistant message)', ['error' => $e->getMessage()]);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Conversation memory post-append failed', ['error' => $e->getMessage()]);
        }

        if ($mongoConversation) {
            $payload = $response->getData(true);
            $payload['conversation_id'] = $mongoConversation->conversation_id;
            $response->setData($payload);
        }

        return $response;
    }

    public function clear(Request $request): JsonResponse
    {
        $request->validate([
            'platform' => 'nullable|string',
            'conversation_id' => 'nullable|string',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $this->memoryService->clearMemory($user->id, $request->input('platform'));

            // Also clear memory context cache key, for extra safety
            $cacheKey = 'memory_context_' . $user->id . '_' . ($request->input('platform') ?? 'express');
            \Illuminate\Support\Facades\Cache::forget($cacheKey);

            $conversationId = $request->input('conversation_id');
            if ($conversationId) {
                $result = $this->conversationStorage->clearConversationById(
                    $user->id,
                    $conversationId
                );
                if ($result === null) {
                    Log::warning('[ChatController] Clear failed: conversation not found', [
                        'conversation_id' => $conversationId,
                        'user_id' => $user->id,
                    ]);
                    return response()->json(['message' => 'Conversation not found'], 404);
                }
                Log::info('[ChatController] Cleared specific MongoDB conversation', [
                    'conversation_id' => $conversationId,
                    'user_id' => $user->id,
                ]);
            } else {
                $count = $this->conversationStorage->clearAllConversations($user->id);
                Log::info('[ChatController] Cleared all MongoDB conversations', [
                    'count' => $count,
                    'user_id' => $user->id,
                ]);
            }
        } catch (\Throwable $e) { 
            
            Log::warning('Failed to clear conversation memory', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to clear conversation'], 500);
        }

        return response()->json(['message' => 'Conversation cleared']);
    }

    public function addFeedback(Request $request): JsonResponse
    {
        $request->validate([
            'conversation_id' => 'required|string',
            'message_id' => 'nullable|string',
            'type' => 'required|in:like,dislike',
            'category' => 'nullable|in:harmful,not_accurate,not_useful_or_incomplete,something_else',
            'comment' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $conversation = \App\Models\AI\AIExpressConversation::where('conversation_id', $request->conversation_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$conversation) {
                return response()->json(['message' => 'Conversation not found'], 404);
            }

            $conversation->refresh();

            $this->conversationStorage->addFeedback(
                $conversation,
                $request->type,
                $request->category,
                $request->comment,
                $request->message_id
            );

            Log::info('ChatController == Feedback added', [
                'conversation_id' => $request->conversation_id,
                'type' => $request->type,
                'category' => $request->category,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Feedback recorded successfully',
                'feedback_summary' => $conversation->feedback_summary,
            ]);
        } catch (\Throwable $e) {
            Log::error('ChatController == Failed to add feedback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Failed to record feedback'], 500);
        }
    }

    public function addConversationRating(Request $request): JsonResponse
    {
        $request->validate([
            'conversation_id' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $conversation = \App\Models\AI\AIExpressConversation::where('conversation_id', $request->conversation_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$conversation) {
                return response()->json(['message' => 'Conversation not found'], 404);
            }

            $this->conversationStorage->addConversationRating(
                $conversation,
                $request->rating,
                $request->comment
            );

            Log::info('[ChatController] Conversation rating added', [
                'conversation_id' => $request->conversation_id,
                'rating' => $request->rating,
                'has_comment' => !empty($request->comment),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your feedback!',
                'rating' => $conversation->overall_convo_feedback,
            ]);
        } catch (\Throwable $e) {
            Log::error('[ChatController] Failed to add conversation rating', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Failed to record rating'], 500);
        }
    }

    public function getConversationHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $conversations = \App\Models\AI\AIExpressConversation::where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->where('metadata.isCleared', '!=', true)
                      ->orWhereNull('metadata.isCleared');
                })
                ->orderBy('updated_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'conversations' => $conversations,
            ]);
        } catch (\Throwable $e) {
            Log::error('[ChatController] Failed to get conversation history', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to get conversation history'], 500);
        }
    }

    /**
     * Get a single conversation by conversation_id (with messages) for switching chats.
     */
    public function getConversation(Request $request): JsonResponse
    {
        $request->validate([
            'conversation_id' => 'required|string',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $conversation = \App\Models\AI\AIExpressConversation::where('user_id', $user->id)
                ->where('conversation_id', $request->conversation_id)
                ->whereNull('deleted_at')
                ->first();

            if (!$conversation) {
                return response()->json(['message' => 'Conversation not found'], 404);
            }

            return response()->json([
                'success' => true,
                'conversation' => $conversation,
            ]);
        } catch (\Throwable $e) {
            Log::error('[ChatController] Failed to get conversation', [
                'error' => $e->getMessage(),
                'conversation_id' => $request->conversation_id,
            ]);
            return response()->json(['message' => 'Failed to load conversation'], 500);
        }
    }

    /**
     * Append an assistant message to a conversation (e.g. after helpbot stream completes).
     */
    public function appendAssistantMessage(Request $request): JsonResponse
    {
        $request->validate([
            'conversation_id' => 'required|string',
            'content' => 'required|string',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $conversation = \App\Models\AI\AIExpressConversation::where('user_id', $user->id)
                ->where('conversation_id', $request->conversation_id)
                ->whereNull('deleted_at')
                ->first();

            if (!$conversation) {
                return response()->json(['message' => 'Conversation not found'], 404);
            }

            $this->conversationStorage->addAssistantMessage(
                $conversation,
                $request->content,
                0,
                ['scope' => 'conversation', 'intent' => 'conversation']
            );

            return response()->json([
                'success' => true,
                'conversation_id' => $conversation->conversation_id,
            ]);
        } catch (\Throwable $e) {
            Log::error('[ChatController] Failed to append assistant message', [
                'error' => $e->getMessage(),
                'conversation_id' => $request->conversation_id,
            ]);
            return response()->json(['message' => 'Failed to save message'], 500);
        }
    }

    /**
     * Explicitly create a new empty conversation document in MongoDB.
     *
     * This is used by the "New chat" button so that a real conversation
     * record exists immediately, even before the first message is sent.
     */
    public function createConversation(Request $request): JsonResponse
    {
        $request->validate([
            'platform' => 'nullable|string',
            'title' => 'nullable|string|max:100',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $platform = $request->input('platform', 'express');
            $rawTitle = trim((string) $request->input('title', ''));

            // Use the central ConversationStorageService so that ALL conversations
            // are created in one consistent way (single document per chat) and
            // always use the same conversation_id format (conv_XXXX...).
            // Pass (userId, platform, conversationId=null, title); default title is EXPRESS AI.
            $title = $rawTitle !== '' ? $rawTitle : 'EXPRESS AI';
            $conversation = $this->conversationStorage->createConversation(
                $user->id,
                $platform,
                null,
                $title
            );

            return response()->json([
                'success' => true,
                'conversation' => $conversation,
            ]);
        } catch (\Throwable $e) {
            Log::error('[ChatController] Failed to create conversation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Failed to create conversation'], 500);
        }
    }

    public function updateConversationTitle(Request $request): JsonResponse
    {
        $request->validate([
            'conversation_id' => 'required|string',
            'title' => 'nullable|string|max:100',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $conversationId = $request->conversation_id;

        try {
            $conversation = \App\Models\AI\AIExpressConversation::where('user_id', $user->id)
                ->where(function ($q) use ($conversationId) {
                    $q->where('conversation_id', $conversationId);

                    if (is_string($conversationId) && strlen($conversationId) === 24) {
                        try {
                            $q->orWhere('_id', new ObjectId($conversationId));
                        } catch (\Throwable $e) {
                        }
                    }
                })
                ->first();

            if (!$conversation) {
                return response()->json(['message' => 'Conversation not found'], 404);
            }

            $title = trim($request->title ?? '');
            $conversation->title = $title === '' ? null : $title;
            $conversation->save();

            Log::info('[ChatController] Conversation title updated', [
                'conversation_id' => $conversationId,
                'title' => $conversation->title,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Title updated successfully',
                'title' => $conversation->title,
            ]);
        } catch (\Throwable $e) {
            Log::error('[ChatController] Failed to update conversation title', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Failed to update title'], 500);
        }
    }

    /**
     * Pin or unpin a conversation.
     *
     * The latest pinned conversation should appear at the top of the list,
     * so we track a pinned_at timestamp in metadata.
     */
    public function pinConversation(Request $request): JsonResponse
    {
        $request->validate([
            'conversation_id' => 'required|string',
            'pinned' => 'nullable|boolean',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $conversationId = $request->conversation_id;

        try {
            $conversation = \App\Models\AI\AIExpressConversation::where('user_id', $user->id)
                ->where('conversation_id', $conversationId)
                ->whereNull('deleted_at')
                ->first();

            if (!$conversation) {
                return response()->json(['message' => 'Conversation not found'], 404);
            }

            $metadata = $conversation->metadata ?? [];
            if (!is_array($metadata)) {
                $metadata = (array) $metadata;
            }

            $shouldPin = $request->has('pinned')
                ? $request->boolean('pinned')
                : empty($metadata['pinned_at']);

            if ($shouldPin) {
                $metadata['pinned_at'] = Carbon::now()->toIso8601String();
            } else {
                unset($metadata['pinned_at']);
            }

            $conversation->metadata = $metadata;
            $conversation->save();

            return response()->json([
                'success' => true,
                'pinned' => $shouldPin,
                'pinned_at' => $metadata['pinned_at'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('[ChatController] Failed to pin conversation', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversationId,
            ]);
            return response()->json(['message' => 'Failed to update pin state'], 500);
        }
    }

    private function detectSimpleGreetings(string $message): ?array
    {
        $message = strtolower(trim($message));
        
        $greetingPatterns = config('ai_detection.greeting_patterns', []);
        foreach ($greetingPatterns as $greeting) {
            if ($message === strtolower($greeting)) {
                Log::info('Fast-path: Simple greeting detected', [
                    'message' => $message,
                    'matched' => $greeting
                ]);
                return ['scope' => 'conversation', 'intent' => 'conversation'];
            }
        }
        
        foreach ($greetingPatterns as $greeting) {
            $greetingLower = strtolower($greeting);
            if (str_starts_with($message, $greetingLower)) {
                $remaining = trim(substr($message, strlen($greetingLower)));
                $simpleFollowUps = ['', '!', '?', '!?', '?!', ',', '.', ' there', ' there!', ' there?'];
                $followUpPatterns = ['how are you', 'how\'re you', 'howre you', 'what\'s up', 'whats up', 'sup'];
                
                if (empty($remaining) || in_array($remaining, $simpleFollowUps)) {
                    Log::info('Fast-path: Simple greeting detected (with punctuation)', [
                        'message' => $message,
                        'matched' => $greeting
                    ]);
                    return ['scope' => 'conversation', 'intent' => 'conversation'];
                }
                
                foreach ($followUpPatterns as $followUp) {
                    if (str_starts_with($remaining, ' ' . $followUp) || str_starts_with($remaining, $followUp)) {
                        Log::info('Fast-path: Simple greeting with follow-up detected', [
                            'message' => $message,
                            'matched' => $greeting,
                            'follow_up' => $followUp
                        ]);
                        return ['scope' => 'conversation', 'intent' => 'conversation'];
                    }
                }
            }
        }
        return null;
    }

    private function isQuickResponse(string $message): bool
    {
        $message = strtolower(trim($message));
        $quickPatterns = ['hi', 'hello', 'greetings', 'thanks', 'thank you', 'hey', 'hiya', 'howdy', 'sup', 'yo', 'what\'s up', 'whats up'];
        $shortAcknowledgments = ['ok', 'okay', 'yes', 'no', 'sure', 'yep', 'nope', 'alright', 'got it', 'k', 'kk'];
        foreach ($shortAcknowledgments as $ack) {
            if ($message === $ack || $message === $ack . '!' || $message === $ack . '?' ||
                str_starts_with($message, $ack . ' ') || str_starts_with($message, $ack . ',')) {
                return true;
            }
        }
        foreach ($quickPatterns as $pattern) {
            if ($message === $pattern ||
                $message === $pattern . '!' ||
                $message === $pattern . '?' ||
                str_starts_with($message, $pattern . ' ') ||
                str_starts_with($message, $pattern . '!') ||
                str_starts_with($message, $pattern . '?')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Persist platform-handled quick response (user message + assistant reply) to MongoDB.
     */
    private function persistPlatformQuickResponse(\Illuminate\Http\Request $request, string $assistantContent): void
    {
        try {
            $user = $request->user();
            if (!$user) {
                return;
            }
            $platform = 'express';
            $mongoConversation = $this->conversationStorage->getOrCreateConversation(
                $user->id,
                $request->input('conversation_id'),
                $platform
            );
            $this->conversationStorage->addUserMessage(
                $mongoConversation,
                $request->message,
                $request->input('websitePosition', 'dashboard'),
                ['intent' => 'conversation', 'scope' => 'conversation']
            );
            $this->conversationStorage->addAssistantMessage(
                $mongoConversation,
                $assistantContent,
                0,
                ['intent' => 'conversation', 'scope' => 'conversation']
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[ChatController] Failed to persist platform quick response', ['error' => $e->getMessage()]);
        }
    }

    private function getQuickResponse(string $message, array $userInfo): string
    {
        $message = strtolower(trim($message));
        $firstName = $userInfo['firstName'] ?? '';
        $nameSuffix = $firstName ? " {$firstName}" : "";
        
        if (str_starts_with($message, 'thanks') || str_starts_with($message, 'thank you')) {
            return "You're welcome{$nameSuffix}! How can I help you with EXPRESS today?";
        }
        $shortAcknowledgments = ['ok', 'okay', 'yes', 'no', 'sure', 'yep', 'nope', 'alright', 'got it', 'k', 'kk'];
        foreach ($shortAcknowledgments as $ack) {
            if ($message === $ack || $message === $ack . '!' || $message === $ack . '?' ||
                str_starts_with($message, $ack . ' ') || str_starts_with($message, $ack . ',')) {
                return "Got it{$nameSuffix}! What would you like to do next?";
            }
        }
        if (str_starts_with($message, 'hi') || str_starts_with($message, 'hello') || str_starts_with($message, 'hey') || 
            str_starts_with($message, 'greetings') || str_starts_with($message, 'howdy') || 
            str_starts_with($message, 'hiya') || $message === 'sup' || $message === 'yo' ||
            str_starts_with($message, 'what\'s up') || str_starts_with($message, 'whats up')) {
            return "Hello{$nameSuffix}! I'm here to help you with EXPRESS, our survey platform. What would you like to know?";
        }
        
        return "Hello{$nameSuffix}! How can I help you with EXPRESS today?";
    }

    //Detect intent and scope from user message
    private function detectIntentAndScope(string $message, ?string $lastAssistantMessage = null, ?array $embedding = null): array
    {
        try {
            $surveyPattern = $this->detectSurveyCreation($message);
            if ($surveyPattern) {
                return $surveyPattern;
            }
            
            $duplicatePattern = $this->detectDuplicateIntent($message);
            if ($duplicatePattern) {
                return $duplicatePattern;
            }
            
            $movePattern = $this->detectMoveIntent($message);
            if ($movePattern) {
                return $movePattern;
            }
            
            if (!$embedding) {
                $embedding = $this->chatService->getEmbedding($message);
            }
            
            $promptMatch = $this->neo4jService->checkPromptMatch($embedding, 0.85);
            if ($promptMatch) {
                Log::info(' Neo4j prompt match found', [
                    'message' => $message,
                    'prompt_name' => $promptMatch['node']['name'] ?? 'unknown',
                    'score' => $promptMatch['score'],
                    'detected_intent' => 'guide',
                    'detected_scope' => 'conversation'
                ]);
                return ['scope' => 'conversation', 'intent' => 'guide', 'details' => null];
            }
            $detectionPrompt = $message;
            if ($lastAssistantMessage) {
                $detectionPrompt = "Previous AI message: {$lastAssistantMessage}\n\nUser message: {$message}\n\nDetermine the intent and scope based on both messages.";
                Log::info('[detectIntentAndScope] Enhanced detection with last AI message', [
                    'user_message' => $message,
                    'last_ai_preview' => mb_substr($lastAssistantMessage, 0, 150)
                ]);
            }
            
            $schema = $this->buildDetectionSchema();
            $request = Prism::structured()
                ->using(...AIModel::getSummaryConfig())
                ->withSchema($schema)
                ->withPrompt($detectionPrompt)
                ->usingTemperature(0.1);

            info($detectionPrompt);
            $response = $request->asStructured();
            $geminiResult = $response->structured ?? [];
            
            $geminiScope = $geminiResult['scope'] ?? null;
            $geminiIntent = $geminiResult['intent'] ?? null;
            
            $validScopes = Scope::values();
            $validIntents = Intent::values();
            
            $isValidScope = $geminiScope && in_array($geminiScope, $validScopes);
            $isValidIntent = $geminiIntent && in_array($geminiIntent, $validIntents);
            
            Log::info('Gemini detection result', [
                'message' => $message,
                'last_ai_message' => $lastAssistantMessage ? mb_substr($lastAssistantMessage, 0, 100) : null,
                'gemini_scope' => $geminiScope ?? 'null',
                'gemini_intent' => $geminiIntent ?? 'null',
                'gemini_details' => $geminiResult['details'] ?? 'null',
                'is_valid_scope' => $isValidScope,
                'is_valid_intent' => $isValidIntent,
                'raw_response' => json_encode($geminiResult)
            ]);
            
            $structured = $geminiResult;

            if ($lastAssistantMessage && (
                str_contains(strtolower($lastAssistantMessage), 'could you please tell me what the question should be about') ||
                str_contains(strtolower($lastAssistantMessage), 'what the question should be about') ||
                str_contains(strtolower($lastAssistantMessage), 'what topic or information')
            )) {
                $isTopicOnly = preg_match('/^(about|regarding|on|for|concerning)\s+/i', strtolower($message)) || 
                               (strlen(strtolower($message)) < 50 && !preg_match('/\b(what|how|when|where|why|who|which|create|make|add|build|generate|design|question|questions)\b/i', strtolower($message)));
                
                if ($isTopicOnly) {
                    Log::info('[detectIntentAndScope] Detected topic-only response after AI asked for details - likely pending question creation', [
                        'user_message' => $message,
                        'last_ai_preview' => mb_substr($lastAssistantMessage, 0, 150)
                    ]);
                }
            }
            
            $questionPhrasePatterns = ['can i', 'is it possible', 'am i able', 'do you support', 'how can i', 'how do i', 'what is', 'what are', 'how to', 'show me how', 'explain', 'tell me about', 'how about', 'what about', 'how does'];
            $messageLower = strtolower(trim($message));
            $isPhrasedAsQuestion = str_contains($message, '?') || $this->chatService->isQuestion($message);
            foreach ($questionPhrasePatterns as $pattern) {
                if (str_starts_with($messageLower, $pattern)) {
                    $isPhrasedAsQuestion = true;
                    break;
                }
            }

            if ($geminiIntent === 'create' && $isPhrasedAsQuestion) {
                foreach ($questionPhrasePatterns as $pattern) {
                    if (str_starts_with($messageLower, $pattern)) {
                        Log::info('Override: Question phrased but Gemini said create, correcting to guide', [
                            'message' => $message,
                            'gemini_intent' => $geminiIntent,
                            'corrected_intent' => 'guide'
                        ]);
                        $structured['intent'] = 'guide';
                        $structured['scope'] = $structured['scope'] ?? 'conversation';
                        break;
                    }
                }
            }

            $actionIntentsAsQuestionBecomesGuide = ['update', 'delete', 'duplicate', 'move'];
            if (in_array($geminiIntent, $actionIntentsAsQuestionBecomesGuide) && $isPhrasedAsQuestion) {
                foreach ($questionPhrasePatterns as $pattern) {
                    if (str_starts_with($messageLower, $pattern)) {
                        Log::info('Override: Question phrased but Gemini said action, correcting to guide for helpbot', [
                            'message' => $message,
                            'gemini_intent' => $geminiIntent,
                            'corrected_intent' => 'guide'
                        ]);
                        $structured['intent'] = 'guide';
                        $structured['scope'] = 'conversation';
                        break;
                    }
                }
            }

            if (!$isValidScope || !$isValidIntent) {
                Log::info('Gemini returned invalid/empty result, using pattern fallback', [
                    'message' => $message,
                    'gemini_result' => $structured,
                    'invalid_scope' => !$isValidScope,
                    'invalid_intent' => !$isValidIntent,
                    'valid_scopes' => $validScopes,
                    'valid_intents' => $validIntents
                ]);
                if ($lastAssistantMessage && (
                    str_contains(strtolower($lastAssistantMessage), 'could you please tell me what the question should be about') ||
                    str_contains(strtolower($lastAssistantMessage), 'what the question should be about')
                )) {
                    $isTopicOnly = preg_match('/^(about|regarding|on|for|concerning)\s+/i', strtolower($message)) || 
                                   (strlen(strtolower($message)) < 50 && !preg_match('/\b(what|how|when|where|why|who|which|create|make|add|build|generate|design|question|questions)\b/i', strtolower($message)));
                    if ($isTopicOnly) {
                        Log::info('Pattern fallback: Detected topic response after AI asked for details - flagging for pending question check', [
                            'user_message' => $message,
                            'last_ai_preview' => mb_substr($lastAssistantMessage, 0, 150)
                        ]);
                    }
                }
                
                if ($this->chatService->isQuestion($message)) {
                    $structured = ['scope' => 'conversation', 'intent' => 'guide'];
                    Log::info('Pattern fallback: detected as question', ['fallback_result' => $structured]);
                } else {
                    $structured = ['scope' => 'conversation', 'intent' => 'conversation'];
                    Log::info(' Pattern fallback: detected as conversation', ['fallback_result' => $structured]);
                }
            } else {
                Log::info(' Using Gemini detection (trusted)', [
                    'message' => $message,
                    'final_scope' => $structured['scope'],
                    'final_intent' => $structured['intent']
                ]);
            }
            
            $confirmationResponse = $this->detectConfirmationResponse($message);
            $hasConfirmation = false;
            $lastMessageIsConfirmationQuestion = $this->isLastMessageConfirmationQuestion($lastAssistantMessage);
            if ($confirmationResponse && $lastMessageIsConfirmationQuestion) {
                $hasConfirmation = true;
                Log::info(' Override: Confirmation response detected (previous AI asked for confirmation)', [
                    'message' => $message,
                    'gemini_scope' => $structured['scope'] ?? 'null',
                    'gemini_intent' => $structured['intent'] ?? 'null',
                    'override_result' => $confirmationResponse
                ]);
                $structured = $confirmationResponse;
            }
            
            Log::info(' Final detection result', [
                'message' => $message,
                'final_scope' => $structured['scope'],
                'final_intent' => $structured['intent'],
                'final_details' => $structured['details'] ?? null,
                'source' => $promptMatch ? 'neo4j_prompt' : ((!$isValidScope || !$isValidIntent) ? 'pattern_fallback' : ($hasConfirmation ? 'confirmation_override' : 'gemini'))
            ]);
            
            return $structured;
        } catch (\Throwable $e) {
            Log::error(' detectIntentAndScope error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'message' => $message
            ]);
            try {
                Log::info('Error fallback: Using pattern detection', ['message' => $message]);
                if ($this->chatService->isQuestion($message)) {
                    return ['scope' => 'conversation', 'intent' => 'guide'];
                }
                return ['scope' => 'conversation', 'intent' => 'conversation'];
            } catch (\Throwable $fallbackError) {
                Log::error('Fallback detection also failed', ['error' => $fallbackError->getMessage()]);
                return ['scope' => 'conversation', 'intent' => 'conversation'];
            }
        }
    }

    private function isInstructionSeekingQuestion(string $message): bool
    {
        $message = strtolower(trim($message));
        
        $instructionPatterns = config('ai_detection.instruction_patterns', []);
        foreach ($instructionPatterns as $pattern) {
            if (str_contains($message, $pattern)) {
                return true;
            }
        }
        
        $questionWords = config('ai_detection.question_words', []);
        $hasQuestionWord = false;
        foreach ($questionWords as $word) {
            if (str_starts_with($message, $word)) {
                $hasQuestionWord = true;
                break;
            }
        }
        
        if ($hasQuestionWord && str_contains($message, '?')) {
            $commandPatterns = config('ai_detection.command_patterns', []);
            $isCommand = false;
            foreach ($commandPatterns as $command) {
                if (str_contains($message, $command)) {
                    $isCommand = true;
                    break;
                }
            }
            
            if (!$isCommand) {
                return true;
            }
        }
        
        $specificPatterns = config('ai_detection.specific_question_patterns', []);
        foreach ($specificPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }
        
        if (preg_match('/^(can i|am i able to|is it possible to|do you support).*(create|make|add|build|generate|design)/i', $message) && str_contains($message, '?')) {
            return true;
        }
        return false;
    }
    
    private function detectQuestionTypeInMessage(string $message): ?string
    {
        $message = strtolower(trim($message));
        $questionTypeMap = config('ai_express.question_type_display_names', []);
        foreach ($questionTypeMap as $pattern => $displayName) {
            if (str_contains($message, $pattern)) {
                Log::info('[detectQuestionTypeInMessage] Found question type in message', [
                    'pattern' => $pattern,
                    'displayName' => $displayName,
                    'message' => $message
                ]);
                return $displayName;
            }
        }
       
        $questionTypeToScope = config('ai_express.question_type_to_scope', []);
        foreach ($questionTypeToScope as $type => $scopeMapping) {
            if (str_contains($message, $type)) {
                $displayName = $questionTypeMap[$type] ?? str_replace('_', ' ', $type);
                if ($displayName) {
                    Log::info('[detectQuestionTypeInMessage] Found question type via scope mapping', [
                        'type' => $type,
                        'displayName' => $displayName,
                        'message' => $message
                    ]);
                    return $displayName;
                }
            }
        } 
        return null;
    }
    private function detectQuestionTypeResponse(string $message): ?array
    {
        $message = strtolower(trim($message));
        if ($this->isInstructionSeekingQuestion($message)) {
            return null;
        }
    
        $specificPatterns = config('ai_detection.specific_question_patterns', []);
        foreach ($specificPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return null;
            }
        }
        
        if (preg_match('/can i ask what is a? .+ question/i', $message) || 
            preg_match('/can i ask what are .+ questions/i', $message) ||
            preg_match('/can you tell me what is a? .+ question/i', $message) ||
            preg_match('/can you explain what is a? .+ question/i', $message)) {
            return null; 
        }
        
        if (preg_match('/^(can i|am i able to|is it possible to|do you support).*(create|make|add|build|generate|design)/i', $message) && str_contains($message, '?')) {
            return null;
        }
        
        $questionPatterns = config('ai_detection.question_patterns', []);
        foreach ($questionPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return null;
            }
        }
        
        $questionTypeMap = config('ai_detection.question_type_map', []);
        foreach ($questionTypeMap as $pattern => $scope) {
            if (str_contains($message, $pattern)) {
                return ['scope' => $scope, 'intent' => 'create'];
            }
        }
        return null;
    }

    private function detectSurveyCreation(string $message): ?array
    {
        $messageLower = strtolower(trim($message));
        $surveyKeywords = ['create a survey', 'create survey', 'generate a survey', 'generate survey', 'make a survey', 'build a survey'];
        
        foreach ($surveyKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                Log::info('Survey creation detected', ['message' => $message]);
                return ['scope' => 'survey', 'intent' => 'create', 'details' => $message];
            }
        }
        
        return null;
    }

    private function detectDuplicateIntent(string $message): ?array
    {
        $messageLower = strtolower(trim($message));
        
        $duplicateKeywords = ['duplicate', 'copy', 'clone'];
        $questionKeywords = ['question', 'questions'];
        
        $hasDuplicateKeyword = false;
        $hasQuestionKeyword = false;
        
        foreach ($duplicateKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                $hasDuplicateKeyword = true;
                break;
            }
        }
        
        foreach ($questionKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                $hasQuestionKeyword = true;
                break;
            }
        }
        
        if ($hasDuplicateKeyword && $hasQuestionKeyword) {
            Log::info('Duplicate question intent detected', ['message' => $message]);
            return ['scope' => 'question', 'intent' => 'duplicate', 'details' => $message];
        }
        
        return null;
    }

    private function detectMoveIntent(string $message): ?array
    {
        $messageLower = strtolower(trim($message));
        
        $moveKeywords = ['move', 'reorder', 'reposition'];
        $hasMoveKeyword = false;
        
        foreach ($moveKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                $hasMoveKeyword = true;
                break;
            }
        }
        
        if (!$hasMoveKeyword) {
            return null;
        }
        
        // Patterns to match: "Move Q3 to Q1", "move question 2 to question 3", "move Q5 to position 1"
        $patterns = [
            '/move\s+(?:q|question)\s*(\d+)\s+to\s+(?:q|question)\s*(\d+)/i',
            '/move\s+(?:q|question)\s*(\d+)\s+to\s+position\s+(\d+)/i',
            '/reorder\s+(?:q|question)\s*(\d+)\s+to\s+(?:q|question)\s*(\d+)/i',
            '/reposition\s+(?:q|question)\s*(\d+)\s+to\s+(?:q|question)\s*(\d+)/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $sourceQuestion = (int)$matches[1];
                $targetQuestion = (int)$matches[2];
                
                if ($sourceQuestion > 0 && $targetQuestion > 0 && $sourceQuestion !== $targetQuestion) {
                    Log::info('Move question intent detected', [
                        'message' => $message,
                        'source_question' => $sourceQuestion,
                        'target_question' => $targetQuestion
                    ]);
                    return [
                        'scope' => 'question',
                        'intent' => 'move',
                        'details' => $message,
                        'source_question' => $sourceQuestion,
                        'target_question' => $targetQuestion
                    ];
                }
            }
        }
        
        // If move keyword exists but no pattern matched, still return move intent
        // The stream handler will parse it or ask for clarification
        if ($hasMoveKeyword && (str_contains($messageLower, 'question') || preg_match('/\bq\d+\b/i', $message))) {
            Log::info('Move question intent detected (unparsed)', ['message' => $message]);
            return ['scope' => 'question', 'intent' => 'move', 'details' => $message];
        }
        
        return null;
    }

    /**
     * Check if the detected intent is an action that should be handled locally
     */
    private function isActionIntent(?string $intent): bool
    {
        if (!$intent) {
            return false;
        }
        
        $actionIntents = ['create', 'update', 'delete', 'copy', 'move', 'navigate', 'list', 'manage', 'duplicate'];
        return in_array($intent, $actionIntents);
    }

    /**
     * Route non-action prompts to centralized helpbot API
     */
    private function routeToHelpbot(Request $request): JsonResponse
    {
        try {
            $helpbotUrl = config('services.ai_helpbot.url', env('VITE_AI_HELPBOT_URL'));
            $apiUrl = rtrim($helpbotUrl, '/') . '/api/chat/message';
            
            $initialIntent = $this->promptService->intent ?? null;

            Log::info('[ChatController] Routing to centralized helpbot', [
                'helpbot_url' => $apiUrl,
                'user_id' => optional($request->user())->id,
                'message_preview' => mb_substr($request->message, 0, 200),
                'initial_intent' => $initialIntent,
            ]);

            $requestData = [
                'prompt' => $request->message,
                'platform' => 'express',
                'websitePosition' => $request->input('websitePosition', 'dashboard'),
                'initial_intent' => $initialIntent,
            ];

            // Use X-User-Id header for helpbot auth (same approach as AIStreamController and frontend)
            $user = $request->user();
            if (! $user) {
                Log::warning('[ChatController] routeToHelpbot: no authenticated user');
                return response()->json(['message' => 'Please log in to use the help service.'], 401);
            }
            $headers = [
                'X-User-Id' => (string) $user->id,
                'X-Requested-With' => 'XMLHttpRequest',
            ];

            // Make request to helpbot API
            Log::info('[ChatController] Sending to helpbot', [
                'url' => $apiUrl,
                'request_data' => $requestData,
            ]);
            
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post($apiUrl, $requestData);
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('[ChatController] Helpbot returns this', [
                    'response_data' => $responseData,
                ]);
                
                // Check if helpbot detected an action and wants to route back
                if (isset($responseData['action_required']) && $responseData['action_required'] === true) {
                    Log::info('[ChatController] Helpbot detected action, handling locally', [
                        'message' => $request->message,
                    ]);
                    // If helpbot says it's an action, handle it locally
                    return $this->handleAction($request);
                }
                
                // Return helpbot's response
                return response()->json([
                    'message' => $responseData['message'] ?? 'I\'m sorry, I couldn\'t process your request.',
                    'platform' => $responseData['platform'] ?? 'express',
                ]);
            } else {
                Log::error('[ChatController] Helpbot API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return response()->json([
                    'message' => 'I\'m sorry, I encountered an error connecting to the help service. Please try again.',
                ], 200);
            }
        } catch (ConnectionException $e) {
            Log::error('[ChatController] Helpbot connection error', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'I\'m having trouble connecting to the help service. Please try again in a moment.',
            ], 200);
        } catch (\Throwable $e) {
            Log::error('[ChatController] Helpbot routing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'I\'m sorry, I encountered an error. Please try again.',
            ], 200);
        }
    }

    /**
     * Whether the last assistant message was asking the user for confirmation (e.g. "Would you like me to create...?").
     * Only when this is true should we treat a short "yes/okay/sure" as intent create.
     */
    private function isLastMessageConfirmationQuestion(?string $lastAssistantMessage): bool
    {
        if ($lastAssistantMessage === null || $lastAssistantMessage === '') {
            return false;
        }
        $lower = strtolower($lastAssistantMessage);
        $confirmationQuestionPhrases = [
            'would you like me to',
            'should i ',
            'shall i ',
            'do you want me to',
            'do you want to',
            'could you please confirm',
            'please confirm',
            'can i create',
            'would you like to',
            'shall i create',
            'should i create',
            'do you want me to create',
            'would you like me to create',
        ];
        foreach ($confirmationQuestionPhrases as $phrase) {
            if (str_contains($lower, $phrase)) {
                return true;
            }
        }
        return false;
    }

    private function detectConfirmationResponse(string $message): ?array
    {
        $message = strtolower(trim($message));
        if ($this->isInstructionSeekingQuestion($message)) {
            return null;
        }
        
        $confirmationPatterns = config('ai_detection.confirmation_patterns', []);
        foreach ($confirmationPatterns as $pattern) {
            if ($message === $pattern) {
                return ['scope' => 'conversation', 'intent' => 'create'];
            }
        }
        return null;
    }

    private function isConfirmationResponse(string $message): bool
    {
        $confirmationResponse = $this->detectConfirmationResponse($message);
        return $confirmationResponse !== null;
    }

    private function handleConfirmation(Request $request, $user): ?JsonResponse
    {
        try {
            $memoryContext = $this->memoryService->getMemoryContext($user->id, $this->promptService->platform);
            $buffer = $memoryContext['buffer'] ?? [];
            $previousAction = null;
            $questionType = null;
            for ($i = count($buffer) - 1; $i >= 0; $i--) {
                $message = $buffer[$i];
                if ($message['role'] === 'assistant') {
                    $content = $message['content'];
                    if (str_contains($content, 'preview_data')) {
                        $actionData = $this->extractActionDataFromMessage($content);
                        if ($actionData) {
                            $previousAction = $actionData;
                            break;
                        }
                    }
                    elseif (str_contains($content, 'Would you like me to create')) {
                        $questionType = $this->extractQuestionTypeFromCreationOffer($content);
                        if ($questionType) {
                            $previousAction = $this->createActionFromQuestionType($questionType);
                            break;
                        }
                    }
                }
            }
            
            if (!$previousAction) {
                Log::info('No previous action found for confirmation', [
                    'user_id' => $user->id,
                    'message' => $request->message
                ]);
                return null;
            }
            
            Log::info('Found previous action for confirmation', [
                'user_id' => $user->id,
                'action' => $previousAction['action'] ?? 'unknown',
                'scope' => $previousAction['scope'] ?? 'unknown'
            ]);

            if ($this->isSimpleConfirmationWithoutDetails($request->message)) {
                $questionTypeDisplay = $questionType ?? $this->getQuestionTypeDisplayName($previousAction['scope'] ?? 'question');
                $firstName = $this->getUserTypeInfo($request)['firstName'];
                $nameSuffix = $firstName ? " {$firstName}" : "";
                
                return response()->json([
                    'message' => "Great{$nameSuffix}! I'd be happy to create a {$questionTypeDisplay} question for you. Could you please tell me what the question should be about? For example, what topic or information are you trying to gather?",
                    'type' => 'chat',
                    'awaiting_details' => true,
                    'pending_action' => $previousAction
                ]);
            }
            return null;
            
        } catch (\Throwable $e) {
            Log::error('Confirmation handling failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    private function isSimpleConfirmationWithoutDetails(string $message): bool
    {
        $message = strtolower(trim($message));
        $simpleConfirmations = config('ai_detection.confirmation_patterns', []);
        
        if (in_array($message, $simpleConfirmations)) {
            return true;
        }
        $questionTypeMap = config('ai_express.question_type_display_names', []);
        $hasQuestionType = false;
        foreach ($questionTypeMap as $pattern => $displayName) {
            if (str_contains($message, $pattern)) {
                $hasQuestionType = true;
                break;
            }
        }
        if ($hasQuestionType && strlen($message) < 30) {
            return true;
        }
        if (strlen($message) < 20 && !preg_match('/\b(what|how|when|where|why|who|which|about|regarding|concerning|for|on|regarding)\b/i', $message)) {
            return true;
        }
        return false;
    }

    private function getQuestionTypeDisplayName(string $scope): string
    {
        $displayNames = config('ai_express.question_type_display_names', []);
        return $displayNames[$scope] ?? str_replace('_', ' ', $scope);
    }

    private function checkForPendingQuestionCreation(Request $request): ?array
    {
        try {
            $user = $request->user();
            if (!$user) {
                return null;
            }
            $lastUserMessage = strtolower(trim($request->message));
            if (preg_match('/\b(what|how|when|where|why|who|which|create|make|add|build|generate|design)\b/i', $lastUserMessage) && 
                strlen($lastUserMessage) > 30) {
                return null;
            }
        
            $isTopicOnlyMessage = false;
            if (preg_match('/^(about|regarding|on|for|concerning)\s+/i', $lastUserMessage) && strlen($lastUserMessage) < 100) {
                $isTopicOnlyMessage = true;
            } elseif (strlen($lastUserMessage) < 50 && !preg_match('/\b(what|how|when|where|why|who|which|create|make|add|build|generate|design|question|questions)\b/i', $lastUserMessage)) {
                $isTopicOnlyMessage = true;
            }
            
            if (!$isTopicOnlyMessage) {
                return null;
            }
            
            $memoryContext = $this->memoryService->getMemoryContext($user->id, 'express');
            $buffer = $memoryContext['buffer'] ?? [];
            
            if (empty($buffer)) {
                return null;
            }  
            Log::info('[checkForPendingQuestionCreation] Checking buffer for topic-only message', [
                'buffer_count' => count($buffer),
                'user_message' => $lastUserMessage,
                'is_topic_only' => $isTopicOnlyMessage,
            ]);
           
            for ($i = count($buffer) - 1; $i >= 0 && $i >= max(0, count($buffer) - 10); $i--) {
                $message = $buffer[$i];
                if ($message['role'] === 'assistant') {
                    $content = $message['content'] ?? '';
                    $contentLower = strtolower($content);

                    $isAskingForDetails = str_contains($contentLower, 'could you please tell me what the question should be about') ||
                        str_contains($contentLower, 'what the question should be about') ||
                        str_contains($contentLower, 'tell me what the question should be about') ||
                        str_contains($contentLower, 'what topic or information') ||
                        str_contains($contentLower, 'what are you trying to gather') ||
                        str_contains($contentLower, 'what should the question be about') ||
                        (str_contains($contentLower, 'what') && str_contains($contentLower, 'question') && str_contains($contentLower, 'about') && str_contains($contentLower, '?'));
                    
                    if ($isAskingForDetails) {
                        Log::info('[checkForPendingQuestionCreation] Found assistant asking for details', [
                            'message_index' => $i,
                            'content_preview' => mb_substr($content, 0, 200),
                            'buffer_length' => count($buffer)
                        ]);
                        
                        for ($j = $i - 1; $j >= 0 && $j >= max(0, $i - 10); $j--) {
                            $prevMessage = $buffer[$j] ?? null;
                            if ($prevMessage && $prevMessage['role'] === 'assistant') {
                                $prevContent = $prevMessage['content'] ?? '';
                                if (str_contains($prevContent, 'Would you like me to create')) {
                                    $questionType = $this->extractQuestionTypeFromCreationOffer($prevContent);
                                    if ($questionType) {
                                        $actionData = $this->createActionFromQuestionType($questionType);
                                        Log::info('[checkForPendingQuestionCreation] Found pending question creation', [
                                            'question_type' => $questionType,
                                            'scope' => $actionData['scope'],
                                            'intent' => $actionData['intent'],
                                            'user_message' => $lastUserMessage,
                                            'message_index' => $i,
                                            'creation_offer_index' => $j
                                        ]);
                                        return $actionData;
                                    }
                                }
                            }
                        }    
                        if ($i > 0) {
                            $prevMessage = $buffer[$i - 1] ?? null;
                            if ($prevMessage && $prevMessage['role'] === 'assistant') {
                                $prevContent = $prevMessage['content'] ?? '';
                                if (str_contains($prevContent, 'Would you like me to create')) {
                                    $questionType = $this->extractQuestionTypeFromCreationOffer($prevContent);
                                    if ($questionType) {
                                        $actionData = $this->createActionFromQuestionType($questionType);
                                        Log::info('[checkForPendingQuestionCreation] Found pending question creation (adjacent messages)', [
                                            'question_type' => $questionType,
                                            'scope' => $actionData['scope'],
                                            'intent' => $actionData['intent'],
                                            'user_message' => $lastUserMessage
                                        ]);
                                        return $actionData;
                                    }
                                }
                            }
                        }    
                        for ($j = $i - 1; $j >= 0 && $j >= max(0, $i - 5); $j--) {
                            $prevMessage = $buffer[$j] ?? null;
                            if ($prevMessage && $prevMessage['role'] === 'user') {
                                $prevUserContent = strtolower(trim($prevMessage['content'] ?? ''));
                                if (in_array($prevUserContent, ['yes', 'yes please', 'yeah', 'yep', 'sure', 'ok', 'okay'])) {
                                    for ($k = $j - 1; $k >= 0 && $k >= max(0, $j - 5); $k--) {
                                        $earlierMessage = $buffer[$k] ?? null;
                                        if ($earlierMessage && $earlierMessage['role'] === 'assistant') {
                                            $earlierContent = $earlierMessage['content'] ?? '';
                                            if (str_contains($earlierContent, 'Would you like me to create')) {
                                                $questionType = $this->extractQuestionTypeFromCreationOffer($earlierContent);
                                                if ($questionType) {
                                                    $actionData = $this->createActionFromQuestionType($questionType);
                                                    Log::info('[checkForPendingQuestionCreation] Found pending question creation (via confirmation)', [
                                                        'question_type' => $questionType,
                                                        'scope' => $actionData['scope'],
                                                        'intent' => $actionData['intent'],
                                                        'user_message' => $lastUserMessage,
                                                        'confirmation_index' => $j,
                                                        'creation_offer_index' => $k
                                                    ]);
                                                    return $actionData;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } 
            return null;
        } catch (\Throwable $e) {
            Log::warning('Failed to check for pending question creation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    private function extractQuestionTypeFromCreationOffer(string $content): ?string
    {
        if (preg_match('/Would you like me to create a ([^?]+) question/i', $content, $matches)) {
            $questionType = trim($matches[1]);
            return $questionType;
        }
        
        return null;
    }

    private function createActionFromQuestionType(string $questionType): array
    {
        $questionTypeMap = config('ai_express.question_type_to_action', []);
        $questionType = strtolower(trim($questionType));
        $mapping = $questionTypeMap[$questionType] ?? null;   
        if ($mapping) {
            return [
                'action' => $mapping['action'],
                'scope' => $mapping['scope'],
                'intent' => 'create'
            ];
        }     
        return [
            'action' => 'express_question_create',
            'scope' => 'question',
            'intent' => 'create'
        ];
    }

    private function extractActionDataFromMessage(string $content): ?array
    {
        if (str_contains($content, 'matrix question') && str_contains($content, 'Would you like me to create')) {
            return [
                'action' => 'express_matrix_create',
                'scope' => 'matrix',
                'intent' => 'create'
            ];
        }
        
        if (str_contains($content, 'text selection question') && str_contains($content, 'Would you like me to create')) {
            return [
                'action' => 'express_text_selection_create',
                'scope' => 'text_selection',
                'intent' => 'create'
            ];
        }
        
        if (str_contains($content, 'image selection question') && str_contains($content, 'Would you like me to create')) {
            return [
                'action' => 'express_image_selection_create',
                'scope' => 'image_selection',
                'intent' => 'create'
            ];
        }
        
        if (str_contains($content, 'descriptive question') && str_contains($content, 'Would you like me to create')) {
            return [
                'action' => 'express_descriptive_create',
                'scope' => 'descriptive',
                'intent' => 'create'
            ];
        }
        // will add more in the futeru
        return null;
    }

    private function executePreviousAction(Request $request, array $actionData): JsonResponse
    {
        try {
            $executionRequest = new Request();
            $executionRequest->merge([
                'questionnaire_code' => $request->input('questionnaire_code'),
            ]);

            $aiExecutionController = new \App\Http\Controllers\Api\AiExecutionController();
            
            $questionData = $this->generateBasicQuestionData($actionData);
            
            switch ($actionData['action']) {
                case 'express_matrix_create':
                case 'express_text_selection_create':
                case 'express_image_selection_create':
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
                case 'express_pie_chart_create':
                case 'express_bar_chart_create':
                    return $aiExecutionController->executeCreateQuestion(
                        $executionRequest, 
                        $questionData, 
                        $actionData['scope']
                    );
                default:
                    Log::error('Unknown action type in confirmation', [
                        'action' => $actionData['action'],
                    ]);
                    return response()->json([
                        'message' => 'Unknown action type: ' . $actionData['action'],
                    ], 400);
            }
        } catch (\Throwable $e) {
            Log::error('Previous action execution failed', [
                'action' => $actionData['action'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Failed to execute the previous action. Please try again.',
            ], 500);
        }
    }

    private function generateBasicQuestionData(array $actionData): array
    {
        $questionName = 'Question';
        $questionText = 'Please answer the following:';
        
        switch ($actionData['action']) {
            case 'express_matrix_create':
                return [
                    'title_and_question_text' => [
                        'name' => $questionName,
                        'question_text' => $questionText
                    ],
                    'columns' => [
                        ['text' => 'Strongly Disagree', 'equivalent' => 'strongly_disagree', 'width' => 'auto', 'random_choice' => false],
                        ['text' => 'Disagree', 'equivalent' => 'disagree', 'width' => 'auto', 'random_choice' => false],
                        ['text' => 'Neutral', 'equivalent' => 'neutral', 'width' => 'auto', 'random_choice' => false],
                        ['text' => 'Agree', 'equivalent' => 'agree', 'width' => 'auto', 'random_choice' => false],
                        ['text' => 'Strongly Agree', 'equivalent' => 'strongly_agree', 'width' => 'auto', 'random_choice' => false]
                    ],
                    'required' => false,
                    'edit_layout_width' => [
                        'desktop' => ['desktop width' => 100, 'units' => 'percentage'],
                        'tablet' => ['tablet width' => 100, 'units' => 'percentage'],
                        'mobile' => ['mobile width' => 100, 'units' => 'percentage']
                    ],
                    'single_answer_per_column' => false,
                    'selections' => [
                        'apply_select_all' => false,
                        'apply_unselect_all' => false
                    ],
                    'rows' => [
                        ['text' => 'Item 1', 'equivalent' => 'item_1', 'write_in' => ['enabled' => false], 'optional_row' => false],
                        ['text' => 'Item 2', 'equivalent' => 'item_2', 'write_in' => ['enabled' => false], 'optional_row' => false],
                        ['text' => 'Item 3', 'equivalent' => 'item_3', 'write_in' => ['enabled' => false], 'optional_row' => false]
                    ]
                ];
            default:
                return [
                    'title_and_question_text' => [
                        'name' => $questionName,
                        'question_text' => $questionText
                    ],
                    'required' => false
                ];
        }
    }
    private function buildDetectionSchema(): ObjectSchema
    {
        $scopeSchema = new EnumSchema(
            name: 'scope',
            description: config('ai_prompts.schema_descriptions.scope', ''),
            options: Scope::values()
        );

        $intentSchema = new EnumSchema(
            name: 'intent',
            description: config('ai_prompts.schema_descriptions.intent', ''),
            options: Intent::values()
        );

        $detailsSchema = new StringSchema(
            name: 'details',
            description: config('ai_prompts.schema_descriptions.details', ''),
            nullable: true
        );

        Log::info('Schema objects created', [
            'scopeSchema' => get_class($scopeSchema),
            'intentSchema' => get_class($intentSchema), 
            'detailsSchema' => get_class($detailsSchema)
        ]);

        $properties = [$scopeSchema, $intentSchema, $detailsSchema];

        foreach ($properties as $index => $property) {
            if (!($property instanceof \Prism\Prism\Contracts\Schema)) {
                Log::error("Property at index {$index} is not a Schema object", [
                    'type' => gettype($property),
                    'value' => $property
                ]);
                throw new \InvalidArgumentException("Property at index {$index} is not a Schema object");
            }
        }
        return new ObjectSchema(
            name: 'scope_and_intent_detection',
            description: '...',
            properties: $properties
        );
        
    }

    // Handle conversation intent
    private function handleConversation(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if ($user && $this->isConfirmationResponse($request->message)) {
                $confirmationResult = $this->handleConfirmation($request, $user);
                if ($confirmationResult) return $confirmationResult;
            }

            $userInfo = $this->getUserTypeInfo($request);
            
            if ($this->isQuickResponse($request->message)) {
                $responseText = $this->getQuickResponse($request->message, $userInfo);
                $this->persistPlatformQuickResponse($request, $responseText);
                return response()->json(['message' => $responseText]);
            }

            $memoryContext = '';
            if ($user) {
                $memoryContextCacheKey = 'memory_context_' . $user->id . '_' . $this->promptService->platform;
                $memoryContext = \Illuminate\Support\Facades\Cache::remember($memoryContextCacheKey, now()->addMinutes(5), function() use ($user) {
                    return $this->memoryService->renderContextString($user->id, $this->promptService->platform);
                });
            }
            $basePrompt = config('ai_prompts.base_prompts.conversation', 'You are a helpful and expressive AI assistant for EXPRESS, a survey platform. You ONLY have knowledge about EXPRESS. When users ask questions about EXPRESS features, provide clear explanations with specific step-by-step UI guidance. Respond naturally and keep responses concise.');
            $systemPrompt = $this->buildSystemPrompt($basePrompt, $userInfo['type'], $userInfo['firstName'], $memoryContext);

            try {
                $embedding = $this->chatService->getEmbedding($request->message);
                $currentPage = $this->detectCurrentPage($request);
                $pageContext = $this->getPageContext($request);
                
                // TEMPORARY LOG: Before getContext call
                Log::info('[ChatController TEMP] About to call getContext', [
                    'user_id' => optional($user)->id,
                    'message' => mb_substr($request->message, 0, 100),
                    'currentPage' => $currentPage,
                    'pageContext' => $pageContext,
                    'embedding_length' => count($embedding),
                    'embedding_preview' => array_slice($embedding, 0, 5)
                ]);
                
                $neo4jContext = $this->promptService->neo4jService->getContext($embedding, $currentPage);
                
                // TEMPORARY LOG: After getContext call
                Log::info('[ChatController TEMP] getContext returned', [
                    'user_id' => optional($user)->id,
                    'has_context' => !empty($neo4jContext['context']),
                    'context_length' => strlen($neo4jContext['context'] ?? ''),
                    'has_path' => !empty($neo4jContext['path']),
                    'has_instructions' => !empty($neo4jContext['instructions']),
                    'has_relatedNodes' => !empty($neo4jContext['relatedNodes']),
                    'relatedNodes_count' => count($neo4jContext['relatedNodes'] ?? []),
                    'context_preview' => mb_substr($neo4jContext['context'] ?? '', 0, 200),
                    'path' => $neo4jContext['path'] ?? null,
                    'instructions_preview' => mb_substr($neo4jContext['instructions'] ?? '', 0, 200),
                    'full_response' => $neo4jContext
                ]);
                
                if (!empty($neo4jContext['context'])) {
                    $contextInfo = "User is on main page: {$pageContext['main_page']}";
                    if ($pageContext['active_sidebar']) {
                        $contextInfo .= " with {$pageContext['active_sidebar']} sidebar open";
                    }
                    $systemPrompt .= "\n\nEXPRESS Knowledge Base ({$contextInfo}):\n" . $neo4jContext['context'];
                    
                    if (!empty($neo4jContext['relatedNodes'])) {
                        $workflowSteps = array_filter(array_map(function($node) {
                            if (in_array($node['type'], ['Action', 'Modal', 'Property']) && 
                                (str_contains(strtolower($node['name']), 'add') || 
                                 str_contains(strtolower($node['name']), 'button') || 
                                 str_contains(strtolower($node['name']), 'modal') ||
                                 str_contains(strtolower($node['name']), 'click'))) {
                                return $node['name'];
                            }
                            return null;
                        }, $neo4jContext['relatedNodes']));
                        
                        if (!empty($workflowSteps)) {
                            $systemPrompt .= "\n\nUI Workflow Steps: " . implode(' → ', array_unique($workflowSteps));
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Neo4j context retrieval failed in conversation handler', ['error' => $e->getMessage()]);
            }

            $response = Prism::text()
                ->using(...AIModel::getDefaultConfig())
                ->withSystemPrompt($systemPrompt)
                ->withPrompt($request->message)
                ->asText();
            $detectedQuestionType = $this->detectQuestionTypeInMessage($request->message);
            if ($detectedQuestionType) {
                $firstName = $userInfo['firstName'];
                $nameSuffix = $firstName ? " for you {$firstName}" : " for you";
                $response->text .= "\n\nWould you like me to create a {$detectedQuestionType} question{$nameSuffix}?";
            }
            
            return response()->json(['message' => $response->text]);
        } catch (\Throwable $e) {
            Log::error('handleConversation failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'I\'m sorry, I encountered an error. Could you please try again?']);
        }
    }

    // Handle guide intent
    private function handleGuide(Request $request): JsonResponse
    {
        try {
            if (!$this->chatService->isQuestion($request->message)) {
                return response()->json([
                    'message' => 'I\'m not sure how to help with that request. I can only answer questions about EXPRESS, our survey platform. Could you please rephrase it as a question about EXPRESS?'
                ]);
            }

            $userInfo = $this->getUserTypeInfo($request);
            
            if ($this->isQuickResponse($request->message)) {
                $responseText = $this->getQuickResponse($request->message, $userInfo);
                $this->persistPlatformQuickResponse($request, $responseText);
                return response()->json(['message' => $responseText]);
            }

            $embedding = $this->chatService->getEmbedding($request->message);
            $embeddingIn = $this->estimateTokens($request->message);
            $currentPage = $this->detectCurrentPage($request);
            
            // TEMPORARY LOG: Before getContext call in handleGuide
            Log::info('[ChatController TEMP] handleGuide - About to call getContext', [
                'user_id' => optional($request->user())->id,
                'message' => mb_substr($request->message, 0, 100),
                'currentPage' => $currentPage,
                'embedding_length' => count($embedding),
                'embedding_preview' => array_slice($embedding, 0, 5)
            ]);
            
            try {
                $props = $this->promptService->neo4jService->getContext($embedding, $currentPage);
                
                // TEMPORARY LOG: After getContext call in handleGuide
                Log::info('[ChatController TEMP] handleGuide - getContext returned', [
                    'user_id' => optional($request->user())->id,
                    'has_context' => !empty($props['context']),
                    'context_length' => strlen($props['context'] ?? ''),
                    'has_path' => !empty($props['path']),
                    'has_instructions' => !empty($props['instructions']),
                    'has_relatedNodes' => !empty($props['relatedNodes']),
                    'relatedNodes_count' => count($props['relatedNodes'] ?? []),
                    'context_preview' => mb_substr($props['context'] ?? '', 0, 200),
                    'path' => $props['path'] ?? null,
                    'instructions_preview' => mb_substr($props['instructions'] ?? '', 0, 200),
                    'full_response' => $props
                ]);
                
                $context = $props['context'];
                $path = $props['path'];
                $instructions = $props['instructions'];
            } catch (\Throwable $e) {
                Log::warning('Neo4j context retrieval failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'user_id' => optional($request->user())->id,
                    'currentPage' => $currentPage
                ]);
                $context = '';
                $path = '';
                $instructions = '';
            }

            if (empty($context) && empty($path) && empty($instructions)) {
                return response()->json([
                    'message' => 'I couldn\'t find specific information about that in EXPRESS. I can only help with questions related to our EXPRESS survey platform. Could you try rephrasing your question or ask about a different EXPRESS feature?'
                ]);
            }

            $basePrompt = config('ai_prompts.base_prompts.guide', 'You are a helpful guide for EXPRESS, a survey platform. Use HTML tags for formatting (no markdown).');
            $systemPrompt = $this->buildSystemPrompt($basePrompt, $userInfo['type'], $userInfo['firstName']);
            
            $userPrompt = $systemPrompt
                . "Answer the following question: \n {$request->message} \n The user currently has the {$request->websitePosition} page open. To help the user, guide them according to $path. Use the following instructions to help: \n $instructions \n Then, use the following context to answer the user's question if necessary: \n $context Don't just use all of the context - select what's necessary to answer the user's question. Reword the context to suit the user's question. NEVER let the user know that you have been provided context. If the context does not answer the user's question, apologise and say that you have not been given that information. Remember: Use HTML tags (<b>, <u>, <i>) instead of markdown (** or __). ";
            $retrievalTokens = $this->estimateTokens(($path ?? '') . ' ' . ($instructions ?? '') . ' ' . ($context ?? ''));
        
            $reply = Prism::text()
                ->using(...AIModel::getDefaultConfig())
                ->withPrompt($userPrompt)
                ->asText();

            $guideResponse = $reply->text;
            
            $detectedQuestionType = $this->detectQuestionTypeInMessage($request->message);
            if ($detectedQuestionType) {
                $firstName = $userInfo['firstName'];
                $nameSuffix = $firstName ? " for you {$firstName}" : " for you";
                $guideResponse .= "\n\nWould you like me to create a {$detectedQuestionType} question{$nameSuffix}?";
            }

            info($guideResponse);
            $usageIn = $this->estimateTokens($userPrompt) + $embeddingIn + ($retrievalTokens ?? 0);
            $usageOut = $this->estimateTokens($guideResponse);

            return response()->json([
                'message' => $guideResponse,
                'usage_in' => $usageIn,
                'usage_out' => $usageOut,
            ]);
        } catch (\Throwable $e) {
            Log::error('handleGuide failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'I\'m sorry, I encountered an error while looking up information about EXPRESS. I can only help with questions related to our EXPRESS survey platform. Could you please try again or ask me something else about EXPRESS?'
            ]);
        }
    }

    // Handle action intent (create/update)
    private function handleAction(Request $request): JsonResponse
    {
        try {
            // Handle question choice update (add/edit/remove)
            if ($this->promptService->scope === 'question_choice' && in_array($this->promptService->intent, ['update', 'delete'])) {
                $structuredResponse = $this->detectIntentAndScope($request->message);
                $details = $structuredResponse['details'] ?? [];
                $questionNumber = $details['question_number'] ?? null;
                $action = $details['action'] ?? 'add';
                $choiceText = $details['choice_text'] ?? null;
                $oldChoiceText = $details['old_choice_text'] ?? null;

                if (!$questionNumber || !$choiceText) {
                    return response()->json([
                        'message' => 'I couldn\'t understand which question or choice you want to update. Please try again with a format like "add this choice in Q1 \'Robotics\'" or "edit choice in Q1 \'Option 1\' to \'New Option\'".'
                    ]);
                }

                $questionnaireCode = $request->input('questionnaire_code');
                if (!$questionnaireCode) {
                    return response()->json([
                        'message' => 'Questionnaire context is required. Please make sure you\'re on a questionnaire page.'
                    ]);
                }

                $questionnaire = \App\Models\Questionnaire::where('code', $questionnaireCode)->first();
                if (!$questionnaire) {
                    return response()->json(['message' => 'Questionnaire not found.']);
                }

                $variableStart = $questionnaire->setup['variable_start'] ?? 1;
                $order = $questionNumber - $variableStart + 1;
                $question = \App\Models\Question::where('questionnaire_id', $questionnaire->id)
                    ->where('order', $order)
                    ->first();

                if (!$question) {
                    return response()->json(['message' => "Question Q{$questionNumber} not found."]);
                }

                // Validate question type supports choices
                $choiceSupportedTypes = [2, 3, 16, 20];
                if (!in_array($question->question_type_id, $choiceSupportedTypes)) {
                    return response()->json(['message' => "This question type does not support choices."]);
                }

                // Generate confirmation message
                $actionDisplay = ucfirst($action);
                if ($action === 'add') {
                    $confirmationMessage = "I'll add the choice '{$choiceText}' to Question Q{$questionNumber}. Would you like me to proceed?";
                } elseif ($action === 'edit') {
                    $confirmationMessage = "I'll update the choice in Question Q{$questionNumber}: '{$oldChoiceText}' → '{$choiceText}'. Would you like me to proceed?";
                } else {
                    $confirmationMessage = "I'll remove the choice '{$choiceText}' from Question Q{$questionNumber}. Would you like me to proceed?";
                }
                
                $actionData = [
                    'action' => 'express_question_choice_update',
                    'scope' => 'question_choice',
                    'intent' => $action === 'remove' ? 'delete' : 'update',
                    'message' => $confirmationMessage,
                    'preview_data' => [
                        'question_id' => $question->id,
                        'question_order' => $order,
                        'question_number' => $questionNumber,
                        'action' => $action,
                        'choice_text' => $choiceText,
                        'old_choice_text' => $oldChoiceText,
                    ],
                    'questionnaire_code' => $questionnaireCode,
                    'show_confirmation' => true,
                    'confirmation_buttons' => [
                        [
                            'text' => 'Yes, Update',
                            'action' => 'confirm',
                            'style' => 'primary',
                            'data' => [
                                'action' => 'express_question_choice_update',
                                'scope' => 'question_choice',
                                'intent' => $action === 'remove' ? 'delete' : 'update',
                                'preview_data' => [
                                    'question_id' => $question->id,
                                    'question_order' => $order,
                                    'question_number' => $questionNumber,
                                    'action' => $action,
                                    'choice_text' => $choiceText,
                                    'old_choice_text' => $oldChoiceText,
                                ],
                                'questionnaire_code' => $questionnaireCode
                            ]
                        ],
                        [
                            'text' => 'Cancel',
                            'action' => 'cancel',
                            'style' => 'secondary',
                            'data' => []
                        ]
                    ]
                ];

                return response()->json([
                    'action_data' => $actionData,
                    'message' => $confirmationMessage
                ]);
            }
            
            // Handle question options update
            if ($this->promptService->scope === 'question_options' && $this->promptService->intent === 'update') {
                $structuredResponse = $this->detectIntentAndScope($request->message);
                $details = $structuredResponse['details'] ?? [];
                $questionNumber = $details['question_number'] ?? null;
                $optionType = $details['option_type'] ?? null;
                $optionValue = $details['option_value'] ?? true;
                $multiSelectType = $details['multi_select_type'] ?? null;

                if (!$questionNumber || !$optionType) {
                    return response()->json([
                        'message' => 'I couldn\'t understand which question or option you want to update. Please try again with a format like "make Q1 required" or "make question 2 randomise choices".'
                    ]);
                }

                $questionnaireCode = $request->input('questionnaire_code');
                if (!$questionnaireCode) {
                    return response()->json([
                        'message' => 'Questionnaire context is required. Please make sure you\'re on a questionnaire page.'
                    ]);
                }

                $questionnaire = \App\Models\Questionnaire::where('code', $questionnaireCode)->first();
                if (!$questionnaire) {
                    return response()->json(['message' => 'Questionnaire not found.']);
                }

                $variableStart = $questionnaire->setup['variable_start'] ?? 1;
                $order = $questionNumber - $variableStart + 1;
                $question = \App\Models\Question::where('questionnaire_id', $questionnaire->id)
                    ->where('order', $order)
                    ->first();

                if (!$question) {
                    return response()->json(['message' => "Question Q{$questionNumber} not found."]);
                }

                // Validate option is applicable
                $applicableOptions = [
                    2 => ['required', 'random_choices', 'multiple_selection', 'dropdown', 'select_all', 'unselect_all', 'scoring'],
                    3 => ['required', 'random_choices', 'multiple_selection', 'select_all', 'unselect_all', 'scoring'],
                    11 => ['required', 'random_choices', 'multiple_selection', 'scoring'],
                ];
                $defaultOptions = ['required', 'scoring'];
                $options = $applicableOptions[$question->question_type_id] ?? $defaultOptions;
                
                if (!in_array($optionType, $options)) {
                    $optionDisplay = ucfirst(str_replace('_', ' ', $optionType));
                    return response()->json([
                        'message' => "The option '{$optionDisplay}' is not applicable for this question type."
                    ]);
                }

                $optionDisplayName = ucfirst(str_replace('_', ' ', $optionType));
                $valueDisplay = $optionValue ? 'enable' : 'disable';
                $confirmationMessage = "I'll {$valueDisplay} '{$optionDisplayName}' for Question Q{$questionNumber}. Would you like me to proceed?";
                
                $actionData = [
                    'action' => 'express_question_options_update',
                    'scope' => 'question_options',
                    'intent' => 'update',
                    'message' => $confirmationMessage,
                    'preview_data' => [
                        'question_id' => $question->id,
                        'question_order' => $order,
                        'question_number' => $questionNumber,
                        'option_type' => $optionType,
                        'option_value' => $optionValue,
                        'multi_select_type' => $multiSelectType,
                    ],
                    'questionnaire_code' => $questionnaireCode,
                    'show_confirmation' => true,
                    'confirmation_buttons' => [
                        [
                            'text' => 'Yes, Update',
                            'action' => 'confirm',
                            'style' => 'primary',
                            'data' => [
                                'action' => 'express_question_options_update',
                                'scope' => 'question_options',
                                'intent' => 'update',
                                'preview_data' => [
                                    'question_id' => $question->id,
                                    'question_order' => $order,
                                    'question_number' => $questionNumber,
                                    'option_type' => $optionType,
                                    'option_value' => $optionValue,
                                    'multi_select_type' => $multiSelectType,
                                ],
                                'questionnaire_code' => $questionnaireCode
                            ]
                        ],
                        [
                            'text' => 'Cancel',
                            'action' => 'cancel',
                            'style' => 'secondary',
                            'data' => []
                        ]
                    ]
                ];

                return response()->json($actionData);
            }

            if ($this->promptService->scope === 'question' && $this->promptService->intent === 'create') {
                $platformIncongruency = $this->handlePlatformIncongruence($request);

                if ($platformIncongruency) {
                    return $platformIncongruency;
                }
            }
            $schema = $this->promptService->getSchema();
            if (!$schema) {
                return response()->json([
                    'message' => 'I\'m not sure how to help with that request. I can only assist with EXPRESS survey platform features. Could you please rephrase it or try asking about a specific EXPRESS feature?'
                ]);
            }

            $user = $request->user();
            $memoryContext = '';
            
            try {
                $memoryContextCacheKey = 'memory_context_' . $user->id . '_' . $this->promptService->platform;
                $memoryContext = \Illuminate\Support\Facades\Cache::remember($memoryContextCacheKey, now()->addMinutes(5), function() use ($user) {
                    return $this->memoryService->renderContextString(
                        $user->id,
                        $this->promptService->platform
                    );
                });
            } catch (\Throwable $e) {
                Log::warning('Memory context retrieval failed in handleAction', ['error' => $e->getMessage()]);
            }

            $userInfo = $this->getUserTypeInfo($request);
            $baseSystemPrompt = $this->promptService->getSystemPrompt();
            $systemPrompt = $this->buildSystemPrompt($baseSystemPrompt, $userInfo['type'], $userInfo['firstName'], $memoryContext);
            
            $prompt = $this->promptService->getPrompt($request);
            

            $query = Prism::structured()
                ->using(...AIModel::getDefaultConfig())
                ->withSchema($schema)
                ->withSystemPrompt($systemPrompt)
                ->withPrompt($prompt)
                ->usingTemperature(0.1);

            // $logger = new LoggerHelpers();
            // $logger->LogSchemaRequest($query);
                
            $response = $query->asStructured();
            info($response->structured);
            $subscriptionNeeded = $this->promptService->checkSubscription($response->structured);
            if ($subscriptionNeeded) {
                return $subscriptionNeeded;
            }

            $missingMessage = $this->promptService->checkMissingParameters($response->structured);
            info($missingMessage);
            if ($missingMessage) {
                return $missingMessage;
            }

            $usageIn = $this->estimateTokens($systemPrompt . ' ' . (is_string($prompt) ? $prompt : json_encode($prompt)));
            $usageOut = $this->estimateTokens(json_encode($response->structured));
            $responseData = [
                'action' => $this->promptService->action,
                'scope' => $this->promptService->scope,
                'intent' => $this->promptService->intent,
                'message' => 'I\'ve prepared the question data. Would you like me to create this question?',
                'preview_data' => $response->structured ?? [],
                'questionnaire_code' => $request->input('questionnaire_code'),
                'usage_in' => $usageIn,
                'usage_out' => $usageOut,
                'show_confirmation' => true,
                'confirmation_buttons' => [
                    [
                        'text' => 'Yes, Create Question',
                        'action' => 'confirm',
                        'style' => 'primary',
                        'data' => [
                            'action' => $this->promptService->action,
                            'scope' => $this->promptService->scope,
                            'intent' => $this->promptService->intent,
                            'preview_data' => $response->structured ?? [],
                            'questionnaire_code' => $request->input('questionnaire_code')
                        ]
                    ],
                    [
                        'text' => 'Cancel',
                        'action' => 'cancel',
                        'style' => 'secondary',
                        'data' => []
                    ]
                ]
            ];
            
            Log::info('[BACKEND DEBUG] Sending confirmation response', [
                'show_confirmation' => $responseData['show_confirmation'],
                'has_confirmation_buttons' => !empty($responseData['confirmation_buttons']),
                'action' => $responseData['action'],
                'scope' => $responseData['scope'],
                'intent' => $responseData['intent']
            ]);
            
            return response()->json($responseData);
        } catch (\Throwable $e) {
            Log::error('handleAction failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'I\'m sorry, I encountered an error while processing your action request. I can only help with EXPRESS survey platform features. Could you please try again or ask me something else about EXPRESS?'
            ]);
        }
    }

    private function handleSurveyGeneration(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['message' => 'Authentication required'], 401);
            }

            $questionnaireCode = $request->input('questionnaire_code');
            if (!$questionnaireCode) {
                return response()->json(['message' => 'Questionnaire context required for survey generation'], 400);
            }

            $surveyPrompt = $request->message;
            $userInfo = $this->getUserTypeInfo($request);

            $planSchema = new ObjectSchema(
                name: 'survey_plan',
                description: 'Plan for survey generation',
                properties: [
                    new ArraySchema(
                        name: 'planned_questions',
                        description: 'Array of planned questions with their types',
                        items: new ObjectSchema(
                            name: 'planned_question',
                            description: 'A planned question',
                            properties: [
                                new EnumSchema(
                                    name: 'question_type',
                                    description: 'Question type to use',
                                    options: ['descriptive', 'text_selection', 'image_selection', 'single_textbox', 'multiple_textbox', 'open_ended', 'slider', 'ranking', 'star_rating', 'button_rating', 'segmentation', 'matrix', 'pairwise_comparison', 'best-worst_scale', 'pie_chart', 'bar_chart']
                                ),
                                new StringSchema(
                                    name: 'purpose',
                                    description: 'Purpose of this question in the survey'
                                )
                            ],
                            requiredFields: ['question_type', 'purpose']
                        )
                    )
                ],
                requiredFields: ['planned_questions']
            );

            $requestedCount = $this->extractSurveyQuestionCount($surveyPrompt);
            $countInstruction = $requestedCount
                ? "Plan EXACTLY {$requestedCount} questions."
                : "Plan 5-8 questions that will thoroughly cover the topic.";
            $planPrompt = "Create a plan for a survey based on: {$surveyPrompt}. " .
                         "List the question types you plan to use and the purpose of each question. " .
                         $countInstruction;

            Log::info('[Survey Generation] Requesting survey plan (non-streaming)', [
                'prompt' => $surveyPrompt,
                'questionnaire_code' => $questionnaireCode
            ]);

            try {
                $planResponse = Prism::structured()
                    ->using(...AIModel::getSummaryConfig())
                    ->withSchema($planSchema)
                    ->withSystemPrompt("You are planning a survey. List the question types you will use.")
                    ->withPrompt($planPrompt)
                    // ->usingTemperature(0.2)
                    ->asStructured();

                $planData = $planResponse->structured ?? [];
                $plannedTypes = array_column($planData['planned_questions'] ?? [], 'question_type');
                if ($requestedCount && count($plannedTypes) > $requestedCount) {
                    $plannedTypes = array_slice($plannedTypes, 0, $requestedCount);
                }
                
                Log::info('[Survey Generation] Survey plan received (non-streaming)', [
                    'planned_question_types' => $plannedTypes,
                    'total_planned' => count($planData['planned_questions'] ?? []),
                    'plan_details' => $planData
                ]);
            } catch (\Throwable $e) {
                Log::warning('[Survey Generation] Plan generation failed, continuing anyway', [
                    'error' => $e->getMessage()
                ]);
                $plannedTypes = [];
            }

            $surveySchema = $this->buildCompleteSurveyQuestionSchema($plannedTypes);

            Log::info('[Survey Generation] Schema structure (non-streaming)', [
                'schema_name' => 'survey_generation',
                'properties_count' => 1,
                'has_questions_array' => true,
                'question_properties' => [
                    'question_type' => 'enum',
                    'title_and_question_text' => 'object',
                    'text_options' => 'array (optional)',
                    'items' => 'array (optional)',
                    'choices' => 'array (optional)'
                ],
                'required_fields' => ['questions']
            ]);

            $systemPrompt = "You are an AI assistant for EXPRESS survey platform. Generate a complete survey based on the user's request. " .
                          "Create 5-6 diverse questions with appropriate question types. Mix different question types for better survey design. " .
                          "For each question, determine the best question type based on the information being collected. " .
                          "CRITICAL FOR SLIDER QUESTIONS: Understand the distinction between 'items' and 'choices'. " .
                          "'Items' are the SPECIFIC SCENARIOS/SITUATIONS/ASPECTS being rated (what respondents evaluate). Items must be coherent, related, and form a logical set. Examples: For influence questions: 'When I see messaging on signage as I approach the gas station', 'When I see messaging as I drive up to the pump', 'When the Gas Station Staff speaks to me'. For satisfaction questions: 'Atmosphere of the party', 'Quality of food and drinks', 'Entertainment provided'. Items should be 2-6 related scenarios/aspects that make sense together. " .
                          "'Choices' are the SCALE LABELS/VALUES (the rating scale itself). Choices must be contextually appropriate scale labels that match the question's intent and form a coherent progression. Examples: For influence questions: 'Very unlikely to be influenced', 'Unlikely', 'Neutral', 'Likely', 'Highly likely to be influenced'. For satisfaction: 'Very dissatisfied', 'Dissatisfied', 'Neutral', 'Satisfied', 'Very satisfied'. For agreement: 'Strongly Disagree', 'Disagree', 'Neutral', 'Agree', 'Strongly Agree'. PREFER TEXT-BASED LABELS over numeric values. Generate 3-7 scale labels that form a logical progression. " .
                          "DO NOT confuse these - scale labels like 'Not at all' or 'Very likely' should NEVER be in 'items', they belong in 'choices'. Items are what is being rated, choices are how it is rated. " .
                          "For slider questions, generate 2-6 coherent items (related scenarios/aspects) and 3-7 choices (contextually appropriate scale labels). Ensure items relate to each other and choices match the question's intent. " .
                          "CRITICAL FOR MATRIX QUESTIONS: Understand the distinction between 'rows' and 'columns'. " .
                          "'Rows' are the STATEMENTS/ASPECTS being evaluated (what respondents rate). Rows must be coherent, related statements or aspects that form a logical set. Examples: For satisfaction: 'Product quality', 'Customer service', 'Price value', 'Ease of use'. For agreement: 'The product meets my needs', 'I would recommend this product', 'The price is reasonable'. Generate 3-8 related rows that make sense together. " .
                          "'Columns' are the ANSWER CHOICES/OPTIONS (the rating scale itself). Columns must be contextually appropriate answer options that match the question's intent and form a coherent set. Examples: For satisfaction: 'Very Satisfied', 'Satisfied', 'Neutral', 'Dissatisfied', 'Very Dissatisfied'. For agreement: 'Strongly Agree', 'Agree', 'Neutral', 'Disagree', 'Strongly Disagree'. For frequency: 'Always', 'Often', 'Sometimes', 'Rarely', 'Never'. Generate 3-7 columns that form a logical set of answer options appropriate for rating the rows. " .
                          "For matrix questions, ALWAYS generate both rows and columns. Generate 3-8 coherent rows (related statements/aspects) and 3-7 columns (contextually appropriate answer options). Ensure rows relate to each other and columns match the question's intent. " .
                          "CRITICAL FOR RANKING QUESTIONS: ALWAYS generate 'items' - the options respondents will rank (e.g., brand names, product features). Generate 3-6 items. " .
                          "CRITICAL FOR STAR_RATING QUESTIONS: ALWAYS generate 'items' - the aspects respondents rate with stars (e.g., 'Product quality', 'Customer service'). Generate 3-6 items. Also set number_of_rating_stars (default 5). " .
                          "CRITICAL FOR MULTIPLE_TEXTBOX QUESTIONS: ALWAYS generate 'textboxes' - the labels for each text input (e.g., 'First Name', 'Last Name', 'Email'). Generate 2-5 textbox labels. " .
                          "Generate realistic choices/options for selection-type questions. " .
                          "Ensure questions are logically ordered and flow well together.";

            $prompt = "Generate a complete survey based on this request: {$surveyPrompt}. " .
                     "Create a comprehensive set of questions that thoroughly covers the topic. " .
                     "Use appropriate question types for each question. " .
                     "For slider questions, ensure 'items' contain coherent, related scenarios/aspects being rated (e.g., for influence: 'When I see messaging on signage', 'When staff speaks to me'; for satisfaction: 'Product quality', 'Customer service'). Ensure 'choices' contain TEXT-BASED scale labels with spaces only—no underscores (e.g. 'very low', 'very high', not 'very_low', 'very_high'). Match the question's intent (e.g., for influence: 'Very unlikely to be influenced' to 'Highly likely to be influenced'; for satisfaction: 'Very dissatisfied' to 'Very satisfied'). Items must form a logical set, and choices must form a coherent scale. Prefer descriptive text labels over numeric values. " .
                     "For matrix questions, ensure 'rows' contain coherent, related statements/aspects being evaluated (e.g., for satisfaction: 'Product quality', 'Customer service', 'Price value'; for agreement: 'The product meets my needs', 'I would recommend this product'). Ensure 'columns' contain contextually appropriate answer options that match the question's intent (e.g., for satisfaction: 'Very Satisfied' to 'Very Dissatisfied'; for agreement: 'Strongly Agree' to 'Strongly Disagree'). Rows must form a logical set, and columns must form a coherent set of answer options appropriate for rating those rows. ALWAYS generate both rows and columns for matrix questions. " .
                     "For ranking questions: ALWAYS include 'items' array with 3-6 options to rank. " .
                     "For star_rating questions: ALWAYS include 'items' array with 3-6 aspects to rate, and number_of_rating_stars (default 5). " .
                     "For multiple_textbox questions: ALWAYS include 'textboxes' array with 2-5 field labels (e.g., First Name, Last Name, Email). " .
                     "Return the survey structure with all questions and their configurations.";
            if ($requestedCount) {
                $prompt .= " CRITICAL: Generate EXACTLY {$requestedCount} distinct, complete questions. Each question must have all required data (matrix: rows and columns; slider: items and choices; ranking: items; star_rating: items; multiple_textbox: textboxes). Never output duplicate or incomplete questions.";
            }
            if (!empty($plannedTypes)) {
                $typesList = implode(', ', $plannedTypes);
                $prompt .= " Generate questions in this exact order and type: {$typesList}. Use ONLY these types - do not substitute with other question types.";
            }

            Log::info('[Survey Generation] Sending request to AI model (non-streaming)', [
                'prompt_length' => strlen($prompt),
                'system_prompt_length' => strlen($systemPrompt),
                'planned_types' => $plannedTypes
            ]);

            try {
                $response = Prism::structured()
                    ->using(...AIModel::getDefaultConfig())
                    ->withSchema($surveySchema)
                    ->withSystemPrompt($systemPrompt)
                    ->withPrompt($prompt)
                    ->usingTemperature(0.3)
                    ->withClientOptions(['timeout' => 60])
                    ->asStructured();
                
                Log::info('[Survey Generation] AI response received successfully (non-streaming)');
            } catch (\Throwable $e) {
                Log::error('[Survey Generation] Schema validation failed (non-streaming)', [
                    'error' => $e->getMessage(),
                    'error_type' => get_class($e),
                    'planned_types' => $plannedTypes,
                    'schema_name' => 'survey_generation',
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

            $surveyData = $response->structured ?? [];
            $rawQuestions = $surveyData['questions'] ?? [];
            $surveyData['questions'] = $this->filterIncompleteSurveyQuestions($rawQuestions);
            
            Log::info('[Survey Generation] Parsing survey data (non-streaming)', [
                'has_questions' => isset($surveyData['questions']),
                'questions_count' => count($surveyData['questions']),
                'question_types_generated' => array_column($surveyData['questions'], 'question_type'),
                'filtered_from' => count($rawQuestions) - count($surveyData['questions'])
            ]);
            
            if (empty($surveyData['questions']) || !is_array($surveyData['questions'])) {
                Log::error('[Survey Generation] Invalid survey data structure (non-streaming)', [
                    'survey_data_keys' => array_keys($surveyData),
                    'questions_type' => gettype($surveyData['questions'] ?? null)
                ]);
                return response()->json([
                    'message' => 'Failed to generate survey questions. Please try again.',
                ], 500);
            }

            $totalQuestions = count($surveyData['questions']);
            $questionTypes = array_column($surveyData['questions'], 'question_type');
            
            Log::info('[Survey Generation] Survey generated successfully (non-streaming)', [
                'total_questions' => $totalQuestions,
                'question_types' => $questionTypes,
                'type_counts' => array_count_values($questionTypes)
            ]);

            $responseData = [
                'action' => 'express_survey_create',
                'scope' => 'survey',
                'intent' => 'create',
                'message' => 'I\'ve generated a complete survey with ' . $totalQuestions . ' questions. Would you like me to create all these questions?',
                'survey_data' => $surveyData,
                'questionnaire_code' => $questionnaireCode,
                'total_questions' => $totalQuestions,
                'show_confirmation' => true,
                'confirmation_buttons' => [
                    [
                        'text' => 'Yes',
                        'action' => 'confirm',
                        'style' => 'primary',
                        'data' => [
                            'action' => 'express_survey_create',
                            'scope' => 'survey',
                            'intent' => 'create',
                            'survey_data' => $surveyData,
                            'questionnaire_code' => $questionnaireCode
                        ]
                    ],
                    [
                        'text' => 'Cancel',
                        'action' => 'cancel',
                        'style' => 'secondary',
                        'data' => []
                    ]
                ]
            ];

            return response()->json($responseData);
        } catch (\Throwable $e) {
            Log::error('Survey generation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'I\'m sorry, I encountered an error while generating the survey. Please try again.',
            ], 500);
        }
    }

    private function handlePlatformIncongruence(Request $request): ?JsonResponse
    {
        $schema = new ObjectSchema (
                name: 'platform_object',
                description: 'The platform mentioned by the user.',
                properties: [
                    new EnumSchema(
                        name: 'platform',
                        description: 'The platform mentioned by the user. DO NOT MAKE YOUR OWN. If not specified, use null as default.',
                        options: Platform::values(),
                        nullable: true
                    ),
                    new EnumSchema(
                        name: 'question_type',
                        description: 'The question type mentioned by the user. DO NOT MAKE YOUR OWN - THIS SHOULD BE EXPLICITLY SPECIFIED BY THE USER. If not specified, use null as default. DO NOT GUESS OR ASSUME ANY QUESTION TYPE. Note: "description", "multiple choice", "textbox", "charts", and "rating" are CATEGORIES that contain multiple question types - if the user mentions these, use them as the question_type.',
                        options: EXPRESSType::values(),
                        nullable: true
                    )
                ]
            );
        $userPlatform = Prism::structured()
            ->using(...AIModel::getDefaultConfig())
            ->withSchema($schema)
            ->withPrompt($request->message)
            ->usingTemperature(0.1)
            ->asStructured();

        $platform = $userPlatform->structured;

        if (empty($platform['question_type']) || !$this->isExplicitQuestionType($request->message)) {
            $platform['question_type'] = null;
        }
        info($platform);
        if ($this->promptService->platform === 'express') {
                    $expressQuestionTypeResponse = $this->handleExpressQuestionTypes($platform);
                    if ($expressQuestionTypeResponse) {
                        return $expressQuestionTypeResponse;
                    }
                }

        if (!empty($platform['platform']) && $platform['platform'] !== $this->promptService->platform) {
            $userPlatform = strtoupper($platform['platform']);
            
            return response()->json([
                'message' => "I'm sorry, I can only help with EXPRESS survey platform features. I don't have knowledge about {$userPlatform} or other platforms. Would you like me to help you with something related to EXPRESS instead?"
            ], 200);
        }
        return null; 
    }
    private function isExplicitQuestionType(string $message): bool
    {
        $message = strtolower(trim($message));
        $explicitKeywords = config('ai_detection.explicit_question_types', []);
        
        foreach ($explicitKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        
        return false;
    }

    private function handleExpressQuestionTypes(array $platform): ?JsonResponse
    {
        $messageMap = config('ai_express.question_type_messages', []);
        $expressType = $platform['question_type'] ?? null;

        if (!$expressType) {
            return response()->json([
                'message' => 'Please specify what type of EXPRESS question you\'d like to create. You can choose from: text selection, image selection, descriptive, video player, single textbox, multiple textbox, open ended, pie chart, bar chart, slider, ranking, star rating, button rating, segmentation, matrix, pairwise comparison, or best-worst scale.'
            ]);
        } elseif ($expressType && isset($messageMap[$expressType])) {
            return response()->json(['message' => $messageMap[$expressType]]);
        } else {
            $typeMapping = config('ai_express.question_type_to_scope', []);
            $mappedType = $typeMapping[$expressType] ?? $expressType;
            $this->promptService->scope = $mappedType;
            return null;
        }
    }

    public function diagnose(): JsonResponse
    {
        try {
            $diagnosis = $this->neo4jService->diagnoseExpressNodes();
            return response()->json($diagnosis);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Diagnosis failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function warmupNeo4j(): JsonResponse
    {
        try {
            // This will establish the connection early
            $result = $this->neo4jService->testConnection();
            return response()->json([
                'status' => 'success',
                'message' => 'Neo4j connection established',
                'connection_test' => $result
            ]);
        } catch (\Exception $e) {
            Log::warning('Neo4j warmup failed', ['error' => $e->getMessage()]);
            // Don't fail - just log it, connection will be established on first use
            return response()->json([
                'status' => 'warning',
                'message' => 'Neo4j warmup attempted but connection will be established on first use'
            ]);
        }
    }

    public function clearEmbeddingCache(): JsonResponse
    {
        $this->chatService->clearEmbeddingCache();
        return response()->json(['message' => 'Embedding cache cleared successfully']);
    }

    private function isBulkCreationOveruse(string $message): bool
    {
        $text = mb_strtolower(trim($message));
        $creationHints = config('ai_detection.bulk_creation.verbs', []);
        $targets = config('ai_detection.bulk_creation.targets', []);
        $hasCreationVerb = false;
        foreach ($creationHints as $hint) {
            if (str_contains($text, $hint)) { 
                $hasCreationVerb = true; 
                break; 
            }
        }
        if (!$hasCreationVerb) return false;
        $hasTarget = false;
        foreach ($targets as $target) {
            if (str_contains($text, $target)) { 
                $hasTarget = true; 
                break; 
            }
        }
        if (!$hasTarget) return false;

        $maxBatch = config('ai_chat.bulk_limit', 10);

        if (preg_match_all('/\b(\d{1,3}(?:[,\s]?\d{3})*|\d+)\b/u', $text, $m) && !empty($m[1])) {
            foreach ($m[1] as $raw) {
                $n = (int) str_replace([',', ' '], '', $raw);
                if ($n >= $maxBatch) return true;
            }
        }

        $numberWords = config('ai_detection.bulk_creation.number_words', []);
        $massPatterns = config('ai_detection.bulk_creation.mass_patterns', []);
        
        if (preg_match('/\b(' . implode('|', $massPatterns) . ')\s+of\b/u', $text)) {
            return true;
        }
        
        $tokens = preg_split('/\s+/u', $text) ?: [];
        $window = 6;
        for ($i = 0; $i < count($tokens); $i++) {
            $value = 0;
            $current = 0;
            $seenAny = false;
            for ($j = $i; $j < min($i + $window, count($tokens)); $j++) {
                $t = rtrim($tokens[$j], '.,!?');
                if ($t === 'and') continue;
                if (isset($numberWords[$t])) {
                    $seenAny = true;
                    $w = $numberWords[$t];
                    if ($w === 100 || $w === 1000) {
                        $current = max(1, $current) * $w;
                    } else {
                        $current += $w;
                    }
                    $value = max($value, $current);
                } else {
                    if ($seenAny) break;
                }
            }
            if ($seenAny && $value >= $maxBatch) {
                return true;
            }
        }

        return false;
    }

    public function getCacheStats(): JsonResponse
    {
        return response()->json($this->chatService->getCacheStats());
    }

    public function tokenUsage(Request $request): JsonResponse
{
    try {
        $user = $request->user();
        $limit = (int) config('ai_chat.daily_token_limit', 25000);
        
        if (!$user) {
            return response()->json([
                'used' => 0,
                'limit' => $limit,
                'percent' => 0,
            ]);
        }

        $used = 0;

        try {
            $record = MongoTokenManager::where('user_id', $user->id)->first();
            $used = (int) ($record?->overall_token_used ?? 0);
        } catch (\Throwable $e) {
            \Log::warning('Mongo token usage read failed', ['error' => $e->getMessage()]);
        }

        $percent = (int) min(100, round(($used / max(1, $limit)) * 100));

        $response = [
            'used' => $used,
            'limit' => $limit,
            'percent' => $percent,
        ];

        return response()->json($response);
    } catch (\Throwable $e) {
        \Log::warning('tokenUsage failed', ['error' => $e->getMessage()]);
        return response()->json([
            'used' => 0,
                'limit' => (int) config('ai_chat.daily_token_limit', 25000),
            'percent' => 0,
        ]);
    }
    }

    private function detectCurrentPage(Request $request): string
    {
        $websitePosition = $request->websitePosition;
        $platform = $request->platform;
        $currentUrl = $request->input('current_url');
        $activeSidebar = $request->input('active_sidebar');
        $sidebarState = $request->input('sidebar_state');
        $mainPage = $request->input('main_page');
        
        Log::info('Current Page Detection Input', [
            'websitePosition' => $websitePosition,
            'platform' => $platform,
            'currentUrl' => $currentUrl,
            'activeSidebar' => $activeSidebar,
            'sidebarState' => $sidebarState,
            'mainPage' => $mainPage
        ]);
        
        $urlSegments = $this->parseUrlSegments($currentUrl ?: $websitePosition);
        
        if ($activeSidebar) {
            $sidebarPage = $this->mapSidebarToPage($activeSidebar);
            if ($sidebarPage) {
                Log::info('Active sidebar detected - using Neo4j context for page', [
                    'activeSidebar' => $activeSidebar, 
                    'mappedPage' => $sidebarPage,
                    'mainPage' => $mainPage,
                    'user_id' => optional($request->user())->id
                ]);
                return $sidebarPage;
            }
        }
        
        if ($sidebarState && is_array($sidebarState)) {
            foreach ($sidebarState as $expandedItem) {
                $expandedPage = $this->mapSidebarToPage($expandedItem);
                if ($expandedPage) {
                    Log::info('Expanded sidebar detected', ['expandedItem' => $expandedItem, 'mappedPage' => $expandedPage, 'mainPage' => $mainPage]);
                    return $expandedPage;
                }
            }
        }
        
        if ($mainPage) {
            Log::info('Using main page from frontend', ['mainPage' => $mainPage]);
            return $mainPage;
        }
        
        $urlPage = $this->detectPageFromUrl($urlSegments);
        if ($urlPage) {
            Log::info('URL-based page detection', ['urlSegments' => $urlSegments, 'detectedPage' => $urlPage]);
            return $urlPage;
        }
        
        $legacyPage = $this->mapLegacyPosition($websitePosition);
        if ($legacyPage) {
            Log::info('Legacy position mapping', ['websitePosition' => $websitePosition, 'mappedPage' => $legacyPage]);
            return $legacyPage;
        }
        
        Log::info('Using default fallback', [
            'websitePosition' => $websitePosition,
            'platform' => $platform,
            'currentUrl' => $currentUrl,
            'activeSidebar' => $activeSidebar
        ]);
        return 'EXPRESS';
    }
    
    private function getPageContext(Request $request): array
    {
        $activeSidebar = $request->input('active_sidebar');
        $mainPage = $request->input('main_page') ?: $request->websitePosition;
        
        $context = [
            'main_page' => $mainPage,
            'active_sidebar' => $activeSidebar ? $this->mapSidebarToPage($activeSidebar) : null,
        ];
        
        return $context;
    }
    
    private function parseUrlSegments(string $url): array
    {
        if (empty($url)) {
            return [];
        }
        
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return [];
        }
        
        $segments = array_filter(explode('/', trim($path, '/')));
        
        $filteredSegments = array_filter($segments, function($segment) {
            return !in_array($segment, ['www', 'express', 'connoisseur-suite', 'co', 'uk']);
        });
        
        return array_values($filteredSegments);
    }
    
    private function mapSidebarToPage(string $sidebarItem): ?string
    {
        $sidebarMapping = config('ai_express.sidebar_to_page', []);
        $normalizedItem = strtolower(str_replace(['_', ' '], '-', $sidebarItem));
        return $sidebarMapping[$normalizedItem] ?? null;
    }
    
    private function detectPageFromUrl(array $segments): ?string
    {
        if (empty($segments)) return null;
        
        if (in_array('survey', $segments) || in_array('questionnaire', $segments)) {
            $subPages = ['questions' => 'questionnaire', 'settings' => 'settings', 'themes' => 'themes', 
                         'gallery' => 'gallery', 'advanced' => 'advanced', 'preview' => 'preview'];
            foreach ($subPages as $page => $result) {
                if (in_array($page, $segments)) return $result;
            }
            return 'questionnaire';
        }
        
        $urlMapping = config('ai_express.url_to_page', []);
        foreach ($segments as $segment) {
            if (isset($urlMapping[$segment])) {
                return $urlMapping[$segment];
            }
        }
        
        return null;
    }
    
    private function mapLegacyPosition(string $websitePosition): ?string
    {
        $legacyMapping = config('ai_express.legacy_position_to_page', []);
        return $legacyMapping[$websitePosition] ?? null;
    }

    // sendMessageStream and streaming handlers moved to AIStreamController
    // Removed: sendMessageStream, handleConversationStream, handleExpertModeStream, handleGuideStream, handleActionStream, handleDuplicateQuestionStream
    // Note: streamSimpleMessage kept in ChatController as it's needed by handleSurveyGenerationStream
    // All other streaming functions removed - they are now in AIStreamController
    // streamSimpleMessage and handleSurveyGenerationStream kept below as they are needed by handleSurveyGenerationStream

    /**
     * Stream a simple message to the client
     * 
     * @param string $message The message to stream to the client
     * @return StreamedResponse SSE stream with word-by-word text chunks
     */
    private function streamSimpleMessage(string $message)
    {
        return response()->stream(function () use ($message) {
            // Split message into words for a natural streaming effect
            $words = explode(' ', $message);
            foreach ($words as $index => $word) {
                $text = $word . ($index < count($words) - 1 ? ' ' : '');
                echo "data: " . json_encode(['text' => $text]) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
                usleep(50000);
            }
            
            echo "data: " . json_encode(['done' => true]) . "\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function handleSurveyGenerationStream(Request $request)
    {
        set_time_limit(300);
        
        return response()->stream(function () use ($request) {
            try {
                $user = $request->user();
                if (!$user) {
                    echo "data: " . json_encode(['error' => 'Authentication required']) . "\n\n";
                    if (ob_get_level() > 0) ob_flush();
                    flush();
                    return;
                }

                $questionnaireCode = $request->input('questionnaire_code');
                if (!$questionnaireCode) {
                    echo "data: " . json_encode(['error' => 'Questionnaire context required for survey generation']) . "\n\n";
                    if (ob_get_level() > 0) ob_flush();
                    flush();
                    return;
                }

                $surveyPrompt = $request->message;
                echo "data: " . json_encode([
                    'progress' => [
                        'stage' => 'planning',
                        'message' => 'Planning survey structure...',
                        'current' => 0,
                        'total' => 0
                    ]
                ]) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
            
            $planSchema = new ObjectSchema(
                name: 'survey_plan',
                description: 'Plan for survey generation',
                properties: [
                    new ArraySchema(
                        name: 'planned_questions',
                        description: 'Array of planned questions with their types',
                        items: new ObjectSchema(
                            name: 'planned_question',
                            description: 'A planned question',
                            properties: [
                                new EnumSchema(
                                    name: 'question_type',
                                    description: 'Question type to use',
                                    options: ['descriptive', 'text_selection', 'image_selection', 'single_textbox', 'multiple_textbox', 'open_ended', 'slider', 'ranking', 'star_rating', 'button_rating', 'segmentation', 'matrix', 'pairwise_comparison', 'best-worst_scale', 'pie_chart', 'bar_chart']
                                ),
                                new StringSchema(
                                    name: 'purpose',
                                    description: 'Purpose of this question in the survey'
                                )
                            ],
                            requiredFields: ['question_type', 'purpose']
                        )
                    )
                ],
                requiredFields: ['planned_questions']
            );

            $requestedCount = $this->extractSurveyQuestionCount($surveyPrompt);
            $countInstruction = $requestedCount
                ? "Plan EXACTLY {$requestedCount} questions."
                : "Plan 5-6 questions that will thoroughly cover the topic.";
            $planPrompt = "Create a plan for a survey based on: {$surveyPrompt}. " .
                         "List the question types you plan to use and the purpose of each question. " .
                         $countInstruction;

            Log::info('[Survey Generation] Requesting survey plan (streaming)', [
                'prompt' => $surveyPrompt,
                'questionnaire_code' => $questionnaireCode
            ]);

            try {
                $planResponse = Prism::structured()
                    ->using(...AIModel::getSummaryConfig())
                    ->withSchema($planSchema)
                    ->withSystemPrompt("You are planning a survey. List the question types you will use.")
                    ->withPrompt($planPrompt)
                    ->usingTemperature(0.2)
                    ->asStructured();

                $planData = $planResponse->structured ?? [];
                $plannedTypes = array_column($planData['planned_questions'] ?? [], 'question_type');
                if ($requestedCount && count($plannedTypes) > $requestedCount) {
                    $plannedTypes = array_slice($plannedTypes, 0, $requestedCount);
                }
                
                Log::info('[Survey Generation] Survey plan received (streaming)', [
                    'planned_question_types' => $plannedTypes,
                    'total_planned' => count($planData['planned_questions'] ?? []),
                    'plan_details' => $planData
                ]);
                
                // Send progress update: Plan completed
                $totalPlanned = count($plannedTypes) > 0 ? count($plannedTypes) : (count($planData['planned_questions'] ?? []) ?: 6);
                echo "data: " . json_encode([
                    'progress' => [
                        'stage' => 'plan_complete',
                        'message' => "Plan completed. Generating {$totalPlanned} questions...",
                        'current' => 1,
                        'total' => $totalPlanned > 0 ? $totalPlanned : 6
                    ]
                ]) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
            } catch (\Throwable $e) {
                Log::warning('[Survey Generation] Plan generation failed (streaming)', [
                    'error' => $e->getMessage()
                ]);
                $planErrorMessage = 'Survey plan could not be generated. This might be due to the AI provider being temporarily overloaded or a network issue. Please try again in a few moments.';
                echo "data: " . json_encode([
                    'progress' => [
                        'stage' => 'error',
                        'message' => $planErrorMessage,
                        'current' => 0,
                        'total' => 0
                    ]
                ]) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
                echo "data: " . json_encode(['error' => $planErrorMessage]) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
                $this->markUserMessageUnanswered($request);
                return;
            }

            $surveySchema = $this->buildCompleteSurveyQuestionSchema($plannedTypes);

            Log::info('[Survey Generation] Schema structure (streaming)', [
                'schema_name' => 'survey_generation',
                'properties_count' => 1,
                'has_questions_array' => true,
                'question_properties' => [
                    'question_type' => 'enum',
                    'title_and_question_text' => 'object',
                    'text_options' => 'array (optional)',
                    'items' => 'array (optional)',
                    'choices' => 'array (optional)'
                ],
                'required_fields' => ['questions'],
                'planned_types' => $plannedTypes
            ]);

            $systemPrompt = config('ai_prompts.survey_generation.system');
            $prompt = str_replace(':survey_prompt', $surveyPrompt, config('ai_prompts.survey_generation.user'));
            if ($requestedCount) {
                $prompt .= " CRITICAL: Generate EXACTLY {$requestedCount} distinct, complete questions. Each question must have all required data (matrix: rows and columns; slider: items and choices). Never output duplicate or incomplete questions.";
            }
            if (!empty($plannedTypes)) {
                $typesList = implode(', ', $plannedTypes);
                $prompt .= " Generate questions in this exact order and type: {$typesList}. Use ONLY these types - do not substitute with other question types.";
            }

            $totalPlanned = count($plannedTypes) > 0 ? count($plannedTypes) : 6;
            echo "data: " . json_encode([
                'progress' => [
                    'stage' => 'generating',
                    'message' => 'Generating survey questions...',
                    'current' => 1,
                    'total' => $totalPlanned
                ]
            ]) . "\n\n";
            if (ob_get_level() > 0) ob_flush();
            flush();

            Log::info('[Survey Generation] Sending request to AI model (streaming)', [
                'prompt_length' => strlen($prompt),
                'system_prompt_length' => strlen($systemPrompt),
                'planned_types' => $plannedTypes
            ]);

            try {
                $schemaReflection = new \ReflectionClass($surveySchema);
                $schemaProperties = [];
                if ($schemaReflection->hasProperty('properties')) {
                    $prop = $schemaReflection->getProperty('properties');
                    $prop->setAccessible(true);
                    $schemaProperties = $prop->getValue($surveySchema);
                }
                
                Log::info('[Survey Generation] Schema structure details (streaming)', [
                    'schema_name' => $surveySchema->name ?? 'unknown',
                    'schema_description' => $surveySchema->description ?? 'unknown',
                    'properties_count' => is_array($schemaProperties) ? count($schemaProperties) : 0,
                    'properties_types' => is_array($schemaProperties) ? array_map(function($prop) {
                        return [
                            'type' => get_class($prop),
                            'name' => $prop->name ?? 'unknown',
                        ];
                    }, $schemaProperties) : [],
                ]);
            } catch (\Throwable $schemaLogError) {
                Log::warning('[Survey Generation] Failed to log schema structure', [
                    'error' => $schemaLogError->getMessage()
                ]);
            }

            try {
                $response = Prism::structured()
                    ->using(...AIModel::getDefaultConfig())
                    ->withSchema($surveySchema)
                    ->withSystemPrompt($systemPrompt)
                    ->withPrompt($prompt)
                    ->usingTemperature(0.3)
                    ->withClientOptions(['timeout' => 120])
                    ->asStructured();
                
                Log::info('[Survey Generation] AI response received successfully (streaming)');
                
                $totalPlanned = count($plannedTypes) > 0 ? count($plannedTypes) : 6;
                // Send completion progress update
                echo "data: " . json_encode([
                    'progress' => [
                        'stage' => 'questions_complete',
                        'message' => 'Questions generated successfully!',
                        'current' => $totalPlanned,
                        'total' => $totalPlanned
                    ]
                ]) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
                
                // Small delay to show completion message
                usleep(800000); // 0.8 seconds
            } catch (\Throwable $e) {
                $errorDetails = [
                    'error' => $e->getMessage(),
                    'error_type' => get_class($e),
                    'planned_types' => $plannedTypes,
                    'schema_name' => 'survey_generation',
                ];
                
                $previous = $e->getPrevious();
                if ($previous) {
                    $errorDetails['previous_error'] = $previous->getMessage();
                    $errorDetails['previous_error_type'] = get_class($previous);
                    
                    $response = null;
                    if (property_exists($previous, 'response') && $previous->response) {
                        $response = $previous->response;
                    } 
                    elseif (method_exists($previous, 'getResponse')) {
                        $response = $previous->getResponse();
                    } elseif (method_exists($previous, 'response')) {
                        $response = $previous->response();
                    }
                    
                    if ($response) {
                        try {
                            if (method_exists($response, 'body')) {
                                $responseBody = $response->body();
                            } 
                            elseif (method_exists($response, 'json')) {
                                $responseBody = json_encode($response->json());
                            }
                            elseif (method_exists($response, 'getBody')) {
                                $body = $response->getBody();
                                if (method_exists($body, 'rewind')) {
                                    $body->rewind();
                                }
                                $responseBody = $body->getContents();
                            } else {
                                $responseBody = (string) $response;
                            }
                            
                            $errorDetails['response_body'] = $responseBody;
                            $errorDetails['response_body_length'] = strlen($responseBody);
                            $jsonError = json_decode($responseBody, true);
                            if ($jsonError) {
                                $errorDetails['parsed_json'] = $jsonError;
                                if (isset($jsonError['error'])) {
                                    $errorDetails['openai_error'] = $jsonError['error'];
                                    if (isset($jsonError['error']['message'])) {
                                        $errorDetails['openai_error_message'] = $jsonError['error']['message'];
                                    }
                                    if (isset($jsonError['error']['type'])) {
                                        $errorDetails['openai_error_type'] = $jsonError['error']['type'];
                                    }
                                    if (isset($jsonError['error']['code'])) {
                                        $errorDetails['openai_error_code'] = $jsonError['error']['code'];
                                    }
                                }
                            } else {
                                $errorDetails['json_parse_error'] = json_last_error_msg();
                            }
                        } catch (\Throwable $bodyError) {
                            $errorDetails['body_read_error'] = $bodyError->getMessage();
                            $errorDetails['body_read_error_type'] = get_class($bodyError);
                            $errorDetails['body_read_error_trace'] = $bodyError->getTraceAsString();
                        }
                    } else {
                        $errorDetails['no_response_object'] = 'Response object not accessible';
                        try {
                            $reflection = new \ReflectionClass($previous);
                            if ($reflection->hasProperty('response')) {
                                $prop = $reflection->getProperty('response');
                                $prop->setAccessible(true);
                                $response = $prop->getValue($previous);
                                if ($response && method_exists($response, 'body')) {
                                    $errorDetails['response_body_via_reflection'] = $response->body();
                                }
                            }
                        } catch (\Throwable $reflectionError) {
                            $errorDetails['reflection_error'] = $reflectionError->getMessage();
                        }
                    }
                } else {
                    $errorDetails['no_previous_exception'] = true;
                }
                
                Log::error('[Survey Generation] Schema validation failed (streaming)', $errorDetails);
                
                if (isset($errorDetails['openai_error_message'])) {
                    Log::error('[Survey Generation] OpenAI Error Message', [
                        'full_error' => $errorDetails['openai_error_message'],
                        'error_type' => $errorDetails['openai_error_type'] ?? 'unknown',
                        'error_code' => $errorDetails['openai_error_code'] ?? 'unknown',
                    ]);
                }
                
                $isTimeoutError = str_contains($e->getMessage(), 'Timeout') || 
                                 str_contains($e->getMessage(), 'timeout') ||
                                 str_contains($e->getMessage(), 'timed out') ||
                                 (isset($errorDetails['previous_error']) && 
                                  (str_contains($errorDetails['previous_error'], 'Timeout') || 
                                   str_contains($errorDetails['previous_error'], 'timeout')));
                
                if ($isTimeoutError) {
                    Log::error('Survey generation stream failed', [
                        'error' => $e->getMessage()
                    ]);
                    
                    echo "data: " . json_encode([
                        'progress' => [
                            'stage' => 'error',
                            'message' => 'Generation timed out. Please try again.',
                            'current' => 0,
                            'total' => 0
                        ]
                    ]) . "\n\n";
                    if (ob_get_level() > 0) ob_flush();
                    flush();
                    
                    $this->markUserMessageUnanswered($request);
                    echo "data: " . json_encode([
                        'error' => 'The survey generation is taking longer than expected. This might be due to network issues or high server load. ' .
                                   'Please try again in a few moments. If the problem persists, try generating a smaller survey or contact support.'
                    ]) . "\n\n";
                    if (ob_get_level() > 0) ob_flush();
                    flush();
                    return;
                }
                
                echo "data: " . json_encode([
                    'progress' => [
                        'stage' => 'error',
                        'message' => 'An error occurred during generation.',
                        'current' => 0,
                        'total' => 0
                    ]
                ]) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
                
                throw $e;
            }

            $surveyData = $response->structured ?? [];
            $rawQuestions = $surveyData['questions'] ?? [];
            $surveyData['questions'] = $this->filterIncompleteSurveyQuestions($rawQuestions);
            
            Log::info('[Survey Generation] Parsing survey data', [
                'has_questions' => isset($surveyData['questions']),
                'questions_count' => count($surveyData['questions']),
                'question_types_generated' => array_column($surveyData['questions'], 'question_type'),
                'filtered_from' => count($rawQuestions) - count($surveyData['questions'])
            ]);
            
            if (empty($surveyData['questions']) || !is_array($surveyData['questions'])) {
                Log::error('[Survey Generation] Invalid survey data structure', [
                    'survey_data_keys' => array_keys($surveyData),
                    'questions_type' => gettype($surveyData['questions'] ?? null)
                ]);
                $this->markUserMessageUnanswered($request);
                echo "data: " . json_encode(['error' => 'Failed to generate survey questions. Please try again.']) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
                return;
            }

            $totalQuestions = count($surveyData['questions']);
            $questionTypes = array_column($surveyData['questions'], 'question_type');
            
            Log::info('[Survey Generation] Survey generated successfully', [
                'total_questions' => $totalQuestions,
                'question_types' => $questionTypes,
                'type_counts' => array_count_values($questionTypes)
            ]);
            
            // Send final completion progress update before showing confirmation
            $totalPlanned = count($plannedTypes) > 0 ? count($plannedTypes) : $totalQuestions;
            echo "data: " . json_encode([
                'progress' => [
                    'stage' => 'finalizing',
                    'message' => 'Finalizing survey structure...',
                    'current' => $totalPlanned,
                    'total' => $totalPlanned
                ]
            ]) . "\n\n";
            if (ob_get_level() > 0) ob_flush();
            flush();
            
            // Delay to show finalizing message
            usleep(800000); // 0.8 seconds
            
            // Send completion message
            echo "data: " . json_encode([
                'progress' => [
                    'stage' => 'complete',
                    'message' => "Survey with {$totalQuestions} questions ready!",
                    'current' => $totalPlanned,
                    'total' => $totalPlanned
                ]
            ]) . "\n\n";
            if (ob_get_level() > 0) ob_flush();
            flush();
            
            // Delay to show completion message before confirmation
            usleep(1000000); // 1 second
            
            $confirmationMessage = 'I\'ve generated a complete survey with ' . $totalQuestions . ' questions. Would you like me to create all these questions?';
            
            $actionData = [
                'action' => 'express_survey_create',
                'scope' => 'survey',
                'intent' => 'create',
                'message' => $confirmationMessage,
                'survey_data' => $surveyData,
                'questionnaire_code' => $questionnaireCode,
                'total_questions' => $totalQuestions,
                'show_confirmation' => true,
                'confirmation_buttons' => [
                    [
                        'text' => 'Yes',
                        'action' => 'confirm',
                        'style' => 'primary',
                        'data' => [
                            'action' => 'express_survey_create',
                            'scope' => 'survey',
                            'intent' => 'create',
                            'survey_data' => $surveyData,
                            'questionnaire_code' => $questionnaireCode
                        ]
                    ],
                    [
                        'text' => 'Cancel',
                        'action' => 'cancel',
                        'style' => 'secondary',
                        'data' => []
                    ]
                ]
            ];

            // Get platform from request or default to 'express'
            $platform = $request->input('platform', 'express');
            
            $this->memoryService->appendMessage(
                $request->user()->id,
                $platform,
                'assistant',
                $confirmationMessage
            );
            
            $mongoConversation = $request->input('_mongo_conversation');
            if ($mongoConversation) {
                try {
                    $result = $this->conversationStorage->addAssistantMessage(
                        $mongoConversation,
                        $confirmationMessage,
                        0,
                        [
                            'action' => $actionData['action'],
                            'scope' => $actionData['scope'],
                            'intent' => $actionData['intent'],
                        ]
                    );
                } catch (\Throwable $e) {
                    Log::warning('MongoDB conversation storage failed (survey stream)', ['error' => $e->getMessage()]);
                }
            }
            
            $responseData = [
                'action_data' => $actionData,
                'done' => true
            ];
            if ($mongoConversation) {
                $responseData['conversation_id'] = $mongoConversation->conversation_id;
            }
            echo "data: " . json_encode($responseData) . "\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
            } catch (\Throwable $e) {
                Log::error('[ChatController] Survey stream error', ['error' => $e->getMessage()]);
                echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
