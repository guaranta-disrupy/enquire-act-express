<?php

namespace App\Actions\App\Round;

use App\Models\Test;
use App\Models\Survey;
use App\Services\AiUsagePolicy;
use Illuminate\Support\Facades\DB;

class CopyRound
{
    /**
     * Copy/duplicate multiple rounds
     */
    public static function copy($user, $input): array
    {
        \Log::info('CopyRound::copy called', [
            'user_id' => $user->id,
            'input' => $input
        ]);
        
        $copies = $input['copies'] ?? [$input];
        $limit = AiUsagePolicy::getActionLimit($user);
        
        $totalRequested = count($copies);
        $copiedTests = [];
        $skipped = [];
        $errors = [];
        
        // Slice to limit
        $copiesToProcess = array_slice($copies, 0, $limit);
        $skipped = array_slice($copies, $limit);
        
        \Log::info('Processing round copies', [
            'total_requested' => $totalRequested,
            'to_process' => count($copiesToProcess),
            'to_skip' => count($skipped)
        ]);
        
        DB::beginTransaction();
        
        try {
            foreach ($copiesToProcess as $copyData) {
                \Log::info('Processing round copy', ['copy_data' => $copyData]);
                
                // Extract copy data
                $roundName = $copyData['round_name'] ?? null;
                $sourceSurveyCode = $copyData['source_survey_code'] ?? null;
                $targetSurveyCode = $copyData['target_survey_code'] ?? $sourceSurveyCode;
                $newName = $copyData['new_name'] ?? null;
                
                if (!$roundName || !$sourceSurveyCode || !$targetSurveyCode) {
                    $errors[] = "Missing required fields";
                    \Log::warning('Missing required fields', ['copy_data' => $copyData]);
                    continue;
                }
                
                // Find source survey
                $sourceSurvey = self::findSurvey($user, $sourceSurveyCode);
                if (!$sourceSurvey) {
                    $errors[] = "Source survey not found: {$sourceSurveyCode}";
                    continue;
                }
                
                // Find target survey
                $targetSurvey = self::findSurvey($user, $targetSurveyCode);
                if (!$targetSurvey) {
                    $errors[] = "Target survey not found: {$targetSurveyCode}";
                    continue;
                }
                
                // Find the round to copy
                $sourceTest = UpdateRound::findRound($sourceSurvey, $roundName);
                if (!$sourceTest) {
                    $errors[] = "Round not found: {$roundName}";
                    continue;
                }
                
                // Create a copy
                $newTest = $sourceTest->replicate();
                $newTest->survey_id = $targetSurvey->id;
                
                // Get the config and update the name
                $config = $newTest->config ?? [];
                $originalName = $config['name'] ?? 'Round ' . $sourceTest->part;
                
                if ($newName) {
                    $config['name'] = $newName;
                    $finalName = $newName;
                } else {
                    $config['name'] = $originalName . ' (Copy)';
                    $finalName = $originalName . ' (Copy)';
                }
                
                $newTest->config = $config;
                
                // Get the highest part number in target survey
                $maxPart = Test::where('survey_id', $targetSurvey->id)->max('part') ?? 0;
                $newTest->part = $maxPart + 1;
                
                $newTest->save();
                
                \Log::info('Round copied successfully', [
                    'source_test_id' => $sourceTest->id,
                    'new_test_id' => $newTest->id,
                    'new_name' => $finalName
                ]);
                
                $copiedTests[] = [
                    'id' => $newTest->id,
                    'name' => $finalName,
                    'source_survey' => $sourceSurvey->name,
                    'target_survey' => $targetSurvey->name,
                    'survey_id' => $targetSurvey->id,
                ];
            }
            
            DB::commit();
            
            \Log::info('All round copies committed', [
                'copied_count' => count($copiedTests),
                'error_count' => count($errors)
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to copy rounds', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
        
        return [
            'items' => $copiedTests,
            'skipped' => $skipped,
            'errors' => $errors,
            'total_copied' => count($copiedTests),
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
}
