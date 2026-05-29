<?php

namespace App\Services;

use App\Models\ConversationMemory;
use App\Models\AI\AIExpressConversation;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Schema\ArraySchema;
use App\AI\Enums\AIModel;

class ConversationMemoryService
{
    private const CACHE_KEY_PREFIX = 'conv_mem:';
    private const MAX_BUFFER_MESSAGES = 18;
    private const MAX_BUFFER_TOKENS = 1800;
    private const MAX_SUMMARY_TOKENS = 2400;
    private const SUMMARY_COOLDOWN_MINUTES = 8;

    private const SUMMARY_TURN_MIN = 5;          
    private const SUMMARY_TURN_TARGET = 8;      
    private const TOTAL_TOKEN_THRESHOLD = 2200;  

    public function __construct(
        private readonly CacheRepository $cache,
        private ?ConversationStorageService $conversationStorage = null
    ) {}

    public function setConversationStorage(ConversationStorageService $conversationStorage): void
    {
        $this->conversationStorage = $conversationStorage;
    }

    // append a message to the cache buffer
    public function appendMessage(int $userId, ?string $platform, string $role, string $content, ?string $scope = null): void
    {
        $key = $this->makeCacheKey($userId, $platform);
        $buffer = $this->cache->get($key, []);

        $buffer[] = [
            'role' => $role,
            'content' => $content,
            'ts' => now()->toIso8601String(),
        ];

        // Trim by count first
        if (count($buffer) > self::MAX_BUFFER_MESSAGES) {
            $buffer = array_slice($buffer, -self::MAX_BUFFER_MESSAGES);
        }

        // trim by rough token budget
        $buffer = $this->trimByTokenBudget($buffer, self::MAX_BUFFER_TOKENS);

        $this->cache->put($key, $buffer, now()->addHours(6));

        $memoryContextCacheKey = 'memory_context_' . $userId . '_' . ($platform ?? 'express');
        \Illuminate\Support\Facades\Cache::forget($memoryContextCacheKey);

        $this->debug('appendMessage', [
            'user_id' => $userId,
            'platform' => $platform,
            'role' => $role,
            'content_preview' => mb_substr($content, 0, 200),
            'buffer_count' => count($buffer),
        ]);
    }

    // retrieve the combined memory context summary + buffer
    // MongoDB is primary source, SQL is backup
    public function getMemoryContext(int $userId, ?string $platform, ?string $scope = null): array
    {
        $key = $this->makeCacheKey($userId, $platform);
        $buffer = $this->cache->get($key, []);

        $summary = null;
        $summarySource = 'none';

        // Try MongoDB first (primary source)
        if ($this->conversationStorage) {
            try {
                $mongoConversation = $this->conversationStorage->getOrCreateConversation($userId, null, $platform);
                if ($mongoConversation) {
                    $conversationSummary = $mongoConversation->conversation_summary ?? [];
                    $summary = is_array($conversationSummary) ? ($conversationSummary['summary'] ?? null) : null;
                    if (!empty($summary)) {
                        $summarySource = 'mongodb';
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to fetch summary from MongoDB, falling back to SQL', [
                    'error' => $e->getMessage(),
                    'user_id' => $userId,
                    'platform' => $platform,
                ]);
            }
        }

        // Fallback to SQL if MongoDB summary not available (backup)
        if (empty($summary)) {
            try {
                $sqlSummary = ConversationMemory::query()
                    ->where('user_id', $userId)
                    ->when($platform, fn($q) => $q->where('platform', $platform))
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($sqlSummary && !empty($sqlSummary->summary)) {
                    $summary = $sqlSummary->summary;
                    $summarySource = 'sql';
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to fetch summary from SQL', [
                    'error' => $e->getMessage(),
                    'user_id' => $userId,
                    'platform' => $platform,
                ]);
            }
        }

        // console log fetched context sizes
        $this->debug('getMemoryContext', [
            'user_id' => $userId,
            'platform' => $platform,
            'has_summary' => (bool) $summary,
            'summary_source' => $summarySource,
            'buffer_count' => count($buffer),
        ]);

        return [
            'summary' => $summary,
            'buffer' => $buffer,
        ];
    }

    /**
     * Summarize if needed based on multiple triggers:
     * - Turn count since last summary (min/target bounds)
     * - Combined token threshold (summary + buffer)
     * - Topic shift detection (significant subject change)
     */
    public function summarizeIfNeeded(int $userId, ?string $platform, ?string $scope = null, ?AIExpressConversation $mongoConversation = null): void
    {
        $key = $this->makeCacheKey($userId, $platform);
        $buffer = $this->cache->get($key, []);

        if (empty($buffer)) {
            return;
        }

        $existingSummary = '';
        $lastSummaryAt = null;
        
        if ($this->conversationStorage) {
            try {
                $mongoConversation = $this->conversationStorage->getOrCreateConversation($userId, null, $platform);
                if ($mongoConversation) {
                    $conversationSummary = $mongoConversation->conversation_summary ?? [];
                    $existingSummary = is_array($conversationSummary) ? ($conversationSummary['summary'] ?? '') : '';
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to fetch existing summary from MongoDB for trigger calculation', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        $memory = null;
        if (empty($existingSummary)) {
            try {
                $memory = ConversationMemory::query()
                    ->where('user_id', $userId)
                    ->when($platform, fn($q) => $q->where('platform', $platform))
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($memory) {
                    $existingSummary = $memory->summary ?? '';
                    $lastSummaryAt = $memory->last_summary_at ? Carbon::parse($memory->last_summary_at) : null;
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to fetch existing summary from SQL for trigger calculation', [
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            try {
                $memory = ConversationMemory::query()
                    ->where('user_id', $userId)
                    ->when($platform, fn($q) => $q->where('platform', $platform))
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($memory) {
                    $lastSummaryAt = $memory->last_summary_at ? Carbon::parse($memory->last_summary_at) : null;
                }
            } catch (\Throwable $e) {
            }
        }

        $turnsSince = $this->countTurnsSince($buffer, $lastSummaryAt);

        $bufferText = $this->bufferToText($buffer);
        $combinedForTokens = $existingSummary . "\n\n" . $bufferText;
        $combinedTokenEstimate = $this->estimateTokens($combinedForTokens);
        $topicShiftDetected = $this->detectTopicShift($existingSummary, $buffer);

        $cooldownOk = true;
        if ($lastSummaryAt) {
            $cooldownOk = $lastSummaryAt->clone()
                ->addMinutes(self::SUMMARY_COOLDOWN_MINUTES)
                ->isPast();
        }

        $turnTrigger = $turnsSince >= self::SUMMARY_TURN_TARGET || ($turnsSince >= self::SUMMARY_TURN_MIN && $cooldownOk);
        $tokenTrigger = $combinedTokenEstimate >= self::TOTAL_TOKEN_THRESHOLD;
        $topicTrigger = $topicShiftDetected && $cooldownOk;

        $shouldSummarize = $turnTrigger || $tokenTrigger || $topicTrigger;

        if (!$shouldSummarize) {
            $this->debug('summarizeIfNeeded.skip', [
                'user_id' => $userId,
                'platform' => $platform,
                'turns_since' => $turnsSince,
                'combined_tokens' => $combinedTokenEstimate,
                'cooldown_ok' => $cooldownOk,
                'topic_shift' => $topicShiftDetected,
            ]);
            return;
        }

        $this->debug('summarizeIfNeeded.start', [
            'user_id' => $userId,
            'platform' => $platform,
            'turns_since' => $turnsSince,
            'combined_tokens' => $combinedTokenEstimate,
            'cooldown_ok' => $cooldownOk,
            'topic_shift' => $topicShiftDetected,
            'trigger' => $topicTrigger ? 'topic' : ($tokenTrigger ? 'tokens' : 'turns'),
        ]);

        $combined = $this->composeForSummarization($existingSummary, $buffer);
        $summaryData = $this->summarizeStructured($combined, $mongoConversation);
        $summary = $summaryData['summary'];
        $keyTopics = $summaryData['key_topics'] ?? [];

        if ($this->estimateTokens($summary) > self::MAX_SUMMARY_TOKENS) {
            $summary = $this->summarizeText($summary);
        }

        $tokenCount = $this->estimateTokens($summary);
        $model = new ConversationMemory([
            'user_id' => $userId,
            'platform' => $platform,
            'summary' => $summary,
            'token_count' => $tokenCount,
            'last_summary_at' => now(),
        ]);
        $model->save();

        if ($mongoConversation) {
            try {
                $mongoConversation->refresh();
                $mongoConversation->updateConversationSummary([
                    'summary' => $summary,
                    'key_topics' => $keyTopics,
                ]);
                $mongoConversation->save();
            } catch (\Throwable $e) {
                Log::warning('Failed to save summary to MongoDB', ['error' => $e->getMessage()]);
            }
        }

        $tail = array_slice($buffer, -4);
        $this->cache->put($key, $tail, now()->addHours(1));

        $this->debug('summarizeIfNeeded.done', [
            'user_id' => $userId,
            'platform' => $platform,
            'memory_id' => $model->id,
            'summary_token_count' => $tokenCount,
            'summary_preview' => mb_substr($summary, 0, 300),
            'tail_count' => count($tail),
            'mongo_saved' => $mongoConversation !== null,
        ]);
    }

    // build a concise context string that can be injected into prompts.
    public function renderContextString(int $userId, ?string $platform, ?string $scope = null): ?string
    {
        $ctx = $this->getMemoryContext($userId, $platform);
        $parts = [];

        if (!empty($ctx['summary'])) {
            $parts[] = "Conversation summary so far: \n" . $ctx['summary'];
        }

        if (!empty($ctx['buffer'])) {
            $parts[] = "Recent turns: \n" . $this->bufferToText($ctx['buffer']);
        }

        return empty($parts) ? null : implode("\n\n", $parts);
    }

    // buffer to text
    private function bufferToText(array $buffer): string
    {
        return collect($buffer)
            ->map(fn($m) => strtoupper($m['role']) . ': ' . $m['content'])
            ->implode("\n");
    }

    private function summarizeStructured(string $text, ?AIExpressConversation $mongoConversation = null): array
    {
        try {
            $messages = $mongoConversation?->messages ?? [];
            $recentMessages = is_array($messages) ? array_slice($messages, -12) : [];
            
            $messageLines = [];
            if (!empty($recentMessages)) {
                foreach ($recentMessages as $m) {
                    if (is_object($m)) { $m = (array) $m; }
                    $role = $m['role'] ?? 'user';
                    $content = $m['content'] ?? '';
                    $content = trim(preg_replace('/\s+/', ' ', (string) $content));
                    $messageLines[] = strtoupper($role) . ': ' . mb_substr($content, 0, 800);
                }
            }
            
            $contextText = !empty($messageLines) 
                ? "Conversation (most recent first):\n" . implode("\n", $messageLines) . "\n\nExisting summary:\n" . $text
                : $text;

            $systemPrompt = "You are an assistant that writes ultra-concise chat summaries for product UI.\n" .
                "Output a short summary (<= 60 words), key topics as short phrases.\n" .
                "Do not use markdown. Use neutral, helpful tone. Avoid usernames and timestamps.";

            $prompt = $contextText . "\n\nSummarize the conversation now. " .
    "Be sure to mention any questions, surveys, or components that were created, including their type and topic.";

            $schema = new ObjectSchema(
                name: 'conversation_summary',
                description: 'A compact summary of a multi-turn chat, including topics.',
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

            if (empty($summary)) {
                return $this->fallbackSummarize($text);
            }

            return [
                'summary' => $summary,
                'key_topics' => $keyTopics,
            ];
        } catch (\Throwable $e) {
            Log::warning('ConversationMemory structured summarize failed', ['error' => $e->getMessage()]);
            return $this->fallbackSummarize($text);
        }
    }

    private function summarizeText(string $text): string
    {
        try {
            $prompt = "Summarize the following conversation content into a concise, factual memory that preserves decisions, preferences, names and important context. Use neutral tone.\n\n" . $text;
            $reply = Prism::text()
                ->using(...AIModel::getSummaryConfig())
                ->withPrompt($prompt)
                ->asText();
            return trim($reply->text);
        } catch (\Throwable $e) {
            Log::warning('ConversationMemory summarize failed', ['error' => $e->getMessage()]);
            return mb_substr($text, 0, 4000);
        }
    }

    private function fallbackSummarize(string $text): array
    {
        $summary = $this->summarizeText($text);
        return [
            'summary' => $summary,
            'key_topics' => [],
        ];
    }

    // summary composer
    private function composeForSummarization(string $existingSummary, array $buffer): string
    {
        $parts = [];
        if (!empty($existingSummary)) {
            $parts[] = "Existing summary:\n" . $existingSummary;
        }
        $parts[] = "New recent turns:\n" . $this->bufferToText($buffer);
        return implode("\n\n", $parts);
    }

    // token estimator
    private function estimateTokens(string $text): int
    {
        return (int) ceil(mb_strlen($text) / 4);
    }

    // Trim a buffer array so that combined text stays within a token budget.
    private function trimByTokenBudget(array $buffer, int $maxTokens): array
    {
        $trimmed = $buffer;
        while (!empty($trimmed) && $this->estimateTokens($this->bufferToText($trimmed)) > $maxTokens) {
            array_shift($trimmed);
        }
        return $trimmed;
    }

    private function makeCacheKey(int $userId, ?string $platform, ?string $scope = null): string
    {
        $parts = [self::CACHE_KEY_PREFIX, $userId, $platform ?: 'any'];
        return implode(':', $parts);
    }

     //Clear conversation memory
    public function clearMemory(int $userId, ?string $platform, ?string $scope = null): void
    {
        // Delete DB summaries
        ConversationMemory::query()
            ->where('user_id', $userId)
            ->when($platform, fn($q) => $q->where('platform', $platform))
            ->delete();

        // Forget cached buffers
        $this->cache->forget($this->makeCacheKey($userId, $platform));

        $this->debug('clearMemory', [
            'user_id' => $userId,
            'platform' => $platform,
        ]);
    }

    //Count messages whose timestamp is after the provided time.
    private function countTurnsSince(array $buffer, ?Carbon $since): int
    {
        if (!$since) {
            return count($buffer);
        }
        $count = 0;
        foreach ($buffer as $m) {
            try {
                if (!empty($m['ts']) && Carbon::parse($m['ts'])->greaterThan($since)) {
                    $count++;
                }
            } catch (\Throwable $e) {
                // ignore bad timestamp
            }
        }
        return $count;
    }

    // change topic detector
    private function detectTopicShift(string $existingSummary, array $buffer): bool
    {
        try {
            $recent = array_slice($buffer, -6);
            $recentText = $this->bufferToText($recent);
            $prompt = "You will receive an existing conversation summary (may be empty) and the most recent turns. Reply with 'YES' if the latest user/assistant messages indicate a significant topic change from the existing summary (new subject, goal, or domain), otherwise reply with 'NO'.\n\nExisting summary:\n" . ($existingSummary ?: '[none]') . "\n\nRecent turns:\n" . $recentText;
            $reply = Prism::text()
                ->using(...AIModel::getDefaultConfig())
                ->withPrompt($prompt)
                ->asText();
            $text = strtoupper(trim($reply->text));
            return str_contains($text, 'YES');
        } catch (\Throwable $e) {
            // On failure, default to no topic shift to avoid over-summarizing
            Log::info('[ConversationMemory] topicShift.detect_failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function debug(string $event, array $context = []): void
    {
        try {
            if (config('app.debug') || env('CONV_MEMORY_DEBUG')) {
                Log::info('[ConversationMemory] ' . $event, $context);
            }
        } catch (\Throwable $e) {
            // no fail due to logging
        }
    }
}

