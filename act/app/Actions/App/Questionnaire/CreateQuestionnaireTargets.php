<?php

namespace App\Actions\App\Questionnaire;

use App\Models\Test;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateQuestionnaireTargets
{
    /**
     * Add new targets to a test
     */
    public static function create($user, $input): array
    {
        Log::info('CreateQuestionnaireTargets::create called', [
            'user_id' => $user->id,
            'input' => $input
        ]);
        
        $testId = $input['test_id'] ?? null;
        $targets = $input['targets'] ?? [];
        
        if (!$testId || empty($targets)) {
            throw new \Exception('Missing required fields: test_id or targets');
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
            
            Log::info('Test found for target creation', [
                'test_id' => $test->id,
                'survey_id' => $test->survey_id,
                'current_targets_count' => count($test->targets ?? [])
            ]);
            
            $currentTargets = collect($test->targets ?? []);
            $maxIndex = $currentTargets->max('index') ?? 0;
            $createdTargets = [];
            $errors = [];
            
            foreach ($targets as $targetData) {
                $targetText = $targetData['target'] ?? null;
                
                if (!$targetText) {
                    $errors[] = "Missing target text in target data";
                    continue;
                }
                
                // Auto-increment index
                $maxIndex++;
                
                $newTarget = [
                    'target' => $targetText,
                    'equivalent' => $targetData['equivalent'] ?? $targetText,
                    'index' => $maxIndex,
                    'can_unselect' => $targetData['can_unselect'] ?? false,
                    'colors' => $targetData['colors'] ?? [],
                    'value' => $targetData['value'] ?? $maxIndex,
                ];
                
                $currentTargets->push($newTarget);
                
                $createdTargets[] = [
                    'index' => $maxIndex,
                    'target' => $targetText,
                ];
            }
            
            // Sort by index and convert to array
            $targetsArray = $currentTargets->sortBy('index')->values()->toArray();
            $test->targets = $targetsArray;
            $test->save();
            
            DB::commit();
            
            Log::info('Questionnaire targets created successfully', [
                'test_id' => $test->id,
                'survey_id' => $test->survey_id,
                'created_count' => count($createdTargets),
                'error_count' => count($errors)
            ]);
            
            return [
                'created' => $createdTargets,
                'errors' => $errors,
                'total_created' => count($createdTargets),
                'total_errors' => count($errors),
                'survey_id' => $test->survey_id,
                'test_id' => $test->id
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create questionnaire targets', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}