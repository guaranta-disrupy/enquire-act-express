<?php

namespace App\Http\Controllers\API\AI;

use App\Http\Controllers\Controller;
use App\Models\AI\AIActConversation;
use App\Services\ConversationMemoryService;
use App\Services\ConversationStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConversationController extends Controller
{
    public function __construct(
        private readonly ConversationStorageService $conversationStorage,
        private readonly ConversationMemoryService $memoryService
    ) {}

    public function getConversationHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $conversations = AIActConversation::where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->orderBy('updated_at', 'desc')
                ->limit(100)
                ->get()
                ->filter(function ($conv) {
                    $metadata = $conv->metadata ?? [];
                    if (!is_array($metadata)) {
                        $metadata = json_decode(json_encode($metadata), true) ?: [];
                    }
                    $isCleared = $metadata['isCleared'] ?? $metadata['is_cleared'] ?? false;
                    return !$isCleared;
                })
                ->map(function ($conv) {
                    return [
                        'id' => (string)$conv->_id,
                        'conversation_id' => $conv->conversation_id,
                        'user_id' => $conv->user_id,
                        'platform' => $conv->platform,
                        'title' => $conv->title ?? null,
                        'messages' => $conv->messages ?? [],
                        'metadata' => $conv->metadata ?? [],
                        'created_at' => $conv->created_at,
                        'updated_at' => $conv->updated_at,
                        'deleted_at' => $conv->deleted_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'conversations' => $conversations,
            ]);
        } catch (\Throwable $e) {
            Log::error('[ConversationController] Failed to get conversation history', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to get conversation history'], 500);
        }
    }

    public function getConversation(Request $request, string $conversation): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $isObjectId = preg_match('/^[0-9a-f]{24}$/i', $conversation);

            $conversationModel = null;
            if ($isObjectId) {
                $conversationModel = AIActConversation::where('_id', $conversation)
                    ->where('user_id', $user->id)
                    ->whereNull('deleted_at')
                    ->first();
            }

            if (!$conversationModel) {
                $conversationModel = AIActConversation::where('conversation_id', $conversation)
                    ->where('user_id', $user->id)
                    ->whereNull('deleted_at')
                    ->first();
            }

            if (!$conversationModel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'conversation' => [
                    'id' => (string)$conversationModel->_id,
                    'conversation_id' => $conversationModel->conversation_id,
                    'user_id' => $conversationModel->user_id,
                    'platform' => $conversationModel->platform,
                    'title' => $conversationModel->title ?? null,
                    'messages' => $conversationModel->messages ?? [],
                    'metadata' => $conversationModel->metadata ?? [],
                    'feedback_summary' => $conversationModel->feedback_summary ?? [],
                    'created_at' => $conversationModel->created_at,
                    'updated_at' => $conversationModel->updated_at,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[ConversationController] Failed to get conversation', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversation,
                'user_id' => $user->id,
            ]);
            return response()->json(['message' => 'Failed to get conversation'], 500);
        }
    }

    public function createConversation(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $platform = $request->input('platform', 'fluent');
            $conversation = $this->conversationStorage->createConversation($user->id, $platform);

            $firstName = $this->getFirstName($request);
            $aiName = config('ai_chat.default_ai_name', 'Flins');
            $greeting = $firstName
                ? "Hello {$firstName}! I'm {$aiName}, your assistant for nextsensus. How can I help you today?"
                : "Hello! I'm {$aiName}, your assistant for nextsensus. How can I help you today?";

            // Use tokenless method for greeting (no token charges)
            $this->conversationStorage->addAssistantMessageTokenless(
                $conversation,
                $greeting,
                [
                    'intent' => 'conversation',
                    'scope' => 'conversation',
                    'is_greeting' => true,
                    'type' => 'greeting',
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
                    'messages' => $conversation->messages ?? [],
                    'created_at' => $conversation->created_at,
                    'updated_at' => $conversation->updated_at,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[ConversationController] Failed to create conversation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Failed to create conversation'], 500);
        }
    }

    public function deleteConversation(Request $request, string $conversation): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $conversationId = urldecode($conversation);

            Log::info('[ConversationController] Delete conversation attempt', [
                'conversation_id_raw' => $conversation,
                'conversation_id_decoded' => $conversationId,
                'user_id' => $user->id,
            ]);

            $isObjectId = preg_match('/^[0-9a-f]{24}$/i', $conversationId);

            $existingConversation = null;
            if ($isObjectId) {
                $existingConversation = AIActConversation::where('_id', $conversationId)
                    ->where('user_id', $user->id)
                    ->first();
            }

            if (!$existingConversation) {
                $existingConversation = AIActConversation::where('conversation_id', $conversationId)
                    ->where('user_id', $user->id)
                    ->first();
            }

            if (!$existingConversation) {
                $allConversations = AIActConversation::where('user_id', $user->id)
                    ->get(['_id', 'conversation_id', 'deleted_at', 'metadata'])
                    ->map(function($conv) {
                        return [
                            '_id' => (string)$conv->_id,
                            'conversation_id' => $conv->conversation_id,
                            'deleted_at' => $conv->deleted_at,
                            'isCleared' => $conv->metadata['isCleared'] ?? false,
                        ];
                    });

                Log::warning('[ConversationController] Conversation not found for deletion', [
                    'requested_conversation_id' => $conversationId,
                    'requested_conversation_id_raw' => $conversation,
                    'user_id' => $user->id,
                    'available_conversations' => $allConversations->toArray(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found',
                ], 404);
            }

            $clearedConversation = $this->conversationStorage->clearConversationById(
                $user->id,
                $conversationId
            );

            if (!$clearedConversation) {
                Log::error('[ConversationController] Failed to clear conversation after finding it', [
                    'conversation_id' => $conversation,
                    'user_id' => $user->id,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete conversation',
                ], 500);
            }

            $this->memoryService->clearMemory($user->id, $clearedConversation->platform, $clearedConversation->conversation_id);

            Log::info('[ConversationController] Conversation deleted successfully', [
                'conversation_id' => $conversationId,
                'user_id' => $user->id,
                'metadata' => $clearedConversation->metadata,
                'deleted_at' => $clearedConversation->deleted_at,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Conversation deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('[ConversationController] Failed to delete conversation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'conversation_id' => $conversationId ?? $conversation,
                'user_id' => $user->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete conversation',
            ], 500);
        }
    }

    public function clear(Request $request): JsonResponse
    {
        $request->validate([
            'platform' => 'nullable|string',
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $conversationId = $request->input('conversation_id');
            $this->memoryService->clearMemory(
                $user->id,
                $request->input('platform'),
                $conversationId
            );

            if ($conversationId) {
                $result = $this->conversationStorage->clearConversationById(
                    $user->id,
                    $conversationId
                );

                if (!$result) {
                    Log::warning('[ConversationController] Failed to clear conversation - not found', [
                        'conversation_id' => $conversationId,
                        'user_id' => $user->id,
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Conversation not found',
                    ], 404);
                }

                Log::info('[ConversationController] Cleared specific MongoDB conversation', [
                    'conversation_id' => $conversationId,
                    'user_id' => $user->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Conversation cleared successfully',
                ]);
            }

            $count = $this->conversationStorage->clearAllConversations($user->id);
            Log::info('[ConversationController] Cleared all MongoDB conversations', [
                'count' => $count,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'All conversations cleared successfully',
                'count' => $count,
            ]);
        } catch (\Throwable $e) {
            Log::error('[ConversationController] Failed to clear conversation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'conversation_id' => $request->input('conversation_id'),
                'user_id' => $user->id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear conversation',
            ], 500);
        }
    }

    private function getFirstName(Request $request): ?string
    {
        try {
            $user = $request->user();
            if (! $user) {
                return null;
            }

            $first = null;
            if (isset($user->first_name) && trim((string) $user->first_name) !== '') {
                $first = trim((string) $user->first_name);
            } elseif (isset($user->name) && trim((string) $user->name) !== '') {
                $parts = preg_split('/\s+/', trim((string) $user->name));
                $first = $parts[0] ?? null;
            }

            return $first ?: null;
        } catch (\Throwable $e) {
            Log::warning('[ConversationController] Error extracting first name', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
