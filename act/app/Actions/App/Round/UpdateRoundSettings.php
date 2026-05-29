<?php

namespace App\Actions\App\Round;

use App\Models\Test;
use App\Services\AiUsagePolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateRoundSettings
{
    /**
     * Update round/test settings by test_id
     */
    public static function update($user, $input): array
    {
        Log::info('UpdateRoundSettings::update called', [
            'user_id' => $user->id,
            'input' => $input
        ]);
        
        $testId = $input['test_id'] ?? null;
        
        if (!$testId) {
            throw new \Exception('Missing required field: test_id');
        }
        
        // Find the test
        $test = Test::where('id', $testId)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$test) {
            throw new \Exception("Test not found with ID: {$testId}");
        }
        
        Log::info('Test found', [
            'test_id' => $test->id,
            'test_name' => $test->name,
            'current_config' => $test->config
        ]);
        
        DB::beginTransaction();
        
        try {
            $config = $test->config ?? [];
            $updated = false;
            $changes = [];
            $attemptedUpdates = []; // Track what user tried to update
            
            // Mapping of setting names to user-friendly labels
            $settingLabels = [
                'practice_trial' => 'Practice trial',
                'main_trial' => 'Main trial',
                'random' => 'Randomize targets',
                'random_prime' => 'Randomize primes',
                'prime_as_next' => 'Prime as next button',
                'single_select' => 'Single select',
                'auto_next' => 'Auto next round',
                'trial_unselect' => 'Trial unselect',
                'trial_unselect_label' => 'Trial unselect label',
                'empty_selection' => 'Empty selection text',
                'limit_reach' => 'Limit reach text',
                'limit' => 'Selection limit',
                'display_by' => 'Display targets by',
                'target_delay' => 'Target delay',
            ];
            
            // Handle boolean settings
            $booleanSettings = [
                'practice_trial',
                'main_trial',
                'random',
                'random_prime',
                'prime_as_next',
                'single_select',
                'auto_next',
                'trial_unselect',
            ];
            
            foreach ($booleanSettings as $setting) {
                if (isset($input[$setting]) && $input[$setting] !== null) {
                    $oldValue = $config[$setting] ?? false;
                    // Handle both string "true"/"false" from StringSchema and boolean values
                    $newValue = is_string($input[$setting]) 
                        ? ($input[$setting] === 'true') 
                        : (bool) $input[$setting];
                    
                    // Track attempted update
                    $attemptedUpdates[$setting] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                        'label' => $settingLabels[$setting] ?? $setting
                    ];
                    
                    if ($oldValue !== $newValue) {
                        $config[$setting] = $newValue;
                        $updated = true;
                        $changes[] = "{$setting}: " . ($oldValue ? 'true' : 'false') . " → " . ($newValue ? 'true' : 'false');
                    }
                }
            }
            
            // Handle mutual exclusivity: practice_trial and main_trial
            $practiceTrialValue = (isset($input['practice_trial']) && $input['practice_trial'] !== null)
                ? (is_string($input['practice_trial']) ? $input['practice_trial'] === 'true' : (bool) $input['practice_trial'])
                : null;
            $mainTrialValue = (isset($input['main_trial']) && $input['main_trial'] !== null)
                ? (is_string($input['main_trial']) ? $input['main_trial'] === 'true' : (bool) $input['main_trial'])
                : null;
            
            if ($practiceTrialValue === true) {
                if (($config['main_trial'] ?? false) === true) {
                    $config['main_trial'] = false;
                    $updated = true;
                    $changes[] = "main_trial: true → false (mutually exclusive with practice_trial)";
                }
            }
            
            if ($mainTrialValue === true) {
                if (($config['practice_trial'] ?? false) === true) {
                    $config['practice_trial'] = false;
                    $updated = true;
                    $changes[] = "practice_trial: true → false (mutually exclusive with main_trial)";
                }
            }
            
            // Handle mutual exclusivity: prime_as_next and auto_next
            $primeAsNextValue = (isset($input['prime_as_next']) && $input['prime_as_next'] !== null)
                ? (is_string($input['prime_as_next']) ? $input['prime_as_next'] === 'true' : (bool) $input['prime_as_next'])
                : null;
            $autoNextValue = (isset($input['auto_next']) && $input['auto_next'] !== null)
                ? (is_string($input['auto_next']) ? $input['auto_next'] === 'true' : (bool) $input['auto_next'])
                : null;
            
            if ($primeAsNextValue === true) {
                if (($config['auto_next'] ?? false) === true) {
                    $config['auto_next'] = false;
                    $updated = true;
                    $changes[] = "auto_next: true → false (mutually exclusive with prime_as_next)";
                }
            }
            
            if ($autoNextValue === true) {
                if (($config['prime_as_next'] ?? false) === true) {
                    $config['prime_as_next'] = false;
                    $updated = true;
                    $changes[] = "prime_as_next: true → false (mutually exclusive with auto_next)";
                }
            }
            
            // Handle text settings
            $textSettings = [
                'trial_unselect_label',
                'empty_selection',
                'limit_reach',
            ];
            
            foreach ($textSettings as $setting) {
                if (isset($input[$setting]) && $input[$setting] !== null) {
                    $oldValue = $config[$setting] ?? null;
                    $newValue = $input[$setting];
                    
                    // Track attempted update
                    $attemptedUpdates[$setting] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                        'label' => $settingLabels[$setting] ?? $setting
                    ];
                    
                    if ($oldValue !== $newValue) {
                        $config[$setting] = $newValue;
                        $updated = true;
                        $changes[] = "{$setting}: " . ($oldValue ? "'{$oldValue}'" : 'null') . " → '{$newValue}'";
                    }
                }
            }
            
            // Handle number settings
            $numberSettings = [
                'limit',
                'display_by',
                'target_delay',
            ];
            
            foreach ($numberSettings as $setting) {
                if (isset($input[$setting]) && $input[$setting] !== null) {
                    $oldValue = $config[$setting] ?? null;
                    $newValue = $input[$setting];
                    
                    // Convert to appropriate type
                    if ($setting === 'target_delay' || $setting === 'display_by' || $setting === 'limit') {
                        $newValue = is_numeric($newValue) ? (int) $newValue : $newValue;
                    }
                    
                    // Track attempted update
                    $attemptedUpdates[$setting] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                        'label' => $settingLabels[$setting] ?? $setting
                    ];
                    
                    if ($oldValue !== $newValue) {
                        $config[$setting] = $newValue;
                        $updated = true;
                        $changes[] = "{$setting}: " . ($oldValue !== null ? $oldValue : 'null') . " → {$newValue}";
                    }
                }
            }
            
            if ($updated) {
                $test->config = $config;
                $test->save();
                
                DB::commit();
                
                Log::info('Round settings updated successfully', [
                    'test_id' => $test->id,
                    'changes' => $changes
                ]);
                
                return [
                    'updated' => [[
                        'id' => $test->id,
                        'name' => $test->name,
                        'survey_id' => $test->survey_id,
                        'changes' => $changes
                    ]],
                    'skipped' => [],
                    'errors' => [],
                    'total_updated' => 1,
                    'total_skipped' => 0,
                    'total_errors' => 0
                ];
            } else {
                DB::rollBack();
                
                // Check if user tried to update settings that are already set to the requested value
                if (!empty($attemptedUpdates)) {
                    $alreadySetMessages = [];
                    
                    foreach ($attemptedUpdates as $setting => $updateInfo) {
                        $oldValue = $updateInfo['old'];
                        $newValue = $updateInfo['new'];
                        $label = $updateInfo['label'];
                        
                        // Check if the value is already set to what user wants
                        if ($oldValue === $newValue) {
                            if (is_bool($newValue)) {
                                $status = $newValue ? 'enabled' : 'disabled';
                                $alreadySetMessages[] = "{$label} is already {$status}";
                            } else {
                                $alreadySetMessages[] = "{$label} is already set to '{$newValue}'";
                            }
                        }
                    }
                    
                    if (!empty($alreadySetMessages)) {
                        $message = implode('. ', $alreadySetMessages) . '.';
                        
                        Log::info('Settings already set to requested values', [
                            'test_id' => $test->id,
                            'attempted_updates' => $attemptedUpdates,
                            'message' => $message
                        ]);
                        
                        return [
                            'updated' => [[
                                'id' => $test->id,
                                'name' => $test->name,
                                'survey_id' => $test->survey_id,
                                'changes' => [],
                                'already_set' => true,
                                'message' => $message
                            ]],
                            'skipped' => [],
                            'errors' => [],
                            'total_updated' => 1,
                            'total_skipped' => 0,
                            'total_errors' => 0
                        ];
                    }
                }
                
                Log::info('No changes made to round settings', ['test_id' => $test->id]);
                
                return [
                    'updated' => [],
                    'skipped' => [],
                    'errors' => ['No settings were changed.'],
                    'total_updated' => 0,
                    'total_skipped' => 0,
                    'total_errors' => 1
                ];
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update round settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}