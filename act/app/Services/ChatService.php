<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use App\AI\Enums\AIModel;

class ChatService
{
    private const QUESTION_WORDS = [
        'how', 'what', 'when', 'where', 'why', 'who',
        'can', 'could', 'would', 'should', 'will', 'may', 'might',
        'is', 'are', 'do', 'does', 'did', 'was', 'were',
        'have', 'has', 'had', 'am', 'be', 'been', 'being'
    ];

    private const CACHE_TTL_DAYS = 30;

    /**
     * Check if a message is a question
     */
    public function isQuestion(string $message): bool
    {
        $message = trim($message);
        
        // Check for question mark at the end
        if (str_ends_with($message, '?')) {
            return true;
        }
        
        // Check for question words at the beginning or after spaces
        $questionWordFound = collect(self::QUESTION_WORDS)->contains(function($word) use ($message) {
            return stripos($message, $word . ' ') === 0 || 
                   stripos($message, ' ' . $word . ' ') !== false;
        });
        
        if ($questionWordFound) {
            return true;
        }
        
        // common question patterns
        $questionPatterns = [
            '/^(tell me|explain|describe|show me|help me)/i',
            '/^(i need|i want|i would like)/i',
            '/^(please|can you|could you|would you)/i',
            '/^(is there|are there|do you have)/i',
            '/^(what is|what are|how do|how can|how does)/i',
            '/^(where is|where are|when is|when are)/i',
            '/^(why is|why are|why do|why does)/i',
            '/^(how about|what about)/i',
            '/^(tell me about|explain about)/i',
            '/^(can you tell me|could you tell me)/i',
            '/^(do you know|do you have information)/i'
        ];
        
        foreach ($questionPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get embedding for message with caching
     */
    public function getEmbedding(string $message): array
    {
        $cacheKey = 'embedding_' . md5(strtolower(trim($message)));
        return Cache::remember($cacheKey, now()->addDays(self::CACHE_TTL_DAYS), function() use ($message, $cacheKey) {
            $this->logCacheMiss($message, $cacheKey);

            $response = Prism::embeddings()
                ->using(...AIModel::getEmbeddingConfig())
                ->fromInput($message)
                ->asEmbeddings();
            return $response->embeddings[0]->embedding;
        });
    }

    public function formatGuideResponse(array $payload): string
    {
        if (isset($payload['type']) && $payload['type'] === 'faq') {
            return "Q: {$payload['question']}<br><br>A: {$payload['answer']}";
        }
        return $payload['text'] ?? 'No relevant guide information found.';
    }

    public function clearEmbeddingCache(): void
    {
        $keys = Cache::get('embedding_keys', []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget('embedding_keys');
    }

    public function getCacheStats(): array
    {
        $keys = Cache::get('embedding_keys', []);
        $stats = [
            'total_cached' => count($keys),
            'cache_size' => 0,
        ];

        foreach ($keys as $key) {
            if ($value = Cache::get($key)) {
                $stats['cache_size'] += strlen(serialize($value));
            }
        }

        return $stats;
    }

    private function logCacheMiss(string $message, string $cacheKey): void
    {
        Log::info('Embedding cache miss', [
            'question' => $message,
            'cache_key' => $cacheKey
        ]);
    }
} 