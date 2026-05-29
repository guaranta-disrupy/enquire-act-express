<?php

namespace App\Http\Controllers\API;

use App\AI\Enums\Intent;
use App\AI\Enums\Scope;
use App\AI\Enums\Platform;
use App\AI\Enums\ACTType;
use App\AI\Enums\AIModel;
use App\Http\Controllers\API\Concerns\ChatControllerConversationManagement;
use App\Http\Controllers\API\Concerns\ChatControllerCoreHelpers;
use App\Http\Controllers\API\Concerns\ChatControllerPageContext;
use App\Http\Controllers\Controller;
use App\Services\ChatService;
use App\Services\PromptService;
use App\Services\Neo4JService;
use App\Services\ConversationMemoryService;
use App\Services\ConversationStorageService;
use App\Services\TokenManagerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\StreamedResponse;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Prism;
use App\Models\AI\MongoTokenManager;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateBaseQuestionSchema;

class ChatController extends Controller
{
    use ChatControllerCoreHelpers;
    use ChatControllerPageContext;
    use ChatControllerConversationManagement;

    private ChatService $chatService;
    private PromptService $promptService;
    private Neo4JService $neo4jService;
    private ConversationMemoryService $memoryService;
    private ConversationStorageService $conversationStorage;
    private TokenManagerService $tokenManager;

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

            // Only update existing conversation, don't auto-create
            $requestedConversationId = $request->input('conversation_id');
            if (!$requestedConversationId) {
                return response()->json(['message' => 'Conversation ID required'], 400);
            }

            $conversation = $this->conversationStorage->getOrCreateConversation(
                $user->id,
                $requestedConversationId
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
        $platform = $request->input('platform', 'act');

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
                    $memoryContext = $this->memoryService->getMemoryContext($user->id, 'act');
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
                'message' => 'I\'m sorry, I\'m not quite sure what you\'re asking for! I can only help you with questions related to ACT, our survey platform. Could you please rephrase your request or ask about something related to ACT?',
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
                'message' => 'I\'m sorry, I encountered an error while processing your request. Please try again or rephrase your question about ACT.',
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
                
                // Only store in MongoDB if conversation_id is provided
                // Don't auto-create conversations
                $requestedConversationId = $request->input('conversation_id');
                if ($requestedConversationId) {
                    try {
                        $mongoConversation = $this->conversationStorage->getOrCreateConversation(
                            $user->id,
                            $requestedConversationId,
                            $platform
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
            }
        } catch (\Throwable $e) {
            Log::warning('Conversation memory pre-append failed', ['error' => $e->getMessage()]);
        }

        try {
            $response = match(true) {
                $this->promptService->isForConversation() => $this->handleConversation($request),
                $this->promptService->isForGuide() => $this->handleGuide($request),
                $this->promptService->scope === 'survey' && $this->promptService->intent === 'create' => $this->handleSurveyGeneration($request),
                default => $this->handleAction($request)
            };
        } catch (\Throwable $e) {
            Log::error('Handler execution failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'I\'m sorry, I encountered an error while processing your request. Please try again or ask me something else about ACT.',
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
                        // Only get existing conversation for summary, don't auto-create
                        $requestedConversationId = $request->input('conversation_id');
                        if ($requestedConversationId) {
                            try {
                                $mongoConversationForSummary = $this->conversationStorage->getOrCreateConversation(
                                    $user->id,
                                    $requestedConversationId
                                );
                            } catch (\Throwable $e) {
                                Log::warning('Failed to get conversation for summary', ['error' => $e->getMessage()]);
                            }
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

        $memoryCleared = true;
        try {
            $this->memoryService->clearMemory(
                $user->id,
                $request->input('platform'),
            );
        } catch (\Throwable $e) {
            $memoryCleared = false;
            Log::warning('Failed to clear conversation memory', ['error' => $e->getMessage()]);
        }

        try {
            $conversationId = $request->input('conversation_id');
            if ($conversationId) {
                $result = $this->conversationStorage->clearConversationById(
                    $user->id,
                    $conversationId
                );
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
            Log::error('Failed to clear MongoDB conversations', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'conversation_id' => $request->input('conversation_id'),
            ]);
            return response()->json(['message' => 'Failed to clear conversation'], 500);
        }

        $response = ['message' => 'Conversation cleared'];
        if (!$memoryCleared) {
            $response['memory_warning'] = 'Local memory store could not be cleared, but Mongo history was reset.';
        }

        return response()->json($response, $memoryCleared ? 200 : 202);
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
            $conversation = \App\Models\AI\AIActConversation::where('conversation_id', $request->conversation_id)
                ->where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->first();

            if (!$conversation) {
                $fallbackConversation = \App\Models\AI\AIActConversation::where('user_id', $user->id)
                    ->whereNull('deleted_at')
                    ->orderBy('updated_at', 'desc')
                    ->first();

                if ($fallbackConversation) {
                    Log::warning('[ChatController] Conversation rating fallback hit - provided conversation not found, using latest', [
                        'requested_conversation_id' => $request->conversation_id,
                        'fallback_conversation_id' => $fallbackConversation->conversation_id,
                        'user_id' => $user->id,
                    ]);
                    $conversation = $fallbackConversation;
                } else {
                    Log::warning('[ChatController] Conversation rating failed - no conversations available', [
                        'requested_conversation_id' => $request->conversation_id,
                        'user_id' => $user->id,
                    ]);
                    return response()->json(['message' => 'Conversation not found'], 404);
                }
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
            $conversation = \App\Models\AI\AIActConversation::where('conversation_id', $request->conversation_id)
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
            $conversations = \App\Models\AI\AIActConversation::where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->get();

                // Sort: pinned first (by pinned_at desc), then by updated_at desc
                $sortedConversations = $conversations->sort(function ($a, $b) {
                // Helper function to safely get metadata as array
                $getMetadata = function ($conv) {
                    $metadata = $conv->metadata ?? [];
                    if (!is_array($metadata)) {
                        $metadata = json_decode(json_encode($metadata), true) ?: [];
                    }
                    return $metadata;
                };
                
                $metadataA = $getMetadata($a);
                $metadataB = $getMetadata($b);
                
                $pinnedAtA = isset($metadataA['pinned_at']) && $metadataA['pinned_at'] 
                    ? strtotime($metadataA['pinned_at']) 
                    : 0;
                $pinnedAtB = isset($metadataB['pinned_at']) && $metadataB['pinned_at'] 
                    ? strtotime($metadataB['pinned_at']) 
                    : 0;
                $isPinnedA = $pinnedAtA > 0;
                $isPinnedB = $pinnedAtB > 0;
                
                // Pinned items come first
                if ($isPinnedA && !$isPinnedB) return -1;
                if (!$isPinnedA && $isPinnedB) return 1;
                
                // If both pinned, sort by pinned_at (newest pinned first)
                if ($isPinnedA && $isPinnedB) {
                    return $pinnedAtB <=> $pinnedAtA;
                }
                
                // If neither pinned, sort by updated_at (newest first)
                return strtotime($b->updated_at) <=> strtotime($a->updated_at);
            })->values()->take(10);

                Log::info('[ChatController] history count', ['count' => $conversations->count()]);

            return response()->json([
                'success' => true,
                'conversations' => $sortedConversations,
                
            ]);
        } catch (\Throwable $e) {
            Log::error('[ChatController] Failed to get conversation history', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to get conversation history'], 500);
        }
    }

    private function detectSimpleGreetings(string $message): ?array
    {
        $message = strtolower(trim($message));
        
        $greetingPatterns = $this->getGreetingPatterns();
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

    private function handleSimpleGreetingStream(Request $request, string $platform)
    {
        $user = $request->user();
        $assistantMessage = $this->buildSimpleGreetingResponse($user);
        $conversationId = null;
        $assistantMessageId = null;
        $mongoConversation = null;

        if ($user) {
            try {
                $this->memoryService->appendMessage(
                    $user->id,
                    $platform,
                    'user',
                    $request->message
                );
            } catch (\Throwable $e) {
                Log::warning('Conversation memory append failed (greeting user message)', ['error' => $e->getMessage()]);
            }

            // Only store in MongoDB if a conversation_id was explicitly provided
            // Don't auto-create conversations for simple greetings
            $requestedConversationId = $request->input('conversation_id');
            if ($requestedConversationId) {
                try {
                    $mongoConversation = $this->conversationStorage->getOrCreateConversation(
                        $user->id,
                        $requestedConversationId,
                        $platform
                    );
                    $this->conversationStorage->addUserMessage(
                        $mongoConversation,
                        $request->message,
                        $request->input('websitePosition', 'dashboard'),
                        [
                            'intent' => 'conversation',
                            'scope' => 'conversation',
                        ]
                    );
                    $conversationId = $mongoConversation->conversation_id;
                } catch (\Throwable $e) {
                    Log::warning('MongoDB conversation storage failed (greeting user message)', ['error' => $e->getMessage()]);
                }
            }
        }

        if ($user) {
            try {
                $this->memoryService->appendMessage(
                    $user->id,
                    $platform,
                    'assistant',
                    $assistantMessage
                );
            } catch (\Throwable $e) {
                Log::warning('Conversation memory append failed (greeting assistant message)', ['error' => $e->getMessage()]);
            }
        }

        // Only store assistant message if we have a conversation (i.e., conversation_id was provided)
        if ($mongoConversation) {
            try {
                $result = $this->conversationStorage->addAssistantMessage(
                    $mongoConversation,
                    $assistantMessage,
                    0,
                    [
                        'intent' => 'conversation',
                        'scope' => 'conversation',
                    ]
                );
                $assistantMessageId = $result['message_id'] ?? null;
            } catch (\Throwable $e) {
                Log::warning('MongoDB conversation storage failed (greeting assistant message)', ['error' => $e->getMessage()]);
            }
        }

        return $this->streamSimpleMessage($assistantMessage, $conversationId, $assistantMessageId);
    }

    private function buildSimpleGreetingResponse($user = null): string
    {
        $responses = config('ai_detection.simple_greeting_responses', []);
        if (empty($responses)) {
            $responses = [
                'Hello:name! How can I help you inside ACT today?',
                'Hi:name, I\'m here and ready to help with anything in ACT. Just let me know!',
                'Hey there:name! Need anything in the ACT survey platform?',
                'Hi there! I can help you build or edit anything inside ACT—what should we work on?',
                'Hello! I\'m ready when you are. What ACT task would you like to tackle?',
            ];
        }

        $firstName = null;
        if ($user) {
            $firstName = $user->first_name ?? $user->name ?? null;
        }

        $response = $responses[array_rand($responses)];
        $replacement = $firstName ? ' ' . $firstName : '';
        $response = str_replace(':name', $replacement, $response);

        return trim(preg_replace('/\s+/', ' ', $response));
    }

    /**
     * Retrieve configured greeting patterns with sensible defaults.
     */
    private function getGreetingPatterns(): array
    {
        $configured = config('ai_detection.greeting_patterns');
        
        if (is_array($configured) && !empty($configured)) {
            return $configured;
        }
        
        return [
            'hi',
            'hello',
            'hey',
            'hey there',
            'hello there',
            'good morning',
            'good afternoon',
            'good evening',
            'hi there',
            'greetings',
            'yo',
            'sup',
            'what\'s up',
            'whats up',
        ];
    }

    //Detect intent and scope from user message
    private function detectIntentAndScope(string $message, ?string $lastAssistantMessage = null, ?array $embedding = null): array
    {
        try {
            $surveyPattern = $this->detectSurveyCreation($message);
            if ($surveyPattern) {
                return $surveyPattern;
            }
            
            
            $roundCopyPattern = $this->detectRoundCopy($message);
            if ($roundCopyPattern) {
                return $roundCopyPattern;
            }
            
            $roundMovePattern = $this->detectRoundMove($message);
            if ($roundMovePattern) {
                return $roundMovePattern;
            }
            
            // Check questionnaire instructions update BEFORE other updates to avoid false positives
            $questionnaireInstructionsPattern = $this->detectQuestionnaireInstructionsUpdate($message);
            if ($questionnaireInstructionsPattern) {
                return $questionnaireInstructionsPattern;
            }
            
            // Check questionnaire style update BEFORE target/prime updates to avoid false positives
            // (e.g., "change background color of target" should be style update, not target text update)
            $questionnaireUpdatePattern = $this->detectQuestionnaireUpdate($message);
            if ($questionnaireUpdatePattern) {
                return $questionnaireUpdatePattern;
            }
            
            // Check questionnaire targets BEFORE primes to avoid false positives
            // (e.g., "remove thankful target" should be target deletion, not prime deletion)
            $questionnaireTargetsPattern = $this->detectQuestionnaireTargetsUpdate($message);
            if ($questionnaireTargetsPattern) {
                return $questionnaireTargetsPattern;
            }
            
            $questionnairePrimesPattern = $this->detectQuestionnairePrimesUpdate($message);
            if ($questionnairePrimesPattern) {
                return $questionnairePrimesPattern;
            }
            
            // Check round creation BEFORE round settings update to avoid false positives
            // (e.g., "make main round" should be creation, not settings update)
            $roundPattern = $this->detectRoundCreation($message);
            if ($roundPattern) {
                return $roundPattern;
            }
            
            // Check round settings update BEFORE general round update to avoid false positives
            $roundSettingsPattern = $this->detectRoundSettingsUpdate($message);
            if ($roundSettingsPattern) {
                return $roundSettingsPattern;
            }
            
            $roundUpdatePattern = $this->detectRoundUpdate($message);
            if ($roundUpdatePattern) {
                return $roundUpdatePattern;
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
            
            if ($geminiIntent === 'create' && str_contains(strtolower($message), '?')) {
                $questionPatterns = ['can i', 'is it possible', 'am i able', 'do you support', 'how can i', 'how do i', 'what is', 'what are'];
                foreach ($questionPatterns as $pattern) {
                    if (str_starts_with(strtolower(trim($message)), $pattern)) {
                        Log::info('Override: Question detected but Gemini said create, correcting to guide', [
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
            if ($confirmationResponse) {
                $hasConfirmation = true;
                Log::info(' Override: Confirmation response detected (specific case)', [
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
                
                // Try pattern detection methods before defaulting to conversation
                $questionnaireInstructionsPattern = $this->detectQuestionnaireInstructionsUpdate($message);
                if ($questionnaireInstructionsPattern) {
                    return $questionnaireInstructionsPattern;
                }
                
                $questionnaireUpdatePattern = $this->detectQuestionnaireUpdate($message);
                if ($questionnaireUpdatePattern) {
                    return $questionnaireUpdatePattern;
                }
                
                $questionnaireTargetsPattern = $this->detectQuestionnaireTargetsUpdate($message);
                if ($questionnaireTargetsPattern) {
                    return $questionnaireTargetsPattern;
                }
                
                $questionnairePrimesPattern = $this->detectQuestionnairePrimesUpdate($message);
                if ($questionnairePrimesPattern) {
                    return $questionnairePrimesPattern;
                }
                
                $roundSettingsPattern = $this->detectRoundSettingsUpdate($message);
                if ($roundSettingsPattern) {
                    return $roundSettingsPattern;
                }
                
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
        $questionTypeMap = config('ai_act.question_type_display_names', []);
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
       
        $questionTypeToScope = config('ai_act.question_type_to_scope', []);
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

    private function detectRoundCreation(string $message): ?array
    {
        $messageLower = strtolower(trim($message));
        $roundKeywords = [
            'create a round', 'create round', 'create a test round', 'create test round',
            'generate a round', 'generate round', 'make a round', 'make round', 'build a round', 'build round',
            'add a round', 'add round', 'create a descriptive round', 'create descriptive round',
            'create a practice round', 'create practice round', 'create a main round', 'create main round',
            'add a descriptive round', 'add descriptive round', 'add a practice round', 'add practice round',
            'add a main round', 'add main round', 'create a training round', 'create training round'
        ];
        
        foreach ($roundKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                Log::info('Round creation detected', ['message' => $message]);
                return ['scope' => 'round', 'intent' => 'create', 'details' => $message];
            }
        }
        
        // Check for "make [type] round" patterns (e.g., "make main round", "make descriptive round")
        // This pattern indicates creation, not settings update
        $hasMake = str_contains($messageLower, 'make');
        $hasRound = str_contains($messageLower, 'round');
        $roundTypeKeywords = ['main', 'practice', 'descriptive', 'training', 'test'];
        $hasRoundType = false;
        foreach ($roundTypeKeywords as $type) {
            if (str_contains($messageLower, $type)) {
                $hasRoundType = true;
                break;
            }
        }
        
        // Pattern: "make [type] round" = creation (e.g., "make main round", "make descriptive round")
        if ($hasMake && $hasRound && $hasRoundType) {
            // Exclude if it's clearly about updating settings (e.g., "make this round main")
            if (!str_contains($messageLower, 'this round') && 
                !str_contains($messageLower, 'the round') &&
                !str_contains($messageLower, 'current round')) {
                Log::info('Round creation detected (make [type] round pattern)', ['message' => $message]);
                return ['scope' => 'round', 'intent' => 'create', 'details' => $message];
            }
        }
        
        return null;
    }

    private function detectRoundUpdate(string $message): ?array
    {
        $messageLower = strtolower(trim($message));
        
        // Check for update/change/modify + round patterns
        // Exclude style-related keywords to avoid false positives with questionnaire updates
        $hasUpdate = str_contains($messageLower, 'update') || 
                    str_contains($messageLower, 'change') || 
                    str_contains($messageLower, 'modify') || 
                    str_contains($messageLower, 'edit') || 
                    str_contains($messageLower, 'rename');
        
        // Check for round-specific keywords (excluding style keywords)
        $hasRound = (str_contains($messageLower, 'round') || 
                    str_contains($messageLower, 'practice') || 
                    str_contains($messageLower, 'main') || 
                    str_contains($messageLower, 'descriptive')) &&
                    // Exclude if it's clearly a style update
                    !str_contains($messageLower, 'font') &&
                    !str_contains($messageLower, 'size') &&
                    !str_contains($messageLower, 'color') &&
                    !str_contains($messageLower, 'style');
        
        // Check for header updates that are NOT style-related
        $hasHeader = str_contains($messageLower, 'header') &&
                    !str_contains($messageLower, 'font') &&
                    !str_contains($messageLower, 'size') &&
                    !str_contains($messageLower, 'color') &&
                    !str_contains($messageLower, 'style');
        
        // Check for bottom instruction/text updates that are NOT style-related
        $hasBottom = (str_contains($messageLower, 'bottom') && 
                     (str_contains($messageLower, 'instruction') || 
                      str_contains($messageLower, 'text') ||
                      str_contains($messageLower, 'footer'))) &&
                    !str_contains($messageLower, 'font') &&
                    !str_contains($messageLower, 'size') &&
                    !str_contains($messageLower, 'color') &&
                    !str_contains($messageLower, 'style');
        
        $hasRound = $hasRound || $hasHeader || $hasBottom;
        
        // Check for specific update patterns
        $updateKeywords = [
            'update round', 'update a round', 'update the round', 'change round', 'modify round',
            'rename round', 'edit round', 'update practice round', 'update main round',
            'update descriptive round', 'change round name', 'rename the round',
            'update round header', 'change round header', 'update header', 'change header',
            'update bottom instruction', 'change bottom instruction', 'modify bottom instruction',
            'update bottom text', 'change bottom text', 'modify bottom text',
            'update bottom', 'change bottom', 'modify bottom',
            'update the first round', 'update the second round', 'update the last round',
            'update first round', 'update second round', 'update last round'
        ];
        
        foreach ($updateKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                Log::info('Round update detected', ['message' => $message]);
                return ['scope' => 'round', 'intent' => 'update', 'details' => $message];
            }
        }
        
        // Also check for update + round combination
        if ($hasUpdate && $hasRound) {
            Log::info('Round update detected (pattern match)', ['message' => $message]);
            return ['scope' => 'round', 'intent' => 'update', 'details' => $message];
        }
        
        return null;
    }

    private function detectRoundSettingsUpdate(string $message): ?array
    {
        $messageLower = strtolower(trim($message));
        
        // Round settings keywords - these are specific to test/round configuration
        $settingsKeywords = [
            // Randomization
            'randomize targets', 'randomize the targets', 'randomize target',
            'randomize primes', 'randomize the primes', 'randomize prime',
            'random targets', 'random primes',
            
            // Selection settings
            'single select', 'single selection', 'make single select', 'enable single select',
            'multiple select', 'multiple selection',
            'selection limit', 'set selection limit', 'limit selection',
            'display by', 'display targets by', 'show targets by',
            
            // Trial types
            'practice trial', 'enable practice trial', 'disable practice trial',
            'main trial', 'enable main trial', 'disable main trial',
            'trial unselect', 'trial select', 'enable trial unselect', 'enable trial select',
            'trial unselect label', 'unselect label',
            
            // Navigation/advancement
            'auto next', 'auto advance', 'enable auto next', 'disable auto next',
            'prime as next', 'prime as button', 'disable prime as button', 'enable prime as button',
            'prime as next button', 'treat prime as button',
            
            // Display/timing
            'target delay', 'display delay', 'set target delay',
            'empty selection', 'empty selection text', 'empty selection message',
            'limit reach', 'limit reach text', 'limit reach message',
        ];
        
        // Check for specific settings patterns
        foreach ($settingsKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                Log::info('Round settings update detected', ['message' => $message, 'keyword' => $keyword]);
                return ['scope' => 'round', 'intent' => 'update_settings', 'details' => $message];
            }
        }
        
        // Check for enable/disable + setting patterns
        $hasEnableDisable = str_contains($messageLower, 'enable') || 
                           str_contains($messageLower, 'disable') ||
                           str_contains($messageLower, 'turn on') ||
                           str_contains($messageLower, 'turn off');
        
        $hasSetting = str_contains($messageLower, 'random') ||
                     str_contains($messageLower, 'select') ||
                     str_contains($messageLower, 'trial') ||
                     str_contains($messageLower, 'prime') ||
                     str_contains($messageLower, 'target') ||
                     str_contains($messageLower, 'auto') ||
                     str_contains($messageLower, 'limit') ||
                     str_contains($messageLower, 'delay') ||
                     str_contains($messageLower, 'display');
        
        // Check for "make this round" or "set" patterns
        // Require "this round", "the round", or "current round" to distinguish from creation patterns
        $hasMakeSet = (str_contains($messageLower, 'make') || 
                      str_contains($messageLower, 'set')) &&
                      (str_contains($messageLower, 'this round') || 
                       str_contains($messageLower, 'the round') ||
                       str_contains($messageLower, 'current round') ||
                       str_contains($messageLower, 'single') ||
                       str_contains($messageLower, 'random'));
        
        if (($hasEnableDisable && $hasSetting) || $hasMakeSet) {
            // Exclude if it's clearly about creating/updating targets/primes content
            if (str_contains($messageLower, 'add') || 
                str_contains($messageLower, 'create') ||
                (str_contains($messageLower, 'change') && (str_contains($messageLower, 'to ') || str_contains($messageLower, 'from')))) {
                return null;
            }
            
            // Exclude if it's a round creation pattern (e.g., "make main round" without "this")
            if (str_contains($messageLower, 'make') && 
                str_contains($messageLower, 'round') && 
                !str_contains($messageLower, 'this round') && 
                !str_contains($messageLower, 'the round') &&
                !str_contains($messageLower, 'current round')) {
                // Check if it has a round type keyword (main, practice, descriptive) - this is creation
                $roundTypeKeywords = ['main', 'practice', 'descriptive', 'training', 'test'];
                foreach ($roundTypeKeywords as $type) {
                    if (str_contains($messageLower, $type)) {
                        return null; // This is a creation pattern, not settings update
                    }
                }
            }
            
            Log::info('Round settings update detected (pattern match)', ['message' => $message]);
            return ['scope' => 'round', 'intent' => 'update_settings', 'details' => $message];
        }
        
        return null;
    }

    private function detectQuestionnaireUpdate(string $message): ?array
    {
        $messageLower = strtolower(trim($message));
        
        // Style-related keywords
        $styleKeywords = [
            'font family', 'font-family', 'font size', 'font-size', 'font weight', 'font-weight',
            'font style', 'font-style', 'font color', 'font-color', 'background color', 'background-color', 'bg color', 'bg-color',
            'border color', 'border-color', 'text color', 'text-color', 'line height', 'line-height',
            'padding', 'margin', 'width', 'height', 'opacity', 'blur', 'position', 'display',
            'arial', 'times new roman', 'helvetica', 'verdana', 'georgia', 'courier',
            'change font', 'update font', 'set font', 'make font',
            'change color', 'update color', 'set color', 'make color',
            'change size', 'update size', 'set size', 'make size',
            'change style', 'update style', 'set style', 'modify style',
            'normal', 'italic', 'bold', 'underline', 'strikethrough'
        ];
        
        // Check for update/change/modify + style patterns
        $hasUpdate = str_contains($messageLower, 'update') || 
                    str_contains($messageLower, 'change') || 
                    str_contains($messageLower, 'modify') || 
                    str_contains($messageLower, 'edit') || 
                    str_contains($messageLower, 'set') ||
                    str_contains($messageLower, 'make');
        
        $hasStyle = false;
        foreach ($styleKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                $hasStyle = true;
                break;
            }
        }
        
        // Check for specific style update patterns
        $updateKeywords = [
            'change font family', 'update font family', 'set font family',
            'change font to', 'update font to', 'set font to',
            'change color to', 'update color to', 'set color to',
            'change size to', 'update size to', 'set size to',
            'change background', 'update background', 'set background',
            'change header', 'update header', 'set header',
            'change target', 'update target', 'set target',
            'change button', 'update button', 'set button',
            'change prime', 'update prime', 'set prime',
            'change footer', 'update footer', 'set footer',
            'footer text', 'footer font', 'footer color', 'footer size', 'footer style'
        ];
        
        foreach ($updateKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                Log::info('Questionnaire style update detected', ['message' => $message]);
                return ['scope' => 'questionnaire', 'intent' => 'update', 'details' => $message];
            }
        }
        
        // Also check for update + style combination
        if ($hasUpdate && $hasStyle) {
            Log::info('Questionnaire style update detected (pattern match)', ['message' => $message]);
            return ['scope' => 'questionnaire', 'intent' => 'update', 'details' => $message];
        }
        
        return null;
    }

    private function detectQuestionnaireInstructionsUpdate(string $message): ?array
    {
        $messageLower = strtolower(trim($message));
        
        // Exclude "bottom instruction" - this should be handled as a round update, not questionnaire
        if (str_contains($messageLower, 'bottom') && str_contains($messageLower, 'instruction')) {
            return null;
        }
        
        // Check for instructions/header/bottom text update patterns
        $instructionsKeywords = [
            'update instructions', 'change instructions', 'modify instructions', 'edit instructions',
            'update instruction header', 'change instruction header', 'update header text', 'change header text',
            'update main header', 'change main header', 'modify main header',
            'update bottom text', 'change bottom text', 'modify bottom text',
            'update footer text', 'change footer text', 'modify footer text',
            'update instructions header', 'change instructions header',
            'set instructions', 'set header', 'set bottom text', 'set footer text'
        ];
        
        foreach ($instructionsKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                Log::info('Questionnaire instructions update detected', ['message' => $message]);
                return ['scope' => 'questionnaire', 'intent' => 'update_instructions', 'details' => $message];
            }
        }
        
        // Check for specific patterns: "change the header to X" or "change the bottom to X"
        // These should be caught BEFORE primes/targets detection
        $specificHeaderBottomPatterns = [
            '/change\s+(the\s+)?header\s+to\s+/i',
            '/update\s+(the\s+)?header\s+to\s+/i',
            '/change\s+(the\s+)?bottom\s+to\s+/i',
            '/update\s+(the\s+)?bottom\s+to\s+/i',
            '/change\s+(the\s+)?footer\s+to\s+/i',
            '/update\s+(the\s+)?footer\s+to\s+/i',
        ];
        
        foreach ($specificHeaderBottomPatterns as $pattern) {
            if (preg_match($pattern, $messageLower)) {
                // Exclude if it's clearly a style update (e.g., "change header color")
                $isStyleUpdate = str_contains($messageLower, 'font') ||
                                str_contains($messageLower, 'size') ||
                                str_contains($messageLower, 'color') ||
                                str_contains($messageLower, 'style') ||
                                str_contains($messageLower, 'background');
                
                if (!$isStyleUpdate) {
                    Log::info('Questionnaire instructions update detected (header/bottom pattern)', ['message' => $message, 'pattern' => $pattern]);
                    return ['scope' => 'questionnaire', 'intent' => 'update_instructions', 'details' => $message];
                }
            }
        }
        
        // Check for update/change + (header/bottom/footer) + (text/instructions) patterns
        $hasUpdate = str_contains($messageLower, 'update') || 
                    str_contains($messageLower, 'change') || 
                    str_contains($messageLower, 'modify') || 
                    str_contains($messageLower, 'edit') || 
                    str_contains($messageLower, 'set');
        
        $hasInstructions = (str_contains($messageLower, 'header') && 
                           (str_contains($messageLower, 'text') || str_contains($messageLower, 'instruction'))) ||
                          (str_contains($messageLower, 'bottom') && str_contains($messageLower, 'text') && !str_contains($messageLower, 'instruction')) ||
                          (str_contains($messageLower, 'footer') && str_contains($messageLower, 'text'));
        
        // Exclude style-related updates
        $isStyleUpdate = str_contains($messageLower, 'font') ||
                        str_contains($messageLower, 'size') ||
                        str_contains($messageLower, 'color') ||
                        str_contains($messageLower, 'style');
        
        if ($hasUpdate && $hasInstructions && !$isStyleUpdate) {
            Log::info('Questionnaire instructions update detected (pattern match)', ['message' => $message]);
            return ['scope' => 'questionnaire', 'intent' => 'update_instructions', 'details' => $message];
        }
        
        return null;
    }

    private function detectQuestionnairePrimesUpdate(string $message): ?array
    {
        $messageLower = strtolower(trim($message));
        
        // CRITICAL: Exclude patterns that mention "target" - those should be handled by target detection
        if (str_contains($messageLower, 'target')) {
            return null;
        }
        
        // CRITICAL: Exclude patterns that mention "header" or "bottom" - those should be handled by instructions detection
        // "header" and "bottom" refer to main_header and bottom_text in instructions, not primes
        if (str_contains($messageLower, 'header') || str_contains($messageLower, 'bottom')) {
            return null;
        }
        
        // Check for prime-related update/create/ patterns
        $primeKeywords = [
            'update prime', 'change prime', 'modify prime', 'edit prime',
            'add prime', 'create prime', 'new prime', 'insert prime',
            'update primes', 'change primes', 'add primes', 'create primes',
        ];
        
        // Check for specific creation patterns - handle "add X as prime", "add X, Y, Z as primes", etc.
        $specificCreatePatterns = [
            // Match "add X, Y, Z as primes" or "add X, Y, and Z as primes" (comma-separated)
            '/add\s+.*?\s+as\s+primes/i',
            '/create\s+.*?\s+as\s+primes/i',
            '/insert\s+.*?\s+as\s+primes/i',
            // Match "add X as prime" (single)
            '/add\s+.*?\s+as\s+prime/i',
            '/create\s+.*?\s+as\s+prime/i',
            '/insert\s+.*?\s+as\s+prime/i',
            // Match "add X, Y, Z primes" (without "as")
            '/add\s+.*?\s+primes/i',
            '/create\s+.*?\s+primes/i',
            '/insert\s+.*?\s+primes/i',
        ];
        
        // Check specific creation patterns first
        foreach ($specificCreatePatterns as $pattern) {
            if (preg_match($pattern, $messageLower)) {
                Log::info('Questionnaire primes create detected (specific pattern)', ['message' => $message, 'pattern' => $pattern]);
                return ['scope' => 'questionnaire', 'intent' => 'create_primes', 'details' => $message];
            }
        }
        
        // Check for specific update patterns - handle "change X prime to Y", "update X prime as Y", etc.
        $specificUpdatePatterns = [
            // Match "change X prime to Y" or "change X to Y"
            '/change\s+.*?\s+prime\s+to\s+/i',
            '/change\s+.*?\s+to\s+/i',
            '/update\s+.*?\s+prime\s+to\s+/i',
            '/update\s+.*?\s+as\s+/i',
            '/modify\s+.*?\s+prime\s+to\s+/i',
            '/edit\s+.*?\s+prime\s+to\s+/i',
            // Match "change X, Y primes to A, B" (multiple)
            '/change\s+.*?\s+primes\s+to\s+/i',
            '/update\s+.*?\s+primes\s+to\s+/i',
        ];
        
        // Check specific update patterns
        foreach ($specificUpdatePatterns as $pattern) {
            if (preg_match($pattern, $messageLower)) {
                Log::info('Questionnaire primes update detected (specific pattern)', ['message' => $message, 'pattern' => $pattern]);
                return ['scope' => 'questionnaire', 'intent' => 'update_primes', 'details' => $message];
            }
        }
        
        
        // Fall back to keyword matching
        foreach ($primeKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                // Determine intent based on keyword
                if (str_contains($keyword, 'add') || str_contains($keyword, 'create') || str_contains($keyword, 'new') || str_contains($keyword, 'insert')) {
                    Log::info('Questionnaire primes create detected', ['message' => $message]);
                    return ['scope' => 'questionnaire', 'intent' => 'create_primes', 'details' => $message];
                } else {
                    Log::info('Questionnaire primes update detected', ['message' => $message]);
                    return ['scope' => 'questionnaire', 'intent' => 'update_primes', 'details' => $message];
                }
            }
        }
        
        return null;
    }

    private function detectQuestionnaireTargetsUpdate(string $message): ?array
    {
        $messageLower = strtolower(trim($message));
        
        // Check for target-related update/create patterns
        $targetKeywords = [
            'update target', 'change target', 'modify target', 'edit target',
            'add target', 'create target', 'new target', 'insert target',
            'update targets', 'change targets', 'add targets', 'create targets',
        ];
        
        // Check for specific creation patterns - handle "add X as target", "add X, Y, Z as targets", etc.
        $specificCreatePatterns = [
            // Match "add X, Y, Z as targets" or "add X, Y, and Z as targets" (comma-separated)
            '/add\s+.*?\s+as\s+targets/i',
            '/create\s+.*?\s+as\s+targets/i',
            '/insert\s+.*?\s+as\s+targets/i',
            // Match "add X as target" (single)
            '/add\s+.*?\s+as\s+target/i',
            '/create\s+.*?\s+as\s+target/i',
            '/insert\s+.*?\s+as\s+target/i',
            // Match "add X, Y, Z targets" (without "as")
            '/add\s+.*?\s+targets/i',
            '/create\s+.*?\s+targets/i',
            '/insert\s+.*?\s+targets/i',
        ];
        
        // Check specific creation patterns first
        foreach ($specificCreatePatterns as $pattern) {
            if (preg_match($pattern, $messageLower)) {
                Log::info('Questionnaire targets create detected (specific pattern)', ['message' => $message, 'pattern' => $pattern]);
                return ['scope' => 'questionnaire', 'intent' => 'create_targets', 'details' => $message];
            }
        }
        
        // Exclude style-related keywords to avoid false positives
        $styleKeywords = [
            'background color', 'background-color', 'bg color', 'bg-color',
            'font family', 'font-family', 'font size', 'font-size', 'font weight', 'font-weight',
            'font style', 'font-style', 'font color', 'font-color',
            'border color', 'border-color', 'text color', 'text-color', 'line height', 'line-height',
            'padding', 'margin', 'width', 'height', 'opacity', 'blur', 'position', 'display',
            'transparent', 'no color', 'nocolor',
            'change font', 'update font', 'set font', 'make font',
            'change color', 'update color', 'set color', 'make color',
            'change size', 'update size', 'set size', 'make size',
            'change style', 'update style', 'set style', 'modify style',
        ];
        
        $hasStyleKeyword = false;
        foreach ($styleKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                $hasStyleKeyword = true;
                break;
            }
        }
        
        // If it contains style keywords, it's likely a style update, not a target text update
        if ($hasStyleKeyword) {
            Log::info('Questionnaire targets update skipped (style-related)', ['message' => $message]);
            return null;
        }
        
        // Check for specific update patterns - handle "change X target to Y", "update X target as Y", etc.
        // These patterns should match text changes like "change Option A target to Option B"
        // Note: messageLower is already lowercased, so "Option A" becomes "option a"
        
        // First, check for the most common pattern: "update [the] [option X] target [here] to [option Y] [here]"
        // This pattern explicitly matches:
        // - "update the option a target to option o"
        // - "update the option a target here to option o"
        // - "update the option a target to option o here"
        // - "update the Option B target here to Option Z"
        if (preg_match('/update\s+(the\s+)?(option\s+[a-z]|[\w\s]+?)\s+target(\s+here)?\s+to\s+([\w\s]+?)(\s+here)?$/i', $messageLower)) {
            Log::info('Questionnaire targets update detected (update X target to Y pattern)', ['message' => $message]);
            return ['scope' => 'questionnaire', 'intent' => 'update_targets', 'details' => $message];
        }
        
        // Check for other update patterns
        $specificUpdatePatterns = [
            // Match "change [the] [option a/any text] target [here] to [option o/any text] [here]"
            '/change\s+(the\s+)?([\'"]?)(option\s+[a-z]|[\w\s]+?)\2\s+target(\s+here)?\s+to\s+([\'"]?)([\w\s]+?)\4(\s+here)?/i',
            // Match "change X to Y target" (target at the end)
            '/change\s+(the\s+)?([\'"]?)(option\s+[a-z]|[\w\s]+?)\2\s+to\s+([\'"]?)([\w\s]+?)\3\s+target/i',
            // Match "update X as Y target" (target at the end)
            '/update\s+(the\s+)?([\'"]?)(option\s+[a-z]|[\w\s]+?)\2\s+as\s+([\'"]?)([\w\s]+?)\3\s+target/i',
            // Match "modify X target [here] to Y [here]"
            '/modify\s+(the\s+)?([\'"]?)(option\s+[a-z]|[\w\s]+?)\2\s+target(\s+here)?\s+to\s+([\'"]?)([\w\s]+?)\4(\s+here)?/i',
            // Match "edit X target [here] to Y [here]"
            '/edit\s+(the\s+)?([\'"]?)(option\s+[a-z]|[\w\s]+?)\2\s+target(\s+here)?\s+to\s+([\'"]?)([\w\s]+?)\4(\s+here)?/i',
            // Match "change X, Y targets to A, B" (multiple)
            '/change\s+.*?\s+targets\s+to\s+/i',
            '/update\s+.*?\s+targets\s+to\s+/i',
        ];
        
        // Check specific update patterns
        foreach ($specificUpdatePatterns as $pattern) {
            if (preg_match($pattern, $messageLower)) {
                Log::info('Questionnaire targets update detected (specific pattern)', ['message' => $message, 'pattern' => $pattern]);
                return ['scope' => 'questionnaire', 'intent' => 'update_targets', 'details' => $message];
            }
        }
        
        
        
        // Fall back to keyword matching
        foreach ($targetKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                // Determine intent based on keyword
                if (str_contains($keyword, 'add') || str_contains($keyword, 'create') || str_contains($keyword, 'new') || str_contains($keyword, 'insert')) {
                    Log::info('Questionnaire targets create detected', ['message' => $message]);
                    return ['scope' => 'questionnaire', 'intent' => 'create_targets', 'details' => $message];
                } else {
                    Log::info('Questionnaire targets update detected', ['message' => $message]);
                    return ['scope' => 'questionnaire', 'intent' => 'update_targets', 'details' => $message];
                }
            }
        }
        
        return null;
    }


    private function detectRoundCopy(string $message): ?array
    {
        $messageLower = strtolower(trim($message));
        $copyKeywords = [
            'copy round', 'copy a round', 'copy the round', 'duplicate round', 'duplicate a round',
            'duplicate the round', 'copy practice round', 'copy main round', 'copy descriptive round',
            'duplicate practice round', 'duplicate main round', 'duplicate descriptive round',
            'copy first', 'copy second', 'copy third', 'copy last', 'copy the first', 'copy the second',
            'copy the third', 'copy the last', 'duplicate first', 'duplicate second', 'duplicate third',
            'duplicate last', 'duplicate the first', 'duplicate the second', 'duplicate the third',
            'duplicate the last', 'clone round', 'clone the round'
        ];
        
        // Check for copy/duplicate + round keywords
        $hasCopy = str_contains($messageLower, 'copy') || str_contains($messageLower, 'duplicate') || 
                   str_contains($messageLower, 'clone');
        $hasRound = str_contains($messageLower, 'round') || str_contains($messageLower, 'practice') || 
                    str_contains($messageLower, 'main') || str_contains($messageLower, 'descriptive') ||
                    str_contains($messageLower, 'first') || str_contains($messageLower, 'second') ||
                    str_contains($messageLower, 'third') || str_contains($messageLower, 'last');
        
        if ($hasCopy && $hasRound) {
            Log::info('Round copy detected', ['message' => $message]);
            return ['scope' => 'round', 'intent' => 'copy', 'details' => $message];
        }
        
        // Also check for specific copy patterns
        foreach ($copyKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                Log::info('Round copy detected', ['message' => $message]);
                return ['scope' => 'round', 'intent' => 'copy', 'details' => $message];
            }
        }
        
        return null;
    }

    private function detectRoundMove(string $message): ?array
    {
        $messageLower = strtolower(trim($message));
        $moveKeywords = [
            'move round', 'move a round', 'move the round', 'transfer round', 'transfer a round',
            'transfer the round', 'move practice round', 'move main round', 'move descriptive round',
            'move first', 'move second', 'move third', 'move last', 'move the first', 'move the second',
            'move the third', 'move the last', 'transfer first', 'transfer second', 'transfer third',
            'transfer last', 'relocate round', 'relocate the round'
        ];
        
        // Check for move/transfer + round keywords
        $hasMove = str_contains($messageLower, 'move') || str_contains($messageLower, 'transfer') || 
                   str_contains($messageLower, 'relocate');
        $hasRound = str_contains($messageLower, 'round') || str_contains($messageLower, 'practice') || 
                    str_contains($messageLower, 'main') || str_contains($messageLower, 'descriptive') ||
                    str_contains($messageLower, 'first') || str_contains($messageLower, 'second') ||
                    str_contains($messageLower, 'third') || str_contains($messageLower, 'last');
        
        if ($hasMove && $hasRound) {
            Log::info('Round move detected', ['message' => $message]);
            return ['scope' => 'round', 'intent' => 'move', 'details' => $message];
        }
        
        // Also check for specific move patterns
        foreach ($moveKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                Log::info('Round move detected', ['message' => $message]);
                return ['scope' => 'round', 'intent' => 'move', 'details' => $message];
            }
        }
        
        return null;
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
            
            $memoryContext = $this->memoryService->getMemoryContext($user->id, 'act');
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
        $questionTypeMap = config('ai_act.question_type_to_action', []);
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
            'action' => 'act_question_create',
            'scope' => 'question',
            'intent' => 'create'
        ];
    }

    private function extractActionDataFromMessage(string $content): ?array
    {
        if (str_contains($content, 'matrix question') && str_contains($content, 'Would you like me to create')) {
            return [
                'action' => 'act_matrix_create',
                'scope' => 'matrix',
                'intent' => 'create'
            ];
        }
        
        if (str_contains($content, 'text selection question') && str_contains($content, 'Would you like me to create')) {
            return [
                'action' => 'act_text_selection_create',
                'scope' => 'text_selection',
                'intent' => 'create'
            ];
        }
        
        if (str_contains($content, 'image selection question') && str_contains($content, 'Would you like me to create')) {
            return [
                'action' => 'act_image_selection_create',
                'scope' => 'image_selection',
                'intent' => 'create'
            ];
        }
        
        if (str_contains($content, 'descriptive question') && str_contains($content, 'Would you like me to create')) {
            return [
                'action' => 'act_descriptive_create',
                'scope' => 'descriptive',
                'intent' => 'create'
            ];
        }
        // will add more in the future
        return null;
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
            $memoryContext = '';
            if ($user) {
                $memoryContextCacheKey = 'memory_context_' . $user->id . '_' . $this->promptService->platform;
                $memoryContext = \Illuminate\Support\Facades\Cache::remember($memoryContextCacheKey, now()->addMinutes(5), function() use ($user) {
                    return $this->memoryService->renderContextString($user->id, $this->promptService->platform);
                });
            }
            $basePrompt = config('ai_prompts.base_prompts.conversation', 'You are a helpful and expressive AI assistant for ACT, a survey platform. You ONLY have knowledge about ACT. When users ask questions about ACT features, provide clear explanations with specific step-by-step UI guidance. Respond naturally and keep responses concise.');
            $systemPrompt = $this->buildSystemPrompt($basePrompt, $userInfo['type'], $userInfo['firstName'], $memoryContext);

            try {
                $embedding = $this->chatService->getEmbedding($request->message);
                $currentPage = $this->detectCurrentPage($request);
                $pageContext = $this->getPageContext($request);
                $neo4jContext = $this->promptService->neo4jService->getContext($embedding, $currentPage);
                
                if (!empty($neo4jContext['context'])) {
                    $contextInfo = "User is on main page: {$pageContext['main_page']}";
                    if ($pageContext['active_sidebar']) {
                        $contextInfo .= " with {$pageContext['active_sidebar']} sidebar open";
                    }
                    $systemPrompt .= "\n\nACT Knowledge Base ({$contextInfo}):\n" . $neo4jContext['context'];
                    
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
                    'message' => 'I\'m not sure how to help with that request. I can only answer questions about ACT, our survey platform. Could you please rephrase it as a question about ACT?'
                ]);
            }

            $embedding = $this->chatService->getEmbedding($request->message);
            $embeddingIn = $this->estimateTokens($request->message);
            $userInfo = $this->getUserTypeInfo($request);
            $currentPage = $this->detectCurrentPage($request);
            
            try {
                $props = $this->promptService->neo4jService->getContext($embedding, $currentPage);
                $context = $props['context'];
                $path = $props['path'];
                $instructions = $props['instructions'];
            } catch (\Throwable $e) {
                Log::warning('Neo4j context retrieval failed', ['error' => $e->getMessage()]);
                $context = '';
                $path = '';
                $instructions = '';
            }

            if (empty($context) && empty($path) && empty($instructions)) {
                return response()->json([
                    'message' => 'I couldn\'t find specific information about that in ACT. I can only help with questions related to our ACT survey platform. Could you try rephrasing your question or ask about a different ACT feature?'
                ]);
            }

            $basePrompt = config('ai_prompts.base_prompts.guide', 'You are a helpful guide for ACT, a survey platform. Use HTML tags for formatting (no markdown).');
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
                'message' => 'I\'m sorry, I encountered an error while looking up information about ACT. I can only help with questions related to our ACT survey platform. Could you please try again or ask me something else about ACT?'
            ]);
        }
    }

    // Handle action intent (create/update)
    private function handleAction(Request $request): JsonResponse
    {
        try {
            if ($this->promptService->scope === 'question' && $this->promptService->intent === 'create') {
                $platformIncongruency = $this->handlePlatformIncongruence($request);

                if ($platformIncongruency) {
                    return $platformIncongruency;
                }
            }
            $schema = $this->promptService->getSchema();
            if (!$schema) {
                return response()->json([
                    'message' => 'I\'m not sure how to help with that request. I can only assist with ACT survey platform features. Could you please rephrase it or try asking about a specific ACT feature?'
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
                'message' => 'I\'m sorry, I encountered an error while processing your action request. I can only help with ACT survey platform features. Could you please try again or ask me something else about ACT?'
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
            
            $surveySchema = new ObjectSchema(
                name: 'survey_generation',
                description: 'Generate a complete survey with multiple questions',
                properties: [
                    new ArraySchema(
                        name: 'questions',
                        description: 'Array of question definitions',
                        items: new ObjectSchema(
                            name: 'question',
                            description: 'Question definition for survey',
                            properties: [
                                new EnumSchema(
                                    name: 'question_type',
                                    description: 'Question type',
                                    options: ['descriptive', 'text_selection', 'image_selection', 'single_textbox', 'multiple_textbox', 'open_ended', 'slider', 'ranking', 'star_rating', 'button_rating', 'segmentation', 'matrix', 'pairwise_comparison', 'best-worst_scale', 'pie_chart', 'bar_chart']
                                ),
                                (new CreateBaseQuestionSchema())->getSchema(),
                                new ArraySchema(
                                    name: 'text_options',
                                    description: 'Text options/choices for selection questions (text_selection, image_selection). Only include if question_type is text_selection or image_selection.',
                                    items: new ObjectSchema(
                                        name: 'text_option',
                                        description: 'A choice option',
                                        properties: [
                                            new StringSchema(
                                                name: 'text',
                                                description: 'The choice text'
                                            )
                                        ],
                                        requiredFields: ['text']
                                    )
                                ),
                                new ArraySchema(
                                    name: 'items',
                                    description: 'Items for slider/ranking questions. Only include if question_type is slider or ranking.',
                                    items: new ObjectSchema(
                                        name: 'item',
                                        description: 'An item',
                                        properties: [
                                            new StringSchema(
                                                name: 'text',
                                                description: 'The item text'
                                            )
                                        ],
                                        requiredFields: ['text']
                                    )
                                ),
                                new ArraySchema(
                                    name: 'choices',
                                    description: 'Scale values for slider questions. Only include if question_type is slider.',
                                    items: new ObjectSchema(
                                        name: 'scale_value',
                                        description: 'A scale value',
                                        properties: [
                                            new StringSchema(
                                                name: 'value',
                                                description: 'The scale value text'
                                            )
                                        ],
                                        requiredFields: ['value']
                                    )
                                )
                            ],
                            requiredFields: ['question_type', 'title_and_question_text']
                        )
                    )
                ],
                requiredFields: ['questions']
            );

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

            $planPrompt = "Create a plan for a survey based on: {$surveyPrompt}. " .
                         "List the question types you plan to use and the purpose of each question. " .
                         "Plan 5-8 questions that will thoroughly cover the topic.";

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
                    ->usingTemperature(0.2)
                    ->asStructured();

                $planData = $planResponse->structured ?? [];
                $plannedTypes = array_column($planData['planned_questions'] ?? [], 'question_type');
                
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

            $surveySchema = new ObjectSchema(
                name: 'survey_generation',
                description: 'Generate a complete survey with multiple questions',
                properties: [
                    new ArraySchema(
                        name: 'questions',
                        description: 'Array of question definitions',
                        items: new ObjectSchema(
                            name: 'question',
                            description: 'Question definition for survey',
                            properties: [
                                new EnumSchema(
                                    name: 'question_type',
                                    description: 'Question type',
                                    options: ['descriptive', 'text_selection', 'image_selection', 'single_textbox', 'multiple_textbox', 'open_ended', 'slider', 'ranking', 'star_rating', 'button_rating', 'segmentation', 'matrix', 'pairwise_comparison', 'best-worst_scale', 'pie_chart', 'bar_chart']
                                ),
                                (new CreateBaseQuestionSchema())->getSchema(),
                                new ArraySchema(
                                    name: 'text_options',
                                    description: 'Text options/choices for selection questions (text_selection, image_selection). Only include if question_type is text_selection or image_selection.',
                                    items: new ObjectSchema(
                                        name: 'text_option',
                                        description: 'A choice option',
                                        properties: [
                                            new StringSchema(
                                                name: 'text',
                                                description: 'The choice text'
                                            )
                                        ],
                                        requiredFields: ['text']
                                    )
                                ),
                                new ArraySchema(
                                    name: 'items',
                                    description: 'Items for slider/ranking questions. Only include if question_type is slider or ranking.',
                                    items: new ObjectSchema(
                                        name: 'item',
                                        description: 'An item',
                                        properties: [
                                            new StringSchema(
                                                name: 'text',
                                                description: 'The item text'
                                            )
                                        ],
                                        requiredFields: ['text']
                                    )
                                ),
                                new ArraySchema(
                                    name: 'choices',
                                    description: 'Scale values for slider questions. Only include if question_type is slider.',
                                    items: new ObjectSchema(
                                        name: 'scale_value',
                                        description: 'A scale value',
                                        properties: [
                                            new StringSchema(
                                                name: 'value',
                                                description: 'The scale value text'
                                            )
                                        ],
                                        requiredFields: ['value']
                                    )
                                )
                            ],
                            requiredFields: ['question_type', 'title_and_question_text']
                        )
                    )
                ],
                requiredFields: ['questions']
            );

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

            $systemPrompt = "You are an AI assistant for ACT survey platform. Generate a complete survey based on the user's request. " .
                          "Create 5-8 diverse questions with appropriate question types. Mix different question types for better survey design. " .
                          "For each question, determine the best question type based on the information being collected. " .
                          "Generate realistic choices/options for selection-type questions. " .
                          "Ensure questions are logically ordered and flow well together.";

            $prompt = "Generate a complete survey based on this request: {$surveyPrompt}. " .
                     "Create a comprehensive set of questions that thoroughly covers the topic. " .
                     "Use appropriate question types for each question. " .
                     "Return the survey structure with all questions and their configurations.";

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
            
            Log::info('[Survey Generation] Parsing survey data (non-streaming)', [
                'has_questions' => isset($surveyData['questions']),
                'questions_count' => count($surveyData['questions'] ?? []),
                'question_types_generated' => array_column($surveyData['questions'] ?? [], 'question_type')
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
                        'text' => 'Yes, Create All Questions',
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
                        options: ACTType::values(),
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
        if ($this->promptService->platform === 'act') {
                    $actQuestionTypeResponse = $this->handleActQuestionTypes($platform);
                    if ($actQuestionTypeResponse) {
                        return $actQuestionTypeResponse;
                    }
                }

        if (!empty($platform['platform']) && $platform['platform'] !== $this->promptService->platform) {
            $userPlatform = strtoupper($platform['platform']);
            
            return response()->json([
                'message' => "I'm sorry, I can only help with ACT survey platform features. I don't have knowledge about {$userPlatform} or other platforms. Would you like me to help you with something related to ACT instead?"
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

    private function handleActQuestionTypes(array $platform): ?JsonResponse
    {
        $messageMap = config('ai_act.question_type_messages', []);
        $actType = $platform['question_type'] ?? null;

        if (!$actType) {
            return response()->json([
                'message' => 'Please specify what type of ACT question you\'d like to create. You can choose from: text selection, image selection, descriptive, video player, single textbox, multiple textbox, open ended, pie chart, bar chart, slider, ranking, star rating, button rating, segmentation, matrix, pairwise comparison, or best-worst scale.'
            ]);
        } elseif ($actType && isset($messageMap[$actType])) {
            return response()->json(['message' => $messageMap[$actType]]);
        } else {
            $typeMapping = config('ai_act.question_type_to_scope', []);
            $mappedType = $typeMapping[$actType] ?? $actType;
            $this->promptService->scope = $mappedType;
            return null;
        }
    }

    public function diagnose(): JsonResponse
    {
        try {
            $diagnosis = $this->neo4jService->diagnoseActNodes();
            return response()->json($diagnosis);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Diagnosis failed: ' . $e->getMessage()
            ], 500);
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
                'limit' => (int) config('ai _chat.daily_token_limit', 25000),
                'percent' => 0,
            ]);
        }
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
            'test_id' => 'nullable|integer',
        ]);

        Log::info('CHAT CONTROLLER DEBUG == Action confirmation received', [
            'action' => $request->action,
            'scope' => $request->scope,
            'intent' => $request->intent,
            'questionnaire_code' => $request->questionnaire_code,
            'test_id' => $request->test_id,
            'user_id' => optional($request->user())->id,
        ]);

        try {
            $executionRequest = new Request();
            $executionRequest->merge([
                'questionnaire_code' => $request->questionnaire_code,
            ]);

            $aiExecutionController = new \App\Http\Controllers\API\AiExecutionController();
            
            // Merge test_id and device info into preview_data if available
            $previewData = $request->preview_data ?? [];
            if ($request->test_id) {
                $previewData['test_id'] = $request->test_id;
            }
            // Include device info from actionData if available
            if ($request->input('active_device')) {
                $previewData['active_device'] = $request->input('active_device');
            }
            if ($request->input('current_orientation')) {
                $previewData['current_orientation'] = $request->input('current_orientation');
            }
            
            switch ($request->action) {
                
                case 'act_round_create':
                case 'app_round_create':
                case 'act_round_update':
                case 'app_round_update':
                case 'act_round_update_settings':
                case 'app_round_update_settings':
                case 'act_round_copy':
                case 'app_round_copy':
                case 'act_round_move':
                case 'app_round_move':
                case 'act_questionnaire_update':
                case 'app_questionnaire_update':
                case 'act_questionnaire_update_primes':
                case 'app_questionnaire_update_primes':
                case 'act_questionnaire_create_primes':
                case 'app_questionnaire_create_primes':
                case 'act_questionnaire_update_targets':
                case 'app_questionnaire_update_targets':
                case 'act_questionnaire_create_targets':
                case 'app_questionnaire_create_targets':
                case 'act_questionnaire_update_instructions':
                case 'app_questionnaire_update_instructions':
                    return $aiExecutionController->actions($request->action, $previewData);
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
     * Send message and get streaming response
     * 
     * Main streaming endpoint that handles AI chat messages with real-time response streaming.
     * Routes to appropriate streaming handler based on detected intent:
     * - Conversation: General chat responses (handleConversationStream)
     * - Guide: Help/documentation responses with context (handleGuideStream)
     * - Actions: Returns JSON for user confirmation (handleActionStream)
     * 
     * @param Request $request Contains user message, platform, and context information
     * @return StreamedResponse|JsonResponse SSE stream for conversation/guide, JSON for actions
     */
    public function sendMessageStream(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        if (config('app.debug') || env('CONV_MEMORY_DEBUG')) {
            Log::info('[ChatController] incoming stream', [
                'user_id' => optional($request->user())->id,
                'message_preview' => mb_substr($request->message, 0, 200),
                'platform' => $request->platform,
                'websitePosition' => $request->websitePosition,
            ]);
        }

        if ($this->isBulkCreationOveruse($request->message)) {
            $this->markUserMessageUnanswered($request);
            return $this->streamSimpleMessage("It looks like you're asking to create a large number of items at once. For safety and to avoid mistakes, please limit your request to a smaller batch. Would you like me to proceed with a smaller number or a single item?");
        }

        if ($request->input('expert_mode', false)) {
            Log::info('[ChatController] Expert mode detected - routing to expert mode stream immediately', [
                'expert_mode' => true,
                'message_preview' => mb_substr($request->message, 0, 100)
            ]);
            return $this->handleExpertModeStream($request);
        }

        $platform = $request->input('platform', 'act');
        
        if ($this->detectSimpleGreetings($request->message)) {
            return $this->handleSimpleGreetingStream($request, $platform);
        }
        try {
            $user = $request->user();
            if ($user) {
                $dailyLimit = (int) config('ai_chat.daily_token_limit', 25000);
                try {
                    $record = \App\Models\AI\MongoTokenManager::where('user_id', $user->id)->first();
                    $currentUsage = 0;
                    
                    if ($record) {
                        $currentUsage = (int) ($record->overall_token_used ?? 0);
                    }
                    
                    if ($currentUsage >= $dailyLimit) {
                        try {
                            // Only store in existing conversation, don't auto-create
                            $requestedConversationId = $request->input('conversation_id');
                            if ($requestedConversationId) {
                                $mongoConversation = $this->conversationStorage->getOrCreateConversation($user->id, $requestedConversationId, $platform);
                                $this->conversationStorage->addUserMessage(
                                    $mongoConversation,
                                    $request->message,
                                    $request->input('websitePosition', 'dashboard'),
                                    ['intent' => 'conversation', 'scope' => 'conversation']
                                );
                            }
                        } catch (\Throwable $e) {
                            Log::warning('Failed to store user message prior to token limit block', ['error' => $e->getMessage()]);
                        }
                        $this->markUserMessageUnanswered($request);
                        return $this->streamSimpleMessage("You've reached your daily AI token limit ({$currentUsage}/{$dailyLimit} tokens used). Please try again tomorrow.");
                    }
                } catch (\Throwable $e) {
                    Log::warning('Mongo token usage read failed - falling back to SQL estimate', ['error' => $e->getMessage()]);
                }
                $requestTokens = $this->estimateTokens($request->message);
                $replyBudget = 1000;
                $needed = max(0, $requestTokens + $replyBudget);
                if (!$this->hasTokenRemaining($user->id, $needed)) {
                    try {
                        // Only store in existing conversation, don't auto-create
                        $requestedConversationId = $request->input('conversation_id');
                        if ($requestedConversationId) {
                            $mongoConversation = $this->conversationStorage->getOrCreateConversation($user->id, $requestedConversationId, $platform);
                            $this->conversationStorage->addUserMessage(
                                $mongoConversation,
                                $request->message,
                                $request->input('websitePosition', 'dashboard'),
                                ['intent' => 'conversation', 'scope' => 'conversation']
                            );
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Failed to store user message', ['error' => $e->getMessage()]);
                    }
                    $this->markUserMessageUnanswered($request);
                    return $this->streamSimpleMessage("You've reached your daily AI token limit. Please try again tomorrow.");
                }
            }
        } catch (\Throwable $e) {
            Log::warning('TokenManager pre-check failed', ['error' => $e->getMessage()]);
        }

        $structuredResponse = [];
        $embedding = null;
        try {
            $pendingQuestion = $this->checkForPendingQuestionCreation($request);
            if ($pendingQuestion) {
                Log::info('[ChatController] Detected pending question creation BEFORE detection, skipping normal detection', [
                    'scope' => $pendingQuestion['scope'],
                    'intent' => $pendingQuestion['intent'],
                    'user_message' => $request->message
                ]);
                $structuredResponse['scope'] = $pendingQuestion['scope'];
                $structuredResponse['intent'] = $pendingQuestion['intent'];
                $structuredResponse['needs_name_generation'] = true;
                $structuredResponse['questionnaire_code'] = $request->input('questionnaire_code');
            } else {
                $embedding = $this->chatService->getEmbedding($request->message);
                $lastAssistantMessage = null;
                try {
                    $user = $request->user();
                    if ($user) {
                        $memoryContext = $this->memoryService->getMemoryContext($user->id, $platform);
                        $buffer = $memoryContext['buffer'] ?? [];
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
                $structuredResponse = $this->detectIntentAndScope($request->message, $lastAssistantMessage, $embedding);
                $structuredResponse['questionnaire_code'] = $request->input('questionnaire_code');
            }
        } catch (\Throwable $e) {
            Log::warning('Detection failed', ['error' => $e->getMessage()]);
            $structuredResponse = ['scope' => null, 'intent' => null];
        }

        if (empty($structuredResponse['scope']) && !empty($structuredResponse['intent'])) {
            if (in_array($structuredResponse['intent'], ['conversation', 'guide', 'documentation'])) {
                $structuredResponse['scope'] = $structuredResponse['intent'] === 'documentation' ? 'guide' : $structuredResponse['intent'];
                Log::info('[ChatController] Fallback: setting scope to match intent', ['scope' => $structuredResponse['scope']]);
            }
        }

        if (config('app.debug') || env('CONV_MEMORY_DEBUG')) {
            Log::info('[ChatController] detection', $structuredResponse);
        }
        if (!isset($structuredResponse['needs_name_generation'])) {
            $pendingQuestion = $this->checkForPendingQuestionCreation($request);
            if ($pendingQuestion) {
                Log::info('[ChatController] Detected pending question creation, updating scope/intent', [
                    'scope' => $pendingQuestion['scope'],
                    'intent' => $pendingQuestion['intent'],
                    'original_scope' => $structuredResponse['scope'] ?? 'null',
                    'original_intent' => $structuredResponse['intent'] ?? 'null',
                    'user_message' => $request->message
                ]);
                $structuredResponse['scope'] = $pendingQuestion['scope'];
                $structuredResponse['intent'] = $pendingQuestion['intent'];
                $structuredResponse['needs_name_generation'] = true; // Flag to indicate we should proceed with creation
            }
        }

        if (empty($structuredResponse['scope']) || empty($structuredResponse['intent'])) {
            Log::warning('[ChatController] Missing scope or intent after detection', $structuredResponse);
            // Store user message first (only if conversation already exists)
            try {
                $user = $request->user();
                if ($user) {
                    $requestedConversationId = $request->input('conversation_id');
                    if ($requestedConversationId) {
                        $mongoConversation = $this->conversationStorage->getOrCreateConversation($user->id, $requestedConversationId, $platform);
                        $this->conversationStorage->addUserMessage(
                            $mongoConversation,
                            $request->message,
                            $request->input('websitePosition', 'dashboard'),
                            ['intent' => 'conversation', 'scope' => 'conversation']
                        );
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to store user message', ['error' => $e->getMessage()]);
            }
            $this->markUserMessageUnanswered($request);
            $errorMessage = 'I\'m sorry, I\'m not quite sure what you\'re asking for! Could you please rephrase your request?';
            return $this->streamSimpleMessage($errorMessage);
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
            $this->markUserMessageUnanswered($request);
            return $this->streamSimpleMessage('I\'m sorry, I encountered an error while processing your request. Please try again or rephrase your question about ACT.');
        }
        try {
            $user = $request->user();
            if ($user) {
                $this->memoryService->appendMessage(
                    $user->id,
                    $platform,
                    'user',
                    $request->message
                );
                
                // Store in MongoDB only if conversation_id is provided
                // Don't auto-create conversations
                $requestedConversationId = $request->input('conversation_id');
                if ($requestedConversationId) {
                    try {
                        $mongoConversation = $this->conversationStorage->getOrCreateConversation(
                            $user->id,
                            $requestedConversationId,
                            $platform
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
                        $request->merge(['_mongo_conversation' => $mongoConversation]);
                    } catch (\Throwable $e) {
                        Log::warning('MongoDB conversation storage failed (streaming user message)', ['error' => $e->getMessage()]);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Conversation memory pre-append failed', ['error' => $e->getMessage()]);
        }

        if ($embedding) {
            $request->merge(['_embedding' => $embedding]);
        }

        if ($this->promptService->scope === 'survey' && $this->promptService->intent === 'create') {
            Log::info('[ChatController] Routing to survey generation stream');
            return $this->handleSurveyGenerationStream($request);
        } elseif ($this->promptService->intent === 'create') {
            Log::info('[ChatController] Routing to action handler (streaming) - intent is create');
            return $this->handleActionStream($request, $structuredResponse);
        } elseif ($this->promptService->isForGuide()) {
            Log::info('[ChatController] Routing to guide stream');
            return $this->handleGuideStream($request);
        } elseif ($this->promptService->isForConversation()) {
            Log::info('[ChatController] Routing to conversation stream');
            return $this->handleConversationStream($request);
        } else {
            Log::info('[ChatController] Routing to action handler (streaming)');
            return $this->handleActionStream($request, $structuredResponse);
        }
    }

    private function handleConversationStream(Request $request)
    {
        $user = $request->user();
        if ($user && $this->isConfirmationResponse($request->message)) {
            $confirmationResult = $this->handleConfirmation($request, $user);
            if ($confirmationResult) {
                $data = $confirmationResult->getData(true);
                if (isset($data['awaiting_details']) && $data['awaiting_details']) {
                    return $this->streamSimpleMessage($data['message'] ?? 'Could you please tell me what the question should be about?');
                }
                return $confirmationResult;
            }
        }

        $userInfo = $this->getUserTypeInfo($request);
        $user = $request->user();
        
        $memoryContextCacheKey = 'memory_context_' . $user->id . '_' . $this->promptService->platform;
        $memoryContext = \Illuminate\Support\Facades\Cache::remember($memoryContextCacheKey, now()->addMinutes(5), function() use ($user) {
            return $this->memoryService->renderContextString(
                $user->id,
                $this->promptService->platform
            );
        });

        $basePrompt = config('ai_prompts.base_prompts.conversation_stream', 'You are a helpful and expressive AI assistant for ACT, a survey platform. Respond naturally in a friendly way. If asked about non-ACT topics, politely redirect to ACT features. Keep responses concise.');
        $systemPrompt = $this->buildSystemPrompt($basePrompt, $userInfo['type'], $userInfo['firstName'], $memoryContext);

        try {
            $embedding = $request->input('_embedding') ?? $this->chatService->getEmbedding($request->message);
            $currentPage = $this->detectCurrentPage($request);
            $pageContext = $this->getPageContext($request);
            
            $neo4jContext = $this->promptService->neo4jService->getContext($embedding, $currentPage);
            
            if (!empty($neo4jContext['context'])) {
                $contextInfo = "User is on main page: {$pageContext['main_page']}";
                if ($pageContext['active_sidebar']) {
                    $contextInfo .= " with {$pageContext['active_sidebar']} sidebar open";
                }
                
                $systemPrompt .= "\n\ACT Knowledge Base ({$contextInfo}):\n" . $neo4jContext['context'];
                
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
            Log::warning('Neo4j context retrieval failed in conversation stream', ['error' => $e->getMessage()]);
        }

        $response = Prism::text()
            ->using(...AIModel::getDefaultConfig())
            ->withSystemPrompt($systemPrompt)
            ->withPrompt($request->message)
            ->asText();
            
        $fullResponse = $response->text;
        $detectedQuestionType = $this->detectQuestionTypeInMessage($request->message);
        if ($detectedQuestionType) {
            $firstName = $userInfo['firstName'];
            $nameSuffix = $firstName ? " for you {$firstName}" : " for you";
            $fullResponse .= "\n\nWould you like me to create a {$detectedQuestionType} question{$nameSuffix}?";
        }
        if ($this->isOffTopicResponse($fullResponse)) {
            $this->markUserMessageUnanswered($request);
        }

        return response()->stream(function () use ($fullResponse, $request) {
            Log::info('[ChatController] Stream callback started');        
            try {
                $words = preg_split('/(\s+)/u', $fullResponse, -1, PREG_SPLIT_DELIM_CAPTURE);
                
                foreach ($words as $word) {
                    if ($word !== '') {
                        echo "data: " . json_encode(['text' => $word]) . "\n\n";
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                        usleep(30000);
                    }
                }            
                Log::info('[ChatController] Stream complete', ['length' => strlen($fullResponse)]);
                $messageId = null; // Initialize before try block to ensure it's always defined
                try {
                    $this->memoryService->appendMessage(
                        $request->user()->id,
                        $this->promptService->platform,
                        'assistant',
                        $fullResponse
                    );
                    $mongoConversation = $request->input('_mongo_conversation');
                    $mongoConversationForSummary = $mongoConversation;
                    if (!$mongoConversationForSummary) {
                        // Only get existing conversation for summary, don't auto-create
                        $requestedConversationId = $request->input('conversation_id');
                        if ($requestedConversationId) {
                            try {
                                $mongoConversationForSummary = $this->conversationStorage->getOrCreateConversation(
                                    $request->user()->id,
                                    $requestedConversationId
                                );
                            } catch (\Throwable $e) {
                                Log::warning('Failed to get conversation for summary', ['error' => $e->getMessage()]);
                            }
                        }
                    }
                    $this->memoryService->summarizeIfNeeded($request->user()->id, $this->promptService->platform, null, $mongoConversationForSummary);
                    if ($mongoConversation) {
                        try {
                            $result = $this->conversationStorage->addAssistantMessage(
                                $mongoConversation,
                                $fullResponse,
                                0
                            );
                            $messageId = $result['message_id'] ?? null;
                        } catch (\Throwable $e) {
                            Log::warning('MongoDB conversation storage failed (streaming assistant message)', ['error' => $e->getMessage()]);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to store streamed conversation response', ['error' => $e->getMessage()]);
                }
                
                $completionData = ['done' => true];
                if ($mongoConversation) {
                    $completionData['conversation_id'] = $mongoConversation->conversation_id;
                }
                if ($messageId) {
                    $completionData['message_id'] = $messageId;
                }
                echo "data: " . json_encode($completionData) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            } catch (\Throwable $e) {
                Log::error('[ChatController] Stream error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
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

    // Handle guide intent with streaming response
    private function handleGuideStream(Request $request)
    {
        if (!$this->chatService->isQuestion($request->message)) {
            $this->markUserMessageUnanswered($request);
            return $this->streamSimpleMessage('I\'m not sure how to help with that request. Could you please rephrase it as a question?');
        }

        $userInfo = $this->getUserTypeInfo($request);
        $creationDetails = $this->detectCreationQuestion($request->message);
        $embedding = $request->input('_embedding') ?? $this->chatService->getEmbedding($request->message);

        $currentPage = $this->detectCurrentPage($request);
        $pageContext = $this->getPageContext($request);
        
        $props = $this->promptService->neo4jService->getContext($embedding, $currentPage);
        $context = $props['context'];
        $path = $props['path'];
        $instructions = $props['instructions'];

        if (empty($context) && empty($path) && empty($instructions)) {
            $this->markUserMessageUnanswered($request);
            return $this->streamSimpleMessage('I couldn\'t find specific information about that. Could you try rephrasing your question?');
        }

        $creationOffer = $creationDetails 
            ? "\n\nIMPORTANT: After providing guidance, offer to create the " . ucfirst($creationDetails['item']) . " for them." 
            : '';
        
        $basePrompt = config('ai_prompts.base_prompts.guide', 'You are a helpful guide for ACT, a survey platform. Use HTML tags for formatting only (no markdown).');
        
        $personalityPrompt = $this->buildSystemPrompt($basePrompt, $userInfo['type'], $userInfo['firstName']);
        
        $contextInfo = "Main page: {$pageContext['main_page']}";
        if ($pageContext['active_sidebar']) {
            $contextInfo .= ", Active sidebar: {$pageContext['active_sidebar']}";
        }
        
        $userPrompt = $personalityPrompt
            . "Answer this question: {$request->message}\n"
            . "User context: {$contextInfo}\n"
            . "Guide using: {$path}\n"
            . "Instructions: {$instructions}\n"
            . "Context: {$context}\n"
            . "Select only relevant context and reword naturally. Don't mention you have context. Remember: Use HTML tags (<b>, <u>, <i>) instead of markdown (** or __)."
            . $creationOffer;

        $response = Prism::text()
            ->using(...AIModel::getDefaultConfig())
            ->withPrompt($userPrompt)
            ->asText();
            
        $fullResponse = $response->text;

        $detectedQuestionType = $this->detectQuestionTypeInMessage($request->message);
        if ($detectedQuestionType) {
            $firstName = $userInfo['firstName'];
            $nameSuffix = $firstName ? " for you {$firstName}" : " for you";
            $fullResponse .= "\n\nWould you like me to create a {$detectedQuestionType} question{$nameSuffix}?";
        }
        $isOffTopic = $this->isOffTopicResponse($fullResponse);
        Log::info('Guide off-topic detection', [
            'is_off_topic' => $isOffTopic,
            'response_preview' => mb_substr($fullResponse, 0, 200)
        ]);
        
        if ($isOffTopic) {
            $this->markUserMessageUnanswered($request);
        }

        return response()->stream(function () use ($fullResponse, $request) {
            Log::info('[ChatController] Guide stream callback started');
            try {
                $words = preg_split('/(\s+)/u', $fullResponse, -1, PREG_SPLIT_DELIM_CAPTURE);
                foreach ($words as $word) {
                    if ($word !== '') {
                        echo "data: " . json_encode(['text' => $word]) . "\n\n";
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                        usleep(30000);
                    }
                }
                
                Log::info('[ChatController] Guide stream complete', ['length' => strlen($fullResponse)]);
            
                try {
                    $this->memoryService->appendMessage(
                        $request->user()->id,
                        $this->promptService->platform,
                        'assistant',
                        $fullResponse
                    );
                    $mongoConversation = $request->input('_mongo_conversation');
                    $mongoConversationForSummary = $mongoConversation;
                    if (!$mongoConversationForSummary) {
                        // Only get existing conversation for summary, don't auto-create
                        $requestedConversationId = $request->input('conversation_id');
                        if ($requestedConversationId) {
                            try {
                                $mongoConversationForSummary = $this->conversationStorage->getOrCreateConversation(
                                    $request->user()->id,
                                    $requestedConversationId
                                );
                            } catch (\Throwable $e) {
                                Log::warning('Failed to get conversation for summary', ['error' => $e->getMessage()]);
                            }
                        }
                    }
                    $this->memoryService->summarizeIfNeeded($request->user()->id, $this->promptService->platform, null, $mongoConversationForSummary);
                    
                    $messageId = null;
                    if ($mongoConversation) {
                        try {
                            $result = $this->conversationStorage->addAssistantMessage(
                                $mongoConversation,
                                $fullResponse,
                                0
                            );
                            $messageId = $result['message_id'] ?? null;
                        } catch (\Throwable $e) {
                            Log::warning('MongoDB conversation storage failed (guide stream)', ['error' => $e->getMessage()]);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to store streamed guide response', ['error' => $e->getMessage()]);
                }

                $completionData = ['done' => true];
                if ($mongoConversation) {
                    $completionData['conversation_id'] = $mongoConversation->conversation_id;
                }
                if ($messageId) {
                    $completionData['message_id'] = $messageId;
                }
                echo "data: " . json_encode($completionData) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            } catch (\Throwable $e) {
                Log::error('[ChatController] Guide stream error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
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

    private function handleActionStream(Request $request, array $structuredResponse = [])
    {
        $userInfo = $this->getUserTypeInfo($request);
        $user = $request->user();
   
        if ($user && $this->promptService->scope === 'conversation' && $this->promptService->intent === 'create') {
            $confirmationResult = $this->handleConfirmation($request, $user);
            if ($confirmationResult) {
                $data = $confirmationResult->getData(true);
                if (isset($data['awaiting_details']) && $data['awaiting_details']) {
                    return $this->streamSimpleMessage($data['message'] ?? 'Could you please tell me what the question should be about?');
                }
                return $confirmationResult;
            }
        }
        
        if ($this->promptService->scope === 'question' && $this->promptService->intent === 'create') {
            $platformIncongruency = $this->handlePlatformIncongruence($request);
            if ($platformIncongruency) {
                $this->markUserMessageUnanswered($request);
                $data = $platformIncongruency->getData(true);
                return $this->streamSimpleMessage($data['message'] ?? 'Platform mismatch detected.');
            }
        }

        $schema = $this->promptService->getSchema();
        if (!$schema) {
            $this->markUserMessageUnanswered($request);
            return $this->streamSimpleMessage('I\'m not sure how to help with that request. Could you please rephrase it?');
        }

        $memoryContextCacheKey = 'memory_context_' . $request->user()->id . '_' . $this->promptService->platform;
        $memoryContext = \Illuminate\Support\Facades\Cache::remember($memoryContextCacheKey, now()->addMinutes(5), function() use ($request) {
            return $this->memoryService->renderContextString($request->user()->id, $this->promptService->platform);
        });
        $currentFolderId = $request->folder ?? null;
        
        $baseSystemPrompt = $this->promptService->getSystemPrompt($currentFolderId);
        $systemPrompt = $this->buildSystemPrompt($baseSystemPrompt, $userInfo['type'], $userInfo['firstName'], $memoryContext);
        
        // Add questionnaire_code to system prompt for round creation
        if ($this->promptService->scope === 'round' && $request->input('questionnaire_code')) {
            $systemPrompt .= "\n\nIMPORTANT: The current survey/questionnaire code is: " . $request->input('questionnaire_code') . ". You MUST use this exact value as the survey_code in your response.";
        }
        
        // Add test_id and questionnaire_code to system prompt for questionnaire updates
        if ($this->promptService->scope === 'questionnaire' && $this->promptService->intent === 'update') {
            $questionnaireCode = $request->input('questionnaire_code');
            $testId = $request->input('test_id');
            $activeDevice = $request->input('active_device');
            $currentOrientation = $request->input('current_orientation');
            
            if ($questionnaireCode) {
                $systemPrompt .= "\n\nIMPORTANT: The current survey/questionnaire code is: " . $questionnaireCode . ". You MUST use this exact value as the survey_code in your response.";
            }
            if ($testId) {
                $systemPrompt .= "\n\nIMPORTANT: The user is currently viewing/editing test/round ID: " . $testId . ". This is the current context - the styles should be updated for this survey.";
            }
            
            // Add device context if available
            if ($activeDevice) {
                $deviceContext = "\n\nDEVICE CONTEXT: The user is currently viewing the editor in '{$activeDevice}' device mode.";
                if ($currentOrientation) {
                    $deviceContext .= " The current orientation is '{$currentOrientation}'. ";
                }
                $deviceContext .= "\n\nCRITICAL DEVICE-TO-ORIENTATION MAPPING: When the user explicitly mentions a device in their request, you MUST map it correctly:\n";
                $deviceContext .= "- 'mobile' or 'mobile setup' -> ALWAYS use 'portrait' orientation\n";
                $deviceContext .= "- 'desktop' or 'desktop setup' -> ALWAYS use 'landscape' orientation\n";
                $deviceContext .= "- 'tablet', 'tab', 'tablet setup', or 'tab setup' -> ALWAYS use 'tabletPortrait' orientation (tablets use tabletPortrait by default)\n";
                $deviceContext .= "When the user requests style changes without explicitly specifying a device or orientation, you should apply the changes to the orientation that matches the current device context. Desktop always uses 'landscape' orientation. Tablet and mobile can use 'portrait', 'landscape', or 'tabletPortrait' depending on the device and rotation state.";
                $systemPrompt .= $deviceContext;
            }
            
            $systemPrompt .= "\n\nCRITICAL PRIME/TARGET MAPPING: When the user mentions 'primes' or 'prime', you MUST use the '--prime-' prefix for CSS variables. When the user mentions 'targets' or 'target', you MUST use the '--target-' prefix for CSS variables. Examples: 'font size of primes' -> '--prime-font-size', 'font size of targets' -> '--target-font-size', 'background color of primes' -> '--prime-bg-color', 'background color of targets' -> '--target-bg-color'. DO NOT confuse primes with targets - they are different elements with different CSS variables.";
            $systemPrompt .= "\n\nCRITICAL UNIT PREFERENCE: For all size-related CSS values (font sizes, widths, heights, margins, paddings, borders, etc.), ALWAYS use 'vmin' as the default unit when the user does not specify a unit. For example, if the user says 'change font size to 5' or 'make it 10', use '5vmin' or '10vmin'. Only use other units (px, rem, %, etc.) if the user explicitly specifies them. The 'vmin' unit is preferred for responsive design.";
            $systemPrompt .= "\n\nCRITICAL FOOTER MAPPING: When the user mentions 'footer' or 'footer text', you MUST use the '--bottom-text-' prefix for CSS variables. Examples: 'Footer Font Weight' -> '--bottom-text-font-weight', 'Footer Font Size' -> '--bottom-text-font-size', 'Footer Font Color' -> '--bottom-text-color', 'Footer Font Style' -> '--bottom-text-font-style', 'Footer Line Height' -> '--bottom-text-line-height', 'Footer Width' -> '--bottom-text-width'. DO NOT use '--footer-' prefix - it does not exist. Always use '--bottom-text-' for footer-related styles.";
        }
        
        // Add context for instructions updates
        if ($this->promptService->scope === 'questionnaire' && $this->promptService->intent === 'update_instructions') {
            $testId = $request->input('test_id');
            if ($testId && $user) {
                try {
                    $test = \App\Models\Test::where('id', $testId)
                        ->where('user_id', $user->id)
                        ->first();
                    
                    if ($test) {
                        $config = $test->config ?? [];
                        $currentHeader = strip_tags($config['main_header'] ?? '');
                        $currentBottom = strip_tags($config['bottom_text'] ?? '');
                        
                        $systemPrompt .= "\n\nCRITICAL - CURRENT INSTRUCTIONS IN TEST {$testId}:\n";
                        if ($currentHeader) {
                            $systemPrompt .= "  - Main Header (header): '{$currentHeader}'\n";
                        }
                        if ($currentBottom) {
                            $systemPrompt .= "  - Bottom Text (bottom): '{$currentBottom}'\n";
                        }
                        $systemPrompt .= "\nCRITICAL MAPPING: When the user says 'change the header' or 'update the header', you MUST set the main_header field with the new text. When the user says 'change the bottom' or 'update the bottom', you MUST set the bottom_text field with the new text. The word 'header' refers to main_header, and 'bottom' refers to bottom_text. Set the other field to null if not being updated.";
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to load test data for instructions context', [
                        'test_id' => $testId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        // Add context for round settings updates
        if ($this->promptService->scope === 'round' && $this->promptService->intent === 'update_settings') {
            $testId = $request->input('test_id');
            if ($testId && $user) {
                try {
                    $test = \App\Models\Test::where('id', $testId)
                        ->where('user_id', $user->id)
                        ->first();
                    
                    if ($test) {
                        $config = $test->config ?? [];
                        
                        $systemPrompt .= "\n\nCRITICAL - CURRENT SETTINGS IN TEST {$testId}:\n";
                        $systemPrompt .= "  - Practice Trial: " . (($config['practice_trial'] ?? false) ? 'enabled' : 'disabled') . "\n";
                        $systemPrompt .= "  - Main Trial: " . (($config['main_trial'] ?? false) ? 'enabled' : 'disabled') . "\n";
                        $systemPrompt .= "  - Randomize Targets: " . (($config['random'] ?? false) ? 'enabled' : 'disabled') . "\n";
                        $systemPrompt .= "  - Randomize Primes: " . (($config['random_prime'] ?? false) ? 'enabled' : 'disabled') . "\n";
                        $systemPrompt .= "  - Prime as Next Button: " . (($config['prime_as_next'] ?? false) ? 'enabled' : 'disabled') . "\n";
                        $systemPrompt .= "  - Single Select: " . (($config['single_select'] ?? false) ? 'enabled' : 'disabled') . "\n";
                        $systemPrompt .= "  - Auto Next: " . (($config['auto_next'] ?? false) ? 'enabled' : 'disabled') . "\n";
                        $systemPrompt .= "  - Trial Unselect: " . (($config['trial_unselect'] ?? false) ? 'enabled' : 'disabled') . "\n";
                        
                        if (isset($config['trial_unselect_label'])) {
                            $systemPrompt .= "  - Trial Unselect Label: '{$config['trial_unselect_label']}'\n";
                        }
                        if (isset($config['empty_selection'])) {
                            $systemPrompt .= "  - Empty Selection Text: '{$config['empty_selection']}'\n";
                        }
                        if (isset($config['limit_reach'])) {
                            $systemPrompt .= "  - Limit Reach Text: '{$config['limit_reach']}'\n";
                        }
                        if (isset($config['limit'])) {
                            $systemPrompt .= "  - Selection Limit: {$config['limit']} (0 = unlimited)\n";
                        }
                        if (isset($config['display_by'])) {
                            $systemPrompt .= "  - Display Targets By: {$config['display_by']}\n";
                        }
                        if (isset($config['target_delay'])) {
                            $systemPrompt .= "  - Target Delay: {$config['target_delay']} ms\n";
                        }
                        
                        $systemPrompt .= "\nCRITICAL: Only include settings in the update that the user explicitly mentions changing. Do not include all settings - only the ones the user wants to modify. The test_id is {$testId}.";
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to load test data for round settings context', [
                        'test_id' => $testId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        // Add current targets/primes data to system prompt for update operations
        if ($this->promptService->scope === 'questionnaire' && 
            in_array($this->promptService->intent, ['update_targets', 'update_primes', 'create_targets', 'create_primes'])) {
            $testId = $request->input('test_id');
            if ($testId && $user) {
                try {
                    $test = \App\Models\Test::where('id', $testId)
                        ->where('user_id', $user->id)
                        ->first();
                    
                    if ($test) {
                        if (in_array($this->promptService->intent, ['update_targets', 'create_targets'])) {
                            $targets = $test->targets ?? collect([]);
                            if ($targets->isNotEmpty()) {
                                $targetsList = $targets->map(function($target) {
                                    $index = $target['index'] ?? '?';
                                    $equivalent = $target['equivalent'] ?? 'N/A';
                                    $targetText = strip_tags($target['target'] ?? '');
                                    return "  - Index {$index}: equivalent='{$equivalent}', target='{$targetText}'";
                                })->join("\n");
                                $systemPrompt .= "\n\nCRITICAL - CURRENT TARGETS IN TEST {$testId}:\n{$targetsList}\n\nYou MUST use the exact equivalent text values shown above when identifying targets to update. Users can directly mention the equivalent text (e.g., 'change Option A target to Option B'). Extract the exact equivalent text the user mentions and match it against the list above. 
                                DO NOT make up or guess equivalent values like 'Option A', 'Option B' - use the actual equivalents shown above.
                                \n\nIMPORTANT FOR MULTIPLE TARGETS (UPDATE): When the user wants to update multiple targets (e.g., 'change Option A and Option B targets to Option C and Option D', 'update Red and Blue targets to Green and Yellow'), you MUST extract EACH target update separately. For 'change Option A and Option B targets to Option C and Option D', create 2 update objects: [{'identifier': 'Option A', 'target': 'Option C'}, {'identifier': 'Option B', 'target': 'Option D'}]. Use the OLD target name as the identifier to find it, and the NEW name as the target text. ALWAYS include ALL mentioned updates.
                                \n\nIMPORTANT FOR MULTIPLE TARGETS (CREATE): When the user wants to add multiple targets (e.g., 'add Option A, Option B, and Option C as targets', 'add Red, Blue, and Green as targets'), you MUST extract EACH target separately and create a separate target object for each. Split on commas and 'and'. Example: 'add Option A, Option B, and Option C as targets' - create 3 targets: [{'target': 'Option A'}, {'target': 'Option B'}, {'target': 'Option C'}]. ALWAYS include ALL mentioned targets in the targets array, not just the first one.";
                            }
                        }
                        
                        if (in_array($this->promptService->intent, ['update_primes', 'create_primes'])) {
                            $primes = $test->primes ?? collect([]);
                            if ($primes->isNotEmpty()) {
                                $primesList = $primes->map(function($prime) {
                                    $index = $prime['index'] ?? '?';
                                    $equivalent = $prime['equivalent'] ?? 'N/A';
                                    $primeText = strip_tags($prime['prime'] ?? '');
                                    return "  - Index {$index}: equivalent='{$equivalent}', prime='{$primeText}'";
                                })->join("\n");
                                $systemPrompt .= "\n\nCRITICAL - CURRENT PRIMES IN TEST {$testId}:\n{$primesList}\n\nYou MUST use the exact equivalent text values shown above when identifying primes to update. Users can directly mention the equivalent text (e.g., 'change Love prime to Hatred'). Extract the exact equivalent text the user mentions and match it against the list above. DO NOT make up or guess equivalent values - use the actual equivalents shown above.
                                \n\nIMPORTANT FOR MULTIPLE PRIMES (UPDATE): When the user wants to update multiple primes (e.g., 'change Love and Hate primes to Happy and Sad', 'update Secure and Safe primes to Happy and Sad'), you MUST extract EACH prime update separately. For 'change Love and Hate primes to Happy and Sad', create 2 update objects: [{'identifier': 'Love', 'prime': 'Happy'}, {'identifier': 'Hate', 'prime': 'Sad'}]. Use the OLD prime name as the identifier to find it, and the NEW name as the prime text. ALWAYS include ALL mentioned updates.\n\nIMPORTANT FOR MULTIPLE PRIMES (CREATE): When the user wants to add multiple primes (e.g., 'add Option A, Option B, and Option C as primes', 'add Happy, Sad, and Angry as primes'), you MUST extract EACH prime separately and create a separate prime object for each. Split on commas and 'and'. Example: 'add Option A, Option B, and Option C as primes' → create 3 primes: [{'prime': 'Option A'}, {'prime': 'Option B'}, {'prime': 'Option C'}]. ALWAYS include ALL mentioned primes in the primes array, not just the first one.";
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to load test data for AI context', [
                        'test_id' => $testId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        if (isset($structuredResponse['needs_name_generation']) && $structuredResponse['needs_name_generation']) {
            $userDetails = trim($request->message);
            $userDetails = preg_replace('/^(about|regarding|on|for|concerning)\s+/i', '', $userDetails);
            $questionTypeDisplay = $this->getQuestionTypeDisplayName($this->promptService->scope);
            
            if ($this->promptService->scope === 'pairwise_comparison') {
                $systemPrompt .= "\n\nCRITICAL FOR PAIRWISE COMPARISON: The user wants to create a pairwise comparison question about: \"{$userDetails}\". You MUST: (1) First identify 3-5 distinct base options/items related to this topic, (2) Assign each base option a unique sequential value (1, 2, 3, etc.), (3) Generate ALL pairwise combinations of these base options (for n options, create n*(n-1)/2 items), (4) Each statement must be wrapped in <p> tags, (5) Index must start at 1 and increment sequentially. The same base option must always use the same value across all items.";
            } else {
                $systemPrompt .= "\n\nIMPORTANT: The user wants to create a {$questionTypeDisplay} question about: \"{$userDetails}\". You MUST generate appropriate question text and all required options/choices based on this topic. Create a complete, usable question with realistic options that relate to the topic. ALWAYS generate a meaningful, concise title (3-8 words) for the question based on its content - the title should summarize what the question is about.";
            }
        }
        
        $prompt = $this->promptService->getPrompt($request);
        
        if (isset($structuredResponse['needs_name_generation']) && $structuredResponse['needs_name_generation']) {
            $userDetails = trim($request->message);
            $userDetails = preg_replace('/^(about|regarding|on|for|concerning)\s+/i', '', $userDetails);
            $questionTypeDisplay = $this->getQuestionTypeDisplayName($this->promptService->scope);
            
            if ($this->promptService->scope === 'pairwise_comparison') {
                $prompt = "Generate a pairwise comparison question about: {$userDetails}. First identify 3-5 base options related to this topic, then generate ALL pairwise combinations of these options. Each base option must have a unique value (1, 2, 3, etc.) and statements must be wrapped in <p> tags.";
            } else {
                $prompt = "Generate a {$questionTypeDisplay} question about: {$userDetails}. Include appropriate question text and all necessary options/choices.";
            }
        }

        $query = Prism::structured()
            ->using(...AIModel::getDefaultConfig())
            ->withSchema($schema)
            ->withSystemPrompt($systemPrompt)
            ->withPrompt($prompt)
            ->usingTemperature(0.1);

        // Debug: Write schema to file for comparison
        file_put_contents(
            storage_path('logs/schema_' . $schema->name() . '.json'),
            json_encode($schema->toArray(), JSON_PRETTY_PRINT)
        );
        \Log::info('[DEBUG] Schema written to schema_' . $schema->name() . '.json');

        try {
            $response = $query->asStructured();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('OpenAI API connection timeout', [
                'error' => $e->getMessage(),
                'action' => $this->promptService->action,
                'scope' => $this->promptService->scope,
                'intent' => $this->promptService->intent
            ]);
            $this->markUserMessageUnanswered($request);
            return $this->streamSimpleMessage('I\'m having trouble connecting to the AI service right now. This might be due to a network timeout. Please try again in a moment.');
        } catch (\Throwable $e) {
            Log::error('Error executing Prism structured query', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'action' => $this->promptService->action,
                'scope' => $this->promptService->scope,
                'intent' => $this->promptService->intent
            ]);
            $this->markUserMessageUnanswered($request);
            return $this->streamSimpleMessage('I encountered an error while processing your request. Please try again or rephrase your request.');
        }

        info($response->structured);
        $subscriptionNeeded = $this->promptService->checkSubscription($response->structured);
        if ($subscriptionNeeded) {
            $this->markUserMessageUnanswered($request);
            $data = $subscriptionNeeded->getData(true);
            return $this->streamSimpleMessage($data['message'] ?? 'Subscription upgrade required.');
        }

        $needsNameGeneration = $structuredResponse['needs_name_generation'] ?? false;
        $missingMessage = $this->promptService->checkMissingParameters($response->structured, $needsNameGeneration);

        info($missingMessage);

        if ($missingMessage) {
            $this->markUserMessageUnanswered($request);
            $data = $missingMessage->getData(true);
            return $this->streamSimpleMessage($data['message'] ?? 'Missing required parameters.');
        }

        if ($this->promptService->action === 'app_component_copy') {
            $copies = $response->structured['copies'] ?? [];
            $hasInvalidCopy = false;
            
            foreach ($copies as $copy) {
                if (empty($copy['target_survey_uuid'])) {
                    $hasInvalidCopy = true;
                    break;
                }
            }
            
            if ($hasInvalidCopy || empty($copies)) {
                $this->markUserMessageUnanswered($request);
                $errorMessage = $response->structured['message'] ?? 'Unable to copy component - target survey not found.';
                return $this->streamSimpleMessage($errorMessage);
            }
        }

        \Log::info('Structured response for action (streaming):', [
            'action' => $this->promptService->action,
            'parameters' => $response->structured
        ]);
        $confirmationMessage = $response->structured['message'] ?? 'Do you want to proceed with this action?';
        $usageIn = $this->estimateTokens($systemPrompt . ' ' . (is_string($prompt) ? $prompt : json_encode($prompt)));
        $usageOut = $this->estimateTokens(json_encode($response->structured));
        $actionData = [
            'action' => $this->promptService->action,
            'scope' => $this->promptService->scope,
            'intent' => $this->promptService->intent,
            'message' => $confirmationMessage,
            'preview_data' => $response->structured,
            'questionnaire_code' => $request->input('questionnaire_code'),
            'test_id' => $request->input('test_id'),
            'active_device' => $request->input('active_device'),
            'current_orientation' => $request->input('current_orientation'),
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
                        'preview_data' => $response->structured,
                        'questionnaire_code' => $request->input('questionnaire_code'),
                        'test_id' => $request->input('test_id')
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

        return response()->stream(function () use ($confirmationMessage, $actionData, $request) {
            Log::info('[ChatController] Action stream callback started');
            $messageId = null; // Initialize outside try blocks to maintain scope
            try {
                // First, stream the confirmation message word-by-word
                Log::info('[ChatController] Streaming confirmation message');
                $words = preg_split('/(\s+)/u', $confirmationMessage, -1, PREG_SPLIT_DELIM_CAPTURE);
                foreach ($words as $word) {
                    if ($word !== '') {
                        echo "data: " . json_encode(['text' => $word]) . "\n\n";
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                        usleep(30000); // Small delay for natural streaming effect
                    }
                }

                // Store message to memory
                try {
                    $this->memoryService->appendMessage(
                        $request->user()->id,
                        $this->promptService->platform,
                        'assistant',
                        $confirmationMessage
                    );
                    $mongoConversation = $request->input('_mongo_conversation');
                    $mongoConversationForSummary = $mongoConversation;
                    if (!$mongoConversationForSummary) {
                        // Only get existing conversation for summary, don't auto-create
                        $requestedConversationId = $request->input('conversation_id');
                        if ($requestedConversationId) {
                            try {
                                $mongoConversationForSummary = $this->conversationStorage->getOrCreateConversation(
                                    $request->user()->id,
                                    $requestedConversationId
                                );
                            } catch (\Throwable $e) {
                                Log::warning('Failed to get conversation for summary', ['error' => $e->getMessage()]);
                            }
                        }
                    }
                    $this->memoryService->summarizeIfNeeded($request->user()->id, $this->promptService->platform, null, $mongoConversationForSummary);
                    
                    if ($mongoConversation) {
                        try {
                            $result = $this->conversationStorage->addAssistantMessage(
                                $mongoConversation,
                                $confirmationMessage,
                                0,
                                [
                                    'action' => $actionData['action'] ?? null,
                                    'scope' => $actionData['scope'] ?? null,
                                    'intent' => $actionData['intent'] ?? null,
                                ]
                            );
                            $messageId = $result['message_id'] ?? null;
                        } catch (\Throwable $e) {
                            Log::warning('MongoDB conversation storage failed (action stream)', ['error' => $e->getMessage()]);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to store streamed action response', ['error' => $e->getMessage()]);
                }

                // Then send the action data
                Log::info('[ChatController] Sending action data');
                $actionData['loading'] = false;              
                $responseData = [
                    'action_data' => $actionData,
                    'done' => true
                ];
                if ($mongoConversation) {
                    $responseData['conversation_id'] = $mongoConversation->conversation_id;
                }
                if ($messageId) {
                    $responseData['message_id'] = $messageId;
                }
                echo "data: " . json_encode($responseData) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                Log::info('[ChatController] Action stream complete with action data');
            } catch (\Throwable $e) {
                Log::error('[ChatController] Action stream error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
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

    /**
     * @param string $message The message to stream to the client
     * @param string|null $conversationId Optional conversation ID to return to frontend
     * @param string|null $messageId Optional assistant message ID to return to frontend
     * @return StreamedResponse SSE stream with word-by-word text chunks
     */
    private function streamSimpleMessage(string $message, ?string $conversationId = null, ?string $messageId = null)
    {
        return response()->stream(function () use ($message, $conversationId, $messageId) {
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
            
            $finalPayload = ['done' => true];
            if ($conversationId) {
                $finalPayload['conversation_id'] = $conversationId;
            }
            if ($messageId) {
                $finalPayload['message_id'] = $messageId;
            }
            echo "data: " . json_encode($finalPayload) . "\n\n";
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

    private function detectCreationQuestion(string $message): ?array
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

    private function handleSurveyGenerationStream(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->streamSimpleMessage('Authentication required');
            }

            $questionnaireCode = $request->input('questionnaire_code');
            if (!$questionnaireCode) {
                return $this->streamSimpleMessage('Questionnaire context required for survey generation');
            }

            $surveyPrompt = $request->message;
            
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

            $planPrompt = "Create a plan for a survey based on: {$surveyPrompt}. " .
                         "List the question types you plan to use and the purpose of each question. " .
                         "Plan 5-8 questions that will thoroughly cover the topic.";

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
                
                Log::info('[Survey Generation] Survey plan received (streaming)', [
                    'planned_question_types' => $plannedTypes,
                    'total_planned' => count($planData['planned_questions'] ?? []),
                    'plan_details' => $planData
                ]);
            } catch (\Throwable $e) {
                Log::warning('[Survey Generation] Plan generation failed (streaming), continuing anyway', [
                    'error' => $e->getMessage()
                ]);
                $plannedTypes = [];
            }

            $surveySchema = new ObjectSchema(
                name: 'survey_generation',
                description: 'Generate a complete survey with multiple questions',
                properties: [
                    new ArraySchema(
                        name: 'questions',
                        description: 'Array of question definitions',
                        items: new ObjectSchema(
                            name: 'question',
                            description: 'Question definition for survey',
                            properties: [
                                new EnumSchema(
                                    name: 'question_type',
                                    description: 'Question type',
                                    options: ['descriptive', 'text_selection', 'image_selection', 'single_textbox', 'multiple_textbox', 'open_ended', 'slider', 'ranking', 'star_rating', 'button_rating', 'segmentation', 'matrix', 'pairwise_comparison', 'best-worst_scale', 'pie_chart', 'bar_chart']
                                ),
                                (new CreateBaseQuestionSchema())->getSchema(),
                                new ArraySchema(
                                    name: 'text_options',
                                    description: 'Text options/choices for selection questions (text_selection, image_selection). Only include if question_type is text_selection or image_selection.',
                                    items: new ObjectSchema(
                                        name: 'text_option',
                                        description: 'A choice option',
                                        properties: [
                                            new StringSchema(
                                                name: 'text',
                                                description: 'The choice text'
                                            )
                                        ],
                                        requiredFields: ['text']
                                    )
                                ),
                                new ArraySchema(
                                    name: 'items',
                                    description: 'Items for slider/ranking questions. Only include if question_type is slider or ranking.',
                                    items: new ObjectSchema(
                                        name: 'item',
                                        description: 'An item',
                                        properties: [
                                            new StringSchema(
                                                name: 'text',
                                                description: 'The item text'
                                            )
                                        ],
                                        requiredFields: ['text']
                                    )
                                ),
                                new ArraySchema(
                                    name: 'choices',
                                    description: 'Scale values for slider questions. Only include if question_type is slider.',
                                    items: new ObjectSchema(
                                        name: 'scale_value',
                                        description: 'A scale value',
                                        properties: [
                                            new StringSchema(
                                                name: 'value',
                                                description: 'The scale value text'
                                            )
                                        ],
                                        requiredFields: ['value']
                                    )
                                )
                            ],
                            requiredFields: ['question_type', 'title_and_question_text']
                        )
                    )
                ],
                requiredFields: ['questions']
            );

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

            $systemPrompt = "You are an AI assistant for ACT survey platform. Generate a complete survey based on the user's request. " .
                          "Create 5-8 diverse questions with appropriate question types. Mix different question types for better survey design. " .
                          "For each question, determine the best question type based on the information being collected. " .
                          "Generate realistic choices/options for selection-type questions. " .
                          "Ensure questions are logically ordered and flow well together.";

            $prompt = "Generate a complete survey based on this request: {$surveyPrompt}. " .
                     "Create a comprehensive set of questions that thoroughly covers the topic. " .
                     "Use appropriate question types for each question. " .
                     "Return the survey structure with all questions and their configurations.";

            Log::info('[Survey Generation] Sending request to AI model (streaming)', [
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
                    ->asStructured();
                
                Log::info('[Survey Generation] AI response received successfully (streaming)');
            } catch (\Throwable $e) {
                Log::error('[Survey Generation] Schema validation failed (streaming)', [
                    'error' => $e->getMessage(),
                    'error_type' => get_class($e),
                    'planned_types' => $plannedTypes,
                    'schema_name' => 'survey_generation',
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

            $surveyData = $response->structured ?? [];
            
            Log::info('[Survey Generation] Parsing survey data', [
                'has_questions' => isset($surveyData['questions']),
                'questions_count' => count($surveyData['questions'] ?? []),
                'question_types_generated' => array_column($surveyData['questions'] ?? [], 'question_type')
            ]);
            
            if (empty($surveyData['questions']) || !is_array($surveyData['questions'])) {
                Log::error('[Survey Generation] Invalid survey data structure', [
                    'survey_data_keys' => array_keys($surveyData),
                    'questions_type' => gettype($surveyData['questions'] ?? null)
                ]);
                $this->markUserMessageUnanswered($request);
                return $this->streamSimpleMessage('Failed to generate survey questions. Please try again.');
            }

            $totalQuestions = count($surveyData['questions']);
            $questionTypes = array_column($surveyData['questions'], 'question_type');
            
            Log::info('[Survey Generation] Survey generated successfully', [
                'total_questions' => $totalQuestions,
                'question_types' => $questionTypes,
                'type_counts' => array_count_values($questionTypes)
            ]);
            
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
                        'text' => 'Yes, Create All Questions',
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

            return response()->stream(function () use ($confirmationMessage, $actionData, $request) {
                try {
                    $this->memoryService->appendMessage(
                        $request->user()->id,
                        $this->promptService->platform,
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
        } catch (\Throwable $e) {
            Log::error('Survey generation stream failed', ['error' => $e->getMessage()]);
            $this->markUserMessageUnanswered($request);
            return $this->streamSimpleMessage('I\'m sorry, I encountered an error while generating the survey. Please try again.');
        }
    }

    private function handleExpertModeStream(Request $request)
    {
        $userInfo = $this->getUserTypeInfo($request);
        $user = $request->user();
        $platform = $request->input('platform', 'act'); // Get platform from request, default to 'act'
    
    // Line 3166: Log expert mode usage
    Log::info('[Expert Mode] Using expert knowledge base (Category, Question, Answer, Component, Tools nodes)');
    
    // Lines 3169-3195: Store user message in conversation history
    try {
        if ($user) {
            $this->memoryService->appendMessage(
                $user->id,
                $platform,
                'user',
                $request->message
            );
            
            // Store in MongoDB conversation only if conversation_id is provided
            $requestedConversationId = $request->input('conversation_id');
            if ($requestedConversationId) {
                try {
                    $mongoConversation = $this->conversationStorage->getOrCreateConversation(
                        $user->id,
                        $requestedConversationId,
                        $platform
                    );
                    $this->conversationStorage->addUserMessage(
                        $mongoConversation,
                        $request->message,
                        $request->input('websitePosition', 'dashboard'),
                        ['intent' => 'expert', 'scope' => 'expert']
                    );
                } catch (\Throwable $e) {
                    Log::warning('[Expert Mode] MongoDB conversation storage failed (user message)', ['error' => $e->getMessage()]);
                }
            }
        }
    } catch (\Throwable $e) {
        Log::warning('[Expert Mode] Conversation memory pre-append failed', ['error' => $e->getMessage()]);
    }
    
    // Line 3197: Build system prompt with expert persona
    $expertContext = ['context' => '', 'relatedNodes' => []];
    
    // Lines 3201-3245: Retrieve expert context from knowledge base
    try {
        // Line 3202: Get embedding for vector search
        $embedding = $request->input('_embedding') ?? $this->chatService->getEmbedding($request->message);
        
        // Line 3209: Retrieve expert context
        $expertContext = $this->neo4jService->getExpertContext($embedding);
        
        // Log what was retrieved
        Log::info('[Expert Mode] Expert context retrieved', [
            'has_context' => !empty($expertContext['context']),
            'context_length' => strlen($expertContext['context'] ?? ''),
            'context_preview' => mb_substr($expertContext['context'] ?? '', 0, 500),
            'related_nodes_count' => count($expertContext['relatedNodes'] ?? []),
            'node_ids' => $expertContext['nodeIds'] ?? []
        ]);
    } catch (\Throwable $e) {
        Log::error('[Expert Mode] Context retrieval failed', ['error' => $e->getMessage()]);
        $expertContext = ['context' => '', 'relatedNodes' => []];
    }
    
    // Build system prompt based on whether we have knowledge base content
    if (!empty($expertContext['context'])) {
        // Knowledge base is available - use strict RAG mode
        $knowledgeBaseContent = $expertContext['context'];
        $contextLength = strlen($knowledgeBaseContent);
        $relatedNodeCount = count($expertContext['relatedNodes'] ?? []);
        
        Log::info('[Expert Mode] Knowledge base content retrieved - using STRICT RAG mode', [
            'context_length' => $contextLength,
            'context_preview' => mb_substr($knowledgeBaseContent, 0, 300),
            'related_nodes_count' => $relatedNodeCount,
            'node_ids' => $expertContext['nodeIds'] ?? []
        ]);
        
        $basePrompt = config('ai_prompts.base_prompts.expert_mode', 'You are a research expert...');
        $systemPrompt = $this->buildSystemPrompt($basePrompt, $userInfo['type'], $userInfo['firstName']);
        
        // CRITICAL: Very strict RAG instructions with explicit examples
        $systemPrompt .= "\n\n=== ⚠️⚠️⚠️ CRITICAL: RESEARCH KNOWLEDGE BASE (MANDATORY - USE ONLY THIS CONTENT) ⚠️⚠️⚠️ ===\n";
        $systemPrompt .= "The following content from the Research Knowledge Base is the ONLY information you are authorized to use.\n";
        $systemPrompt .= "You MUST base your response EXCLUSIVELY on this content. Do NOT use any other knowledge.\n\n";
        $systemPrompt .= $knowledgeBaseContent;
        $systemPrompt .= "\n\n=== END OF RESEARCH KNOWLEDGE BASE ===\n";
        $systemPrompt .= "\n\n🚨🚨🚨 ABSOLUTE STRICT RAG REQUIREMENTS - VIOLATION IS NOT PERMITTED 🚨🚨🚨\n";
        $systemPrompt .= "\nYOU ARE FORBIDDEN FROM:\n";
        $systemPrompt .= "- Using ANY information from your training data\n";
        $systemPrompt .= "- Adding examples, details, or explanations not in the knowledge base\n";
        $systemPrompt .= "- Inferring or extrapolating beyond what is explicitly stated\n";
        $systemPrompt .= "- Using general knowledge about research methods\n";
        $systemPrompt .= "- Providing additional context or background not in the knowledge base\n";
        $systemPrompt .= "- Mentioning 'Research Knowledge Base', 'knowledge base', 'according to the knowledge base', or any similar phrases in your response\n";
        $systemPrompt .= "\nYOU MUST:\n";
        $systemPrompt .= "1. Read the knowledge base content above CAREFULLY\n";
        $systemPrompt .= "2. Extract ONLY the information relevant to the user's question from the knowledge base\n";
        $systemPrompt .= "3. Restate that information naturally in your own words, as if it were your own knowledge, maintaining the same meaning and structure\n";
        $systemPrompt .= "4. If the knowledge base discusses concepts in a specific order, maintain that order\n";
        $systemPrompt .= "5. If the knowledge base uses specific terminology, use the same terminology\n";
        $systemPrompt .= "6. If the knowledge base provides examples, you may reference them but not add new ones\n";
        $systemPrompt .= "7. Write your response naturally and directly - do NOT mention that you are using a knowledge base\n";
        $systemPrompt .= "8. If the knowledge base does NOT contain information to answer the question, you MUST say: 'I do not have information to answer this question.' (do NOT mention knowledge base)\n";
        $systemPrompt .= "\nEXAMPLE OF CORRECT BEHAVIOR:\n";
        $systemPrompt .= "If the knowledge base says: 'Qualitative research focuses on understanding experiences through interviews and observations.'\n";
        $systemPrompt .= "CORRECT response: 'Qualitative research focuses on understanding experiences through interviews and observations.'\n";
        $systemPrompt .= "WRONG response: 'According to the Research Knowledge Base, qualitative research focuses on understanding experiences through interviews and observations.' (mentioned knowledge base)\n";
        $systemPrompt .= "WRONG response: 'Qualitative research focuses on understanding experiences through interviews, observations, and surveys.' (added 'surveys' which wasn't in knowledge base)\n";
        $systemPrompt .= "\nREMEMBER: Every fact, concept, method, or detail in your response MUST be traceable to the knowledge base content above.\n";
        $systemPrompt .= "If you cannot find it in the knowledge base, DO NOT include it in your response.\n";
        $systemPrompt .= "Write naturally and directly - act as if the knowledge base content is your own expertise, but DO NOT mention the knowledge base.\n";
        
        $userPrompt = "CRITICAL INSTRUCTIONS:\n";
        $userPrompt .= "1. Read the Research Knowledge Base content in the system prompt above\n";
        $userPrompt .= "2. Find information relevant to the user's question\n";
        $userPrompt .= "3. Restate ONLY that information naturally and directly - do NOT mention the knowledge base\n";
        $userPrompt .= "4. Write as if you are providing expert knowledge directly, without referencing any source\n";
        $userPrompt .= "5. If the knowledge base doesn't contain the answer, simply say you don't have that information (without mentioning knowledge base)\n\n";
        $userPrompt .= "User question: " . $request->message;
    } else {
        // Knowledge base is empty or unavailable - use general knowledge mode
        $systemPrompt = $this->buildSystemPrompt(
            'You are a research expert specializing in survey methodology, research design, and data collection best practices. ' .
            'Provide clear, scholarly, and analytical guidance based on your expertise and general knowledge. ' .
            'You cannot perform actions within the platform; your role is purely to provide expert research recommendations. ' .
            'IMPORTANT: The Research Knowledge Base is currently empty or unavailable. ' .
            'You should provide helpful, expert-level guidance using your general knowledge about research methods, ' .
            'but be transparent that you are not referencing a specific knowledge base. ' .
            'CRITICAL FORMATTING RULES: NEVER use markdown syntax like ** (for bold), __ (for underline), or * (for italic). ' .
            'ALWAYS use HTML tags instead: <b>text</b> for bold, <u>text</u> for underline, <i>text</i> for italic. ' .
            'Do not use too many <br> tags - you can only use it once, not consecutively. ' .
            'Avoid using heading tags (like <h1> to <h5>) but you can use tags like <span>, <b>, <u>, and <i>. ' .
            'IMPORTANT: Use only single spaces between words - never use multiple consecutive spaces. Keep spacing minimal and clean.',
            $userInfo['type'],
            $userInfo['firstName']
        );
        
        $userPrompt = $request->message;
        Log::info('[Expert Mode] Knowledge base is empty - using general knowledge mode');
    }
    
    // Lines 3260-3265: Call AI model with low temperature
    $hasKnowledgeBase = !empty($expertContext['context']);
    
    if ($hasKnowledgeBase) {
        Log::info('[Expert Mode] Generating response using STRICT RAG mode with knowledge base');
    } else {
        Log::info('[Expert Mode] Generating response using general knowledge (no knowledge base content)');
    }
    
    // Use even lower temperature for stricter adherence to knowledge base
    $temperature = $hasKnowledgeBase ? 0.05 : 0.1; // Lower temperature when using knowledge base
    
    $response = Prism::text()
        ->using(...AIModel::getDefaultConfig())
        ->withSystemPrompt($systemPrompt)
        ->withPrompt($userPrompt)
        ->usingTemperature($temperature)
        ->asText();
        
    $fullResponse = $response->text;
    
    // Log response source and validate against knowledge base
    if ($hasKnowledgeBase) {
        $kbPreview = mb_substr($expertContext['context'], 0, 500);
        $responsePreview = mb_substr($fullResponse, 0, 500);
        
        Log::info('[Expert Mode] Response generated from knowledge base', [
            'response_length' => strlen($fullResponse),
            'response_preview' => $responsePreview,
            'knowledge_base_preview' => $kbPreview,
            'source' => 'knowledge_base'
        ]);
        
        // Check if response seems to reference knowledge base content
        $kbKeywords = $this->extractKeywords($expertContext['context']);
        $responseKeywords = $this->extractKeywords($fullResponse);
        $overlap = count(array_intersect($kbKeywords, $responseKeywords));
        $overlapRatio = count($kbKeywords) > 0 ? ($overlap / count($kbKeywords)) * 100 : 0;
        
        Log::info('[Expert Mode] Knowledge base alignment check', [
            'kb_keywords_count' => count($kbKeywords),
            'response_keywords_count' => count($responseKeywords),
            'overlapping_keywords' => $overlap,
            'overlap_percentage' => round($overlapRatio, 2) . '%',
            'kb_keywords_sample' => array_slice($kbKeywords, 0, 10),
            'response_keywords_sample' => array_slice($responseKeywords, 0, 10)
        ]);
        
        if ($overlapRatio < 30) {
            Log::warning('[Expert Mode] Low keyword overlap - response may not be following knowledge base strictly', [
                'overlap_percentage' => round($overlapRatio, 2) . '%',
                'warning' => 'Response may contain information not from knowledge base',
                'threshold' => '30%',
                'recommendation' => 'Consider regenerating response with stricter instructions'
            ]);
        } else {
            Log::info('[Expert Mode] Good keyword overlap - response appears to align with knowledge base', [
                'overlap_percentage' => round($overlapRatio, 2) . '%'
            ]);
        }
        
        // Additional validation: Check for common phrases that suggest general knowledge
        $generalKnowledgePhrases = [
            'generally speaking', 'typically', 'usually', 'commonly', 'often', 
            'in general', 'most researchers', 'many studies', 'research shows',
            'it is known that', 'it is common', 'widely accepted'
        ];
        $foundGeneralPhrases = [];
        $lowerResponse = strtolower($fullResponse);
        foreach ($generalKnowledgePhrases as $phrase) {
            if (strpos($lowerResponse, $phrase) !== false) {
                $foundGeneralPhrases[] = $phrase;
            }
        }
        
        if (!empty($foundGeneralPhrases)) {
            Log::warning('[Expert Mode] Response contains phrases suggesting general knowledge usage', [
                'phrases_found' => $foundGeneralPhrases,
                'warning' => 'Response may be using general knowledge instead of knowledge base'
            ]);
        }
    } else {
        Log::info('[Expert Mode] Response generated from general knowledge', [
            'response_length' => strlen($fullResponse),
            'response_preview' => mb_substr($fullResponse, 0, 500),
            'source' => 'general_knowledge'
        ]);
    }
    
    // Stream response word-by-word for better UX
    return response()->stream(function () use ($fullResponse, $request, $hasKnowledgeBase) {
        Log::info('[Expert Mode] Starting word-by-word stream');
        $platform = $request->input('platform', 'act');
        
        try {
            // Stream word-by-word for natural typing effect
            $words = preg_split('/(\s+)/u', $fullResponse, -1, PREG_SPLIT_DELIM_CAPTURE);
            
            foreach ($words as $word) {
                if ($word !== '') {
                    echo "data: " . json_encode(['text' => $word]) . "\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                    usleep(30000); // Small delay for natural streaming effect
                }
            }
            
            Log::info('[Expert Mode] Stream complete', ['length' => strlen($fullResponse)]);
            
            // Store assistant response in memory
            try {
                $this->memoryService->appendMessage(
                    $request->user()->id,
                    $platform,
                    'assistant',
                    $fullResponse
                );
                
                // Store in MongoDB conversation only if conversation_id is provided
                $requestedConversationId = $request->input('conversation_id');
                if ($requestedConversationId) {
                    try {
                        $mongoConversation = $this->conversationStorage->getOrCreateConversation(
                            $request->user()->id,
                            $requestedConversationId,
                            $platform
                        );
                        $this->conversationStorage->addAssistantMessage(
                            $mongoConversation,
                            $fullResponse,
                            0
                        );
                    } catch (\Throwable $e) {
                        Log::warning('[Expert Mode] MongoDB conversation storage failed', ['error' => $e->getMessage()]);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('[Expert Mode] Failed to store assistant response', ['error' => $e->getMessage()]);
            }
            
            // Send completion signal
            $completionData = ['done' => true];
            if ($hasKnowledgeBase) {
                $completionData['source'] = 'knowledge_base';
            } else {
                $completionData['source'] = 'general_knowledge';
            }
            echo "data: " . json_encode($completionData) . "\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        } catch (\Throwable $e) {
            Log::error('[Expert Mode] Stream error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            echo "data: " . json_encode(['error' => 'An error occurred while streaming the response.']) . "\n\n";
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