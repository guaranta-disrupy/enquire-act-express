<?php

namespace App\Actions\App\Round;

use App\Models\Test;
use App\Models\Survey;
use App\Services\AiUsagePolicy;
use Illuminate\Support\Facades\DB;

class CreateRound
{
    /**
     * Create multiple rounds/tests
     */
    public static function create($user, $input): array
    {
        \Log::info('CreateRound::create called', [
            'user_id' => $user->id,
            'input' => $input
        ]);
        
        $rounds = $input['rounds'] ?? [$input]; // Support batch or single round creation
        $limit = AiUsagePolicy::getActionLimit($user);
        
        $totalRequested = count($rounds);
        $createdTests = [];
        $skipped = [];
        
        // Slice to limit
        $roundsToProcess = array_slice($rounds, 0, $limit);
        $skipped = array_slice($rounds, $limit);
        
        \Log::info('Processing rounds', [
            'total_requested' => $totalRequested,
            'to_process' => count($roundsToProcess),
            'to_skip' => count($skipped)
        ]);
        
        DB::beginTransaction();
        
        try {
            foreach ($roundsToProcess as $roundData) {
                \Log::info('Processing round', ['round_data' => $roundData]);
                // Extract round data
                $surveyId = $roundData['survey_id'] ?? null;
                $surveyCode = $roundData['survey_code'] ?? null;
                $surveyName = $roundData['survey_name'] ?? null;
                $name = $roundData['name'] ?? null;
                $roundType = strtolower($roundData['round_type'] ?? '');
                
                // Determine round type - check explicit round_type field first, then fall back to name detection
                $isPractice = false;
                $isMain = false;
                $isDescriptive = false;
                
                if ($roundType) {
                    // Use explicit round_type if provided
                    $isPractice = $roundType === 'practice';
                    $isMain = $roundType === 'main';
                    $isDescriptive = $roundType === 'descriptive';
                } else {
                    // Fallback to name detection for backwards compatibility
                    $nameLower = strtolower($name ?? '');
                    $isPractice = str_contains($nameLower, 'practice') || str_contains($nameLower, 'training');
                    $isMain = str_contains($nameLower, 'main');
                    $isDescriptive = str_contains($nameLower, 'descriptive') || str_contains($nameLower, 'description');
                }
                
                // Get default config based on round type
                $config = $roundData['config'] ?? self::getDefaultConfigByType($isPractice, $isMain, $isDescriptive, $name);
                $primes = $roundData['primes'] ?? [];
                $targets = $roundData['targets'] ?? [];
                
                // Auto-generate sample primes and targets for practice/main rounds if empty
                if (empty($primes) && empty($targets)) {
                    \Log::info('Checking if sample data should be generated', [
                        'name' => $name,
                        'round_type' => $roundType,
                        'isPractice' => $isPractice,
                        'isMain' => $isMain,
                        'isDescriptive' => $isDescriptive
                    ]);
                    
                    if ($isPractice || $isMain) {
                        $sampleData = self::generateSampleData($isPractice);
                        $primes = $sampleData['primes'];
                        $targets = $sampleData['targets'];
                        
                        \Log::info('Sample data generated', [
                            'primes_count' => count($primes),
                            'targets_count' => count($targets)
                        ]);
                    }
                    // Descriptive rounds don't need primes/targets
                }
                
                // Find the survey
                $survey = null;
                // Try using survey_code as ID first (since questionnaire_code is often the survey ID)
                if ($surveyCode && is_numeric($surveyCode)) {
                    $survey = Survey::with(['group.project'])->find($surveyCode);
                    \Log::info('Attempted to find survey by ID from survey_code', [
                        'survey_code' => $surveyCode,
                        'found' => $survey ? true : false
                    ]);
                }
                
                if (!$survey && $surveyId) {
                    $survey = Survey::with(['group.project'])->find($surveyId);
                } elseif (!$survey && $surveyName) {
                    // Find survey by name in user's accessible projects
                    $userTeamIds = $user->allTeams()->pluck('id');
                    $survey = Survey::with(['group.project'])
                        ->whereHas('group.project', fn($q) => $q->whereIn('team_id', $userTeamIds))
                        ->whereRaw('LOWER(name) = ?', [strtolower($surveyName)])
                        ->first();
                }
                
                if (!$survey) {
                    \Log::warning('Survey not found', [
                        'survey_id' => $surveyId,
                        'survey_code' => $surveyCode,
                        'survey_name' => $surveyName
                    ]);
                    continue; // Skip if survey not found
                }
                
                \Log::info('Survey found', ['survey_id' => $survey->id, 'survey_name' => $survey->name]);
                
                // Check permissions - verify user has access to this survey's project
                $team = $survey->group->project ?? null;
                if (!$team || (!$user->ownsTeam($team) && !$user->belongsToTeam($team))) {
                    \Log::warning('User does not have permission', [
                        'user_id' => $user->id,
                        'team_id' => $team->id ?? null
                    ]);
                    continue; // Skip if no permission
                }
                
                \Log::info('Creating test/round', [
                    'survey_id' => $survey->id,
                    'name' => $name,
                    'config' => $config,
                    'primes_count' => count($primes),
                    'targets_count' => count($targets),
                    'has_primes' => !empty($primes),
                    'has_targets' => !empty($targets)
                ]);
                
                // Create the test/round
                // Get the next part number (max part + 1 to handle deleted rounds)
                $maxPart = $survey->tests()->max('part') ?? 0;
                
                $test = Test::create([
                    'survey_id' => $survey->id,
                    'config' => $config,
                    'primes' => $primes,
                    'targets' => $targets,
                    'user_id' => $user->id,
                    'updated_by' => $user->id,
                    'part' => $maxPart + 1,
                ]);
                
                // Load the survey relationship for route generation
                $test->load('survey');
                
                $createdTests[] = $test;
                \Log::info('Test/round created successfully', ['test_id' => $test->id]);
            }
            
            DB::commit();
            
            \Log::info('CreateRound::create completed', [
                'created_count' => count($createdTests),
                'skipped_count' => count($skipped)
            ]);
            
            return [
                'items' => $createdTests,
                'skipped' => $skipped,
                'total_requested' => $totalRequested,
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('CreateRound::create failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get default config based on round type
     */
    private static function getDefaultConfigByType(bool $isPractice, bool $isMain, bool $isDescriptive, ?string $name): array
    {
        // Descriptive Round Configuration
        if ($isDescriptive) {
            return [
                'type' => 'descriptive',
                'title' => '<p>' . ($name ?? 'This is a descriptive round') . '</p>',
                'auto_next' => false,
                'bottom_text' => '<em>Press the text in the centre to proceed to the next trial</em>',
                'conditions' => [],
                'display_by' => 1,
                'dummy_trial' => false,
                'empty_selection' => 'Please select at least 1 item',
                'limit' => 0,
                'limit_reach' => 'Select Limit',
                'macros' => [],
                'main_header' => null,
                'main_header_timeout' => 3000,
                'main_trial' => false,
                'practice_trial' => false,
                'prime_as_next' => true,
                'prime_header' => null,
                'random' => true,
                'random_prime' => false,
                'random_prime_subsets' => false,
                'single_select' => false,
                'target_delay' => 1500,
                'trial_unselect' => false,
                'trial_unselect_label' => 'none of these',
            ];
        }
        
        // Practice Round Configuration
        if ($isPractice) {
            $headerText = $name ?? 'Practice Round';
            return [
                'type' => 'trial',
                'title' => null,
                'auto_next' => false,
                'bottom_text' => '<p>Select as many as apply. Press the text in the middle to proceed to the next trial.</p>',
                'conditions' => [],
                'display_by' => 6,
                'dummy_trial' => false,
                'empty_selection' => 'Please select at least 1 item',
                'limit' => 0,
                'limit_reach' => 'Select Limit',
                'macros' => [],
                'main_header' => '<p>' . $headerText . '</p>',
                'main_header_timeout' => 3000,
                'main_trial' => false,
                'practice_trial' => true,
                'prime_as_next' => true,
                'prime_header' => null,
                'random' => true,
                'random_prime' => false,
                'random_prime_subsets' => false,
                'single_select' => false,
                'target_delay' => 1500,
                'trial_unselect' => false,
                'trial_unselect_label' => 'none of these',
            ];
        }
        
        // Main Round Configuration (default if not descriptive or practice)
        $headerText = $name ?? 'Main Round';
        return [
            'type' => 'trial',
            'title' => null,
            'auto_next' => false,
            'bottom_text' => '<p>Select as many as apply. Press the text in the middle to proceed to the next trial.</p>',
            'conditions' => [],
            'display_by' => 6,
            'empty_selection' => 'Please select at least 1 item',
            'limit' => 0,
            'macros' => [],
            'main_header' => '<p>' . $headerText . '</p>',
            'main_header_timeout' => 3000,
            'main_trial' => true,
            'practice_trial' => false,
            'prime_as_next' => true,
            'prime_header' => null,
            'random' => true,
            'random_prime' => true,
            'random_prime_subsets' => false,
            'single_select' => false,
            'trial_unselect' => false,
        ];
    }
    
    /**
     * Generate sample primes and targets for practice/main rounds
     */
    private static function generateSampleData(bool $isPractice = false): array
    {
        // Prime text is the same for both practice and main rounds
        $primes = [
            [
                'config' => [
                    'conditions' => [],
                ],
                'conditions' => [],
                'equivalent' => 'If I were completely satisfied, I would feel…',
                'index' => 1,
                'prime' => '<p>If I were completely satisfied, I would feel…</p>',
                'showButtons' => false,
                'value' => 1,
            ],
        ];
        
        if ($isPractice) {
            // Practice Round Sample Data - only first 6 targets
            $practiceTargets = ['calm', 'protected', 'secure', 'safe', 'comforted', 'belonging'];
            
            $targets = [];
            foreach ($practiceTargets as $index => $targetText) {
                $targets[] = [
                    'can_unselect' => false,
                    'colors' => (object)[
                        'active' => null,
                        'hover' => null,
                        'normal' => null,
                    ],
                    'equivalent' => $targetText,
                    'index' => $index + 1,
                    'showButtons' => false,
                    'target' => '<p>' . $targetText . '</p>',
                    'value' => $index + 1,
                ];
            }
        } else {
            // Main Round Sample Data - all 36 targets
            $mainTargets = [
                'calm', 'protected', 'secure', 'safe', 'comforted',
                'supported', 'belonging', 'engaged', 'connected', 'accepted',
                'involved', 'valued', 'like I am having fun', 'happy', 'carefree',
                'relaxed', 'wonderful', 'a sense of enjoyment', 'excited', 'inspired',
                'adventurous', 'stimulated', 'creative', 'energised', 'confident',
                'strong', 'independent', 'successful', 'influential', 'free',
                'in control', 'efficient', 'organised', 'practical', 'sensible',
                'uncomplicated'
            ];
            
            $targets = [];
            foreach ($mainTargets as $index => $targetText) {
                $targets[] = [
                    'can_unselect' => false,
                    'colors' => (object)[
                        'active' => null,
                        'hover' => null,
                        'normal' => null,
                    ],
                    'equivalent' => $targetText,
                    'index' => $index + 1,
                    'showButtons' => false,
                    'target' => '<p>' . $targetText . '</p>',
                    'value' => $index + 1,
                ];
            }
        }
        
        return [
            'primes' => $primes,
            'targets' => $targets,
        ];
    }

}