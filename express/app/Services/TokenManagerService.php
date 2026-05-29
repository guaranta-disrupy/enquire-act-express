<?php

namespace App\Services;

use App\Models\TokenManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TokenManagerService
{
    /**
     * Daily limit per user.
     * Keep small, configurable, and safe by default.
     */
    private int $dailyLimit;

    public function __construct(?int $dailyLimit = null)
    {
        $this->dailyLimit = $dailyLimit ?? (int) (config('prism.daily_token_limit', 25000));
    }

    /**
     * Return tokens used today for the user.
     */
    public function getUsedToday(int $userId): int
    {
        $today = Carbon::today()->toDateString();
        $row = TokenManager::query()
            ->where('user_id', $userId)
            ->where('date', $today)
            ->first();
        return (int) ($row->used_tokens ?? 0);
    }

    /**
     * Check if the user has enough tokens remaining today.
     */
    public function hasRemaining(int $userId, int $neededTokens): bool
    {
        return ($this->dailyLimit - $this->getUsedToday($userId)) >= $neededTokens;
    }

    /**
     * Record usage safely (upsert per day) and clamp to non-negative.
     */
    public function recordUsage(int $userId, int $tokens): void
    {
        if ($tokens <= 0) {
            return;
        }

        $today = Carbon::today()->toDateString();

        try {
            DB::transaction(function () use ($userId, $today, $tokens) {
                $row = TokenManager::query()
                    ->lockForUpdate()
                    ->where('user_id', $userId)
                    ->where('date', $today)
                    ->first();

                if (!$row) {
                    TokenManager::query()->create([
                        'user_id' => $userId,
                        'date' => $today,
                        'used_tokens' => max(0, $tokens),
                    ]);
                    return;
                }

                $row->used_tokens = max(0, (int) $row->used_tokens + $tokens);
                $row->save();
            });
        } catch (\Throwable $e) {
            // Do not block user flow on accounting errors
            Log::warning('TokenManager recordUsage failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);
        }
    }

    /**
     * Estimate tokens for a given string quickly (heuristic, safe, and dependency-free).
     * This avoids hard errors from external tokenizers.
     */
    public function estimateTokens(string $text): int
    {
        $length = mb_strlen($text ?? '');
        if ($length === 0) {
            return 0;
        }
        // Heuristic: ~4 chars per token on average for English-like text.
        return (int) ceil($length / 4);
    }

    //Expose limit for UI or messages.
    public function getDailyLimit(): int
    {
        return $this->dailyLimit;
    }
}


