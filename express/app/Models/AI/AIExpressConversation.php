<?php

namespace App\Models\AI;

use MongoDB\Laravel\Eloquent\Model;
use Carbon\Carbon;

class AIExpressConversation extends Model
{
    /**
     * The connection name for the model.
     */
    protected $connection = 'mongodb';

    /**
     * The collection associated with the model.
     */
    protected $collection = 'ai_express_conversations';
    protected $table = 'ai_express_conversations';
    protected $guarded = [];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'conversation_id',
        'user_id',
        'title',
        'messages',
        'metadata',
        'feedback_summary',
        'conversation_summary',
        'overall_convo_feedback',
        'platform',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Indicates if the model should use timestamps.
     */
    public $timestamps = true;

    /**
     * The attributes that should not be casted to native types.
     * MongoDB will handle these as native BSON types.
     */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * Add a message to the conversation.
     */
    public function addMessage(array $message): self
    {
        $messages = $this->messages ?? [];
        $messages[] = $message;
        $this->messages = $messages;
        
        // Update message count in metadata
        $metadata = $this->metadata ?? [];
        $metadata['message_count'] = count($messages);
        $this->metadata = $metadata;
        
        return $this;
    }

    /**
     * Update feedback summary.
     */
    public function updateFeedbackSummary(array $feedbackData): self
    {
        $summary = $this->feedback_summary ?? [
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
        ];

        if (isset($feedbackData['type'])) {
            if ($feedbackData['type'] === 'like') {
                $summary['overall_likes'] = ($summary['overall_likes'] ?? 0) + 1;
            } elseif ($feedbackData['type'] === 'dislike') {
                $summary['overall_dislikes'] = ($summary['overall_dislikes'] ?? 0) + 1;
                
                if (isset($feedbackData['category'])) {
                    $category = $feedbackData['category'];
                    if (isset($summary['dislike_categories'][$category])) {
                        $summary['dislike_categories'][$category]++;
                    }
                }
            }
        }

        if (isset($feedbackData['comment'])) {
            $summary['comments'][] = $feedbackData['comment'];
        }

        $summary['last_feedback_at'] = Carbon::now()->toIso8601String();
        
        $this->feedback_summary = $summary;
        
        return $this;
    }

    /**
     * Update conversation summary.
     */
    public function updateConversationSummary(array $summaryData): self
    {
        $this->conversation_summary = array_merge($this->conversation_summary ?? [], $summaryData);
        return $this;
    }

    /**
     * Get messages by role.
     */
    public function getMessagesByRole(string $role): array
    {
        return array_filter($this->messages ?? [], function ($message) use ($role) {
            return ($message['role'] ?? '') === $role;
        });
    }

    /**
     * Get the latest message.
     */
    public function getLatestMessage(): ?array
    {
        $messages = $this->messages ?? [];
        return empty($messages) ? null : end($messages);
    }

    /**
     * Scope to get conversations by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get conversations by platform.
     */
    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope to get active conversations.
     */
    public function scopeActive($query)
    {
        return $query->where('metadata.status', 'active');
    }

    /**
     * Scope to get conversations within date range.
     */
    public function scopeWithinDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get the user that owns the conversation.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if conversation is active.
     */
    public function isActive(): bool
    {
        return ($this->metadata['status'] ?? '') === 'active';
    }

    /**
     * Get total tokens used in conversation.
     */
    public function getTotalTokens(): int
    {
        return $this->metadata['total_tokens_used'] ?? 0;
    }

    /**
     * Get message count.
     */
    public function getMessageCount(): int
    {
        return $this->metadata['message_count'] ?? count($this->messages ?? []);
    }
}

