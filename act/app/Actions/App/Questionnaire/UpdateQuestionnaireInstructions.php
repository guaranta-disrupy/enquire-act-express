<?php

namespace App\Actions\App\Questionnaire;

use App\Models\Test;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateQuestionnaireInstructions
{
    /**
     * Update main_header and/or bottom_text in a test
     */
    public static function update($user, $input): array
    {
        Log::info('UpdateQuestionnaireInstructions::update called', [
            'user_id' => $user->id,
            'input' => $input
        ]);
        
        $testId = $input['test_id'] ?? null;
        $mainHeader = $input['main_header'] ?? null;
        $bottomText = $input['bottom_text'] ?? null;
        
        if (!$testId) {
            throw new \Exception('Missing required field: test_id');
        }
        
        if ($mainHeader === null && $bottomText === null) {
            throw new \Exception('At least one of main_header or bottom_text must be provided');
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
            
            Log::info('Test found for instructions update', [
                'test_id' => $test->id,
                'survey_id' => $test->survey_id
            ]);
            
            $config = $test->config ?? [];
            $updated = [];
            $errors = [];
            
            // Update main_header if provided
            if ($mainHeader !== null) {
                // Ensure main_header is wrapped in <p> tags if not already
                if (!str_starts_with(trim($mainHeader), '<p>')) {
                    $mainHeader = '<p>' . $mainHeader . '</p>';
                }
                
                $oldHeader = $config['main_header'] ?? null;
                $config['main_header'] = $mainHeader;
                $updated[] = [
                    'field' => 'main_header',
                    'old_value' => $oldHeader,
                    'new_value' => $mainHeader
                ];
            }
            
            // Update bottom_text if provided
            if ($bottomText !== null) {
                $oldBottomText = $config['bottom_text'] ?? null;
                $config['bottom_text'] = $bottomText;
                $updated[] = [
                    'field' => 'bottom_text',
                    'old_value' => $oldBottomText,
                    'new_value' => $bottomText
                ];
            }
            
            $test->config = $config;
            $test->save();
            
            DB::commit();
            
            Log::info('Questionnaire instructions updated successfully', [
                'test_id' => $test->id,
                'survey_id' => $test->survey_id,
                'updated_count' => count($updated),
                'error_count' => count($errors)
            ]);
            
            return [
                'updated' => $updated,
                'errors' => $errors,
                'total_updated' => count($updated),
                'total_errors' => count($errors),
                'survey_id' => $test->survey_id,
                'test_id' => $test->id
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update questionnaire instructions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}