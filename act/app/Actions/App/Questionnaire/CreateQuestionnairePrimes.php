<?php

namespace App\Actions\App\Questionnaire;

use App\Models\Test;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateQuestionnairePrimes
{
    /**
     * Add new primes to a test
     */
    public static function create($user, $input): array
    {
        Log::info('CreateQuestionnairePrimes::create called', [
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
            
            Log::info('Test found for prime creation', [
                'test_id' => $test->id,
                'survey_id' => $test->survey_id,
                'current_primes_count' => count($test->primes ?? [])
            ]);
            
            $currentPrimes = collect($test->primes ?? []);
            $maxIndex = $currentPrimes->max('index') ?? 0;
            $createdPrimes = [];
            $errors = [];
            
            foreach ($primes as $primeData) {
                $primeText = $primeData['prime'] ?? null;
                
                if (!$primeText) {
                    $errors[] = "Missing prime text in prime data";
                    continue;
                }
                
                // Auto-increment index
                $maxIndex++;
                
                $newPrime = [
                    'prime' => $primeText,
                    'equivalent' => $primeData['equivalent'] ?? $primeText,
                    'index' => $maxIndex,
                    'config' => $primeData['config'] ?? ['conditions' => []],
                ];
                
                $currentPrimes->push($newPrime);
                
                $createdPrimes[] = [
                    'index' => $maxIndex,
                    'prime' => $primeText,
                ];
            }
            
            // Sort by index and convert to array
            $primesArray = $currentPrimes->sortBy('index')->values()->toArray();
            $test->primes = $primesArray;
            $test->save();
            
            DB::commit();
            
            Log::info('Questionnaire primes created successfully', [
                'test_id' => $test->id,
                'survey_id' => $test->survey_id,
                'created_count' => count($createdPrimes),
                'error_count' => count($errors)
            ]);
            
            return [
                'created' => $createdPrimes,
                'errors' => $errors,
                'total_created' => count($createdPrimes),
                'total_errors' => count($errors),
                'survey_id' => $test->survey_id,
                'test_id' => $test->id
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create questionnaire primes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}