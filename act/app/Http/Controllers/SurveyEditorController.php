<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SurveyEditorController extends Controller
{
    /**
     * Display the survey editor interface.
     * The editor will contain an iframe for the live preview.
     */
    public function show(Survey $survey, Request $request)
    {
        $survey->load('tests');

        // If tid is provided, find the test index for that test ID
        $tid = $request->query('tid', 0);
        if ($tid > 0) {
            $testIndex = 0; // Default to 0
            foreach ($survey->tests as $index => $test) {
                if ($test->id == $tid) {
                    $testIndex = $index;
                    break;
                }
            }
        } else {
            $testIndex = 0;
            $trialIndex = 0;
        }
        
        // Check if survey has variables and apply proper transformation
        $variables = $survey->group->project->variables ?? [];
        $maxTotalVariables = $this->getMaxTotalVariables($variables);
        
        if (!empty($variables)) {
            // Use EngineController for proper transformation if variables exist
            try {
                $engineController = new \App\Http\Controllers\EngineController();
                $processedVariables = $engineController->getVariables(
                    $survey->group->project,
                    $maxTotalVariables > 1 ? ['brc' => 1] : []
                );
                $basicTests = $engineController->transformTests($survey->tests, [], $processedVariables, true);
            } catch (\Exception $e) {
                // Fall back to basic transformation
                $basicTests = $this->transformTestsForFrontend($survey->tests->toArray());
            }
        } else {
            // No variables, use basic transformation
            $basicTests = $this->transformTestsForFrontend($survey->tests->toArray());
        }
        return Inertia::render('Engine/SurveyEditor', [
            'survey' => [
                'id' => $survey->id,
                'name' => $survey->name ?? 'Survey',
                'config' => $survey->config ?? [],
                'tests' => $basicTests,
                'editor_start_test_index' => $testIndex,
                'editor_start_trial_index' => 0,
            ],                    
            'maxTotalVariables' => $maxTotalVariables,
            'error' => 'Loaded basic data due to processing error. Some features may be limited.'
        ]);
    }

    private function getMaxTotalVariables($variables) 
    {
        $maxCriteria = 0;
        $countMaxCriteria = 0;
        $maxTotalVariables = 0;

        if (count($variables) > 0) {
            foreach ($variables as $variable) {
                $criteria = $variable->config['criteria'] ?? 0;
                if ($criteria > $maxCriteria) {
                    $maxCriteria = $criteria;
                    $countMaxCriteria = 1;
                } elseif ($criteria === $maxCriteria) {
                    $countMaxCriteria++;
                }
            }
            $maxTotalVariables = count($maxCriteria);
        }
        return $maxTotalVariables;
    }

    /**
     * Render the survey inside an iframe for the editor preview.
     * This uses the existing EngineTest component for a high-fidelity preview.
     */
    public function preview(Survey $survey, Request $request)
    {
        try {
            $testIndex = $request->query('test', 0);
            $trialIndex = $request->query('trial', 0);
            // $tid = $request->query('tid', 0);
            
            // Set execution time limit
            set_time_limit(60);
            
            // If tid is provided, find the test index for that test ID
            // if ($tid > 0) {
            //     $survey->load('tests');
            //     $testIndex = 0; // Default to 0
            //     foreach ($survey->tests as $index => $test) {
            //         if ($test->id == $tid || ($test->config && $test->config['id'] == $tid)) {
            //             $testIndex = $index;
            //             break;
            //         }
            //     }
            // }
            
            $surveyData = $this->getSurveyDataForEditor($survey, $testIndex, $trialIndex, $request);

            return Inertia::render('Engine/EngineTest', [
                'status' => 'not_started',
                'query' => $request->query(),
                'surveyData' => $surveyData,
            ]);
            
        } 
        catch (\Exception $e) {
            \Log::error('SurveyEditor preview error: ' . $e->getMessage());
            
            // Return minimal data to prevent 503 error
            return Inertia::render('Engine/EngineTest', [
                'status' => 'not_started',
                'query' => $request->query(),
                'surveyData' => [
                    'surveys' => [[
                        'id' => $survey->id,
                        'name' => $survey->name ?? 'Survey',
                        'config' => $survey->config ?? [],
                        'tests' => []                        
                    ]],
                    'total' => 0,
                    'token' => 'editor-token',
                    'config' => array_merge($survey->config ?? [], [
                        'editor_start_test_index' => 0,
                        'editor_start_trial_index' => 0,
                    ])
                ],
            ]);
        }
    }

    /**
     * Prepare the survey data in a format compatible with the frontend engine.
     */
    private function getSurveyDataForEditor(Survey $survey, $testIndex, $trialIndex, Request $request) {
        $url_array = $request->all();
        $survey->load('tests');
        
        // Check if survey has variables and apply proper transformation
        $variables = $survey->group->project->variables ?? [];
        $maxTotalVariables = $this->getMaxTotalVariables($variables);
        
        if (!empty($variables)) {
            // Use EngineController for proper transformation if variables exist
            try {
                $engineController = new \App\Http\Controllers\EngineController();
                $variables = $engineController->getVariables(
                    $survey->group->project,
                    count($url_array) > 0
                        ? $url_array
                        : ($maxTotalVariables > 1 ? ['brc' => 1] : [])
                );
                $basicTests = $engineController->transformTests($survey->tests, $url_array, $variables, true);
            } catch (\Exception $e) {
                // \Log::error('Error in final fallback transformation: ' . $e->getMessage());
                // // Fall back to basic transformation
                // $basicTests = $this->transformTestsForFrontend($survey->tests->toArray());
            }
        } else {
            // No variables, use basic transformation
            $basicTests = $this->transformTestsForFrontend($survey->tests->toArray());
        }

        $configX = $survey->config ?? [];
        $configButton = $survey->group->project->config['buttons'];
        $configX['buttons'] = $configButton;

        // for EDITOR mode only, we need to set the display to true for the primes
        // modify the $basicTests array to set config.conditions to an empty array
        // @Test config.conditions
        foreach ($basicTests as &$test) {
            $test['config']['conditions'] = [];
        }
        unset($test);

        // for EDITOR mode only, we need to set the display to true for the primes                
        // loop through $basicTests array and if 'trials' is not empty, then loop to prime and set 'display' to true
        // @Test trials.prime.display
        foreach ($basicTests as &$test) {
            if (!empty($test['trials'])) {
                foreach ($test['trials'] as &$trial) {
                    if (isset($trial['prime'])) {
                        $trial['prime']['display'] = true;
                    }
                }
                unset($trial);
            }
        }
        unset($test);

        return [
            'surveys' => [[
                'id' => $survey->id,
                'name' => $survey->name ?? 'Survey',
                'config' => $survey->config ?? [],
                'tests' => $basicTests
            ]],
            'total' => collect($basicTests)->sum(fn ($test) => count($test['trials'] ?? [])),
            'token' => 'editor-token',
            'config' => array_merge($configX ?? [], [
                'editor_start_test_index' => (int)$testIndex,
                'editor_start_trial_index' => (int)$trialIndex,
            ])
        ];
    }

    /**
     * NOT USED
     * Prepare the survey data in a format compatible with the frontend engine.
     */
    private function getSurveyDataForEditor2(Survey $survey, $testIndex, $trialIndex, Request $request)
    {
        try {
            $url_array = $request->all();
            
            // Use cached data if available, otherwise load efficiently
            $cacheKey = "survey_editor_data_{$survey->id}_" . md5(serialize($url_array));
            $cachedData = cache()->get($cacheKey);
            
            if ($cachedData) {
                $surveyData = $cachedData;
            } else {
                // Optimize database loading
                $survey->load([
                    'tests' => function($query) {
                        $query->select('id', 'survey_id', 'part', 'config', 'primes', 'targets', 'created_at', 'updated_at');
                    },
                    'group.project.variables' => function($query) {
                        $query->select('id', 'project_id', 'name', 'config');
                    }
                ]);

                $variables = $survey->group->project->variables ?? [];
                $maxTotalVariables = $this->getMaxTotalVariables($variables);

                try {
                    $engineController = new \App\Http\Controllers\EngineController();
                    $processedVariables = $engineController->getVariables(
                        $survey->group->project,
                        count($url_array) > 0
                            ? $url_array
                            : ($maxTotalVariables > 1 ? ['brc' => 1] : [])
                    );
                    $tests = $engineController->transformTests($survey->tests, $url_array, $processedVariables);

                    $surveyData = $survey->toArray();
                    $surveyData['tests'] = $tests;
                    
                    // Cache the processed data for 5 minutes
                    cache()->put($cacheKey, $surveyData, 300);
                } catch (\Exception $e) {
                    \Log::error('Error in getSurveyDataForEditor processing: ' . $e->getMessage());
                    \Log::error('Stack trace: ' . $e->getTraceAsString());
                    
                    // Check if survey has variables and apply proper transformation
                    $variables = $survey->group->project->variables ?? [];
                    $maxTotalVariables = $this->getMaxTotalVariables($variables);
                    
                    if (!empty($variables)) {
                        // Use EngineController for proper transformation if variables exist
                        try {
                            $engineController = new \App\Http\Controllers\EngineController();
                            $processedVariables = $engineController->getVariables(
                                $survey->group->project,
                                $maxTotalVariables > 1 ? ['brc' => 1] : []
                            );
                            $tests = $engineController->transformTests($survey->tests, [], $processedVariables);
                        } catch (\Exception $e) {
                            \Log::error('Error in getSurveyDataForEditor fallback transformation: ' . $e->getMessage());
                            // Fall back to basic transformation
                            $tests = $this->transformTestsForFrontend($survey->tests->toArray());
                        }
                    } else {
                        // No variables, use basic transformation
                        $tests = $this->transformTestsForFrontend($survey->tests->toArray());
                    }
                    
                    // Return basic data with properly transformed tests
                    $surveyData = [
                        'id' => $survey->id,
                        'name' => $survey->name,
                        'config' => $survey->config ?? [],
                        'tests' => $tests
                    ];
                }
            }

            // Add editor-specific config to tell the engine where to start
            $editorConfig = [
                'editor_start_test_index' => (int)$testIndex,
                'editor_start_trial_index' => (int)$trialIndex,
            ];

            return [
                'surveys' => [$surveyData],
                'total' => collect($surveyData['tests'])->sum(fn ($test) => count($test['trials'] ?? [])),
                'token' => 'editor-token', // A dummy token for the editor context
                'config' => array_merge($survey->config ?? [], $editorConfig),
            ];
            
        } catch (\Exception $e) {
            \Log::error('Error in getSurveyDataForEditor: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Try to load basic survey data with trials
            try {
                $url_array = $request->all();
                $survey->load('tests');
                
                // Check if survey has variables and apply proper transformation
                $variables = $survey->group->project->variables ?? [];
                $maxTotalVariables = $this->getMaxTotalVariables($variables);
                
                if (!empty($variables)) {
                    // Use EngineController for proper transformation if variables exist
                    try {
                        $engineController = new \App\Http\Controllers\EngineController();
                        $variables = $engineController->getVariables(
                            $survey->group->project,
                            count($url_array) > 0
                                ? $url_array
                                : ($maxTotalVariables > 1 ? ['brc' => 1] : [])
                        );
                        $basicTests = $engineController->transformTests($survey->tests, $url_array, $variables, true);
                    } catch (\Exception $e) {
                        \Log::error('Error in final fallback transformation: ' . $e->getMessage());
                        // Fall back to basic transformation
                        $basicTests = $this->transformTestsForFrontend($survey->tests->toArray());
                    }
                } else {
                    // No variables, use basic transformation
                    $basicTests = $this->transformTestsForFrontend($survey->tests->toArray());
                }

                /**
                 * *************************************************************
                 * *************************************************************
                 * THIS IS THE ENTRY DATA FOR THE EDITOR -- ORIGIGNAL
                 * *************************************************************
                 * *************************************************************
                 */
                $configX = $survey->config ?? [];
                $configButton = $survey->group->project->config['buttons'];
                $configX['buttons'] = $configButton;

                // for EDITOR mode only, we need to set the display to true for the primes
                // modify the $basicTests array to set config.conditions to an empty array
                // @Test config.conditions
                foreach ($basicTests as &$test) {
                    $test['config']['conditions'] = [];
                }
                unset($test);

                // for EDITOR mode only, we need to set the display to true for the primes                
                // loop through $basicTests array and if 'trials' is not empty, then loop to prime and set 'display' to true
                // @Test trials.prime.display
                foreach ($basicTests as &$test) {
                    if (!empty($test['trials'])) {
                        foreach ($test['trials'] as &$trial) {
                            if (isset($trial['prime'])) {
                                $trial['prime']['display'] = true;
                            }
                        }
                        unset($trial);
                    }
                }
                unset($test);

                return [
                    'surveys' => [[
                        'id' => $survey->id,
                        'name' => $survey->name ?? 'Survey',
                        'config' => $survey->config ?? [],
                        'tests' => $basicTests
                    ]],
                    'total' => collect($basicTests)->sum(fn ($test) => count($test['trials'] ?? [])),
                    'token' => 'editor-token',
                    'config' => array_merge($configX ?? [], [
                        'editor_start_test_index' => (int)$testIndex,
                        'editor_start_trial_index' => (int)$trialIndex,
                    ])
                ];
            } catch (\Exception $e2) {
                \Log::error('Error in final fallback: ' . $e2->getMessage());
                
                // Return minimal fallback data
                return [
                    'surveys' => [[
                        'id' => $survey->id,
                        'name' => $survey->name ?? 'Survey',
                        'config' => $survey->config ?? [],
                        'tests' => []
                    ]],
                    'total' => 0,
                    'token' => 'editor-token',
                    'config' => array_merge($survey->config ?? [], [
                        'editor_start_test_index' => (int)$testIndex,
                        'editor_start_trial_index' => (int)$trialIndex,
                    ])
                ];
            }
        }
    }
    
    /**
     * Transforms the tests array to create a trials structure for the frontend.
     * Handles chunking targets based on 'display_by' config.
     */
    private function transformTestsForFrontend(array $tests): array
    {
        return collect($tests)->map(function ($test) {
            $primes = $test['primes'] ?? [];
            $allTargets = collect($test['targets'] ?? []);
            $displayBy = $test['config']['display_by'] ?? 0;

            $newTrials = [];

            $targetChunks = ($displayBy > 0 && $allTargets->isNotEmpty())
                ? $allTargets->chunk($displayBy)
                : collect([$allTargets]);

            foreach ($primes as $prime) {
                foreach ($targetChunks as $chunk) {
                    $newTrials[] = [
                        'prime' => $prime,
                        'targets' => $chunk->values()->all(),
                    ];
                }
            }

            $test['trials'] = $newTrials;
            return $test;
        })->all();
    }

    /**
     * Update the survey's styles in the database.
     */
    public function updateStyles(Request $request, Survey $survey)
    {
        $validated = $request->validate([
            'styles' => 'required|array'
        ]);

        $config = $survey->config ?? [];
        $config['styles'] = $validated['styles'];

        $survey->config = $config;
        $survey->save();
        
        return response()->json(['message' => 'Styles updated successfully.']);
    }

}
