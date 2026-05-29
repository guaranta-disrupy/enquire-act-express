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
use App\Models\Questionnaire;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Prism;
use App\AI\Enums\AIModel;

class AIStreamController extends Controller
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

    /**
     * Send message and get streaming response
     * 
     * Main streaming endpoint that handles AI chat messages with real-time response streaming.
     * Routes to appropriate streaming handler based on detected intent:
     * - Conversation: General chat responses (handleConversationStream)
     * - Guide: Help/guide responses with context (handleGuideStream)
     * - Actions: Returns JSON for user confirmation (handleActionStream)
     * 
     * @param Request $request Contains user message, platform, and context information
     * @return StreamedResponse|JsonResponse SSE stream for conversation/guide, JSON for actions
     */
    public function sendMessageStream(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'user_message_display' => 'nullable|string|max:500',
        ]);

        $messageForStorage = $request->filled('user_message_display') ? $request->user_message_display : $request->message;
        $request->merge(['_message_for_storage' => $messageForStorage]);

        if ($this->isBulkCreationOveruse($request->message)) {
            $this->markUserMessageUnanswered($request);
            return $this->streamSimpleMessage("It looks like you're asking to create a large number of items at once. For safety and to avoid mistakes, please limit your request to a smaller batch. Would you like me to proceed with a smaller number or a single item?");
        }

        if ($request->input('research_mode', false)) {
            Log::info('[ChatController] Research mode detected - routing to AI helpbot', [
                'research_mode' => true,
                'message_preview' => mb_substr($request->message, 0, 100)
            ]);
            $platform = 'express';
            try {
                $user = $request->user();
                if ($user) {
                    $mongoConversation = $this->conversationStorage->getOrCreateConversation(
                        $user->id,
                        $request->input('conversation_id'),
                        $platform
                    );
                    $this->conversationStorage->addUserMessage(
                        $mongoConversation,
                        $request->input('_message_for_storage', $request->message),
                        $request->input('websitePosition', 'dashboard'),
                        ['intent' => 'helpbot', 'scope' => 'helpbot']
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('[ChatController] Failed to store user message for research→helpbot route', ['error' => $e->getMessage()]);
            }
            return response()->json([
                'route_to_helpbot' => true,
                'intent' => 'conversation',
                'scope' => 'conversation',
            ], 200);
        }

        $platform = 'express';
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
                            $mongoConversation = $this->conversationStorage->getOrCreateConversation($user->id, $request->input('conversation_id'), $platform);
                            $this->conversationStorage->addUserMessage(
                                $mongoConversation,
                                $request->message,
                                $request->input('websitePosition', 'dashboard'),
                                ['intent' => 'conversation', 'scope' => 'conversation']
                            );
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
                        $mongoConversation = $this->conversationStorage->getOrCreateConversation($user->id, $request->input('conversation_id'));
                        $this->conversationStorage->addUserMessage(
                            $mongoConversation,
                            $request->message,
                            $request->input('websitePosition', 'dashboard'),
                            ['intent' => 'conversation', 'scope' => 'conversation']
                        );
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
                    'user_message' => $request->message,
                    'topic' => $pendingQuestion['topic'] ?? null
                ]);
                $structuredResponse['scope'] = $pendingQuestion['scope'];
                $structuredResponse['intent'] = $pendingQuestion['intent'];
                $structuredResponse['needs_name_generation'] = true;
                $structuredResponse['questionnaire_code'] = $request->input('questionnaire_code');
                // If topic was extracted from previous context, use it
                if (!empty($pendingQuestion['topic'])) {
                    $structuredResponse['topic'] = $pendingQuestion['topic'];
                }
            } else {
                $embedding = $this->chatService->getEmbedding($request->message);
                $lastAssistantMessage = null;
                try {
                    $user = $request->user();
                    if ($user) {
                        $memoryContext = $this->memoryService->getMemoryContext($user->id, 'express');
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
                $simpleGreeting = $this->detectSimpleGreetings($request->message);
                if ($simpleGreeting) {
                    $structuredResponse = $simpleGreeting;
                    $structuredResponse['is_quick_greeting'] = true;
                } else {
                    $structuredResponse = $this->detectIntentAndScope($request->message, $lastAssistantMessage, $embedding);
                }
                $structuredResponse['questionnaire_code'] = $request->input('questionnaire_code');
            }
        } catch (\Throwable $e) {
            Log::warning('Detection failed', ['error' => $e->getMessage()]);
            $structuredResponse = ['scope' => null, 'intent' => null];
        }

        if (empty($structuredResponse['scope']) && !empty($structuredResponse['intent'])) {
            if (in_array($structuredResponse['intent'], ['conversation', 'guide'])) {
                $structuredResponse['scope'] = $structuredResponse['intent'];
            }
        }

        $scope = $structuredResponse['scope'] ?? null;
        $intent = $structuredResponse['intent'] ?? null;
        Log::info('[AIStreamController] 3 intent detection', ['scope' => $scope, 'intent' => $intent]);
        if (!isset($structuredResponse['needs_name_generation'])) {
            $pendingQuestion = $this->checkForPendingQuestionCreation($request);
            if ($pendingQuestion) {
                $structuredResponse['scope'] = $pendingQuestion['scope'];
                $structuredResponse['intent'] = $pendingQuestion['intent'];
                $structuredResponse['needs_name_generation'] = true; // Flag to indicate we should proceed with creation
                // If topic was extracted from previous context, use it
                if (!empty($pendingQuestion['topic'])) {
                    $structuredResponse['topic'] = $pendingQuestion['topic'];
                }
            }
        }

        if (empty($structuredResponse['scope']) || empty($structuredResponse['intent'])) {
            Log::warning('[AIStreamController] missing scope or intent after detection');
            // Store user message first
            try {
                $user = $request->user();
                if ($user) {
                    $mongoConversation = $this->conversationStorage->getOrCreateConversation($user->id, $request->input('conversation_id'));
                    $this->conversationStorage->addUserMessage(
                        $mongoConversation,
                        $request->input('_message_for_storage', $request->message),
                        $request->input('websitePosition', 'dashboard'),
                        ['intent' => 'conversation', 'scope' => 'conversation']
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to store user message', ['error' => $e->getMessage()]);
            }
            $this->markUserMessageUnanswered($request);
            $errorMessage = 'I\'m sorry, I\'m not quite sure what you\'re asking for! Could you please rephrase your request?';
            return $this->streamSimpleMessage($errorMessage);
        }

        // Platform handles simple greetings and quick responses (e.g. thanks, ok) – do not send to helpbot
        $isQuickGreeting = !empty($structuredResponse['is_quick_greeting']);
        // Only treat as quick response if there's no valid actionable scope/intent detected
        // This prevents overriding correct intent detection (e.g., "thanks, how do I...") with QUICK_RESPONSE
        $hasActionableIntent = !empty($scope) && !empty($intent) && 
            !in_array($scope, ['conversation', null]) && 
            !in_array($intent, ['conversation', null]);
        if ($this->isQuickResponse($request->message) && !$hasActionableIntent) {
            Log::info('[AIStreamController] 4 intent detected as QUICK_RESPONSE');
            $userInfo = $this->getUserTypeInfo($request);
            $responseText = $this->getQuickResponse($request->message, $userInfo);
            $this->persistPlatformQuickResponse($request, $responseText, $platform);
            return $this->streamSimpleMessage($responseText);
        }

        $isExpressAction = $this->isExpressHandledAction($structuredResponse);
        $routeToHelpbot = !$isQuickGreeting && !$isExpressAction;
        if ($routeToHelpbot) {
            // Guidance-seeking questions (e.g. "How do I move a folder?") are guide intent (asking how), not action—
            // so the helpbot gives instructions instead of "use the fluent API". All action intents are overridden to guide.
            $actionIntents = ['create', 'update', 'delete', 'move', 'duplicate'];
            $isActionIntent = in_array($intent, $actionIntents, true);
            $isGuidance = $this->isGuidanceSeekingPrompt($request->message);
            $helpbotIntent = ($isActionIntent && $isGuidance) ? 'guide' : ($intent ?? 'conversation');
            $helpbotScope = ($isActionIntent && $isGuidance) ? 'guide' : ($scope ?? 'conversation');

            $intentLabel = strtoupper((string) ($helpbotIntent ?? $helpbotScope ?? 'conversation'));
            Log::info('[AIStreamController] 4 intent detected as ' . $intentLabel . ($isGuidance && $isActionIntent ? ' (guidance-seeking, using guide)' : ''));
            Log::info('[AIStreamController] 5 passing to ai helpbot', [
                'prompt' => mb_substr($request->message, 0, 200),
                'websitePosition' => $request->input('websitePosition', 'dashboard'),
                'platform' => 'express',
                'intent' => $helpbotIntent,
            ]);
            $conversationIdForClient = null;
            try {
                $user = $request->user();
                if ($user) {
                    $mongoConversation = $this->conversationStorage->getOrCreateConversation(
                        $user->id,
                        $request->input('conversation_id'),
                        $platform
                    );
                    $this->conversationStorage->addUserMessage(
                        $mongoConversation,
                        $request->input('_message_for_storage', $request->message),
                        $request->input('websitePosition', 'dashboard'),
                        ['intent' => 'helpbot', 'scope' => 'helpbot']
                    );
                    $conversationIdForClient = $mongoConversation->conversation_id ?? null;
                }
            } catch (\Throwable $e) {
                Log::warning('[ChatController] Failed to store user message for helpbot route', ['error' => $e->getMessage()]);
            }
            $payload = [
                'route_to_helpbot' => true,
                'intent' => $helpbotIntent,
                'scope' => $helpbotScope,
            ];
            if ($conversationIdForClient) {
                $payload['conversation_id'] = $conversationIdForClient;
            }
            return response()->json($payload, 200);
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
            return $this->streamSimpleMessage('I\'m sorry, I encountered an error while processing your request. Please try again or rephrase your question about EXPRESS.');
        }
        try {
            $user = $request->user();
            $isRetry = $request->boolean('is_retry');
            if ($user && !$isRetry) {
                $this->memoryService->appendMessage(
                    $user->id,
                    $platform,
                    'user',
                    $request->message
                );
                
                // Store in MongoDB
                try {
                    $mongoConversation = $this->conversationStorage->getOrCreateConversation(
                        $user->id,
                        $request->input('conversation_id')
                    );
                    $this->conversationStorage->addUserMessage(
                        $mongoConversation,
                        $request->input('_message_for_storage', $request->message),
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
            } elseif ($user && $isRetry) {
                // Retry: still need mongo conversation for survey generation
                try {
                    $mongoConversation = $this->conversationStorage->getOrCreateConversation(
                        $user->id,
                        $request->input('conversation_id')
                    );
                    $request->merge(['_mongo_conversation' => $mongoConversation]);
                } catch (\Throwable $e) {
                    Log::warning('MongoDB conversation lookup failed (retry)', ['error' => $e->getMessage()]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Conversation memory pre-append failed', ['error' => $e->getMessage()]);
        }

        if ($embedding) {
            $request->merge(['_embedding' => $embedding]);
        }

        $detectedIntent = strtoupper((string) ($this->promptService->intent ?? $this->promptService->scope ?? 'UNKNOWN'));
        Log::info('[AIStreamController] 4 intent detected as ' . $detectedIntent);

        if ($this->promptService->scope === 'question' && $this->promptService->intent === 'duplicate') {
            Log::info('[AIStreamController] 5A executing action (duplicate question)');
            return $this->handleDuplicateQuestionStream($request, $structuredResponse);
        } elseif ($this->promptService->scope === 'question' && $this->promptService->intent === 'move') {
            Log::info('[AIStreamController] 5A executing action (move question)');
            return $this->handleMoveQuestionStream($request, $structuredResponse);
        } elseif ($this->promptService->scope === 'survey' && $this->promptService->intent === 'create') {
            Log::info('[AIStreamController] 5A executing action (survey create)');
            $chatController = app(\App\Http\Controllers\Api\ChatController::class);
            return $chatController->handleSurveyGenerationStream($request);
        } elseif ($this->promptService->intent === 'create') {
            Log::info('[AIStreamController] 5A executing action');
            return $this->handleActionStream($request, $structuredResponse);
        } elseif ($this->promptService->isForGuide() || $this->promptService->isForConversation() || $this->promptService->intent === 'conversation') {
            Log::info('[AIStreamController] 5 passing to ai helpbot', [
                'prompt' => mb_substr($request->message, 0, 200),
                'websitePosition' => $request->input('websitePosition', 'dashboard'),
                'platform' => 'express',
                'intent' => $this->promptService->intent ?? 'conversation',
            ]);
            return $this->routeToHelpbotStream($request, $structuredResponse);
        } else {
            if (in_array($this->promptService->intent, ['create', 'update', 'delete'])) {
                Log::info('[AIStreamController] 5A executing action');
                return $this->handleActionStream($request, $structuredResponse);
            }
            Log::info('[AIStreamController] 5 passing to ai helpbot', [
                'prompt' => mb_substr($request->message, 0, 200),
                'websitePosition' => $request->input('websitePosition', 'dashboard'),
                'platform' => 'express',
                'intent' => $this->promptService->intent ?? 'conversation',
            ]);
            return $this->routeToHelpbotStream($request, $structuredResponse);
        }
    }

    /**
     * Return route_to_helpbot JSON so the frontend streams from the helpbot proxy.
     */
    private function routeToHelpbotStream(Request $request, array $structuredResponse): JsonResponse
    {
        $platform = 'express';
        try {
            $user = $request->user();
            if ($user) {
                $mongoConversation = $this->conversationStorage->getOrCreateConversation(
                    $user->id,
                    $request->input('conversation_id'),
                    $platform
                );
                $this->conversationStorage->addUserMessage(
                    $mongoConversation,
                    $request->message,
                    $request->input('websitePosition', 'dashboard'),
                    ['intent' => 'helpbot', 'scope' => 'helpbot']
                );
            }
        } catch (\Throwable $e) {
            Log::warning('[ChatController] Failed to store user message for helpbot route', ['error' => $e->getMessage()]);
        }
        $intent = $structuredResponse['intent'] ?? 'conversation';
        $scope = $structuredResponse['scope'] ?? null;
        return response()->json([
            'route_to_helpbot' => true,
            'intent' => $intent,
            'scope' => $scope,
        ], 200);
    }

    /**
     * True if the message is asking for guidance (e.g. "how can I remove...") rather than
     * requesting an action. Such prompts should be answered with instructions, not "use the fluent API".
     */
    private function isGuidanceSeekingPrompt(string $message): bool
    {
        $lower = mb_strtolower($message);
        $patterns = [
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
        ];
        foreach ($patterns as $pattern) {
            if (str_starts_with($lower, $pattern) || str_contains($lower, ' ' . $pattern)) {
                return true;
            }
        }
        return false;
    }

    /**
     * True if Express should handle this request (actions and greetings only). Otherwise helpbot handles it.
     */
    private function isExpressHandledAction(array $structuredResponse): bool
    {
        if (!empty($structuredResponse['needs_name_generation'])) {
            return true; // Pending question creation
        }
        $scope = $structuredResponse['scope'] ?? null;
        $intent = $structuredResponse['intent'] ?? null;
        $actionIntents = ['create', 'update', 'delete', 'copy', 'move', 'navigate', 'list', 'manage', 'duplicate'];
        
        // All question type scopes (from Scope enum)
        $questionTypeScopes = [
            'question',
            'descriptive',
            'video_player',
            'text_selection',
            'image_selection',
            'single_textbox',
            'multiple_textbox',
            'open_ended',
            'pie_chart',
            'bar_chart',
            'slider',
            'ranking',
            'star_rating',
            'button_rating',
            'segmentation',
            'matrix',
            'pairwise_comparison',
            'best-worst_scale',
        ];
        
        // Check if scope is a question type and intent is an action
        if (in_array($scope, $questionTypeScopes, true) && in_array($intent, $actionIntents, true)) {
            return true;
        }
        
        // Survey creation
        if ($scope === 'survey' && $intent === 'create') {
            return true;
        }
        
        // Question choice/options updates
        if (in_array($scope, ['question_choice', 'question_options'], true) && in_array($intent, ['update', 'delete'], true)) {
            return true;
        }
        
        return false;
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
        
        if ($this->isQuickResponse($request->message)) {
            $responseText = $this->getQuickResponse($request->message, $userInfo);
            $this->persistPlatformQuickResponse($request, $responseText, $this->promptService->platform ?? 'express');
            return $this->streamSimpleMessage($responseText);
        }

        $user = $request->user();
        
        $memoryContextCacheKey = 'memory_context_' . $user->id . '_' . $this->promptService->platform;
        $memoryContext = \Illuminate\Support\Facades\Cache::remember($memoryContextCacheKey, now()->addMinutes(5), function() use ($user) {
            return $this->memoryService->renderContextString(
                $user->id,
                $this->promptService->platform
            );
        });

        $basePrompt = config('ai_prompts.base_prompts.conversation_stream', 'You are a helpful and expressive AI assistant for EXPRESS, a survey platform. Respond naturally in a friendly way. If asked about non-EXPRESS topics, politely redirect to EXPRESS features. Keep responses concise.');
        $systemPrompt = $this->buildSystemPrompt($basePrompt, $userInfo['type'], $userInfo['firstName'], $memoryContext);

        if ($this->promptService->intent !== 'conversation') {
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
                Log::warning('Neo4j context retrieval failed in conversation stream', ['error' => $e->getMessage()]);
            }
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
                        try {
                            $mongoConversationForSummary = $this->conversationStorage->getOrCreateConversation(
                                $request->user()->id,
                                $request->input('conversation_id')
                            );
                        } catch (\Throwable $e) {
                            Log::warning('Failed to get conversation for summary', ['error' => $e->getMessage()]);
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

    private function handleExpertModeStream(Request $request)
    {
        $userInfo = $this->getUserTypeInfo($request);
        $user = $request->user();
        
        Log::info('[Research Mode] Using expert knowledge base (Category, Question, Answer, Component, Tools nodes)');
        
        try {
            if ($user) {
                $this->memoryService->appendMessage(
                    $user->id,
                    'express',
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
                        $request->input('_message_for_storage', $request->message),
                        $request->input('websitePosition', 'dashboard'),
                        ['intent' => 'research', 'scope' => 'research']
                    );
                    $request->merge(['_mongo_conversation' => $mongoConversation]);
                } catch (\Throwable $e) {
                    Log::warning('[Research Mode] MongoDB conversation storage failed', ['error' => $e->getMessage()]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[Research Mode] Conversation memory pre-append failed', ['error' => $e->getMessage()]);
        }
        
        $basePrompt = config('ai_prompts.base_prompts.research_mode', 'You are a research expert specializing in survey methodology, research design, and data collection best practices. Provide expert guidance with a scholarly, analytical tone. You cannot perform actions within the platform - you provide research expertise and recommendations only.');
        $systemPrompt = $this->buildSystemPrompt($basePrompt, $userInfo['type'], $userInfo['firstName']);
        $expertContext = ['context' => '', 'relatedNodes' => []];

        try {
            $embedding = $request->input('_embedding') ?? $this->chatService->getEmbedding($request->message);
            
            Log::info('[Research Mode] Retrieving context from expert Neo4j knowledge base', [
                'embedding_size' => count($embedding),
                'message_preview' => mb_substr($request->message, 0, 100)
            ]);
            
            $expertContext = $this->neo4jService->getExpertContext($embedding);
            
            Log::info('[Research Mode] Expert context retrieved', [
                'has_context' => !empty($expertContext['context']),
                'context_length' => strlen($expertContext['context'] ?? ''),
                'related_nodes_count' => count($expertContext['relatedNodes'] ?? []),
                'related_nodes_details' => $expertContext['relatedNodes'] ?? [],
                'node ids' => $expertContext['nodeIds'] ?? []
            ]);
            
            if (!empty($expertContext['context'])) {
                $systemPrompt .= "\n\n=== RESEARCH KNOWLEDGE BASE (MANDATORY SOURCE - USE ONLY THIS CONTENT) ===\n";
                $systemPrompt .= "The following is the ONLY authorized content you may use for your response. You MUST base your answer EXCLUSIVELY on this content. Do not add generic advice, factors, or information not found here.\n\n";
                $systemPrompt .= $expertContext['context'];
                $systemPrompt .= "\n\n=== END OF RESEARCH KNOWLEDGE BASE ===\n";
                $systemPrompt .= "\n\nSTRICT RAG REQUIREMENTS - YOU MUST FOLLOW THESE RULES:\n";
                $systemPrompt .= "1. Your response MUST be derived EXCLUSIVELY from the knowledge base content above - no exceptions\n";
                $systemPrompt .= "2. If the knowledge base discusses qualitative vs quantitative vs mixed methods, your answer MUST follow that structure and explain those concepts exactly as presented\n";
                $systemPrompt .= "3. Include ALL the specific examples, explanations, and points from the knowledge base that are relevant to the question\n";
                $systemPrompt .= "4. Do NOT invent or add generic factors like 'research objectives', 'target audience', 'resources' unless they are explicitly mentioned in the knowledge base\n";
                $systemPrompt .= "5. Structure your response to mirror the knowledge base content organization - if it discusses qualitative first, then quantitative, then mixed methods, follow that order\n";
                $systemPrompt .= "6. Paraphrase naturally but preserve the exact meaning, examples, and explanations from the knowledge base\n";
                $systemPrompt .= "7. Your response should be an explanation of what the knowledge base says, not your own independent advice\n";
                $systemPrompt .= "\nVIOLATION WARNING: If you provide generic advice not found in the knowledge base (like 'consider research objectives' or 'evaluate resources'), you are NOT following instructions correctly.";
                Log::info('[Research Mode] Expert knowledge base context added to system prompt', [
                    'context_preview' => mb_substr($expertContext['context'], 0, 200),
                    'full_context_length' => strlen($expertContext['context'])
                ]);
                Log::info('[Research Mode] Full knowledge base context being used', [
                    'context' => $expertContext['context']
                ]);
            } else {
                Log::warning('[Research Mode] No expert context found in knowledge base');
            }
        } catch (\Throwable $e) {
            Log::error('[Research Mode] Context retrieval failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }

        $userPrompt = $request->message;
        if (!empty($expertContext['context'])) {
            $userPrompt = "Using ONLY the Research Knowledge Base content provided above, answer the following question. Your answer must be derived exclusively from the knowledge base content - do not add information not found in the knowledge base:\n\n" . $request->message;
            Log::info('[Research Mode] User prompt enhanced with strict RAG instructions');
        }
        
        Log::info('[Research Mode] Sending request to AI model', [
            'system_prompt_length' => strlen($systemPrompt),
            'user_prompt_length' => strlen($userPrompt),
            'temperature' => 0.1,
            'has_knowledge_base' => !empty($expertContext['context'])
        ]);
        
        $response = Prism::text()
            ->using(...AIModel::getDefaultConfig())
            ->withSystemPrompt($systemPrompt)
            ->withPrompt($userPrompt)
            ->usingTemperature(0.1)
            ->asText();
            
        $fullResponse = $response->text;
        
        Log::info('[Research Mode] AI response received', [
            'response_length' => strlen($fullResponse),
            'response_preview' => mb_substr($fullResponse, 0, 200),
            'response_end' => mb_substr($fullResponse, -200),
            'is_complete' => !empty($fullResponse) && (mb_substr($fullResponse, -1) !== '.' || mb_strlen($fullResponse) > 100)
        ]);

        return response()->stream(function () use ($fullResponse, $request) {
            Log::info('[ChatController] Research mode stream callback started', ['response_length' => strlen($fullResponse)]);
            $messageId = null;
            $mongoConversation = null;
            
            try {
                set_time_limit(300);
                
                $chunks = str_split($fullResponse, 10);
                $chunkCount = count($chunks);
                
                foreach ($chunks as $index => $chunk) {
                    if ($chunk !== '') {
                        echo "data: " . json_encode(['text' => $chunk]) . "\n\n";
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                        
                        if ($index % 10 === 0) {
                            usleep(10000);
                        }
                    }
                }
                
                Log::info('[ChatController] Research mode stream complete', [
                    'length' => strlen($fullResponse),
                    'chunks_sent' => $chunkCount
                ]);
                try {
                    $this->memoryService->appendMessage(
                        $request->user()->id,
                        'express',
                        'assistant',
                        $fullResponse
                    );
                    $mongoConversation = $request->input('_mongo_conversation');
                    $mongoConversationForSummary = $mongoConversation;
                    if (!$mongoConversationForSummary) {
                        try {
                            $mongoConversationForSummary = $this->conversationStorage->getOrCreateConversation(
                                $request->user()->id,
                                $request->input('conversation_id')
                            );
                        } catch (\Throwable $e) {
                            Log::warning('Failed to get conversation for summary', ['error' => $e->getMessage()]);
                        }
                    }
                    $this->memoryService->summarizeIfNeeded($request->user()->id, 'express', null, $mongoConversationForSummary);
                    
                    if ($mongoConversation) {
                        try {
                            $result = $this->conversationStorage->addAssistantMessage(
                                $mongoConversation,
                                $fullResponse,
                                0
                            );
                            $messageId = $result['message_id'] ?? null;
                        } catch (\Throwable $e) {
                            Log::warning('MongoDB conversation storage failed (research mode streaming assistant message)', ['error' => $e->getMessage()]);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to store research mode streamed conversation response', ['error' => $e->getMessage()]);
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
                
                Log::info('[ChatController] Research mode completion signal sent', [
                    'has_conversation_id' => !empty($completionData['conversation_id']),
                    'has_message_id' => !empty($completionData['message_id'])
                ]);
            } catch (\Throwable $e) {
                Log::error('[ChatController] Research mode stream error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
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
        $userInfo = $this->getUserTypeInfo($request);
        
        if ($this->isQuickResponse($request->message)) {
            $responseText = $this->getQuickResponse($request->message, $userInfo);
            $this->persistPlatformQuickResponse($request, $responseText, $this->promptService->platform ?? 'express');
            return $this->streamSimpleMessage($responseText);
        }

        if (!$this->chatService->isQuestion($request->message)) {
            $this->markUserMessageUnanswered($request);
            return $this->streamSimpleMessage('I\'m not sure how to help with that request. Could you please rephrase it as a question?');
        }

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
        
        $basePrompt = config('ai_prompts.base_prompts.guide', 'You are a helpful guide for EXPRESS, a survey platform. Use HTML tags for formatting only (no markdown).');
        
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
                        try {
                            $mongoConversationForSummary = $this->conversationStorage->getOrCreateConversation(
                                $request->user()->id,
                                $request->input('conversation_id')
                            );
                        } catch (\Throwable $e) {
                            Log::warning('Failed to get conversation for summary', ['error' => $e->getMessage()]);
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
   
        if ($user && $this->promptService->intent === 'create' && $this->isConfirmationResponse($request->message)) {
            $confirmationResult = $this->handleConfirmation($request, $user);
            if ($confirmationResult) {
                $data = $confirmationResult->getData(true);
                if (isset($data['awaiting_details']) && $data['awaiting_details']) {
                    return $this->streamSimpleMessage($data['message'] ?? 'Could you please tell me what the question should be about?');
                }
                return $confirmationResult;
            }
        }
        
        // Handle question choice update (add/edit/remove)
        if ($this->promptService->scope === 'question_choice' && in_array($this->promptService->intent, ['update', 'delete'])) {
            $details = $structuredResponse['details'] ?? [];
            $questionNumber = $details['question_number'] ?? null;
            $action = $details['action'] ?? 'add';
            $choiceText = $details['choice_text'] ?? null;
            $oldChoiceText = $details['old_choice_text'] ?? null;

            if (!$questionNumber || !$choiceText) {
                return $this->streamSimpleMessage('I couldn\'t understand which question or choice you want to update. Please try again with a format like "add this choice in Q1 \'Robotics\'" or "edit choice in Q1 \'Option 1\' to \'New Option\'".');
            }

            $questionnaireCode = $request->input('questionnaire_code');
            if (!$questionnaireCode) {
                return $this->streamSimpleMessage('Questionnaire context is required. Please make sure you\'re on a questionnaire page.');
            }

            $questionnaire = \App\Models\Questionnaire::where('code', $questionnaireCode)->first();
            if (!$questionnaire) {
                return $this->streamSimpleMessage('Questionnaire not found.');
            }

            $variableStart = $questionnaire->setup['variable_start'] ?? 1;
            $order = $questionNumber - $variableStart + 1;
            $question = \App\Models\Question::where('questionnaire_id', $questionnaire->id)
                ->where('order', $order)
                ->first();

            if (!$question) {
                return $this->streamSimpleMessage("Question Q{$questionNumber} not found.");
            }

            // Validate question type supports choices
            $choiceSupportedTypes = [2, 3, 16, 20];
            if (!in_array($question->question_type_id, $choiceSupportedTypes)) {
                return $this->streamSimpleMessage("This question type does not support choices.");
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

            return response()->stream(function () use ($confirmationMessage, $actionData, $request) {
                try {
                    $this->memoryService->appendMessage(
                        $request->user()->id,
                        $this->promptService->platform,
                        'assistant',
                        $confirmationMessage
                    );
                    
                    $mongoConversation = $request->input('_mongo_conversation');
                    $messageId = null;
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
                            $messageId = $result['message_id'] ?? null;
                        } catch (\Throwable $e) {
                            Log::warning('MongoDB conversation storage failed (question choice stream)', ['error' => $e->getMessage()]);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to store question choice stream response', ['error' => $e->getMessage()]);
                }
                
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
            }, 200, [
                'Cache-Control' => 'no-cache',
                'Content-Type' => 'text/event-stream',
                'X-Accel-Buffering' => 'no',
            ]);
        }
        
        // Handle question options update
        if ($this->promptService->scope === 'question_options' && $this->promptService->intent === 'update') {
            $details = $structuredResponse['details'] ?? [];
            $questionNumber = $details['question_number'] ?? null;
            $optionType = $details['option_type'] ?? null;
            $optionValue = $details['option_value'] ?? true;
            $multiSelectType = $details['multi_select_type'] ?? null;

            if (!$questionNumber || !$optionType) {
                return $this->streamSimpleMessage('I couldn\'t understand which question or option you want to update. Please try again with a format like "make Q1 required" or "make question 2 randomise choices".');
            }

            $questionnaireCode = $request->input('questionnaire_code');
            if (!$questionnaireCode) {
                return $this->streamSimpleMessage('Questionnaire context is required. Please make sure you\'re on a questionnaire page.');
            }

            // Get question to validate it exists and get current values
            $questionnaire = \App\Models\Questionnaire::where('code', $questionnaireCode)->first();
            if (!$questionnaire) {
                return $this->streamSimpleMessage('Questionnaire not found.');
            }

            $variableStart = $questionnaire->setup['variable_start'] ?? 1;
            $order = $questionNumber - $variableStart + 1;
            $question = \App\Models\Question::where('questionnaire_id', $questionnaire->id)
                ->where('order', $order)
                ->first();

            if (!$question) {
                return $this->streamSimpleMessage("Question Q{$questionNumber} not found.");
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
                return $this->streamSimpleMessage("The option '{$optionDisplay}' is not applicable for this question type.");
            }

            // Generate confirmation message
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

            return response()->stream(function () use ($confirmationMessage, $actionData, $request) {
                try {
                    $this->memoryService->appendMessage(
                        $request->user()->id,
                        $this->promptService->platform,
                        'assistant',
                        $confirmationMessage
                    );
                    
                    $mongoConversation = $request->input('_mongo_conversation');
                    $messageId = null;
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
                            $messageId = $result['message_id'] ?? null;
                        } catch (\Throwable $e) {
                            Log::warning('MongoDB conversation storage failed (question options stream)', ['error' => $e->getMessage()]);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to store question options stream response', ['error' => $e->getMessage()]);
                }
                
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
            }, 200, [
                'Cache-Control' => 'no-cache',
                'Content-Type' => 'text/event-stream',
                'X-Accel-Buffering' => 'no',
            ]);
        }
        
        if ($this->promptService->scope === 'question' && $this->promptService->intent === 'create') {
            $platformIncongruency = $this->handlePlatformIncongruence($request);
            if ($platformIncongruency) {
                $this->markUserMessageUnanswered($request);
                $data = $platformIncongruency->getData(true);
                $responseMessage = $data['message'] ?? 'Platform mismatch detected.';
                // Store the AI's response in the conversation buffer so follow-up messages can find context
                $this->memoryService->appendMessage($request->user()->id, $this->promptService->platform, 'assistant', $responseMessage);
                return $this->streamSimpleMessage($responseMessage);
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
        if (isset($structuredResponse['needs_name_generation']) && $structuredResponse['needs_name_generation']) {
            // Use topic from context if available (when user just replied with question type)
            $userDetails = !empty($structuredResponse['topic']) ? $structuredResponse['topic'] : trim($request->message);
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
            // Use topic from context if available (when user just replied with question type)
            $userDetails = !empty($structuredResponse['topic']) ? $structuredResponse['topic'] : trim($request->message);
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

        $maxRetries = 2;
        $attempt = 0;
        $response = null;
        $lastException = null;

        while ($attempt <= $maxRetries) {
            try {
                $response = $query->asStructured();
                break;
            } catch (ConnectionException $e) {
                $lastException = $e;
                $errorMessage = $e->getMessage();
                if (strpos($errorMessage, 'timed out') !== false || strpos($errorMessage, 'timeout') !== false || strpos($errorMessage, 'Timeout') !== false) {
                    $attempt++;
                    if ($attempt > $maxRetries) {
                        $this->markUserMessageUnanswered($request);
                        return $this->streamSimpleMessage('I encountered a connection timeout while processing your request. Please try sending your message again.');
                    }
                    usleep(500000);
                    continue;
                }
                throw $e;
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                if (strpos($errorMessage, 'timed out') !== false || strpos($errorMessage, 'timeout') !== false || strpos($errorMessage, 'Timeout') !== false) {
                    $lastException = $e;
                    $attempt++;
                    if ($attempt > $maxRetries) {
                        $this->markUserMessageUnanswered($request);
                        return $this->streamSimpleMessage('I encountered a connection timeout while processing your request. Please try sending your message again.');
                    }
                    usleep(500000);
                    continue;
                }
                throw $e;
            }
        }

        if ($response === null) {
            $this->markUserMessageUnanswered($request);
            return $this->streamSimpleMessage('I encountered a connection timeout while processing your request. Please try sending your message again.');
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

        return response()->stream(function () use ($confirmationMessage, $actionData, $request) {
            try {

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
                        try {
                            $mongoConversationForSummary = $this->conversationStorage->getOrCreateConversation(
                                $request->user()->id,
                                $request->input('conversation_id')
                            );
                        } catch (\Throwable $e) {
                            Log::warning('Failed to get conversation for summary', ['error' => $e->getMessage()]);
                        }
                    }
                    $this->memoryService->summarizeIfNeeded($request->user()->id, $this->promptService->platform, null, $mongoConversationForSummary);
                    
                    $messageId = null;
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

                Log::info('[AIStreamController] 6A action executed and saved', [
                    'action' => $actionData['action'] ?? null,
                    'scope' => $actionData['scope'] ?? null,
                    'intent' => $actionData['intent'] ?? null,
                ]);
            } catch (\Throwable $e) {
                Log::warning('[AIStreamController] action stream error', ['error' => $e->getMessage()]);
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
     * Persist platform-handled quick response (user message + assistant reply) to MongoDB.
     */
    private function persistPlatformQuickResponse(Request $request, string $assistantContent, string $platform = 'express'): void
    {
        try {
            $user = $request->user();
            if (!$user) {
                return;
            }
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
            Log::warning('[AIStreamController] Failed to persist platform quick response', ['error' => $e->getMessage()]);
        }
    }

    /**
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

    private function handleDuplicateQuestionStream(Request $request, array $structuredResponse = [])
    {
        try {
            $questionnaireCode = $request->input('questionnaire_code');
            if (!$questionnaireCode) {
                return $this->streamSimpleMessage('Please provide a questionnaire code to duplicate questions.');
            }

            $questionnaire = Questionnaire::where('code', $questionnaireCode)->first();
            if (!$questionnaire) {
                return $this->streamSimpleMessage('Questionnaire not found.');
            }

            $message = 'Select the question you want to duplicate:';
            
            return response()->stream(function () use ($message, $questionnaireCode, $request) {
                try {
                    $mongoConversation = $request->input('_mongo_conversation');
                    
                    $actionData = [
                        'action' => 'express_question_duplicate',
                        'scope' => 'question',
                        'intent' => 'duplicate',
                        'message' => $message,
                        'questionnaire_code' => $questionnaireCode,
                        'show_question_selection' => true,
                        'loading' => false
                    ];

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
                    Log::error('[ChatController] Duplicate question stream error', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
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
            Log::error('[ChatController] Failed to handle duplicate question stream', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->streamSimpleMessage('I encountered an error while processing your duplicate request. Please try again.');
        }
    }

    private function handleMoveQuestionStream(Request $request, array $structuredResponse = [])
    {
        try {
            $questionnaireCode = $request->input('questionnaire_code');
            if (!$questionnaireCode) {
                return $this->streamSimpleMessage('Please provide a questionnaire code to move questions.');
            }

            $questionnaire = Questionnaire::where('code', $questionnaireCode)->first();
            if (!$questionnaire) {
                return $this->streamSimpleMessage('Questionnaire not found.');
            }

            // Check if source and target questions are already parsed from the message
            $sourceQuestion = $structuredResponse['source_question'] ?? null;
            $targetQuestion = $structuredResponse['target_question'] ?? null;
            $message = $structuredResponse['details'] ?? $request->message;

            // Try to parse question numbers from message if not already parsed
            if (!$sourceQuestion || !$targetQuestion) {
                $patterns = [
                    '/move\s+(?:q|question)\s*(\d+)\s+to\s+(?:q|question)\s*(\d+)/i',
                    '/move\s+(?:q|question)\s*(\d+)\s+to\s+position\s+(\d+)/i',
                    '/reorder\s+(?:q|question)\s*(\d+)\s+to\s+(?:q|question)\s*(\d+)/i',
                ];
                
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $message, $matches)) {
                        $sourceQuestion = (int)$matches[1];
                        $targetQuestion = (int)$matches[2];
                        break;
                    }
                }
            }

            // If we have both question numbers, proceed with move
            if ($sourceQuestion && $targetQuestion) {
                if ($sourceQuestion === $targetQuestion) {
                    return $this->streamSimpleMessage('Source and target questions cannot be the same.');
                }

                // Get questions to validate they exist
                $variableStart = $questionnaire->setup['variable_start'] ?? 1;
                $sourceOrder = $sourceQuestion - $variableStart + 1;
                $targetOrder = $targetQuestion - $variableStart + 1;

                $sourceQuestionModel = \App\Models\Question::where('questionnaire_id', $questionnaire->id)
                    ->where('order', $sourceOrder)
                    ->first();

                if (!$sourceQuestionModel) {
                    return $this->streamSimpleMessage("Question Q{$sourceQuestion} not found.");
                }

                $totalQuestions = $questionnaire->questions()->count();
                if ($targetOrder < 1 || $targetOrder > $totalQuestions) {
                    return $this->streamSimpleMessage("Target position Q{$targetQuestion} is invalid. Please choose a position between Q{$variableStart} and Q" . ($variableStart + $totalQuestions - 1) . ".");
                }

                // Create action data for confirmation
                $confirmationMessage = "I'll move Question Q{$sourceQuestion} to position Q{$targetQuestion}. Would you like me to proceed?";
                
                return response()->stream(function () use ($confirmationMessage, $questionnaireCode, $sourceQuestion, $targetQuestion, $sourceOrder, $targetOrder, $request) {
                    try {
                        $mongoConversation = $request->input('_mongo_conversation');
                        
                        $actionData = [
                            'action' => 'express_question_move',
                            'scope' => 'question',
                            'intent' => 'move',
                            'message' => $confirmationMessage,
                            'questionnaire_code' => $questionnaireCode,
                            'preview_data' => [
                                'source_question' => $sourceQuestion,
                                'target_question' => $targetQuestion,
                                'source_order' => $sourceOrder,
                                'target_order' => $targetOrder,
                            ],
                            'show_confirmation' => true,
                            'confirmation_buttons' => [
                                [
                                    'text' => 'Yes, Move Question',
                                    'action' => 'confirm',
                                    'style' => 'primary',
                                    'data' => [
                                        'action' => 'express_question_move',
                                        'scope' => 'question',
                                        'intent' => 'move',
                                        'preview_data' => [
                                            'source_question' => $sourceQuestion,
                                            'target_question' => $targetQuestion,
                                            'source_order' => $sourceOrder,
                                            'target_order' => $targetOrder,
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
                            ],
                            'loading' => false
                        ];

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
                        Log::error('[ChatController] Move question stream error', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
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
            } else {
                // If question numbers are not parsed, ask user to clarify
                return $this->streamSimpleMessage('I couldn\'t understand which questions you want to move. Please use a format like "Move Q3 to Q1" or "move question 2 to question 3".');
            }
        } catch (\Throwable $e) {
            Log::error('[ChatController] Failed to handle move question stream', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->streamSimpleMessage('I encountered an error while processing your move request. Please try again.');
        }
    }
}

