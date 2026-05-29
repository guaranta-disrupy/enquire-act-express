<?php

namespace App\Http\Controllers\Api\AI\Traits;

use App\AI\Enums\Intent;
use App\AI\Enums\Scope;
use App\AI\Enums\Platform;
use App\AI\Enums\EXPRESSType;
use App\AI\Enums\AIModel;
use App\Services\ChatService;
use App\Services\PromptService;
use App\Services\Neo4JService;
use App\Services\ConversationMemoryService;
use App\Services\ConversationStorageService;
use App\Services\TokenManagerService;
use App\Models\AI\MongoTokenManager;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateBaseQuestionSchema;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Prism;

trait ChatControllerHelpers
{
    protected ChatService $chatService;
    protected PromptService $promptService;
    protected Neo4JService $neo4jService;
    protected ConversationMemoryService $memoryService;
    protected ConversationStorageService $conversationStorage;
    protected TokenManagerService $tokenManager;

    protected function getUserTypeInfo(Request $request): array
    {
        $user = $request->user();
        if (!$user) {
            return ['type' => 'normal', 'firstName' => null];
        }
        $firstName = $user->name ? trim(explode(' ', $user->name)[0]) : null;
        return ['type' => 'normal', 'firstName' => $firstName];
    }

    protected function buildSystemPrompt(string $basePrompt, string $userType, ?string $firstName, ?string $memoryContext = null): string
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

    protected function storeUserMessage(Request $request, string $message, array $metadata = []): ?object
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

    protected function storeAssistantMessage(?object $mongoConversation, string $message, int $responseTime = 0, array $metadata = []): ?string
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

    protected function checkTokenLimit(Request $request): ?string
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

    protected function buildCompleteSurveyQuestionSchema(array $plannedTypes = []): ObjectSchema
    {
        $allQuestionTypes = ['descriptive', 'text_selection', 'image_selection', 'single_textbox', 'multiple_textbox', 'open_ended', 'slider', 'ranking', 'star_rating', 'button_rating', 'segmentation', 'matrix', 'pairwise_comparison', 'best-worst_scale', 'pie_chart', 'bar_chart'];
        $schemaQuestionTypes = !empty($plannedTypes) ? $plannedTypes : $allQuestionTypes;

        return new ObjectSchema(
            name: 'survey_generation',
            description: 'Generate a complete survey with multiple questions. Use the same schema structure as individual question creation - each question type requires its specific fields (e.g. matrix requires rows AND columns, slider requires items AND choices).',
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
                                description: 'Question type - use ONLY one of the allowed types. Each type has required fields: text_selection/image_selection=text_options; multiple_textbox=textboxes; slider=items+choices; ranking=items; star_rating=items+number_of_rating_stars; button_rating=items+choices; matrix=rows+columns.',
                                options: $schemaQuestionTypes
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
                                description: 'REQUIRED for slider, ranking, star_rating, and button_rating. CRITICAL: For SLIDER, "items" are the SPECIFIC SCENARIOS/SITUATIONS/ASPECTS being rated (what respondents evaluate), NOT the scale labels. For RANKING: items are the options to rank (e.g., brand names, product features, importance levels). For STAR_RATING: items are the aspects to rate with stars (e.g., "Product quality", "Customer service", "Value for money"). For BUTTON_RATING: same as star_rating. Generate 3-6 items for ranking/star_rating/button_rating. Required for slider (CreateSliderItemSchema: index, name, equivalent, random_item), ranking (CreateRankingItemSchema: text, equivalent, random_item), star_rating (CreateStarRatingItemSchema: text, equivalent, random_item), button_rating (CreateButtonRatingItemSchema: text, equivalent, random_item). Set to null for other question types.',
                                items: new ObjectSchema(
                                    name: 'item',
                                    description: 'An item - structure varies by question type. For slider: include index (number), name (string with HTML), equivalent (string), random_item (boolean). IMPORTANT: Slider items must be coherent, related scenarios/aspects being rated (e.g., "When I see messaging on signage", "When staff speaks to me" for influence questions; "Product quality", "Customer service", "Price value" for satisfaction questions). Items must form a logical set. NOT scale labels. For ranking/star_rating/button_rating: include text (string), equivalent (string), random_item (boolean).',
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
                                            description: 'The item name with HTML formatting (for slider items). MUST be a coherent scenario/aspect being rated, NOT a scale label. Examples: "<p>When I see messaging on signage as I approach the gas station</p>", "<p>Atmosphere of the party</p>", "<p>Quality of food and drinks</p>", "<p>Entertainment provided</p>". WRONG examples: "<p>Not at all</p>", "<p>Slightly</p>", "<p>Very</p>" - these are scale labels and belong in "choices", not "items". Items should form a logical, coherent set. Required for slider items, set to null for ranking/star_rating/button_rating items.',
                                            nullable: true
                                        ),
                                        new StringSchema(
                                            name: 'equivalent',
                                            description: 'The plain text version without HTML (required for all item types). MUST be the same as the item text/name but without HTML tags. For slider: plain text version of name (e.g., if name is "<p>When I see messaging on signage</p>", equivalent should be "When I see messaging on signage"). For ranking/star_rating/button_rating: plain text version of text (e.g., if text is "Starbucks", equivalent should be "Starbucks"). The equivalent value should always match the text/name value.',
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
                                description: 'CRITICAL DISTINCTION: For SLIDER questions, "choices" are the SCALE LABELS/VALUES for the slider (the rating scale itself), NOT the topics/scenarios being rated. Choices must be contextually appropriate scale labels that match the question\'s intent and form a coherent progression. Examples: For influence questions: "Very unlikely to be influenced", "Unlikely", "Neutral", "Likely", "Highly likely to be influenced". For satisfaction: "Very dissatisfied", "Dissatisfied", "Neutral", "Satisfied", "Very satisfied". For agreement: "Strongly Disagree", "Disagree", "Neutral", "Agree", "Strongly Agree". For quality: "Poor", "Fair", "Good", "Very Good", "Excellent". PREFER TEXT-BASED SCALE LABELS over numeric values. Generate 3-7 text-based scale labels that form a coherent rating scale appropriate for the question context. The scale must make sense for rating the items provided. Use numeric values ("1", "2", "3", etc.) only if text labels are not suitable. DO NOT use topic/scenario names like "Atmosphere" or "When staff speaks to me" as choices - those belong in "items". Required for slider and button_rating. For slider: use CreateSliderChoiceSchema format (value, colour). For button_rating: use CreateButtonRatingChoiceSchema format (value, colour). Set to null for other question types.',
                                items: new ObjectSchema(
                                    name: 'choice',
                                    description: 'A choice - structure varies by question type. For slider: include value (string), colour (string). IMPORTANT: Slider choices must be contextually appropriate text-based scale labels that match the question\'s intent (e.g., for influence: "Very unlikely to be influenced" to "Highly likely to be influenced"; for satisfaction: "Very dissatisfied" to "Very satisfied"). The scale must form a coherent progression appropriate for rating the items. NOT topics/scenarios being rated. For button_rating: include value (string), colour (string).',
                                    properties: [
                                        new StringSchema(
                                            name: 'value',
                                            description: 'The scale label/value for the choice. For slider: PREFER contextually appropriate text-based labels that match the question\'s intent. Use human-readable words with spaces only—never underscores (e.g. "very low", "very high", not "very_low", "very_high"). Examples: for influence "Very unlikely to be influenced", "Unlikely", "Neutral", "Likely", "Highly likely to be influenced"; for satisfaction "Very dissatisfied" to "Very satisfied". The label should form a coherent progression. Use numeric values ("1", "2", "3") only if text labels are not appropriate. For button_rating, value ALWAYS depends on the question (e.g. "Yes"/"No" for binary, or scale labels). Required for slider and button_rating choices.',
                                            nullable: false
                                        ),
                                        new StringSchema(
                                            name: 'colour',
                                            description: 'The hexadecimal colour for the choice. Use color gradients that match the scale intensity: darker red (#b71c1c or #FF0000) for negative/low values (e.g., "Very unlikely", "Very dissatisfied", "Poor"), orange/yellow (#ff9800 or #FFA500) for neutral/middle values (e.g., "Neutral", "Moderately"), green/teal (#8bc34a, #009688, or #00FF00) for positive/high values (e.g., "Highly likely", "Very satisfied", "Excellent"). Create a smooth gradient from negative to positive across the scale. Required for slider and button_rating choices.',
                                            nullable: false
                                        )
                                    ],
                                    requiredFields: ['value', 'colour']
                                ),
                                nullable: true
                            ),
                            new ArraySchema(
                                name: 'rows',
                                description: 'CRITICAL DISTINCTION: For MATRIX questions, "rows" are the STATEMENTS/ASPECTS being evaluated (what respondents rate), NOT the answer options. Rows must be coherent, related statements or aspects that form a logical set. Examples: For satisfaction: "Product quality", "Customer service", "Price value", "Ease of use". For agreement: "The product meets my needs", "I would recommend this product", "The price is reasonable". Generate 3-8 related rows that make sense together. Each row should be a complete statement or descriptive phrase. DO NOT use answer options like "Very Satisfied" or "Strongly Agree" as rows - those belong in "columns". Required for matrix questions. Set to null for other question types.',
                                items: (new \App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateMatrixRowSchema())->getSchema(),
                                nullable: true
                            ),
                            new ArraySchema(
                                name: 'columns',
                                description: 'CRITICAL DISTINCTION: For MATRIX questions, "columns" are the ANSWER CHOICES/OPTIONS (the rating scale itself), NOT the statements being evaluated. Columns must be contextually appropriate answer options that match the question\'s intent and form a coherent set. Examples: For satisfaction: "Very Satisfied", "Satisfied", "Neutral", "Dissatisfied", "Very Dissatisfied". For agreement: "Strongly Agree", "Agree", "Neutral", "Disagree", "Strongly Disagree". For frequency: "Always", "Often", "Sometimes", "Rarely", "Never". Generate 3-7 columns that form a logical set of answer options appropriate for rating the rows. DO NOT use statement names like "Product quality" or "Customer service" as columns - those belong in "rows". Required for matrix questions. Set to null for other question types.',
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

    protected function estimateTokens(string $text): int
    {
        $length = mb_strlen($text ?? '');
        if ($length === 0) {
            return 0;
        }
        // Heuristic: ~4 chars per token on average for English-like text.
        return (int) ceil($length / 4);
    }

    protected function recordTokenUsage(int $userId, int $tokens): void
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

    protected function hasTokenRemaining(int $userId, int $neededTokens): bool
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

    protected function markUserMessageUnanswered(Request $request): void
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

    protected function isOffTopicResponse(string $response): bool
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

    /**
     * Find the closest match from a list of words using Levenshtein distance.
     * Returns the matched word if distance is within threshold, null otherwise.
     * 
     * @param string $input The input word to match
     * @param array $words Array of valid words to match against
     * @param int $maxDistance Maximum allowed Levenshtein distance (default: 2 for small typos)
     * @return string|null The matched word or null if no close match found
     */
    protected function findClosestMatch(string $input, array $words, int $maxDistance = 2): ?string
    {
        $input = strtolower(trim($input));
        $shortest = -1;
        $closest = null;
        
        foreach ($words as $word) {
            $wordLower = strtolower(trim($word));
            
            // Exact match
            if ($input === $wordLower) {
                return $wordLower;
            }
            
            // Calculate Levenshtein distance
            $distance = levenshtein($input, $wordLower);
            
            // Check if this is a closer match
            if ($distance <= $maxDistance && ($shortest < 0 || $distance < $shortest)) {
                $shortest = $distance;
                $closest = $wordLower;
            }
        }
        
        return $closest;
    }

    /**
     * Normalize a message by correcting common typos in quick response words.
     * This helps handle typos like "thjanks" -> "thanks", "helo" -> "hello", etc.
     * Handles both single words and multi-word phrases.
     * 
     * @param string $message The original message
     * @return string The normalized message with typos corrected
     */
    protected function normalizeMessage(string $message): string
    {
        $normalized = strtolower(trim($message));
        
        // Common quick response phrases (check these first, before single words)
        $quickPhrases = [
            'thank you',
            'what\'s up',
            'whats up',
            'got it',
        ];
        
        // Common quick response words
        $quickWords = [
            'thanks', 'thankyou',
            'hello', 'hi', 'hey', 'hiya', 'howdy',
            'ok', 'okay', 'yes', 'no', 'sure', 'yep', 'nope', 'alright',
            'greetings', 'sup', 'yo'
        ];
        
        // First, try to match multi-word phrases
        foreach ($quickPhrases as $phrase) {
            $phraseLength = strlen($phrase);
            if (strlen($normalized) < $phraseLength - 2) {
                continue; // Message too short
            }
            
            // Extract the start of the message (phrase length + some tolerance for typos)
            $messageStart = substr($normalized, 0, min(strlen($normalized), $phraseLength + 2));
            
            // Check if message starts with a typo version of the phrase
            // Compare first phraseLength characters
            $messagePhrase = substr($messageStart, 0, $phraseLength);
            $distance = levenshtein($messagePhrase, $phrase);
            
            if ($distance <= 2) {
                // Replace the start with the correct phrase
                $remaining = trim(substr($normalized, strlen($messagePhrase)));
                return $phrase . ($remaining ? ' ' . $remaining : '');
            }
        }
        
        // If no phrase matched, check single words
        $words = preg_split('/\s+/', $normalized);
        $correctedWords = [];
        
        foreach ($words as $word) {
            // Remove punctuation for matching
            $cleanWord = preg_replace('/[!?.,;:]+$/', '', $word);
            $punctuation = substr($word, strlen($cleanWord));
            
            // Try to find closest match in single words
            $match = $this->findClosestMatch($cleanWord, $quickWords, 2);
            
            if ($match !== null) {
                $correctedWords[] = $match . $punctuation;
            } else {
                $correctedWords[] = $word;
            }
        }
        
        return implode(' ', $correctedWords);
    }

    /**
     * Check if a message starts with a word from a list, with typo tolerance.
     * 
     * @param string $message The message to check
     * @param array $words Array of words to check against
     * @param int $maxDistance Maximum Levenshtein distance allowed
     * @return string|null The matched word or null
     */
    protected function startsWithTypoTolerant(string $message, array $words, int $maxDistance = 2): ?string
    {
        $message = strtolower(trim($message));
        
        foreach ($words as $word) {
            $wordLower = strtolower(trim($word));
            
            // Exact match
            if (str_starts_with($message, $wordLower)) {
                return $wordLower;
            }
            
            // Check if message starts with a typo version of the word
            // Extract first N characters where N is the word length + maxDistance
            $checkLength = min(strlen($message), strlen($wordLower) + $maxDistance);
            $messageStart = substr($message, 0, $checkLength);
            
            // Try to find match in the beginning of the message
            $match = $this->findClosestMatch(substr($messageStart, 0, strlen($wordLower)), [$wordLower], $maxDistance);
            if ($match !== null) {
                // Verify it's actually at the start (allowing for small typos)
                $distance = levenshtein(substr($message, 0, strlen($wordLower)), $wordLower);
                if ($distance <= $maxDistance) {
                    return $wordLower;
                }
            }
        }
        
        return null;
    }

    protected function detectSimpleGreetings(string $message): ?array
    {
        $message = strtolower(trim($message));
        $normalizedMessage = $this->normalizeMessage($message);
        
        $greetingPatterns = config('ai_detection.greeting_patterns', []);
        
        // First try exact match on normalized message
        foreach ($greetingPatterns as $greeting) {
            $greetingLower = strtolower($greeting);
            if ($normalizedMessage === $greetingLower) {
                Log::info('Fast-path: Simple greeting detected (with typo correction)', [
                    'original' => $message,
                    'normalized' => $normalizedMessage,
                    'matched' => $greeting
                ]);
                return ['scope' => 'conversation', 'intent' => 'conversation'];
            }
        }
        
        // Try typo-tolerant matching
        foreach ($greetingPatterns as $greeting) {
            $greetingLower = strtolower($greeting);
            
            // Check if normalized message starts with greeting
            if (str_starts_with($normalizedMessage, $greetingLower)) {
                $remaining = trim(substr($normalizedMessage, strlen($greetingLower)));
                $simpleFollowUps = ['', '!', '?', '!?', '?!', ',', '.', ' there', ' there!', ' there?'];
                $followUpPatterns = ['how are you', 'how\'re you', 'howre you', 'what\'s up', 'whats up', 'sup'];
                
                if (empty($remaining) || in_array($remaining, $simpleFollowUps)) {
                    Log::info('Fast-path: Simple greeting detected (with punctuation and typo correction)', [
                        'original' => $message,
                        'normalized' => $normalizedMessage,
                        'matched' => $greeting
                    ]);
                    return ['scope' => 'conversation', 'intent' => 'conversation'];
                }
                
                foreach ($followUpPatterns as $followUp) {
                    if (str_starts_with($remaining, ' ' . $followUp) || str_starts_with($remaining, $followUp)) {
                        Log::info('Fast-path: Simple greeting with follow-up detected (with typo correction)', [
                            'original' => $message,
                            'normalized' => $normalizedMessage,
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

    protected function isQuickResponse(string $message): bool
    {
        $message = strtolower(trim($message));
        $normalizedMessage = $this->normalizeMessage($message);
        
        $quickPatterns = ['hi', 'hello', 'greetings', 'thanks', 'thank you', 'hey', 'hiya', 'howdy', 'sup', 'yo', 'what\'s up', 'whats up'];
        // Short acknowledgments – treat as quick conversation, do not pass to helpbot
        $shortAcknowledgments = ['ok', 'okay', 'yes', 'no', 'sure', 'yep', 'nope', 'alright', 'got it', 'k', 'kk'];
        
        // Check for "thanks" or "thank you" with typo tolerance
        $thanksMatch = $this->startsWithTypoTolerant($normalizedMessage, ['thanks', 'thank you'], 2);
        if ($thanksMatch !== null) {
            $remaining = trim(substr($normalizedMessage, strlen($thanksMatch)));
            if (!empty($remaining) && strlen($remaining) > 3) {
                $justPunctuation = preg_match('/^[!?.,;:\s]*$/', $remaining);
                if (!$justPunctuation) {
                    return false;
                }
            }
            return true;
        }
        
        // Check short acknowledgments with typo tolerance
        foreach ($shortAcknowledgments as $ack) {
            $ackMatch = $this->findClosestMatch($normalizedMessage, [$ack], 2);
            if ($ackMatch !== null) {
                // Check if it's an exact match or with punctuation
                $cleanMessage = preg_replace('/[!?.,;:\s]*$/', '', $normalizedMessage);
                if ($cleanMessage === $ack || 
                    str_starts_with($normalizedMessage, $ack . ' ') || 
                    str_starts_with($normalizedMessage, $ack . ',')) {
                    return true;
                }
            }
        }
        
        // Check quick patterns with typo tolerance
        foreach ($quickPatterns as $pattern) {
            $patternMatch = $this->startsWithTypoTolerant($normalizedMessage, [$pattern], 2);
            if ($patternMatch !== null) {
                // Check exact match or with punctuation
                if ($normalizedMessage === $pattern ||
                    $normalizedMessage === $pattern . '!' ||
                    $normalizedMessage === $pattern . '?' ||
                    str_starts_with($normalizedMessage, $pattern . ' ') ||
                    str_starts_with($normalizedMessage, $pattern . '!') ||
                    str_starts_with($normalizedMessage, $pattern . '?')) {
                    return true;
                }
            }
        }
        
        return false;
    }

    protected function getQuickResponse(string $message, array $userInfo): string
    {
        $message = strtolower(trim($message));
        $normalizedMessage = $this->normalizeMessage($message);
        $firstName = $userInfo['firstName'] ?? '';
        $nameSuffix = $firstName ? " {$firstName}" : "";
        
        // Check for "thanks" or "thank you" with typo tolerance
        $thanksMatch = $this->startsWithTypoTolerant($normalizedMessage, ['thanks', 'thank you'], 2);
        if ($thanksMatch !== null) {
            return "You're welcome{$nameSuffix}! How can I help you with EXPRESS today?";
        }
        
        $shortAcknowledgments = ['ok', 'okay', 'yes', 'no', 'sure', 'yep', 'nope', 'alright', 'got it', 'k', 'kk'];
        foreach ($shortAcknowledgments as $ack) {
            $ackMatch = $this->findClosestMatch($normalizedMessage, [$ack], 2);
            if ($ackMatch !== null) {
                $cleanMessage = preg_replace('/[!?.,;:\s]*$/', '', $normalizedMessage);
                if ($cleanMessage === $ack || 
                    str_starts_with($normalizedMessage, $ack . ' ') || 
                    str_starts_with($normalizedMessage, $ack . ',')) {
                    return "Got it{$nameSuffix}! What would you like to do next?";
                }
            }
        }
        
        // Check greetings with typo tolerance
        $greetingMatch = $this->startsWithTypoTolerant($normalizedMessage, ['hi', 'hello', 'hey', 'greetings', 'howdy', 'hiya'], 2);
        if ($greetingMatch !== null || 
            $normalizedMessage === 'sup' || 
            $normalizedMessage === 'yo' ||
            str_starts_with($normalizedMessage, 'what\'s up') || 
            str_starts_with($normalizedMessage, 'whats up')) {
            return "Hello{$nameSuffix}! I'm here to help you with EXPRESS, our survey platform. What would you like to know?";
        }
        
        return "Hello{$nameSuffix}! How can I help you with EXPRESS today?";
    }

    /**
     * Detect friendly chat questions (e.g. "what's your name?", "who are you?") that we answer ourselves
     * without sending to the AI helpbot. Uses raw message content to avoid normalization changing the phrase.
     */
    protected function isFriendlyChatQuestion(string $message): bool
    {
        $raw = strtolower(trim($message));
        $patterns = [
            'your name', 'whats your name', 'what\'s your name', 'what is your name',
            'who are you', 'who is this', 'what are you', 'who am i talking to',
            'what do you do', 'how are you', 'how\'re you', 'howre you',
        ];
        foreach ($patterns as $pattern) {
            if (str_contains($raw, $pattern)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return a friendly reply for identity/small-talk questions. Handled locally, no helpbot call.
     */
    protected function getFriendlyChatResponse(string $message, array $userInfo): string
    {
        $raw = strtolower(trim($message));
        $firstName = $userInfo['firstName'] ?? '';
        $nameSuffix = $firstName ? " {$firstName}" : '';

        // Name / identity
        if (str_contains($raw, 'your name') || str_contains($raw, 'whats your name') || str_contains($raw, 'what is your name')) {
            return "I'm EXPRESS AI{$nameSuffix}! I'm your assistant for the EXPRESS survey platform. What would you like to do today?";
        }
        if (str_contains($raw, 'who are you') || str_contains($raw, 'who is this') || str_contains($raw, 'what are you') || str_contains($raw, 'who am i talking to')) {
            return "I'm EXPRESS AI, your assistant for building and managing surveys in EXPRESS. How can I help you{$nameSuffix}?";
        }
        if (str_contains($raw, 'what do you do')) {
            return "I help you work with EXPRESS—creating and editing surveys, questions, and logic. What would you like to do{$nameSuffix}?";
        }
        // How are you
        if (str_contains($raw, 'how are you') || str_contains($raw, 'howre you') || str_contains($raw, 'how\'re you')) {
            return "I'm doing well, thanks for asking{$nameSuffix}! Ready to help with EXPRESS whenever you need. What can I do for you?";
        }

        return "I'm EXPRESS AI{$nameSuffix}! How can I help you with EXPRESS today?";
    }

    protected function detectIntentAndScope(string $message, ?string $lastAssistantMessage = null, ?array $embedding = null): array
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
            
            $questionOptionsPattern = $this->detectQuestionOptionsUpdate($message);
            if ($questionOptionsPattern) {
                return $questionOptionsPattern;
            }
            
            $choiceUpdatePattern = $this->detectChoiceUpdate($message);
            if ($choiceUpdatePattern) {
                return $choiceUpdatePattern;
            }
            
            if (!$embedding) {
                $embedding = $this->chatService->getEmbedding($message);
            }
            
            $promptMatch = $this->neo4jService->checkPromptMatch($embedding, 0.85);
            if ($promptMatch) {
                Log::info('[ChatControllerHelpers] 3a Neo4j prompt match', [
                    'prompt_name' => $promptMatch['node']['name'] ?? 'unknown',
                    'score' => $promptMatch['score'],
                ]);
                return ['scope' => 'conversation', 'intent' => 'guide', 'details' => null];
            }
            $detectionPrompt = $message;
            if ($lastAssistantMessage) {
                $detectionPrompt = "Previous AI message: {$lastAssistantMessage}\n\nUser message: {$message}\n\nDetermine the intent and scope based on both messages.";
                Log::info('[ChatControllerHelpers] 3a Enhanced detection with last AI message', [
                    'last_ai_preview' => mb_substr($lastAssistantMessage, 0, 150)
                ]);
            }
            
            $schema = $this->buildDetectionSchema();
            $request = Prism::structured()
                ->using(...AIModel::getSummaryConfig())
                ->withSchema($schema)
                ->withPrompt($detectionPrompt)
                ->usingTemperature(0.1);

            Log::info('[ChatControllerHelpers] 3a intent detection prompt', ['message' => mb_substr($message, 0, 200)]);
            $response = $request->asStructured();
            $geminiResult = $response->structured ?? [];
            
            $geminiScope = $geminiResult['scope'] ?? null;
            $geminiIntent = $geminiResult['intent'] ?? null;

            // Merge legacy "documentation" intent into "guide"
            if ($geminiIntent === 'documentation') {
                $geminiIntent = 'guide';
                $geminiResult['intent'] = 'guide';
            }

            $validScopes = Scope::values();
            $validIntents = Intent::values();

            $isValidScope = $geminiScope && in_array($geminiScope, $validScopes);
            $isValidIntent = $geminiIntent && in_array($geminiIntent, $validIntents);
            
            Log::info('[ChatControllerHelpers] 3b Gemini detection result', [
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
            
            // Override action intents (create, update, delete, move, duplicate, etc.) to 'guide' 
            // when the message is a guidance-seeking question (e.g., "How can I edit...?")
            $actionIntents = ['create', 'update', 'delete', 'move', 'duplicate', 'copy', 'manage'];
            if (in_array($geminiIntent, $actionIntents, true) && $this->isGuidanceSeekingQuestion($message)) {
                Log::info('[ChatControllerHelpers] 3e Override: Guidance-seeking question but Gemini said action intent, correcting to guide', [
                    'original_intent' => $geminiIntent,
                    'original_scope' => $geminiScope,
                    'corrected_intent' => 'guide'
                ]);
                $structured['intent'] = 'guide';
                $structured['scope'] = $structured['scope'] ?? 'conversation';
            }
            
            // Correct scope mapping: "multiple choice" should map to "text_selection", not "multiple_textbox"
            // "multiple textbox" is a different question type (question type ID 5)
            // Only correct if user said "multiple choice" but NOT if they explicitly said "multiple textbox"
            $messageLower = mb_strtolower(trim($message));
            $hasMultipleChoice = str_contains($messageLower, 'multiple choice') || 
                                 str_contains($messageLower, 'multiple-choice') ||
                                 preg_match('/\bmultiple\s+choice\b/i', $message);
            $hasMultipleTextbox = str_contains($messageLower, 'multiple textbox') ||
                                   str_contains($messageLower, 'multiple-textbox') ||
                                   preg_match('/\bmultiple\s+textbox\b/i', $message);
            
            if ($geminiScope === 'multiple_textbox' && $hasMultipleChoice && !$hasMultipleTextbox) {
                Log::info('[ChatControllerHelpers] 3e Override: Correcting scope from multiple_textbox to text_selection for "multiple choice"', [
                    'original_scope' => $geminiScope,
                    'corrected_scope' => 'text_selection',
                    'message' => $message
                ]);
                $structured['scope'] = 'text_selection';
                $geminiScope = 'text_selection'; // Update for validation check
                $isValidScope = true; // Ensure validation passes
            }

            // When Gemini returns valid intent but missing/invalid scope, infer scope from intent instead of discarding both
            if (!$isValidScope && $isValidIntent) {
                $inferredScope = in_array($geminiIntent, ['conversation', 'guide'], true)
                    ? $geminiIntent
                    : 'conversation';
                $structured['scope'] = $inferredScope;
                $isValidScope = true;
                Log::info('[ChatControllerHelpers] 3e Inferred scope from Gemini intent', ['scope' => $inferredScope, 'intent' => $geminiIntent]);
            }
            
            if (!$isValidScope || !$isValidIntent) {
                Log::info('[ChatControllerHelpers] 3e Gemini invalid/empty, using pattern fallback', [
                    'invalid_scope' => !$isValidScope,
                    'invalid_intent' => !$isValidIntent,
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
                    Log::info('[ChatControllerHelpers] 3e Pattern fallback: question', ['final_intent' => 'guide']);
                } else {
                    $structured = ['scope' => 'conversation', 'intent' => 'conversation'];
                    Log::info('[ChatControllerHelpers] 3e Pattern fallback: conversation', ['final_intent' => 'conversation']);
                }
            } else {
                Log::info('[ChatControllerHelpers] 3c Using Gemini detection (trusted)', [
                    'final_scope' => $structured['scope'],
                    'final_intent' => $structured['intent']
                ]);
            }
            
            $confirmationResponse = $this->detectConfirmationResponse($message);
            $hasConfirmation = false;
            $lastMessageIsConfirmationQuestion = $this->isLastMessageConfirmationQuestion($lastAssistantMessage);
            if ($confirmationResponse && $lastMessageIsConfirmationQuestion) {
                $hasConfirmation = true;
                $originalScope = $structured['scope'] ?? 'conversation';
                $originalDetails = $structured['details'] ?? null;
                
                Log::info('[ChatControllerHelpers] 3e Override: Confirmation response detected (previous AI asked for confirmation)', [
                    'preserved_scope' => $originalScope,
                ]);
                
                $structured = [
                    'scope' => $originalScope,
                    'intent' => 'create',
                    'details' => $originalDetails
                ];
            }
            
            Log::info('[ChatControllerHelpers] 3d Final detection result', [
                'final_scope' => $structured['scope'],
                'final_intent' => $structured['intent'],
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
                Log::info('[ChatControllerHelpers] 3e Error fallback: pattern detection');
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

    protected function isInstructionSeekingQuestion(string $message): bool
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

    /**
     * Check if a message is asking for guidance/instructions rather than requesting an action.
     * Questions like "How can I edit...?" should be answered with instructions, not executed as actions.
     */
    protected function isGuidanceSeekingQuestion(string $message): bool
    {
        $lower = mb_strtolower(trim($message));
        
        // Check for question mark
        if (!str_contains($lower, '?')) {
            return false;
        }
        
        // Patterns that indicate guidance-seeking questions
        $guidancePatterns = [
            'how can i ',
            'how do i ',
            'how to ',
            'how could i ',
            'how would i ',
            'what\'s the way to ',
            'whats the way to ',
            'what is the way to ',
            'can you tell me how ',
            'could you tell me how ',
            'what do i need to do to ',
            'where do i ',
            'where can i ',
            'can i ',
            'is it possible to ',
            'am i able to ',
            'do you support ',
        ];
        
        foreach ($guidancePatterns as $pattern) {
            if (str_starts_with($lower, $pattern) || str_contains($lower, ' ' . $pattern)) {
                return true;
            }
        }
        
        return false;
    }
    
    protected function detectQuestionTypeInMessage(string $message): ?string
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

    protected function detectQuestionTypeResponse(string $message): ?array
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

    protected function detectSurveyCreation(string $message): ?array
    {
        // Don't treat guidance questions ("How do I create a survey?") as survey creation—they are guide intent.
        if ($this->isInstructionSeekingQuestion($message)) {
            return null;
        }

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

    protected function detectDuplicateIntent(string $message): ?array
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

    protected function detectQuestionOptionsUpdate(string $message): ?array
    {
        $messageLower = strtolower(trim($message));
        
        // Pattern to match question identifiers: Q1, Q2, question 1, question 2, etc.
        $questionPattern = '/(?:^|\s)(?:q|question)\s*(\d+)/i';
        
        // Extract question number first
        if (!preg_match($questionPattern, $messageLower, $questionMatches)) {
            return null;
        }
        
        $questionNumber = (int)$questionMatches[1];
        $questionIdentifier = $questionMatches[0]; // e.g., " q1" or " question 1"
        
        // Option keywords mapping - order matters (more specific first)
        $optionPatterns = [
            'random_choices' => [
                'randomize choices', 'randomise choices', 'randomize choice', 'randomise choice',
                'random choices', 'random choice', 'shuffle choices', 'shuffle choice',
                'randomize', 'randomise', 'random', 'shuffle'
            ],
            'multiple_selection' => [
                'multiple selection', 'multiple answer', 'allow multiple', 'multi select', 
                'select multiple', 'multiple', 'multi'
            ],
            'required' => ['required', 'require', 'mandatory'],
            'dropdown' => ['dropdown', 'drop down', 'select dropdown', 'dropdown menu'],
            'select_all' => ['select all', 'enable select all', 'selectall'],
            'unselect_all' => ['unselect all', 'un-select all', 'enable unselect all', 'unselectall'],
            'scoring' => ['scoring', 'enable scoring', 'enable points', 'points', 'score']
        ];
        
        // Check for option keywords - can appear before or after question identifier
        $detectedOption = null;
        $optionValue = true; // Default to enable/true
        
        // Negation keywords that indicate disabling an option
        $negationKeywords = ['not', 'no', 'disable', 'turn off', 'deactivate', 'remove', 'unset', 'uncheck', 'off', 'un'];
        
        // Check each option type
        foreach ($optionPatterns as $optionType => $patterns) {
            foreach ($patterns as $pattern) {
                // Create pattern that matches option keyword near question identifier
                // Pattern can be: "randomize q1", "q1 randomize", "make q1 randomize", etc.
                $escapedPattern = preg_quote($pattern, '/');
                
                // Check if pattern exists in message
                if (preg_match('/\b' . $escapedPattern . '\b/i', $messageLower)) {
                    // Verify it's related to the question (appears near question identifier)
                    $questionPos = stripos($messageLower, trim($questionIdentifier));
                    $patternPos = stripos($messageLower, $pattern);
                    
                    if ($questionPos !== false && $patternPos !== false) {
                        // Check if they're reasonably close (within 50 characters or adjacent)
                        $distance = abs($questionPos - $patternPos);
                        if ($distance < 50 || 
                            preg_match('/(?:' . preg_quote(trim($questionIdentifier), '/') . '\s+' . $escapedPattern . '|' . $escapedPattern . '\s+' . preg_quote(trim($questionIdentifier), '/') . ')/i', $messageLower)) {
                            
                            // Check for negation patterns specifically before the option keyword
                            $isNegated = false;
                            
                            // Check for "not [option]" pattern (e.g., "not required", "not random")
                            if (preg_match('/\bnot\s+' . $escapedPattern . '\b/i', $messageLower)) {
                                $isNegated = true;
                            }
                            
                            // Check for "[option] not" pattern (e.g., "required not", "random not")
                            if (!$isNegated && preg_match('/\b' . $escapedPattern . '\s+not\b/i', $messageLower)) {
                                $isNegated = true;
                            }
                            
                            // Check for "un[option]" pattern (e.g., "unrequired", "unrandom")
                            if (!$isNegated && preg_match('/\bun' . $escapedPattern . '\b/i', $messageLower)) {
                                $isNegated = true;
                            }
                            
                            // Check for other negation keywords near the option keyword
                            if (!$isNegated) {
                                foreach ($negationKeywords as $negKeyword) {
                                    if ($negKeyword === 'un') continue; // Already checked above
                                    
                                    // Check if negation keyword appears before the option keyword
                                    $negPos = stripos($messageLower, $negKeyword);
                                    if ($negPos !== false && $patternPos !== false) {
                                        // Check if negation is between question and option, or immediately before option
                                        $negDistance = abs($negPos - $patternPos);
                                        if ($negDistance < 15 && $negPos < $patternPos) {
                                            $isNegated = true;
                                            break;
                                        }
                                    }
                                }
                            }
                            
                            $detectedOption = $optionType;
                            $optionValue = !$isNegated; // false if negated, true otherwise
                            break 2;
                        }
                    }
                }
            }
        }
        
        if (!$detectedOption) {
            return null;
        }
        
        // Extract multiple selection type if applicable
        $multiSelectType = null;
        if ($detectedOption === 'multiple_selection') {
            if (preg_match('/(?:unlimited|no limit)/i', $messageLower)) {
                $multiSelectType = 1; // Unlimited
            } elseif (preg_match('/(?:range|between)\s+(\d+)\s*(?:and|to)\s*(\d+)/i', $messageLower, $rangeMatches)) {
                $multiSelectType = 2; // Range
            } elseif (preg_match('/(?:fixed|exactly)\s+(\d+)/i', $messageLower, $fixedMatches)) {
                $multiSelectType = 3; // Fixed
            } else {
                $multiSelectType = 1; // Default to Unlimited
            }
        }
        
        Log::info('Question options update detected', [
            'message' => $message,
            'question_number' => $questionNumber,
            'option_type' => $detectedOption,
            'option_value' => $optionValue,
            'multi_select_type' => $multiSelectType
        ]);
        
        return [
            'scope' => 'question_options',
            'intent' => 'update',
            'details' => [
                'question_number' => $questionNumber,
                'option_type' => $detectedOption,
                'option_value' => $optionValue,
                'multi_select_type' => $multiSelectType
            ]
        ];
    }

    protected function detectChoiceUpdate(string $message): ?array
    {
        $messageLower = strtolower(trim($message));
        $messageOriginal = trim($message); // Preserve original capitalization
        
        // Pattern to match question identifiers: Q1, Q2, question 1, question 2, etc.
        $questionPattern = '/(?:^|\s)(?:q|question)\s*(\d+)/i';
        
        // Extract question number first (use lowercase for matching)
        if (!preg_match($questionPattern, $messageLower, $questionMatches)) {
            return null;
        }
        
        $questionNumber = (int)$questionMatches[1];
        
        // Patterns for adding/editing choices (use case-insensitive matching on original message)
        $addPatterns = [
            '/add\s+(?:this\s+)?choice\s+(?:in|to|for)\s+(?:q|question)\s*' . $questionNumber . '\s+["\']([^"\']+)["\']/i',
            '/add\s+(?:this\s+)?choice\s+(?:in|to|for)\s+(?:q|question)\s*' . $questionNumber . '\s+([^"\']+?)(?:\s|$)/i',
            '/add\s+["\']([^"\']+)["\']\s+(?:as\s+)?(?:a\s+)?choice\s+(?:in|to|for)\s+(?:q|question)\s*' . $questionNumber . '/i',
            '/add\s+([^"\']+?)\s+(?:as\s+)?(?:a\s+)?choice\s+(?:in|to|for)\s+(?:q|question)\s*' . $questionNumber . '/i',
            '/(?:q|question)\s*' . $questionNumber . '\s+add\s+(?:choice\s+)?["\']([^"\']+)["\']/i',
            '/(?:q|question)\s*' . $questionNumber . '\s+add\s+(?:choice\s+)?([^"\']+?)(?:\s|$)/i',
        ];
        
        $editPatterns = [
            '/edit\s+choice\s+(?:in|for)\s+(?:q|question)\s*' . $questionNumber . '\s+["\']([^"\']+)["\']\s+to\s+["\']([^"\']+)["\']/i',
            '/edit\s+choice\s+(?:in|for)\s+(?:q|question)\s*' . $questionNumber . '\s+["\']([^"\']+)["\']\s+to\s+([^"\']+?)(?:\s|$)/i',
            '/change\s+choice\s+(?:in|for)\s+(?:q|question)\s*' . $questionNumber . '\s+["\']([^"\']+)["\']\s+to\s+["\']([^"\']+)["\']/i',
            '/change\s+choice\s+(?:in|for)\s+(?:q|question)\s*' . $questionNumber . '\s+["\']([^"\']+)["\']\s+to\s+([^"\']+?)(?:\s|$)/i',
            '/update\s+choice\s+(?:in|for)\s+(?:q|question)\s*' . $questionNumber . '\s+["\']([^"\']+)["\']\s+to\s+["\']([^"\']+)["\']/i',
            '/update\s+choice\s+(?:in|for)\s+(?:q|question)\s*' . $questionNumber . '\s+["\']([^"\']+)["\']\s+to\s+([^"\']+?)(?:\s|$)/i',
        ];
        
        $removePatterns = [
            '/remove\s+choice\s+(?:in|from|for)\s+(?:q|question)\s*' . $questionNumber . '\s+["\']([^"\']+)["\']/i',
            '/remove\s+choice\s+(?:in|from|for)\s+(?:q|question)\s*' . $questionNumber . '\s+([^"\']+?)(?:\s|$)/i',
            '/delete\s+choice\s+(?:in|from|for)\s+(?:q|question)\s*' . $questionNumber . '\s+["\']([^"\']+)["\']/i',
            '/delete\s+choice\s+(?:in|from|for)\s+(?:q|question)\s*' . $questionNumber . '\s+([^"\']+?)(?:\s|$)/i',
        ];
        
        $action = null;
        $choiceText = null;
        $oldChoiceText = null;
        
        // Check for edit/update patterns first (they have 2 captures)
        // Match against original message to preserve capitalization
        foreach ($editPatterns as $pattern) {
            if (preg_match($pattern, $messageOriginal, $matches)) {
                $action = 'edit';
                $oldChoiceText = trim($matches[1]);
                $choiceText = trim($matches[2]);
                break;
            }
        }
        
        // Check for remove/delete patterns
        if (!$action) {
            foreach ($removePatterns as $pattern) {
                if (preg_match($pattern, $messageOriginal, $matches)) {
                    $action = 'remove';
                    $choiceText = trim($matches[1]);
                    break;
                }
            }
        }
        
        // Check for add patterns (default action)
        if (!$action) {
            foreach ($addPatterns as $pattern) {
                if (preg_match($pattern, $messageOriginal, $matches)) {
                    $action = 'add';
                    $choiceText = trim($matches[1]);
                    break;
                }
            }
        }
        
        // If no pattern matched, try a simpler pattern: "add [text] to q1" or "q1 add [text]"
        if (!$action) {
            // Try to extract choice text from quotes or after "add"
            if (preg_match('/add\s+["\']([^"\']+)["\']/i', $messageOriginal, $quoteMatches)) {
                $action = 'add';
                $choiceText = trim($quoteMatches[1]);
            } elseif (preg_match('/add\s+([^"\']+?)(?:\s+(?:to|in|for)\s+(?:q|question)\s*' . $questionNumber . '|$)/i', $messageOriginal, $addMatches)) {
                $potentialChoice = trim($addMatches[1]);
                // Make sure it's not just "choice" or "this choice"
                if (!preg_match('/^(?:this\s+)?choice$/i', $potentialChoice) && strlen($potentialChoice) > 2) {
                    $action = 'add';
                    $choiceText = $potentialChoice;
                }
            }
        }
        
        if (!$action || !$choiceText) {
            return null;
        }
        
        // Clean up choice text - remove common prefixes/suffixes (case-insensitive)
        $choiceText = preg_replace('/^(?:choice\s*:?\s*|option\s*:?\s*)/i', '', $choiceText);
        $choiceText = trim($choiceText);
        
        // Also clean old choice text if present
        if ($oldChoiceText) {
            $oldChoiceText = preg_replace('/^(?:choice\s*:?\s*|option\s*:?\s*)/i', '', $oldChoiceText);
            $oldChoiceText = trim($oldChoiceText);
        }
        
        if (empty($choiceText)) {
            return null;
        }
        
        Log::info('Choice update detected', [
            'message' => $message,
            'question_number' => $questionNumber,
            'action' => $action,
            'choice_text' => $choiceText,
            'old_choice_text' => $oldChoiceText
        ]);
        
        return [
            'scope' => 'question_choice',
            'intent' => $action === 'remove' ? 'delete' : 'update',
            'details' => [
                'question_number' => $questionNumber,
                'action' => $action,
                'choice_text' => $choiceText,
                'old_choice_text' => $oldChoiceText
            ]
        ];
    }

    /**
     * Whether the last assistant message was asking the user for confirmation (e.g. "Would you like me to create...?").
     * Only when this is true should we treat a short "yes/okay/sure" as intent create.
     */
    protected function isLastMessageConfirmationQuestion(?string $lastAssistantMessage): bool
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

    protected function detectConfirmationResponse(string $message): ?array
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

    protected function isConfirmationResponse(string $message): bool
    {
        $confirmationResponse = $this->detectConfirmationResponse($message);
        return $confirmationResponse !== null;
    }

    protected function handleConfirmation(Request $request, $user): ?JsonResponse
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

    protected function isSimpleConfirmationWithoutDetails(string $message): bool
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

    protected function getQuestionTypeDisplayName(string $scope): string
    {
        $displayNames = config('ai_express.question_type_display_names', []);
        return $displayNames[$scope] ?? str_replace('_', ' ', $scope);
    }

    protected function checkForPendingQuestionCreation(Request $request): ?array
    {
        try {
            $user = $request->user();
            if (!$user) {
                return null;
            }
            
            $message = trim($request->message ?? '');
            if (empty($message)) {
                return null;
            }
            
            // First check if this is a question options update command
            // If so, don't treat it as a topic-only response to pending question creation
            $questionOptionsPattern = $this->detectQuestionOptionsUpdate($message);
            if ($questionOptionsPattern) {
                return null;
            }
            
            // Also check if this is a choice update command
            $choiceUpdatePattern = $this->detectChoiceUpdate($message);
            if ($choiceUpdatePattern) {
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

                    // Check if AI is asking for question type
                    $isAskingForQuestionType = str_contains($contentLower, 'please specify what type of express question') ||
                        str_contains($contentLower, 'what type of express question') ||
                        str_contains($contentLower, 'you can choose from: text selection');
                    
                    if ($isAskingForQuestionType) {
                        // User's reply should be a question type - extract it from the current message
                        $questionType = $this->detectQuestionTypeInMessage($lastUserMessage);
                        if ($questionType) {
                            // Find the original user message with the topic (before the AI asked for type)
                            $topic = null;
                            for ($j = $i - 1; $j >= 0 && $j >= max(0, $i - 5); $j--) {
                                $prevMessage = $buffer[$j] ?? null;
                                if ($prevMessage && $prevMessage['role'] === 'user') {
                                    $prevContent = $prevMessage['content'] ?? '';
                                    // Check if this was a create question request
                                    if (preg_match('/\b(add|create|make|build|generate)\b.*\b(question|survey)\b.*\b(about|regarding|on|for)\s+(.+)/i', $prevContent, $matches)) {
                                        $topic = trim($matches[4]);
                                        break;
                                    } elseif (preg_match('/\babout\s+(.+)/i', $prevContent, $matches)) {
                                        $topic = trim($matches[1]);
                                        break;
                                    }
                                }
                            }
                            
                            $actionData = $this->createActionFromQuestionType($questionType);
                            if ($topic) {
                                $actionData['topic'] = $topic;
                            }
                            Log::info('[checkForPendingQuestionCreation] Found question type follow-up', [
                                'question_type' => $questionType,
                                'scope' => $actionData['scope'],
                                'intent' => $actionData['intent'],
                                'user_message' => $lastUserMessage,
                                'topic' => $topic,
                            ]);
                            return $actionData;
                        }
                    }

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

    protected function extractQuestionTypeFromCreationOffer(string $content): ?string
    {
        if (preg_match('/Would you like me to create a ([^?]+) question/i', $content, $matches)) {
            $questionType = trim($matches[1]);
            return $questionType;
        }
        
        return null;
    }

    protected function createActionFromQuestionType(string $questionType): array
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

    protected function extractActionDataFromMessage(string $content): ?array
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

    protected function executePreviousAction(Request $request, array $actionData): JsonResponse
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

    protected function generateBasicQuestionData(array $actionData): array
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

    protected function buildDetectionSchema(): ObjectSchema
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

    protected function handlePlatformIncongruence(Request $request): ?JsonResponse
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
                ],
                requiredFields: ['platform', 'question_type']
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

    protected function isExplicitQuestionType(string $message): bool
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

    protected function handleExpressQuestionTypes(array $platform): ?JsonResponse
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

    protected function isBulkCreationOveruse(string $message): bool
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

    protected function detectCurrentPage(Request $request): string
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
    
    protected function getPageContext(Request $request): array
    {
        $activeSidebar = $request->input('active_sidebar');
        $mainPage = $request->input('main_page') ?: $request->websitePosition;
        
        $context = [
            'main_page' => $mainPage,
            'active_sidebar' => $activeSidebar ? $this->mapSidebarToPage($activeSidebar) : null,
        ];
        
        return $context;
    }
    
    protected function parseUrlSegments(string $url): array
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
    
    protected function mapSidebarToPage(string $sidebarItem): ?string
    {
        $sidebarMapping = config('ai_express.sidebar_to_page', []);
        $normalizedItem = strtolower(str_replace(['_', ' '], '-', $sidebarItem));
        return $sidebarMapping[$normalizedItem] ?? null;
    }
    
    protected function detectPageFromUrl(array $segments): ?string
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
    
    protected function mapLegacyPosition(string $websitePosition): ?string
    {
        $legacyMapping = config('ai_express.legacy_position_to_page', []);
        return $legacyMapping[$websitePosition] ?? null;
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

    /**
     * Extract explicit question count from a survey creation prompt.
     * Handles patterns like "3-question survey", "3 questions", "five questions", "10 question survey".
     *
     * @return int|null The extracted count or null if not found
     */
    protected function extractSurveyQuestionCount(string $prompt): ?int
    {
        $prompt = strtolower(trim($prompt));

        // Numeric: "3-question", "3 questions", "3 question survey"
        if (preg_match('/(\d+)\s*[-]?\s*question[s]?/i', $prompt, $m)) {
            $n = (int) $m[1];
            return $n >= 1 && $n <= 20 ? $n : null;
        }

        // Word numbers
        $words = [
            'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5,
            'six' => 6, 'seven' => 7, 'eight' => 8, 'nine' => 9, 'ten' => 10,
        ];
        foreach ($words as $word => $num) {
            if (preg_match('/\b' . $word . '\s+question[s]?/i', $prompt)) {
                return $num;
            }
        }

        return null;
    }

    /**
     * Filter out incomplete survey questions (e.g. matrix with no rows/columns).
     * Prevents promising N questions when the AI outputs malformed duplicates.
     */
    protected function filterIncompleteSurveyQuestions(array $questions): array
    {
        $complete = [];
        foreach ($questions as $q) {
            $type = $q['question_type'] ?? 'descriptive';
            $rows = $q['rows'] ?? $q['items'] ?? [];
            $columns = $q['columns'] ?? $q['choices'] ?? [];

            if ($type === 'matrix') {
                if (empty($rows) || empty($columns)) {
                    Log::warning('[Survey Generation] Filtering incomplete matrix question', [
                        'title' => $q['title_and_question_text']['name'] ?? $q['title_and_question_text']['title'] ?? 'unknown',
                    ]);
                    continue;
                }
            }

            if ($type === 'slider') {
                $items = $q['items'] ?? [];
                $choices = $q['choices'] ?? [];
                if (empty($items) || empty($choices)) {
                    Log::warning('[Survey Generation] Filtering incomplete slider question', [
                        'title' => $q['title_and_question_text']['name'] ?? $q['title_and_question_text']['title'] ?? 'unknown',
                    ]);
                    continue;
                }
            }

            if (in_array($type, ['text_selection', 'image_selection'])) {
                $opts = $q['text_options'] ?? [];
                if (empty($opts)) {
                    Log::warning('[Survey Generation] Filtering incomplete selection question', [
                        'title' => $q['title_and_question_text']['name'] ?? $q['title_and_question_text']['title'] ?? 'unknown',
                    ]);
                    continue;
                }
            }

            $complete[] = $q;
        }
        return $complete;
    }
}

