<?php

namespace App\Actions\App\Questionnaire;

use App\Models\Test;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateQuestionnaireTargets
{
    /**
     * Update existing targets in a test
     */
    public static function update($user, $input): array
    {
        Log::info('UpdateQuestionnaireTargets::update called', [
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
            
            Log::info('Test found for target update', [
                'test_id' => $test->id,
                'survey_id' => $test->survey_id,
                'current_targets_count' => count($test->targets ?? [])
            ]);
            
            $currentTargets = collect($test->targets ?? []);
            $updatedTargets = [];
            $errors = [];
            
            foreach ($targets as $targetData) {
                $identifier = $targetData['identifier'] ?? $targetData['index'] ?? null;
                $targetText = $targetData['target'] ?? null;
                
                if ($identifier === null || $targetText === null) {
                    $errors[] = "Missing identifier/index or target text in target update";
                    continue;
                }
                
                // Find existing target by identifier (can be index or equivalent text)
                $existingTarget = null;
                $targetIndex = null;
                
                // Check if identifier is numeric (index)
                if (is_numeric($identifier)) {
                    $targetIndex = (int)$identifier;
                    $existingTarget = $currentTargets->firstWhere('index', $targetIndex);
                } else {
                    // Try to find by equivalent text (case-insensitive)
                    $identifierLower = strtolower(trim($identifier));
                    $existingTarget = $currentTargets->first(function($target) use ($identifierLower) {
                        $equivalent = strtolower(trim($target['equivalent'] ?? ''));
                        $targetText = strtolower(trim($target['target'] ?? ''));
                        return $equivalent === $identifierLower || 
                               $targetText === $identifierLower ||
                               str_contains($equivalent, $identifierLower) ||
                               str_contains($identifierLower, $equivalent);
                    });
                    
                    if ($existingTarget) {
                        $targetIndex = (int)($existingTarget['index'] ?? 0);
                    }
                }
                
                if ($existingTarget && $targetIndex !== null) {
                    // Update existing target
                    $currentTargets = $currentTargets->map(function($target) use ($targetIndex, $targetText, $targetData, $existingTarget) {
                        if ((int)($target['index'] ?? 0) === $targetIndex) {
                            return array_merge($target, [
                                'target' => $targetText,
                                'equivalent' => $targetData['equivalent'] ?? $targetText,
                                'can_unselect' => $targetData['can_unselect'] ?? $target['can_unselect'] ?? false,
                                'colors' => $targetData['colors'] ?? $target['colors'] ?? [],
                                'value' => $targetData['value'] ?? $target['value'] ?? $targetIndex,
                            ]);
                        }
                        return $target;
                    });
                    
                    $updatedTargets[] = [
                        'index' => $targetIndex,
                        'target' => $targetText,
                        'old_target' => $existingTarget['target'] ?? '',
                        'old_equivalent' => $existingTarget['equivalent'] ?? '',
                        'action' => 'updated'
                    ];
                } else {
                    $errors[] = "Target not found with identifier: {$identifier}";
                }
            }
            
            // Convert back to array and sort by index
            $targetsArray = $currentTargets->sortBy('index')->values()->toArray();
            $test->targets = $targetsArray;
            $test->save();
            
            DB::commit();
            
            Log::info('Questionnaire targets updated successfully', [
                'test_id' => $test->id,
                'survey_id' => $test->survey_id,
                'updated_count' => count($updatedTargets),
                'error_count' => count($errors)
            ]);
            
            return [
                'updated' => $updatedTargets,
                'errors' => $errors,
                'total_updated' => count($updatedTargets),
                'total_errors' => count($errors),
                'survey_id' => $test->survey_id,
                'test_id' => $test->id
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update questionnaire targets', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}