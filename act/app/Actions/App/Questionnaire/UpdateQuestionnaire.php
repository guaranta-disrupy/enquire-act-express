<?php

namespace App\Actions\App\Questionnaire;

use App\Models\Survey;
use App\Models\Test;
use App\Services\AiUsagePolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateQuestionnaire
{
    /**
     * Update questionnaire styles
     * If test_id is provided, styles are stored in Test->config['styles']
     * Otherwise, styles are stored in Survey->config['styles']
     */
    public static function update($user, $input): array
    {
        Log::info('UpdateQuestionnaire::update called', [
            'user_id' => $user->id,
            'input' => $input
        ]);
        
        $surveyCode = $input['survey_code'] ?? null;
        $styleUpdates = $input['style_updates'] ?? [];
        $testId = $input['test_id'] ?? null;
        $globalDevice = $input['active_device'] ?? null;
        $globalOrientation = $input['current_orientation'] ?? null;
        
        if (!$surveyCode || empty($styleUpdates)) {
            throw new \Exception('Missing required fields: survey_code or style_updates');
        }
        
        // Add device info to each style_update if not already present
        foreach ($styleUpdates as &$update) {
            if (!isset($update['device']) && $globalDevice) {
                $update['device'] = $globalDevice;
            }
            if (!isset($update['current_orientation']) && $globalOrientation) {
                $update['current_orientation'] = $globalOrientation;
            }
        }
        unset($update); // Break reference
        
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
            throw new \Exception("Survey not found with code: {$surveyCode}");
        }
        
        Log::info('Survey found', [
            'survey_id' => $survey->id,
            'survey_name' => $survey->name,
            'test_id' => $testId
        ]);
        
        DB::beginTransaction();
        
        try {
            // If test_id is provided, store styles in Test model
            if ($testId) {
                $test = Test::where('id', $testId)
                    ->where('survey_id', $survey->id)
                    ->where('user_id', $user->id)
                    ->first();
                
                if (!$test) {
                    throw new \Exception("Test not found with id: {$testId} for survey: {$survey->id}");
                }
                
                Log::info('Updating test-specific styles', [
                    'test_id' => $test->id,
                    'test_part' => $test->part
                ]);
                
                $config = $test->config ?? [];
                $styles = $config['styles'] ?? [
                    'theme1' => ['landscape' => [], 'portrait' => [], 'tabletPortrait' => []],
                    'theme2' => ['landscape' => [], 'portrait' => [], 'tabletPortrait' => []],
                    'theme3' => ['landscape' => [], 'portrait' => [], 'tabletPortrait' => []],
                ];
                
                $updatedStyles = [];
                $errors = [];
                
                foreach ($styleUpdates as $update) {
                    $variable = $update['variable'] ?? null;
                    $value = $update['value'] ?? null;
                    $theme = $update['theme'] ?? '1';
                    $orientation = $update['orientation'] ?? null;
                    
                    if (!$variable || $value === null) {
                        $errors[] = "Missing variable or value in style update";
                        continue;
                    }
                    
                    // Ensure variable starts with --
                    if (!str_starts_with($variable, '--')) {
                        $variable = '--' . ltrim($variable, '-');
                    }
                    
                    // Validate theme (must be 1, 2, or 3)
                    if ($theme && !in_array($theme, ['1', '2', '3'], true)) {
                        $theme = '1'; // Default to theme 1 if invalid
                    }
                    if (!$theme) {
                        $theme = '1';
                    }
                    
                    // Validate orientation - handle "all" as special value
                    // Normalize orientation value (trim whitespace, ensure lowercase for comparison)
                    $orientation = $orientation ? trim($orientation) : null;
                    $validOrientations = ['landscape', 'portrait', 'tabletPortrait'];
                    
                    Log::info('Processing test-specific style update', [
                        'test_id' => $test->id,
                        'variable' => $variable,
                        'value' => $value,
                        'theme' => $theme,
                        'orientation_raw' => $update['orientation'] ?? null,
                        'orientation_processed' => $orientation,
                        'is_valid' => $orientation && in_array($orientation, $validOrientations, true),
                        'is_all' => $orientation === 'all'
                    ]);
                    
                    // CRITICAL: Only set orientation to null if it's explicitly "all" or invalid
                    // Do NOT set to null if it's a valid orientation like "portrait"
                    if ($orientation === 'all') {
                        Log::info('Orientation is "all", will apply to all orientations', [
                            'test_id' => $test->id,
                            'variable' => $variable
                        ]);
                        $orientation = null; // Apply to all orientations
                    } elseif ($orientation && !in_array($orientation, $validOrientations, true)) {
                        Log::warning('Invalid orientation detected for test-specific styles, will apply to all orientations', [
                            'test_id' => $test->id,
                            'orientation' => $orientation,
                            'variable' => $variable,
                            'valid_orientations' => $validOrientations
                        ]);
                        $orientation = null; // If invalid, apply to all
                    }
                    
                    // Determine which orientations to update - CRITICAL: Only update the specified orientation
                    $orientationsToUpdate = $orientation 
                        ? [$orientation] 
                        : ['landscape', 'portrait', 'tabletPortrait'];
                    
                    Log::info('Test-specific orientations to update', [
                        'test_id' => $test->id,
                        'orientations' => $orientationsToUpdate,
                        'variable' => $variable,
                        'original_orientation' => $update['orientation'] ?? null
                    ]);
                    
                    // Update the style for each orientation
                    foreach ($orientationsToUpdate as $orient) {
                        if (!isset($styles['theme' . $theme][$orient])) {
                            $styles['theme' . $theme][$orient] = [];
                        }
                        
                        $oldValue = $styles['theme' . $theme][$orient][$variable] ?? null;
                        $styles['theme' . $theme][$orient][$variable] = $value;
                        $updatedStyles[] = [
                            'variable' => $variable,
                            'value' => $value,
                            'theme' => $theme,
                            'orientation' => $orient
                        ];
                        
                        Log::info('Test-specific style updated for orientation', [
                            'test_id' => $test->id,
                            'variable' => $variable,
                            'old_value' => $oldValue,
                            'new_value' => $value,
                            'theme' => $theme,
                            'orientation' => $orient
                        ]);
                    }
                }
                
                $config['styles'] = $styles;
                $test->config = $config;
                $test->save();
                
                DB::commit();
                
                // Log the final saved styles structure for debugging
                Log::info('Test-specific questionnaire styles updated successfully', [
                    'test_id' => $test->id,
                    'survey_id' => $survey->id,
                    'updated_count' => count($updatedStyles),
                    'error_count' => count($errors),
                    'saved_styles_structure' => [
                        'theme1' => [
                            'landscape' => array_keys($styles['theme1']['landscape'] ?? []),
                            'portrait' => array_keys($styles['theme1']['portrait'] ?? []),
                            'tabletPortrait' => array_keys($styles['theme1']['tabletPortrait'] ?? [])
                        ]
                    ],
                    'updated_variables_by_orientation' => collect($updatedStyles)->groupBy('orientation')->map(function($group) {
                        return $group->pluck('variable')->toArray();
                    })->toArray()
                ]);
                
                return [
                    'updated' => $updatedStyles,
                    'errors' => $errors,
                    'total_updated' => count($updatedStyles),
                    'total_errors' => count($errors),
                    'survey_id' => $survey->id,
                    'survey_name' => $survey->name,
                    'test_id' => $test->id
                ];
            } else {
                // No test_id provided, store in Survey (global styles)
                $config = $survey->config ?? [];
                $styles = $config['styles'] ?? [
                    'theme1' => ['landscape' => [], 'portrait' => [], 'tabletPortrait' => []],
                    'theme2' => ['landscape' => [], 'portrait' => [], 'tabletPortrait' => []],
                    'theme3' => ['landscape' => [], 'portrait' => [], 'tabletPortrait' => []],
                ];
                
                $updatedStyles = [];
                $errors = [];
                
                foreach ($styleUpdates as $update) {
                    $variable = $update['variable'] ?? null;
                    $value = $update['value'] ?? null;
                    $theme = $update['theme'] ?? '1';
                    $orientation = $update['orientation'] ?? null;
                    $device = $update['device'] ?? null;
                    $currentOrientation = $update['current_orientation'] ?? null;
                    
                    if (!$variable || $value === null) {
                        $errors[] = "Missing variable or value in style update";
                        continue;
                    }
                    
                    // Ensure variable starts with --
                    if (!str_starts_with($variable, '--')) {
                        $variable = '--' . ltrim($variable, '-');
                    }
                    
                    // Validate theme (must be 1, 2, or 3)
                    if ($theme && !in_array($theme, ['1', '2', '3'], true)) {
                        $theme = '1'; // Default to theme 1 if invalid
                    }
                    if (!$theme) {
                        $theme = '1';
                    }
                    
                    // If orientation is not specified but device is provided, infer orientation
                    if (!$orientation && $device) {
                        if ($device === 'desktop') {
                            $orientation = 'landscape';
                        } elseif ($device === 'tablet') {
                            // For tablet, use currentOrientation if available, otherwise default to portrait
                            $orientation = $currentOrientation === 'tabletPortrait' 
                                ? 'tabletPortrait' 
                                : ($currentOrientation ?: 'portrait');
                        } elseif ($device === 'mobile') {
                            // For mobile, use currentOrientation if available, otherwise default to portrait
                            $orientation = $currentOrientation ?: 'portrait';
                        }
                    }
                    
                    // Validate orientation - handle "all" as special value
                    $validOrientations = ['landscape', 'portrait', 'tabletPortrait'];
                    if ($orientation === 'all' || ($orientation && !in_array($orientation, $validOrientations, true))) {
                        $orientation = null; // If "all" or invalid, apply to all
                    }
                    
                    // Determine which orientations to update
                    $orientationsToUpdate = $orientation 
                        ? [$orientation] 
                        : ['landscape', 'portrait', 'tabletPortrait'];
                    
                    // Update the style for each orientation
                    foreach ($orientationsToUpdate as $orient) {
                        if (!isset($styles['theme' . $theme][$orient])) {
                            $styles['theme' . $theme][$orient] = [];
                        }
                        
                        $styles['theme' . $theme][$orient][$variable] = $value;
                        $updatedStyles[] = [
                            'variable' => $variable,
                            'value' => $value,
                            'theme' => $theme,
                            'orientation' => $orient
                        ];
                    }
                }
                
                $config['styles'] = $styles;
                $survey->config = $config;
                $survey->save();
                
                DB::commit();
                
                Log::info('Questionnaire styles updated successfully (global)', [
                    'survey_id' => $survey->id,
                    'updated_count' => count($updatedStyles),
                    'error_count' => count($errors)
                ]);
                
                return [
                    'updated' => $updatedStyles,
                    'errors' => $errors,
                    'total_updated' => count($updatedStyles),
                    'total_errors' => count($errors),
                    'survey_id' => $survey->id,
                    'survey_name' => $survey->name
                ];
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update questionnaire styles', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}