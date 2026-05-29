<?php

namespace App\Http\Controllers\API\Concerns;

use App\Models\AI\MongoTokenManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait ChatControllerCoreHelpers
{
    private function getUserTypeInfo(Request $request): array
    {
        $user = $request->user();
        if (!$user) {
            return ['type' => 'normal', 'firstName' => null];
        }

        $userEmail = $user->email;
        $firstName = $user->name ? trim(explode(' ', $user->name)[0]) : null;
        $devNames = config('ai_chat.user_roles.developers.names', []);
        $qaNames = config('ai_chat.user_roles.qa.names', []);
        
        if ($firstName && in_array($firstName, $devNames)) {
            Log::info('[getUserTypeInfo] Developer detected via name', [
                'firstName' => $firstName,
                'email' => $userEmail
            ]);
            return ['type' => 'developer', 'firstName' => $firstName];
        }
        if ($firstName && in_array($firstName, $qaNames)) {
            Log::info('[getUserTypeInfo] QA detected via name', [
                'firstName' => $firstName,
                'email' => $userEmail
            ]);
            return ['type' => 'qa', 'firstName' => $firstName];
        }
        
        $devEmails = config('ai_chat.user_roles.developers.emails', []);
        $qaEmails = config('ai_chat.user_roles.qa.emails', []);
        
        if ($userEmail && in_array($userEmail, $devEmails)) {
            Log::info('[getUserTypeInfo] Developer detected via email (name not in lists)', [
                'email' => $userEmail,
                'firstName' => $firstName
            ]);
            return ['type' => 'developer', 'firstName' => $firstName];
        }
        
        if ($userEmail && in_array($userEmail, $qaEmails)) {
            Log::info('[getUserTypeInfo] QA detected via email (name not in lists)', [
                'email' => $userEmail,
                'firstName' => $firstName
            ]);
            return ['type' => 'qa', 'firstName' => $firstName];
        }
        
        Log::info('[getUserTypeInfo] Normal user (not found in name or email lists)', [
            'email' => $userEmail,
            'firstName' => $firstName
        ]);
        
        return ['type' => 'normal', 'firstName' => $firstName];
    }

    private function buildSystemPrompt(string $basePrompt, string $userType, ?string $firstName, ?string $memoryContext = null): string
    {
        $prompt = $basePrompt;
        
        if ($firstName && $userType === 'normal') {
            $prompt .= str_replace('{firstName}', $firstName, config('ai_prompts.name_addressing.normal', ''));
        }
        
        if ($userType === 'developer') {
            $prompt .= config('ai_prompts.personalities.developer', '');
        } elseif ($userType === 'qa') {
            $prompt .= config('ai_prompts.personalities.qa', '');
        }
        
        if ($memoryContext) {
            $prompt .= "\n\nContext:\n" . $memoryContext;
        }
        
        return $prompt;
    }

    private function estimateTokens(string $text): int
    {
        $length = mb_strlen($text ?? '');
        if ($length === 0) {
            return 0;
        }
        // Heuristic: ~4 chars per token on average for English-like text.
        return (int) ceil($length / 4);
    }

    private function hasTokenRemaining(int $userId, int $neededTokens): bool
    {
        $dailyLimit = config('ai_chat.daily_token_limit', 25000);
        
        try {
            $record = MongoTokenManager::where('user_id', $userId)->first();
            if (!$record) {
                return $neededTokens <= $dailyLimit;
            }

            $currentUsage = (int) ($record->overall_token_used ?? 0);
           
            return ($currentUsage + $neededTokens) <= $dailyLimit;
        } catch (\Throwable $e) {
            Log::warning('Token check failed', ['error' => $e->getMessage()]);
            return true;
        }
    }

    private function markUserMessageUnanswered(Request $request): void
    {
        try {
            $mongoConversation = $request->input('_mongo_conversation');
            
            if (!$mongoConversation) {
                $user = $request->user();
                if ($user) {
                    // Only get existing conversation, don't auto-create
                    $requestedConversationId = $request->input('conversation_id');
                    if ($requestedConversationId) {
                        $mongoConversation = $this->conversationStorage->getOrCreateConversation(
                            $user->id,
                            $requestedConversationId
                        );
                    }
                }
            }
            
            if ($mongoConversation) {
                $this->conversationStorage->markLastUserMessageAsUnanswered($mongoConversation);
                Log::info('Marked user message as unanswered', [
                    'conversation_id' => $mongoConversation->conversation_id ?? 'unknown'
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to mark message as unanswered', ['error' => $e->getMessage()]);
        }
    }

    private function isOffTopicResponse(string $response): bool
    {
        $offTopicPhrases = config('ai_chat.off_topic_phrases', []);
        $lowerResponse = strtolower($response);

        foreach ($offTopicPhrases as $phrase) {
            if (str_contains($lowerResponse, strtolower($phrase))) {
                return true;
            }
        }
        
        return false;
    }
}
