<?php

namespace App\Actions\App\Round;

use App\Models\Test;
use App\Models\Survey;
use App\Services\AiUsagePolicy;
use Illuminate\Support\Facades\DB;

class MoveRound
{
    /**
     * Move multiple rounds to different surveys
     */
    public static function move($user, $input): array
    {
        \Log::info('MoveRound::move called', [
            'user_id' => $user->id,
            'input' => $input
        ]);
        
        $moves = $input['moves'] ?? [$input];
        $limit = AiUsagePolicy::getActionLimit($user);
        
        $totalRequested = count($moves);
        $movedTests = [];
        $skipped = [];
        $errors = [];
        
        // Slice to limit
        $movesToProcess = array_slice($moves, 0, $limit);
        $skipped = array_slice($moves, $limit);
        
        \Log::info('Processing round moves', [
            'total_requested' => $totalRequested,
            'to_process' => count($movesToProcess),
            'to_skip' => count($skipped)
        ]);
        
        DB::beginTransaction();
        
        try {
            foreach ($movesToProcess as $moveData) {
                \Log::info('Processing round move', ['move_data' => $moveData]);
                
                // Extract move data
                $roundName = $moveData['round_name'] ?? null;
                $sourceSurveyCode = $moveData['source_survey_code'] ?? null;
                $targetSurveyCode = $moveData['target_survey_code'] ?? null;
                $targetPosition = $moveData['target_position'] ?? null;
                
                if (!$roundName || !$sourceSurveyCode || !$targetSurveyCode) {
                    $errors[] = "Missing required fields";
                    \Log::warning('Missing required fields', ['move_data' => $moveData]);
                    continue;
                }
                
                // Find source survey
                $sourceSurvey = self::findSurvey($user, $sourceSurveyCode);
                if (!$sourceSurvey) {
                    $errors[] = "Source survey not found: {$sourceSurveyCode}";
                    continue;
                }
                
                // Find the round to move
                $test = UpdateRound::findRound($sourceSurvey, $roundName);
                if (!$test) {
                    $errors[] = "Round not found: {$roundName}";
                    continue;
                }
                
                // Check if moving within the same survey (reordering)
                if ($sourceSurveyCode === $targetSurveyCode) {
                    // This is a reorder operation within the same survey
                    $oldPart = $test->part;
                    
                    if ($targetPosition) {
                        // Reorder to specific position
                        $newPart = self::getPositionNumber($sourceSurvey, $targetPosition);
                        
                        if ($newPart === null) {
                            $errors[] = "Invalid target position: {$targetPosition}";
                            continue;
                        }
                        
                        if ($newPart !== $oldPart) {
                            // Reorder the rounds
                            self::reorderRounds($sourceSurvey, $test, $oldPart, $newPart);
                            
                            \Log::info('Round reordered successfully', [
                                'test_id' => $test->id,
                                'test_name' => $test->name,
                                'old_part' => $oldPart,
                                'new_part' => $newPart
                            ]);
                        }
                    }
                    
                    $movedTests[] = [
                        'id' => $test->id,
                        'name' => $test->name,
                        'from_survey' => $sourceSurvey->name,
                        'to_survey' => $sourceSurvey->name,
                        'survey_id' => $sourceSurvey->id,
                    ];
                    continue;
                }
                
                // Find target survey (different survey)
                $targetSurvey = self::findSurvey($user, $targetSurveyCode);
                if (!$targetSurvey) {
                    $errors[] = "Target survey not found: {$targetSurveyCode}";
                    continue;
                }
                
                $oldSurveyName = $sourceSurvey->name;
                
                // Move the test to different survey
                $test->survey_id = $targetSurvey->id;
                
                // Get the highest part number in target survey
                $maxPart = Test::where('survey_id', $targetSurvey->id)->max('part') ?? 0;
                $test->part = $maxPart + 1;
                
                $test->save();
                
                \Log::info('Round moved successfully', [
                    'test_id' => $test->id,
                    'test_name' => $test->name,
                    'from_survey' => $oldSurveyName,
                    'to_survey' => $targetSurvey->name
                ]);
                
                $movedTests[] = [
                    'id' => $test->id,
                    'name' => $test->name,
                    'from_survey' => $oldSurveyName,
                    'to_survey' => $targetSurvey->name,
                    'survey_id' => $targetSurvey->id,
                ];
            }
            
            DB::commit();
            
            \Log::info('All round moves committed', [
                'moved_count' => count($movedTests),
                'error_count' => count($errors)
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to move rounds', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
        
        return [
            'items' => $movedTests,
            'skipped' => $skipped,
            'errors' => $errors,
            'total_moved' => count($movedTests),
            'total_skipped' => count($skipped),
            'total_errors' => count($errors),
            'total_requested' => $totalRequested,
        ];
    }
    
    private static function findSurvey($user, $surveyCode): ?Survey
    {
        // Try by ID first
        if (is_numeric($surveyCode)) {
            $survey = Survey::where('id', $surveyCode)
                ->where('user_id', $user->id)
                ->first();
            if ($survey) return $survey;
        }
        
        // Try by questionnaire_code
        return Survey::where('questionnaire_code', $surveyCode)
            ->where('user_id', $user->id)
            ->first();
    }
    
    /**
     * Get the numeric position for a positional word like "first", "second", "last"
     */
    private static function getPositionNumber(Survey $survey, string $position): ?int
    {
        $positionLower = strtolower(trim($position));
        
        // Get total number of rounds
        $totalRounds = Test::where('survey_id', $survey->id)->count();
        
        if ($totalRounds === 0) {
            return null;
        }
        
        // Map positional words to numbers
        switch ($positionLower) {
            case 'first':
            case '1st':
                return 1;
            case 'second':
            case '2nd':
                return 2;
            case 'third':
            case '3rd':
                return 3;
            case 'fourth':
            case '4th':
                return 4;
            case 'fifth':
            case '5th':
                return 5;
            case 'last':
                return $totalRounds;
            default:
                // Try to parse as a number
                if (is_numeric($position)) {
                    $num = (int)$position;
                    return ($num > 0 && $num <= $totalRounds) ? $num : null;
                }
                return null;
        }
    }
    
    /**
     * Reorder rounds by moving a test from oldPart to newPart
     */
    private static function reorderRounds(Survey $survey, Test $test, int $oldPart, int $newPart): void
    {
        \Log::info('Reordering rounds', [
            'survey_id' => $survey->id,
            'test_id' => $test->id,
            'old_part' => $oldPart,
            'new_part' => $newPart
        ]);
        
        // Get all tests in the survey ordered by part
        $allTests = Test::where('survey_id', $survey->id)
            ->orderBy('part')
            ->get();
        
        if ($newPart < $oldPart) {
            // Moving up (e.g., from 3 to 1)
            // Shift down all tests between newPart and oldPart
            foreach ($allTests as $t) {
                if ($t->id === $test->id) {
                    continue;
                }
                if ($t->part >= $newPart && $t->part < $oldPart) {
                    $t->part += 1;
                    $t->save();
                }
            }
        } else {
            // Moving down (e.g., from 1 to 3)
            // Shift up all tests between oldPart and newPart
            foreach ($allTests as $t) {
                if ($t->id === $test->id) {
                    continue;
                }
                if ($t->part > $oldPart && $t->part <= $newPart) {
                    $t->part -= 1;
                    $t->save();
                }
            }
        }
        
        // Finally, set the test to its new position
        $test->part = $newPart;
        $test->save();
        
        \Log::info('Reordering complete', [
            'test_id' => $test->id,
            'new_part' => $newPart
        ]);
    }
}