<?php

namespace App\Actions\App\Round;

use App\Models\Test;
use App\Models\Survey;
use App\Services\AiUsagePolicy;
use Illuminate\Support\Facades\DB;

class UpdateRound
{
    /**
     * Update multiple rounds/tests
     */
    public static function update($user, $input): array
    {
        \Log::info('UpdateRound::update called', [
            'user_id' => $user->id,
            'input' => $input
        ]);
        
        $updates = $input['updates'] ?? [$input]; // Support batch or single round update
        $limit = AiUsagePolicy::getActionLimit($user);
        
        $totalRequested = count($updates);
        $updatedTests = [];
        $skipped = [];
        $errors = [];
        
        // Slice to limit
        $updatesToProcess = array_slice($updates, 0, $limit);
        $skipped = array_slice($updates, $limit);
        
        \Log::info('Processing round updates', [
            'total_requested' => $totalRequested,
            'to_process' => count($updatesToProcess),
            'to_skip' => count($skipped)
        ]);
        
        DB::beginTransaction();
        
        try {
            foreach ($updatesToProcess as $updateData) {
                \Log::info('Processing round update', ['update_data' => $updateData]);
                
                // Extract update data
                $surveyCode = $updateData['survey_code'] ?? null;
                $roundName = $updateData['round_name'] ?? null;
                $newName = $updateData['new_name'] ?? null;
                $mainHeader = $updateData['main_header'] ?? null;
                $bottomText = $updateData['bottom_text'] ?? null;
                $roundType = $updateData['round_type'] ?? null;
                
                if (!$surveyCode || !$roundName) {
                    $errors[] = "Missing required fields: survey_code or round_name";
                    \Log::warning('Missing required fields', ['update_data' => $updateData]);
                    continue;
                }
                
                // Find the survey
                $survey = null;
                
                // Try using survey_code as ID first
                if (is_numeric($surveyCode)) {
                    $survey = Survey::where('id', $surveyCode)
                        ->where('user_id', $user->id)
                        ->first();
                }
                
                // If not found, try by questionnaire_code
                if (!$survey) {
                    $survey = Survey::where('questionnaire_code', $surveyCode)
                        ->where('user_id', $user->id)
                        ->first();
                }
                
                if (!$survey) {
                    $errors[] = "Survey not found with code: {$surveyCode}";
                    \Log::warning('Survey not found', ['survey_code' => $surveyCode]);
                    continue;
                }
                
                \Log::info('Survey found', [
                    'survey_id' => $survey->id,
                    'survey_name' => $survey->name
                ]);
                
                // Find the round/test
                $test = self::findRound($survey, $roundName);
                
                if (!$test) {
                    $errors[] = "Round not found: {$roundName} in survey {$survey->name}";
                    \Log::warning('Round not found', [
                        'round_name' => $roundName,
                        'survey_id' => $survey->id
                    ]);
                    continue;
                }
                
                \Log::info('Round found', [
                    'test_id' => $test->id,
                    'test_name' => $test->name,
                    'current_config' => $test->config
                ]);
                
                // Update the round
                $updated = false;
                $changes = [];
                
                // Update name if provided
                if ($newName !== null && $newName !== $test->name) {
                    $test->name = $newName;
                    $updated = true;
                    $changes[] = "name: '{$test->name}' → '{$newName}'";
                }
                
                // Update main_header if provided
                if ($mainHeader !== null) {
                    $config = $test->config ?? [];
                    
                    // Ensure main_header is wrapped in <p> tags if not already
                    if (!str_starts_with($mainHeader, '<p>')) {
                        $mainHeader = '<p>' . $mainHeader . '</p>';
                    }
                    
                    $oldHeader = $config['main_header'] ?? null;
                    $config['main_header'] = $mainHeader;
                    $test->config = $config;
                    $updated = true;
                    $changes[] = "main_header: " . ($oldHeader ? "'{$oldHeader}'" : 'null') . " → '{$mainHeader}'";
                }
                
                // Update bottom_text if provided
                if ($bottomText !== null) {
                    $config = $test->config ?? [];
                    
                    $oldBottomText = $config['bottom_text'] ?? null;
                    $config['bottom_text'] = $bottomText;
                    $test->config = $config;
                    $updated = true;
                    $changes[] = "bottom_text: " . ($oldBottomText ? "'{$oldBottomText}'" : 'null') . " → '{$bottomText}'";
                }
                
                // Convert round type if provided
                if ($roundType !== null) {
                    $config = $test->config ?? [];
                    
                    // Reset all round type flags
                    $config['practice_trial'] = false;
                    $config['main_trial'] = false;
                    $config['dummy_trial'] = false;
                    
                    // Set the appropriate flag based on type
                    switch ($roundType) {
                        case 'practice':
                            $config['practice_trial'] = true;
                            $config['main_trial'] = false;
                            $changes[] = "round_type: converted to practice round";
                            break;
                        case 'main':
                            $config['practice_trial'] = false;
                            $config['main_trial'] = true;
                            $changes[] = "round_type: converted to main round";
                            break;
                        case 'descriptive':
                            $config['practice_trial'] = false;
                            $config['main_trial'] = false;
                            // Descriptive rounds have type='descriptive' in config
                            if (!isset($config['type']) || $config['type'] !== 'descriptive') {
                                $config['type'] = 'descriptive';
                            }
                            $changes[] = "round_type: converted to descriptive round";
                            break;
                    }
                    
                    $test->config = $config;
                    $updated = true;
                }
                
                if ($updated) {
                    $test->save();
                    
                    \Log::info('Round updated successfully', [
                        'test_id' => $test->id,
                        'changes' => $changes
                    ]);
                    
                    $updatedTests[] = [
                        'id' => $test->id,
                        'name' => $test->name,
                        'survey_id' => $survey->id,
                        'survey_name' => $survey->name,
                        'changes' => $changes
                    ];
                } else {
                    \Log::info('No changes made to round', ['test_id' => $test->id]);
                }
            }
            
            DB::commit();
            
            \Log::info('All round updates committed', [
                'updated_count' => count($updatedTests),
                'error_count' => count($errors)
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update rounds', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
        
        return [
            'updated' => $updatedTests,
            'skipped' => $skipped,
            'errors' => $errors,
            'total_updated' => count($updatedTests),
            'total_skipped' => count($skipped),
            'total_errors' => count($errors)
        ];
    }
    
    /**
     * Find a round by name or position
     */
    public static function findRound(Survey $survey, string $roundName): ?Test
    {
        $roundNameLower = strtolower(trim($roundName));
        
        // Get all tests for this survey ordered by part (round number)
        $tests = Test::where('survey_id', $survey->id)
            ->orderBy('part')
            ->get();
        
        if ($tests->isEmpty()) {
            return null;
        }
        
        // Check for positional references
        switch ($roundNameLower) {
            case 'first':
            case '1st':
                return $tests->first();
                
            case 'second':
            case '2nd':
                return $tests->skip(1)->first();
                
            case 'third':
            case '3rd':
                return $tests->skip(2)->first();
                
            case 'fourth':
            case '4th':
                return $tests->skip(3)->first();
                
            case 'fifth':
            case '5th':
                return $tests->skip(4)->first();
                
            case 'last':
                return $tests->last();
                
            case 'second to last':
            case 'second last':
            case 'penultimate':
                return $tests->count() > 1 ? $tests->reverse()->skip(1)->first() : null;
        }
        
        // Try exact name match (case-insensitive)
        $exactMatch = $tests->first(function ($test) use ($roundNameLower) {
            return strtolower($test->name) === $roundNameLower;
        });
        
        if ($exactMatch) {
            return $exactMatch;
        }
        
        // Try partial name match (case-insensitive)
        $partialMatch = $tests->first(function ($test) use ($roundNameLower) {
            return str_contains(strtolower($test->name), $roundNameLower);
        });
        
        return $partialMatch;
    }
}
