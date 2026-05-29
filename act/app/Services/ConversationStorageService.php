<?php

namespace App\Services;

use App\Models\AI\AIActConversation;
use App\Models\AI\MongoTokenManager;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Schema\ArraySchema;
use App\AI\Enums\AIModel;

class ConversationStorageService
{
    /**
     * Create a new conversation in MongoDB
     */
    public function createConversation(int $userId, string $platform = 'act'): AIActConversation
    {
        $conversation = AIActConversation::create([
            'conversation_id' => 'conv_' . Str::random(16),
            'user_id' => $userId,
            'platform' => $platform,
            'title' => strtoupper($platform) . ' AI',
            'deleted_at' => null,
            'metadata' => [
                'status' => 'active',
                'isCleared' => false,
                'message_count' => 0,
                'total_tokens_used' => 0,
                'retention_until' => Carbon::now()->addDays(90)->toIso8601String(),
                'feedback_version' => '1.0',
                'feedback_source' => 'chat_interface',
            ],
            'messages' => [],
            'feedback_summary' => [
                'overall_likes' => 0,
                'overall_dislikes' => 0,
                'platform_breakdown' => [],
                'dislike_categories' => [
                    'harmful' => 0,
                    'not_accurate' => 0,
                    'not_useful_or_incomplete' => 0,
                    'something_else' => 0,
                ],
                'comments' => [],
                'feedback_completion_rate' => 0,
                'last_feedback_at' => null,
            ],
            'conversation_summary' => [
                'intent' => 'conversation',
                'scope' => 'conversation',
                'summary' => '',
                'key_topics' => [],
                'active_actions' => [],
            ],
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return $conversation;
    }

    /**
     * Add a user message to conversation
     */
    public function addUserMessage(
        AIActConversation $conversation,
        string $content,
        string $websitePosition = 'dashboard',
        array $metadata = []
    ): AIActConversation {
        // Refresh conversation to get latest messages
        $conversation->refresh();
        
        $message = [
            '_id' => 'msg_' . Str::random(8),
            'role' => 'user',
            'content' => $content,
            'type' => 'normal',
            'timestamp' => Carbon::now()->toIso8601String(),
            'feedback_status' => null,
            'feedback' => [
                // 'isLiked' => null,
                'isDisliked' => null,
                'disliked_category' => null,
                'comment' => null,
                'timestamp' => null,
            ],
            'metadata' => array_merge([
                'tokens' => $this->estimateTokens($content),
                'intent' => 'conversation',
                'scope' => 'conversation',
                'website_position' => $websitePosition,
                'response_time_ms' => null,
                'isAnswered' => true,
            ], $metadata),
        ];

        $conversation->addMessage($message);
        $conversation->save();

        // Increment user message tokens into Mongo token_manager (per user/platform)
        try {
            $tokens = $message['metadata']['tokens'] ?? 0;
            if ($tokens > 0) {
                $this->incrementMongoTokenUsage($conversation->user_id, $conversation->platform, $tokens);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to increment Mongo token usage for user message', ['error' => $e->getMessage()]);
        }

        // Track simple operation type (create/update/delete) in active_actions
        try {
            $intent = $metadata['intent'] ?? null;
            $scope = $metadata['scope'] ?? null;
            
            // Add operation to active_actions if it's an action intent
            if (in_array($intent, ['create', 'update', 'delete']) && $scope && $scope !== 'conversation') {
                $this->appendActiveAction($conversation, $intent);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to append action operation to conversation summary', ['error' => $e->getMessage()]);
        }

        return $conversation;
    }

    /**
     * Add a user message without charging tokens.
     */
    public function addUserMessageTokenless(
        AIActConversation $conversation,
        string $content,
        string $websitePosition = 'dashboard',
        array $metadata = []
    ): AIActConversation {
        return $this->addUserMessage(
            $conversation,
            $content,
            $websitePosition,
            array_merge([
                'tokens' => 0,
            ], $metadata)
        );
    }

    /**
     * Add an assistant message to conversation
     */
    public function addAssistantMessage(
        AIActConversation $conversation,
        string $content,
        int $responseTimeMs = 0,
        array $metadata = []
    ): array {
        // Refresh conversation to get latest messages
        $conversation->refresh();
        
        $messageId = 'msg_' . Str::random(8);
        $message = [
            '_id' => $messageId,
            'role' => 'assistant',
            'content' => $content,
            'type' => 'normal',
            'timestamp' => Carbon::now()->toIso8601String(),
            'feedback_status' => null,
            'feedback' => [
                // 'isLiked' => null,
                'isDisliked' => null,
                'disliked_category' => null,
                'comment' => null,
                'timestamp' => null,
            ],
            'metadata' => array_merge([
                'tokens' => $this->estimateTokens($content),
                'intent' => 'conversation',
                'scope' => 'conversation',
                'website_position' => 'dashboard',
                'response_time_ms' => $responseTimeMs,
                'isAnswered' => null,
            ], $metadata),
        ];

        $conversation->addMessage($message);
        
        // Update total tokens
        $totalTokens = collect($conversation->messages)->sum(function ($msg) {
            return $msg['metadata']['tokens'] ?? 0;
        });
        
        $conversationMetadata = $conversation->metadata ?? [];
        if (is_string($conversationMetadata)) {
            try {
                $conversationMetadata = json_decode($conversationMetadata, true) ?? [];
            } catch (\Throwable $e) {
                Log::warning('Failed to decode metadata string in addAssistantMessage', ['error' => $e->getMessage()]);
                $conversationMetadata = [];
            }
        }
        $conversationMetadata['total_tokens_used'] = $totalTokens;
        $conversation->metadata = $conversationMetadata;
        
        $conversation->save();

        // Increment assistant response tokens into Mongo token_manager (per user/platform)
        try {
            $tokens = $message['metadata']['tokens'] ?? 0;
            if ($tokens > 0) {
                $this->incrementMongoTokenUsage($conversation->user_id, $conversation->platform, $tokens);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to increment Mongo token usage', ['error' => $e->getMessage()]);
        }

        // Track simple operation type (create/update/delete) in active_actions
        try {
            $paramMetadata = $metadata;
            $intent = $paramMetadata['intent'] ?? null;
            $scope = $paramMetadata['scope'] ?? null;

            // Add operation to active_actions if it's an action intent
            if (in_array($intent, ['create', 'update', 'delete']) && $scope && $scope !== 'conversation') {
                $this->appendActiveAction($conversation, $intent);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to append action operation to conversation summary', ['error' => $e->getMessage()]);
        }

        // Return both conversation and message ID
        return [
            'conversation' => $conversation,
            'message_id' => $messageId
        ];
    }

    /**
     * Add an assistant message without charging tokens.
     */
    public function addAssistantMessageTokenless(
        AIActConversation $conversation,
        string $content,
        array $metadata = []
    ): array {
        return $this->addAssistantMessage(
            $conversation,
            $content,
            0,
            array_merge([
                'tokens' => 0,
            ], $metadata)
        );
    }

    /**
     * Get or create conversation for user
     * Returns the conversation if found, or creates a new one ONLY if conversationId is provided and valid
     * If conversationId is null, returns null instead of auto-creating
     */
    public function getOrCreateConversation(int $userId, ?string $conversationId = null, ?string $platform = null): ?AIActConversation
    {
        // If no conversation ID provided, don't auto-create - return null
        if (!$conversationId) {
            return null;
        }

        // If specific conversation ID provided and it's valid, try to get it
        if (str_starts_with($conversationId, 'conv_')) {
            $conversation = AIActConversation::where('conversation_id', $conversationId)
                ->where('user_id', $userId)
                ->when($platform, fn($q) => $q->where('platform', $platform))
                ->whereNull('deleted_at')
                ->first();

            if ($conversation) {
                // Check if conversation is cleared
                $isCleared = ($conversation->metadata['isCleared'] ?? false) ||
                            ($conversation->metadata['status'] ?? '') === 'cleared';
                
                if (!$isCleared) {
                    return $conversation;
                }
            }
        }
        
        // Conversation ID provided but not found or is invalid - return null, don't auto-create
        return null;
    }

    /**
     * Get conversation by ID.
     * Accepts both MongoDB _id (ObjectId) and conversation_id.
     */
    public function getConversationById(int $userId, string $conversationId): ?AIActConversation
    {
        // Try by conversation_id first (most common case)
        $conversation = AIActConversation::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        // If not found and looks like MongoDB ObjectId, try _id
        if (!$conversation && preg_match('/^[0-9a-f]{24}$/i', $conversationId)) {
            try {
                $conversation = AIActConversation::where('_id', $conversationId)
                    ->where('user_id', $userId)
                    ->whereNull('deleted_at')
                    ->first();
            } catch (\Throwable $e) {
                // Ignore errors when trying ObjectId lookup
            }
        }

        return $conversation;
    }

    /**
     * Helper method to ensure metadata is returned as an array
     */
    private function getMetadataAsArray(AIActConversation $conversation): array
    {
        $metadata = $conversation->metadata ?? [];
        if (is_string($metadata)) {
            try {
                $metadata = json_decode($metadata, true) ?? [];
            } catch (\Throwable $e) {
                Log::warning('Failed to decode metadata string', ['error' => $e->getMessage()]);
                $metadata = [];
            }
        }
        return is_array($metadata) ? $metadata : [];
    }

    /**
     * Update conversation summary
     */
    public function updateSummary(
        AIActConversation $conversation,
        string $summary,
        array $keyTopics = []
    ): AIActConversation {
        $conversation->updateConversationSummary([
            'summary' => $summary,
            'key_topics' => $keyTopics,
        ]);
        $conversation->save();

        return $conversation;
    }

    /**
     * Add feedback to conversation (keeps legacy support)
     */
    public function addFeedback(
        AIActConversation $conversation,
        string $type,
        ?string $category = null,
        ?string $comment = null,
        ?string $messageId = null
    ): AIActConversation {
        // Update message-level feedback if message ID is provided
        if ($messageId) {
            $this->addMessageFeedback($conversation, $messageId, $type, $category, $comment);
        }
        
        // Update conversation-level feedback summary
        $feedbackData = ['type' => $type];
        
        if ($category) {
            $feedbackData['category'] = $category;
        }
        
        if ($comment) {
            $feedbackData['comment'] = $comment;
        }

        $conversation->updateFeedbackSummary($feedbackData);
        $conversation->save();

        return $conversation;
    }

    /**
     * Add feedback to a specific message
     */
    public function addMessageFeedback(
        AIActConversation $conversation,
        string $messageId,
        string $type,
        ?string $category = null,
        ?string $comment = null
    ): AIActConversation {
        $messages = $conversation->messages;
        $messageFound = false;
        
        if (!is_array($messages)) {
            $messages = [];
        }
        
        \Log::info('Looking for message to add feedback', [
            'message_id' => $messageId,
            'total_messages' => count($messages),
            'message_ids' => array_map(function($m) { return $m['_id'] ?? 'no-id'; }, $messages),
            'raw_messages' => $messages // Debug: see full structure
        ]);
        
        foreach ($messages as $index => &$message) {
            // Convert to array if it's an object
            if (is_object($message)) {
                $message = (array) $message;
            }
            
            // Check both '_id' and 'id' fields (MongoDB converts _id to id in Laravel)
            $currentMessageId = $message['_id'] ?? $message['id'] ?? null;
            
            if ($currentMessageId === $messageId) {
                // Update feedback status
                $message['feedback_status'] = $type === 'like' ? 'liked' : 'disliked';
                
                // Update feedback details
                $message['feedback'] = [
                    // 'isLiked' => $type === 'like' ? true : false,
                    'isDisliked' => $type === 'dislike' ? true : false,
                    'disliked_category' => $category,
                    'comment' => $comment,
                    'timestamp' => Carbon::now()->toIso8601String(),
                ];
                
                // Ensure the _id field is set for MongoDB
                if (!isset($message['_id'])) {
                    $message['_id'] = $currentMessageId;
                }
                
                $messageFound = true;
                \Log::info('Message found and feedback updated', [
                    'message_id' => $messageId,
                    'index' => $index,
                    'feedback_status' => $message['feedback_status']
                ]);
                break;
            }
        }
        unset($message); // Break the reference
        
        if (!$messageFound) {
            \Log::warning('Message not found for feedback', [
                'message_id' => $messageId,
                'conversation_id' => $conversation->conversation_id,
                'available_messages' => array_column($messages, '_id')
            ]);
        }
        
        $conversation->messages = $messages;
        $conversation->save();
        
        return $conversation;
    }

    /**
     * Update action status after execution (call this from frontend after AiExecutionController completes)
     */
    public function updateActionStatus(
        AIActConversation $conversation,
        string $messageId,
        string $result,
        ?int $questionId = null,
        ?string $questionnaireCode = null
    ): AIActConversation {
        // For execution results, we just ensure 'create' is in active_actions
        // (it should already be there from user message or confirmation)
        $this->appendActiveAction($conversation, 'create');

        return $conversation;
    }

    /**
     * Increment token usage in Mongo token_manager for the given user/platform.
     */
    public function incrementMongoTokenUsage(int $userId, string $platform, int $tokens): void
    {
        if ($tokens <= 0) {
            return;
        }

        $now = now();
        $today = $now->toDateString();

        try {
            // Get or create record for this user
            $record = MongoTokenManager::where('user_id', $userId)->first();

            // Check if user has already reached the daily limit - don't increment if at or over limit
            $dailyLimit = (int) (config('prism.daily_token_limit', 25000));
            
            if ($record) {
                $currentUsage = (int) ($record->overall_token_used ?? 0);
                if ($currentUsage >= $dailyLimit) {
                    // User is already at or over the limit - skip incrementing
                    Log::info('Skipping token increment - user already at daily limit', [
                        'user_id' => $userId,
                        'current_usage' => $currentUsage,
                        'daily_limit' => $dailyLimit,
                        'tokens_attempted' => $tokens
                    ]);
                    return;
                }
                
                // Refresh to get latest data and prevent race conditions
                $record->refresh();
            }

            if (!$record) {
                // Create new record
                MongoTokenManager::create([
                    'user_id' => $userId,
                    'overall_token_used' => $tokens,
                    'platforms' => [[
                        'platform_type' => $platform,
                        'token_count' => $tokens,
                        'token_per_day' => [[
                            'token' => $tokens,
                            'date' => $today
                        ]]
                    ]],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                return;
            }

            $platforms = $record->platforms;
            if (is_string($platforms)) {
                $platforms = json_decode($platforms, true);
            }
            if (!is_array($platforms)) {
                $platforms = [];
            }

            // Find existing platform entry
            $foundIndex = null;
            foreach ($platforms as $i => $p) {
                if (($p['platform_type'] ?? null) === $platform) {
                    $foundIndex = $i;
                    break;
                }
            }

            if ($foundIndex !== null) {
                // Update existing platform
                $platforms[$foundIndex]['token_count'] = ($platforms[$foundIndex]['token_count'] ?? 0) + $tokens;
                
                // Update per-day entry
                $updated = false;
                foreach ($platforms[$foundIndex]['token_per_day'] as &$bucket) {
                    $bucketDate = \Carbon\Carbon::parse($bucket['date'])->toDateString();
                    if ($bucketDate === $today) {
                        $bucket['token'] = ($bucket['token'] ?? 0) + $tokens;
                        $updated = true;
                        break;
                    }
                }
                unset($bucket);

                if (!$updated) {
                    $platforms[$foundIndex]['token_per_day'][] = [
                        'token' => $tokens,
                        'date' => $today
                    ];
                }
            } else {
                // Add new platform
                $platforms[] = [
                    'platform_type' => $platform,
                    'token_count' => $tokens,
                    'token_per_day' => [[
                        'token' => $tokens,
                        'date' => $today
                    ]]
                ];
            }

            // Recalculate overall total
            $overall = 0;
            foreach ($platforms as $p) {
                $overall += (int) ($p['token_count'] ?? 0);
            }

            // Save back
            $record->overall_token_used = $overall;
            $record->platforms = $platforms;
            $record->updated_at = $now;
            $record->save();
        } catch (\Throwable $e) {
            Log::warning('MongoDB token usage increment failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'platform' => $platform,
                'tokens' => $tokens
            ]);
        }
    }

    /**
     * Clean up duplicate platform entries in MongoDB token manager
     */
    public function cleanupDuplicatePlatforms(): void
    {
        try {
            $records = MongoTokenManager::all();
            
            foreach ($records as $record) {
                $platforms = $record->platforms;
                if (is_string($platforms)) {
                    $platforms = json_decode($platforms, true);
                }
                if (!is_array($platforms)) {
                    continue;
                }

                // Group platforms by platform_type and merge duplicates
                $mergedPlatforms = [];
                foreach ($platforms as $platform) {
                    $platformType = $platform['platform_type'] ?? 'unknown';
                    
                    if (!isset($mergedPlatforms[$platformType])) {
                        $mergedPlatforms[$platformType] = [
                            'platform_type' => $platformType,
                            'token_count' => 0,
                            'token_per_day' => []
                        ];
                    }
                    
                    // Merge token counts
                    $mergedPlatforms[$platformType]['token_count'] += (int) ($platform['token_count'] ?? 0);
                    
                    // Merge token_per_day entries
                    $existingDays = [];
                    foreach ($mergedPlatforms[$platformType]['token_per_day'] as $day) {
                        $existingDays[$day['date']] = (int) ($day['token'] ?? 0);
                    }
                    
                    foreach ($platform['token_per_day'] ?? [] as $day) {
                        $date = $day['date'] ?? '';
                        $tokens = (int) ($day['token'] ?? 0);
                        $existingDays[$date] = ($existingDays[$date] ?? 0) + $tokens;
                    }
                    
                    // Convert back to array format
                    $mergedPlatforms[$platformType]['token_per_day'] = [];
                    foreach ($existingDays as $date => $tokens) {
                        $mergedPlatforms[$platformType]['token_per_day'][] = [
                            'token' => $tokens,
                            'date' => $date
                        ];
                    }
                }

                // Convert back to indexed array
                $cleanedPlatforms = array_values($mergedPlatforms);
                
                // Recalculate overall total
                $overall = 0;
                foreach ($cleanedPlatforms as $p) {
                    $overall += (int) ($p['token_count'] ?? 0);
                }

                // Update the record if platforms changed
                if (count($cleanedPlatforms) !== count($platforms)) {
                    $record->platforms = $cleanedPlatforms;
                    $record->overall_token_used = $overall;
                    $record->updated_at = now();
                    $record->save();
                    
                    Log::info('Cleaned up duplicate platforms', [
                        'user_id' => $record->user_id,
                        'original_count' => count($platforms),
                        'cleaned_count' => count($cleanedPlatforms),
                        'overall_tokens' => $overall
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to cleanup duplicate platforms', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Mark the last user message as not answered (for error cases)
     */
    public function markLastUserMessageAsUnanswered(AIActConversation $conversation): AIActConversation
    {
        // Force reload from database to avoid stale data
        $conversation->refresh();
        
        $messages = $conversation->messages ?? [];
        if (!is_array($messages) || empty($messages)) {
            Log::warning('No messages found in conversation for marking unanswered', [
                'conversation_id' => $conversation->conversation_id ?? 'unknown'
            ]);
            return $conversation;
        }

        $found = false;
        // Find the last user message and mark it as not answered
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            $message = is_object($messages[$i]) ? (array) $messages[$i] : $messages[$i];
            
            if (($message['role'] ?? null) === 'user') {
                $messageId = $message['_id'] ?? $message['id'] ?? 'unknown';
                if (isset($messages[$i]['metadata'])) {
                    $messages[$i]['metadata']['isAnswered'] = false;
                } else {
                    $messages[$i]['metadata'] = ['isAnswered' => false];
                }
                $found = true;
                Log::info('Marked message as unanswered', [
                    'message_id' => $messageId,
                    'conversation_id' => $conversation->conversation_id,
                    'message_index' => $i
                ]);
                break;
            }
        }

        if (!$found) {
            Log::warning('No user message found to mark as unanswered', [
                'conversation_id' => $conversation->conversation_id,
                'total_messages' => count($messages)
            ]);
        }

        $conversation->messages = $messages;
        $conversation->save();

        return $conversation;
    }

    /**
     * Get recent conversations for a user
     */
    public function getRecentConversations(int $userId, int $limit = 10)
    {
        return AIActConversation::where('user_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Simple token estimation (roughly 4 chars = 1 token)
     */
    private function estimateTokens(string $text): int
    {
        return max(1, (int) ceil(mb_strlen($text) / 4));
    }

    /**
     * Mark conversation as completed/inactive
     */
    public function completeConversation(AIActConversation $conversation): AIActConversation
    {
        $metadata = $conversation->metadata ?? [];
        if (is_string($metadata)) {
            try {
                $metadata = json_decode($metadata, true) ?? [];
            } catch (\Throwable $e) {
                Log::warning('Failed to decode metadata string in completeConversation', ['error' => $e->getMessage()]);
                $metadata = [];
            }
        }
        $metadata['status'] = 'completed';
        $conversation->metadata = $metadata;
        $conversation->save();

        return $conversation;
    }

    /**
     * Clear conversation (soft delete)
     * Marks the conversation as cleared and sets deleted_at timestamp
     */
    public function clearConversation(AIActConversation $conversation): AIActConversation
    {
        // Refresh to get latest data
        $conversation->refresh();
        
        // Ensure metadata is an array (handle case where it might be a string)
        $metadata = $conversation->metadata ?? [];
        if (is_string($metadata)) {
            try {
                $metadata = json_decode($metadata, true) ?? [];
            } catch (\Throwable $e) {
                Log::warning('Failed to decode metadata string', ['error' => $e->getMessage()]);
                $metadata = [];
            }
        }
        
        $metadata['isCleared'] = true;
        $metadata['status'] = 'cleared';
        $conversation->metadata = $metadata;
        $conversation->deleted_at = Carbon::now();
        $conversation->save();

        Log::info('Conversation cleared', [
            'conversation_id' => $conversation->conversation_id,
            'user_id' => $conversation->user_id,
            'deleted_at' => $conversation->deleted_at,
            'status' => $metadata['status']
        ]);

        return $conversation;
    }

    /**
     * Clear conversation by ID
     */
    public function clearConversationById(int $userId, string $conversationId): ?AIActConversation
    {
        $conversation = AIActConversation::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->first();

        if (!$conversation) {
            return null;
        }

        return $this->clearConversation($conversation);
    }

    /**
     * Clear all conversations for a user
     */
    public function clearAllConversations(int $userId): int
    {
        $conversations = AIActConversation::where('user_id', $userId)
            ->whereNull('deleted_at')
            ->get();

        $count = 0;
        foreach ($conversations as $conversation) {
            $this->clearConversation($conversation);
            $count++;
        }

        return $count;
    }

    /**
     * Add overall conversation rating (1-5 stars and optional comment)
     */
    public function addConversationRating(
        AIActConversation $conversation,
        int $rating,
        ?string $comment = null
    ): AIActConversation {
        // Validate rating
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }

        // Refresh conversation to get latest data
        $conversation->refresh();

        // Update overall_convo_feedback with the exact structure requested
        $conversation->overall_convo_feedback = [
            'stars_count' => $rating,
            'comment' => $comment ?: null,
            'rated_at' => Carbon::now()->toIso8601String(),
        ];

        $conversation->save();

        Log::info('Conversation rating added', [
            'conversation_id' => $conversation->conversation_id,
            'rating' => $rating,
            'has_comment' => !empty($comment)
        ]);

        return $conversation;
    }

    /**
     * Build and persist an updated conversation summary using Gemini via Prism.
     * Keeps it concise and updates key topics and any active actions if detected.
     */
    private function summarizeConversation(AIActConversation $conversation): void
    {
        // Use last N messages to keep prompt small
        $messages = $conversation->messages ?? [];
        if (!is_array($messages)) {
            $messages = [];
        }

        $recent = array_slice($messages, -12);
        $lines = array_map(function ($m) {
            if (is_object($m)) { $m = (array) $m; }
            $role = $m['role'] ?? 'user';
            $content = $m['content'] ?? '';
            // Collapse whitespace and trim
            $content = trim(preg_replace('/\s+/', ' ', (string) $content));
            return strtoupper($role) . ': ' . mb_substr($content, 0, 800);
        }, $recent);

        $systemPrompt = "You are an assistant that writes ultra-concise chat summaries for product UI.\n" .
            "Output a short summary (<= 60 words), key topics as short phrases, and any active actions referenced.\n" .
            "Do not use markdown. Use neutral, helpful tone. Avoid usernames and timestamps.";

        $prompt = "Conversation (most recent first):\n" . implode("\n", $lines) . "\n\n" .
            "Summarize the conversation now.";

        $schema = $this->buildConversationSummarySchema();

        $response = Prism::structured()
            ->using(...AIModel::getSummaryConfig())
            ->withSchema($schema)
            ->withSystemPrompt($systemPrompt)
            ->withPrompt($prompt)
            ->usingTemperature(0.2)
            ->asStructured();

        $structured = $response->structured ?? [];
        $summary = (string) ($structured['summary'] ?? '');
        $keyTopics = is_array($structured['key_topics'] ?? null) ? $structured['key_topics'] : [];

        // Persist summary and topics only; keep existing active_actions unchanged
        $conversation->updateConversationSummary([
            'summary' => $summary,
            'key_topics' => $keyTopics,
        ]);
        $conversation->save();
    }

    /**
     * Append operation to conversation_summary.active_actions (keeps unique operations).
     */
    private function appendActiveAction(AIActConversation $conversation, string $operation): void
    {
        $summary = $conversation->conversation_summary ?? [];
        if (!is_array($summary)) { $summary = []; }
        $actions = $summary['active_actions'] ?? [];
        if (!is_array($actions)) { $actions = []; }

        // Add operation if not already present (keep unique)
        if (!in_array($operation, $actions)) {
            $actions[] = $operation;
        }

        $conversation->updateConversationSummary([
            'active_actions' => $actions,
        ]);
        $conversation->save();
    }

    private function buildConversationSummarySchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'conversation_summary',
            description: 'A compact summary of a multi-turn chat, including topics and actions.',
            properties: [
                new StringSchema(
                    name: 'summary',
                    description: 'One or two sentences (<= 60 words) summarizing the conversation.'
                ),
                new ArraySchema(
                    name: 'key_topics',
                    description: 'List of up to 5 short topic phrases.',
                    items: new StringSchema(
                        name: 'topic',
                        description: 'A concise topic phrase.'
                    )
                ),
            ],
            requiredFields: ['summary']
        );
    }

    /**
     * Update conversation title by ID.
     * Accepts both MongoDB _id (ObjectId) and conversation_id.
     * If title is null or empty, it will be set to null.
     */
    public function updateConversationTitle(int $userId, string $conversationId, ?string $title): ?AIActConversation
    {
        $conversation = $this->getConversationById($userId, $conversationId);
        if (! $conversation) {
            return null;
        }
        $conversation->title = $title ? trim($title) : null;
        $conversation->timestamps = false;
        $conversation->save();
        $conversation->timestamps = true;

        return $conversation;
    }

    /**
     * Set or unset conversation pinned state by ID.
     * Accepts both MongoDB _id (ObjectId) and conversation_id.
     * When pinned, sets metadata.pinned_at to current ISO8601 date; when unpinned, sets to null.
     */
    public function setConversationPinned(int $userId, string $conversationId, bool $pinned): ?AIActConversation
    {
        $conversation = $this->getConversationById($userId, $conversationId);
        if (! $conversation) {
            return null;
        }
        $metadata = $conversation->metadata ?? [];
        if (! is_array($metadata)) {
            $metadata = json_decode(json_encode($metadata), true) ?: [];
        }
        $metadata['pinned_at'] = $pinned ? Carbon::now()->toIso8601String() : null;
        $conversation->metadata = $metadata;
        $conversation->updated_at = Carbon::now();
        $conversation->save();

        return $conversation;
    }
}
