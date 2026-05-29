<?php

namespace App\Http\Controllers\API\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait ChatControllerConversationManagement
{
    public function createConversation(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $platform = $request->input('platform', 'act');

            // Create new conversation on backend
            $conversation = $this->conversationStorage->createConversation(
                $user->id,
                $platform
            );

            // Add initial greeting message
            $greetingResponse = $this->buildSimpleGreetingResponse($user);
            
            try {
                $result = $this->conversationStorage->addAssistantMessage(
                    $conversation,
                    $greetingResponse,
                    0,
                    [
                        'intent' => 'conversation',
                        'scope' => 'conversation',
                        'tokens' => 0,
                    ]
                );
                $assistantMessageId = $result['message_id'] ?? null;
            } catch (\Throwable $e) {
                Log::warning('[ChatController] Failed to add initial greeting to new conversation', [
                    'error' => $e->getMessage(),
                    'conversation_id' => $conversation->conversation_id
                ]);
                $assistantMessageId = null;
            }

            Log::info('[ChatController] Conversation created with greeting', [
                'conversation_id' => $conversation->conversation_id,
                'user_id' => $user->id,
                'platform' => $platform,
                'greeting_message_id' => $assistantMessageId
            ]);

            return response()->json([
                'success' => true,
                'conversation_id' => $conversation->conversation_id,
                'platform' => $platform,
                'title' => $conversation->title,
                'greeting_message' => $greetingResponse,
                'message' => 'Conversation created successfully'
            ]);
        } catch (\Throwable $e) {
            Log::error('[ChatController] Error creating conversation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to create conversation',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getConversationMessages(Request $request, string $conversationId): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $conversation = \App\Models\AI\AIActConversation::where('conversation_id', $conversationId)
                ->where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->first();

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found',
                    'messages' => []
                ], 404);
            }

            // Get messages from MongoDB, ensuring timestamps are preserved
            $messages = $conversation->messages ?? [];
            
            // Normalize messages to ensure consistent format
            $normalizedMessages = array_map(function ($message) {
                // Convert to array if it's an object
                if (is_object($message)) {
                    $message = (array) $message;
                }
                
                return [
                    '_id' => $message['_id'] ?? null,
                    'role' => $message['role'] ?? 'assistant',
                    'content' => $message['content'] ?? '',
                    'type' => $message['type'] ?? 'normal',
                    'timestamp' => $message['timestamp'] ?? null, // Preserve MongoDB timestamp
                    'liked' => $message['liked'] ?? false,
                    'disliked' => $message['disliked'] ?? false,
                    'feedbackSubmitted' => $message['feedbackSubmitted'] ?? false,
                    'feedbackDisabled' => $message['feedbackDisabled'] ?? false,
                    'isStreaming' => false, // Always false for loaded messages
                    'metadata' => $message['metadata'] ?? [],
                ];
            }, $messages);

            return response()->json([
                'success' => true,
                'conversation_id' => $conversation->conversation_id,
                'messages' => $normalizedMessages,
            ]);
        } catch (\Throwable $e) {
            Log::error('[ChatController] Failed to get conversation messages', [
                'conversation_id' => $conversationId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to get conversation messages'], 500);
        }
    }

    /**
     * Extract keywords from text for comparison
     */
    private function extractKeywords(string $text, int $minLength = 4): array
    {
        // Remove HTML tags
        $text = strip_tags($text);
        
        // Convert to lowercase and extract words
        $words = preg_split('/\s+/', strtolower($text));
        
        // Filter out common stop words and short words
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'as', 'is', 'was', 'are', 'were', 'been', 'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'can', 'this', 'that', 'these', 'those', 'it', 'its', 'they', 'them', 'their', 'there', 'then', 'than'];
        
        $keywords = array_filter($words, function ($word) use ($minLength, $stopWords) {
            $cleanWord = preg_replace('/[^a-z0-9]/', '', $word);
            return strlen($cleanWord) >= $minLength && !in_array($cleanWord, $stopWords);
        });
        
        // Return unique keywords
        return array_values(array_unique($keywords));
    }

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
        $shouldPin = $request->has('pinned') ? $request->boolean('pinned') : null;

        try {
            // Use ConversationStorageService method
            if ($shouldPin === null) {
                // Toggle: get current state first
                $conversation = $this->conversationStorage->getConversationById($user->id, $conversationId);
                if (!$conversation) {
                    return response()->json(['message' => 'Conversation not found'], 404);
                }
                $metadata = $conversation->metadata ?? [];
                if (!is_array($metadata)) {
                    $metadata = json_decode(json_encode($metadata), true) ?: [];
                }
                $shouldPin = empty($metadata['pinned_at']);
            }

            $conversation = $this->conversationStorage->setConversationPinned($user->id, $conversationId, $shouldPin);

            if (!$conversation) {
                return response()->json(['message' => 'Conversation not found'], 404);
            }

            $metadata = $conversation->metadata ?? [];
            if (!is_array($metadata)) {
                $metadata = json_decode(json_encode($metadata), true) ?: [];
            }
            $pinnedAt = isset($metadata['pinned_at']) ? $metadata['pinned_at'] : null;

            return response()->json([
                'success' => true,
                'pinned' => $shouldPin,
                'pinned_at' => $pinnedAt,
            ]);
        } catch (\Throwable $e) {
            Log::error('[ChatController] Failed to pin conversation', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversationId,
            ]);
            return response()->json(['message' => 'Failed to update pin state'], 500);
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
        $title = trim($request->title ?? '');

        try {
            // Use ConversationStorageService method
            $conversation = $this->conversationStorage->updateConversationTitle(
                $user->id,
                $conversationId,
                $title === '' ? null : $title
            );

            if (!$conversation) {
                return response()->json(['message' => 'Conversation not found'], 404);
            }

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
}
