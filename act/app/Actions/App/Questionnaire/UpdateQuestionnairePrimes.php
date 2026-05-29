<?php

namespace App\Actions\App\Questionnaire;

use App\Models\Test;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateQuestionnairePrimes
{
    /**
     * Update existing primes in a test
     */
    public static function update($user, $input): array
    {
        Log::info('UpdateQuestionnairePrimes::update called', [
            'user_id' => $user->id,
            'input' => $input
        ]);
        
        $testId = $input['test_id'] ?? null;
        $primes = $input['primes'] ?? [];
        
        if (!$testId || empty($primes)) {
            throw new \Exception('Missing required fields: test_id or primes');
        }
        
        // Convert test_id to integer if it's a string
        $testId = is_numeric($testId) ? (int)$testId : $testId;
        
        DB::beginTransaction();
        
        try {
            // Find the test
            $test = Test::where('id', $testId)
                ->where('user_id', $user->id)
                ->first();
            
            if (!$test) {
                throw new \Exception("Test not found with id: {$testId}");
            }
            
            Log::info('Test found for prime update', [
                'test_id' => $test->id,
                'survey_id' => $test->survey_id,
                'current_primes_count' => count($test->primes ?? [])
            ]);
            
            $currentPrimes = collect($test->primes ?? []);
            $updatedPrimes = [];
            $errors = [];
            
            foreach ($primes as $primeData) {
                $identifier = $primeData['identifier'] ?? $primeData['index'] ?? null;
                $primeText = $primeData['prime'] ?? null;
                
                if ($identifier === null || $primeText === null) {
                    $errors[] = "Missing identifier/index or prime text in prime update";
                    continue;
                }
                
                // Find existing prime by identifier (can be index or equivalent text)
                $existingPrime = null;
                $primeIndex = null;
                
                // Check if identifier is numeric (index)
                if (is_numeric($identifier)) {
                    $primeIndex = (int)$identifier;
                    $existingPrime = $currentPrimes->firstWhere('index', $primeIndex);
                } else {
                    // Try to find by equivalent text (case-insensitive)
                    $identifierLower = strtolower(trim($identifier));
                    $existingPrime = $currentPrimes->first(function($prime) use ($identifierLower) {
                        $equivalent = strtolower(trim($prime['equivalent'] ?? ''));
                        $primeText = strtolower(trim($prime['prime'] ?? ''));
                        return $equivalent === $identifierLower || 
                               $primeText === $identifierLower ||
                               str_contains($equivalent, $identifierLower) ||
                               str_contains($identifierLower, $equivalent);
                    });
                    
                    if ($existingPrime) {
                        $primeIndex = (int)($existingPrime['index'] ?? 0);
                    }
                }
                
                if ($existingPrime && $primeIndex !== null) {
                    // Update existing prime
                    $currentPrimes = $currentPrimes->map(function($prime) use ($primeIndex, $primeText, $primeData, $existingPrime) {
                        if ((int)($prime['index'] ?? 0) === $primeIndex) {
                            return array_merge($prime, [
                                'prime' => $primeText,
                                'equivalent' => $primeData['equivalent'] ?? $primeText,
                                'config' => $primeData['config'] ?? $prime['config'] ?? ['conditions' => []],
                            ]);
                        }
                        return $prime;
                    });
                    
                    $updatedPrimes[] = [
                        'index' => $primeIndex,
                        'prime' => $primeText,
                        'old_prime' => $existingPrime['prime'] ?? '',
                        'old_equivalent' => $existingPrime['equivalent'] ?? '',
                        'action' => 'updated'
                    ];
                } else {
                    $errors[] = "Prime not found with identifier: {$identifier}";
                }
            }
            
            // Convert back to array and sort by index
            $primesArray = $currentPrimes->sortBy('index')->values()->toArray();
            $test->primes = $primesArray;
            $test->save();
            
            DB::commit();
            
            Log::info('Questionnaire primes updated successfully', [
                'test_id' => $test->id,
                'survey_id' => $test->survey_id,
                'updated_count' => count($updatedPrimes),
                'error_count' => count($errors)
            ]);
            
            return [
                'updated' => $updatedPrimes,
                'errors' => $errors,
                'total_updated' => count($updatedPrimes),
                'total_errors' => count($errors),
                'survey_id' => $test->survey_id,
                'test_id' => $test->id
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update questionnaire primes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}