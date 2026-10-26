<?php

namespace App\Http\Controllers\API\AI;

use App\Http\Controllers\Controller;
use App\Models\AI\AIActConversation;
use App\Services\ConversationStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UtilityPromptController extends Controller
{
    public function __construct(
        private readonly ConversationStorageService $conversationStorage
    ) {}

    /**
     * Handle utility prompts (greeting, customer support responses) without token charges
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function handleUtilityPrompt(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $type = $request->input('type'); // 'greeting', 'support_submit', 'support_keep_chatting', 'support_initial', 'user_message'
            $conversationId = $request->input('conversation_id');
            $platform = $request->input('platform', 'fluent');
            $firstName = $request->input('first_name');
            $userMessage = $request->input('user_message'); // For saving user messages

            // Validate type
            $validTypes = ['greeting', 'support_submit', 'support_keep_chatting', 'support_initial', 'user_message'];
            if (!in_array($type, $validTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid type. Must be one of: ' . implode(', ', $validTypes)
                ], 400);
            }

            // Get or create conversation
            $conversation = $this->conversationStorage->getOrCreateConversation(
                $user->id,
                $conversationId,
                $platform
            );
            if (!$conversation) {
                $conversation = $this->conversationStorage->createConversation($user->id, $platform);
            }

            // Handle user message saving
            if ($type === 'user_message') {
                if (!$userMessage) {
                    return response()->json([
                        'success' => false,
                        'message' => 'user_message is required for type user_message'
                    ], 400);
                }

                $this->conversationStorage->addUserMessageTokenless(
                    $conversation,
                    $userMessage,
                    $request->input('websitePosition', 'dashboard'),
                    [
                        'intent' => 'conversation',
                        'scope' => 'conversation',
                    ]
                );

                $conversation->refresh();

                return response()->json([
                    'success' => true,
                    'conversation_id' => $conversation->conversation_id,
                    'conversation' => [
                        'id' => (string)$conversation->_id,
                        'conversation_id' => $conversation->conversation_id,
                        'platform' => $conversation->platform,
                    ],
                ]);
            }

            // Generate message content based on type
            $content = $this->generateMessageContent($type, $firstName);

            // Add message with tokens: 0 (tokenless)
            $result = $this->conversationStorage->addAssistantMessageTokenless(
                $conversation,
                $content,
                [
                    'intent' => 'conversation',
                    'scope' => 'conversation',
                    'type' => $type,
                    'is_greeting' => $type === 'greeting',
                ]
            );

            $conversation->refresh();

            // Set message type based on utility prompt type
            // Customer support responses use 'customer_support' type, greeting uses 'normal'
            // support_initial uses 'normal' type because it has hasCustomerSupport flag in frontend
            $messageType = in_array($type, ['support_submit', 'support_keep_chatting']) 
                ? 'customer_support' 
                : 'normal';

            return response()->json([
                'success' => true,
                'conversation_id' => $conversation->conversation_id,
                'message_id' => $result['message_id'],
                'message' => [
                    'role' => 'assistant',
                    'content' => $content,
                    'type' => $messageType,
                    'timestamp' => now()->toIso8601String(),
                ],
                'conversation' => [
                    'id' => (string)$conversation->_id,
                    'conversation_id' => $conversation->conversation_id,
                    'platform' => $conversation->platform,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[UtilityPromptController] Failed to handle utility prompt', [
                'error' => $e->getMessage(),
                'type' => $request->input('type'),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to handle utility prompt'
            ], 500);
        }
    }

    /**
     * Generate message content based on type
     * 
     * @param string $type
     * @param string|null $firstName
     * @return string
     */
    private function generateMessageContent(string $type, ?string $firstName = null): string
    {
        return match ($type) {
            'greeting' => $this->generateGreeting($firstName),
            'support_submit' => '✅ Thank you for contacting us! We\'ve received your support request and will get back to you within 24 hours.',
            'support_keep_chatting' => 'Sure thing! We\'ll continue our conversation here. What would you like help with next?',
            'support_initial' => 'How would you like to proceed?',
            default => 'Hello! How can I help you today?',
        };
    }

    /**
     * Generate greeting message
     * 
     * @param string|null $firstName
     * @return string
     */
    private function generateGreeting(?string $firstName = null): string
    {
        // Get AI name from config or default
        $aiName = config('ai_chat.default_ai_name', 'Flins');
        
        if ($firstName) {
            return "Hello {$firstName}! I'm {$aiName}, your assistant for nextsensus. How can I help you today?";
        }
        
        return "Hello! I'm {$aiName}, your assistant for nextsensus. How can I help you today?";
    }
}
