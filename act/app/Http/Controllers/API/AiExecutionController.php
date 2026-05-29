<?php

namespace App\Http\Controllers\Api;


use App\Actions\App\Round\CreateRound;
use App\Actions\App\Round\UpdateRound;
use App\Actions\App\Round\UpdateRoundSettings;
use App\Actions\App\Round\CopyRound;
use App\Actions\App\Round\MoveRound;
use App\Actions\App\Questionnaire\UpdateQuestionnaire;
use App\Actions\App\Questionnaire\UpdateQuestionnairePrimes;
use App\Actions\App\Questionnaire\CreateQuestionnairePrimes;
use App\Actions\App\Questionnaire\UpdateQuestionnaireTargets;
use App\Actions\App\Questionnaire\CreateQuestionnaireTargets;
use App\Actions\App\Questionnaire\UpdateQuestionnaireInstructions;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use App\Http\Controllers\FolderController;
use App\Services\SurveyService;
use App\Services\ComponentService;
use App\Services\AiUsagePolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AiExecutionController extends Controller
{
    public function execute($actionable_id)
    {
        $actionable = Cache::get($actionable_id);
        if (!$actionable) {
            return response()->json(['message' => 'Actionable not found'], 404);
        }
        return $this->actions($actionable['action'], $actionable['parameters']);
    }


    public function actions($key, $data)
    {
        $action = match ($key) {

            'app_round_create', 'act_round_create', 'fluent_round_create' => function() use ($data) {
                try {
                    $user = Auth::user();
                    \Log::info('Round create action handler called', ['data' => $data]);
                    
                    $result = CreateRound::create($user, $data);
                    \Log::info('CreateRound result', [
                        'items_count' => count($result['items']),
                        'skipped_count' => count($result['skipped']),
                        'total_requested' => $result['total_requested']
                    ]);
                    
                    $tests = $result['items'];
                    $skipped = $result['skipped'];
                    $totalRequested = $result['total_requested'];
                    
                    // Build warning message for skipped items using AiUsagePolicy
                    $warningMessage = '';
                    if (!empty($skipped)) {
                        $limit = AiUsagePolicy::getActionLimit($user);
                        if ($totalRequested > $limit) {
                            $warningMessage = AiUsagePolicy::getSkippedItemsWarning($user, count($skipped), $skipped, 'round');
                        }
                    }
                    
                    if (count($tests) > 1) {
                        \Log::info('Returning multiple rounds response', ['count' => count($tests)]);
                        $firstTest = $tests[0];
                        $response = [
                            'message' => 'Rounds created successfully.' . $warningMessage,
                            'links' => collect($tests)->map(fn($t) => [
                                'name' => $t->config['name'] ?? 'Round ' . $t->part,
                                'route' => route('survey2.update', [$t->survey, $t]),
                            ])->values(),
                        ];
                        
                        // Add refresh_type to trigger rounds list update
                        if ($firstTest && isset($firstTest->survey)) {
                            $response['refresh_type'] = 'rounds_list';
                            $response['survey_id'] = $firstTest->survey->id;
                        }
                        
                        return response()->json($response);
                    }
                    
                    if (count($tests) === 1) {
                        $test = $tests[0];
                        \Log::info('Returning single round response', [
                            'test_id' => $test->id,
                            'has_survey' => isset($test->survey),
                            'survey_id' => $test->survey->id ?? null
                        ]);
                        $response = [
                            'message' => 'Round created successfully.' . $warningMessage,
                            'route' => route('survey2.update', [$test->survey, $test]),
                            'link_text' => $test->config['name'] ?? 'Round ' . $test->part,
                        ];
                        
                        // Add refresh_type to trigger rounds list update
                        if (isset($test->survey)) {
                            $response['refresh_type'] = 'rounds_list';
                            $response['survey_id'] = $test->survey->id;
                        }
                        
                        return response()->json($response);
                    }
                    
                    \Log::error('No rounds in result array', ['tests_count' => count($tests)]);
                    return response()->json(['error' => 'No rounds were created. Please check if the survey exists and you have permission to modify it.'], 400);
                } catch (\Exception $e) {
                    \Log::error('Round creation exception', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return response()->json(['error' => $e->getMessage()], 400);
                } catch (\Throwable $t) {
                    \Log::error('Round creation throwable', [
                        'error' => $t->getMessage(),
                        'trace' => $t->getTraceAsString()
                    ]);
                    return response()->json(['error' => $t->getMessage()], 500);
                }
            },

            'app_round_update', 'act_round_update', 'update_round' => function() use ($data) {
                try {
                    $user = Auth::user();
                    \Log::info('Round update action handler called', ['data' => $data]);
                    
                    $result = UpdateRound::update($user, $data);
                    \Log::info('UpdateRound result', [
                        'updated_count' => $result['total_updated'],
                        'skipped_count' => $result['total_skipped'],
                        'errors_count' => $result['total_errors']
                    ]);
                    
                    $updated = $result['updated'];
                    $skipped = $result['skipped'];
                    $errors = $result['errors'];
                    
                    // Build warning message for skipped items
                    $warningMessage = '';
                    if (!empty($skipped)) {
                        $limit = AiUsagePolicy::getActionLimit($user);
                        $warningMessage = AiUsagePolicy::getSkippedItemsWarning($user, count($skipped), $skipped, 'round');
                    }
                    
                    // Add error messages if any
                    $errorMessage = '';
                    if (!empty($errors)) {
                        $errorMessage = "\n\nErrors:\n" . implode("\n", $errors);
                    }
                    
                    if (count($updated) > 1) {
                        $firstUpdate = $updated[0];
                        $test = \App\Models\Test::find($firstUpdate['id']);
                        $response = [
                            'message' => 'Rounds updated successfully.' . $warningMessage . $errorMessage,
                            'links' => collect($updated)->map(function($update) {
                                $test = \App\Models\Test::find($update['id']);
                                return [
                                    'name' => $update['name'],
                                    'route' => $test ? route('survey2.update', [$test->survey, $test]) : null,
                                ];
                            })->values(),
                        ];
                        
                        // Add refresh_type to trigger rounds list update
                        if ($test) {
                            $response['refresh_type'] = 'rounds_list';
                            $response['survey_id'] = $test->survey_id;
                        }
                        
                        return response()->json($response);
                    }
                    
                    if (count($updated) === 1) {
                        $update = $updated[0];
                        $test = \App\Models\Test::find($update['id']);
                        $response = [
                            'message' => 'Round updated successfully.' . $warningMessage . $errorMessage,
                            'route' => $test ? route('survey2.update', [$test->survey, $test]) : null,
                            'link_text' => $update['name'],
                        ];
                        
                        // Add refresh_type to trigger UI update without page refresh
                        if ($test) {
                            $response['refresh_type'] = 'rounds_list';
                            $response['survey_id'] = $test->survey_id;
                            // Also include test_id for test_data refresh if needed
                            $response['test_id'] = $test->id;
                        }
                        
                        return response()->json($response);
                    }
                    
                    if (!empty($errors)) {
                        return response()->json(['error' => 'Failed to update rounds: ' . implode(', ', $errors)], 400);
                    }
                    
                    return response()->json(['error' => 'No rounds were updated.'], 400);
                } catch (\Exception $e) {
                    \Log::error('Round update exception', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return response()->json(['error' => $e->getMessage()], 400);
                } catch (\Throwable $t) {
                    \Log::error('Round update throwable', [
                        'error' => $t->getMessage(),
                        'trace' => $t->getTraceAsString()
                    ]);
                    return response()->json(['error' => $t->getMessage()], 500);
                }
            },

            'app_round_update_settings', 'act_round_update_settings', 'update_round_settings' => function() use ($data) {
                try {
                    $user = Auth::user();
                    \Log::info('Round settings update action handler called', ['data' => $data]);
                    
                    $result = UpdateRoundSettings::update($user, $data);
                    \Log::info('UpdateRoundSettings result', [
                        'updated_count' => $result['total_updated'],
                        'error_count' => $result['total_errors']
                    ]);
                    
                    $updated = $result['updated'];
                    $errors = $result['errors'];
                    
                    // Check if settings are already set to requested values
                    if (count($updated) === 1 && isset($updated[0]['already_set']) && $updated[0]['already_set'] === true) {
                        $update = $updated[0];
                        $test = \App\Models\Test::find($update['id']);
                        $response = [
                            'message' => $update['message'] ?? 'Settings are already set to the requested values.',
                            'route' => $test ? route('survey2.update', [$test->survey, $test]) : null,
                            'link_text' => $update['name'] ?? null,
                        ];
                        
                        // Add refresh_type to trigger UI update without page refresh
                        if ($test) {
                            $response['refresh_type'] = 'test_data';
                            $response['test_id'] = $test->id;
                            $response['survey_id'] = $test->survey_id;
                        }
                        
                        return response()->json($response);
                    }
                    
                    // Add error messages if any
                    $errorMessage = '';
                    if (!empty($errors)) {
                        $errorMessage = "\n\nErrors:\n" . implode("\n", $errors);
                    }
                    
                    // Use AI-generated message if available, otherwise create user-friendly message
                    $successMessage = $data['message'] ?? 'Round settings updated successfully.';
                    
                    // If we have the AI message, convert it from question to statement (remove ? and make past tense)
                    if (isset($data['message']) && str_ends_with($successMessage, '?')) {
                        $successMessage = rtrim($successMessage, '?');
                        // Convert to past tense if needed
                        $successMessage = str_replace(' will be ', ' were ', $successMessage);
                        $successMessage = str_replace('Enable ', 'Enabled ', $successMessage);
                        $successMessage = str_replace('Disable ', 'Disabled ', $successMessage);
                        $successMessage = str_replace('Set ', 'Set ', $successMessage);
                        $successMessage = str_replace('Change ', 'Changed ', $successMessage);
                        $successMessage = str_replace('Update ', 'Updated ', $successMessage);
                    }
                    
                    if (count($updated) === 1) {
                        $update = $updated[0];
                        $test = \App\Models\Test::find($update['id']);
                        $response = [
                            'message' => $successMessage . $errorMessage,
                            'route' => $test ? route('survey2.update', [$test->survey, $test]) : null,
                            'link_text' => $update['name'],
                        ];
                        
                        // Add refresh_type to trigger UI update without page refresh
                        if ($test) {
                            $response['refresh_type'] = 'test_data';
                            $response['test_id'] = $test->id;
                            $response['survey_id'] = $test->survey_id;
                        }
                        
                        return response()->json($response);
                    }
                    
                    if (!empty($errors)) {
                        return response()->json(['error' => 'Failed to update round settings: ' . implode(', ', $errors)], 400);
                    }
                    
                    return response()->json(['error' => 'No settings were updated.'], 400);
                } catch (\Exception $e) {
                    \Log::error('Round settings update exception', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return response()->json(['error' => $e->getMessage()], 400);
                } catch (\Throwable $t) {
                    \Log::error('Round settings update throwable', [
                        'error' => $t->getMessage(),
                        'trace' => $t->getTraceAsString()
                    ]);
                    return response()->json(['error' => $t->getMessage()], 500);
                }
            },

            'app_round_copy', 'act_round_copy', 'fluent_round_copy' => function() use ($data) {
                try {
                    $user = Auth::user();
                    \Log::info('Round copy action handler called', ['data' => $data]);
                    
                    $result = CopyRound::copy($user, $data);
                    \Log::info('CopyRound result', [
                        'copied_count' => $result['total_copied'],
                        'skipped_count' => $result['total_skipped'],
                        'errors_count' => $result['total_errors']
                    ]);
                    
                    $copied = $result['items'];
                    $skipped = $result['skipped'];
                    $errors = $result['errors'];
                    
                    // Build warning message for skipped items
                    $warningMessage = '';
                    if (!empty($skipped)) {
                        $limit = AiUsagePolicy::getActionLimit($user);
                        $warningMessage = AiUsagePolicy::getSkippedItemsWarning($user, count($skipped), $skipped, 'round');
                    }
                    
                    // Add error messages if any
                    $errorMessage = '';
                    if (!empty($errors)) {
                        $errorMessage = "\n\nErrors:\n" . implode("\n", $errors);
                    }
                    
                    if (count($copied) > 1) {
                        $firstCopy = $copied[0];
                        $test = \App\Models\Test::find($firstCopy['id']);
                        $response = [
                            'message' => 'Rounds copied successfully.' . $warningMessage . $errorMessage,
                            'links' => collect($copied)->map(function($copy) {
                                $test = \App\Models\Test::find($copy['id']);
                                return [
                                    'name' => $copy['name'],
                                    'route' => $test ? route('survey2.update', [$test->survey, $test]) : null,
                                ];
                            })->values(),
                        ];
                        
                        // Add refresh_type to trigger rounds list update
                        if ($test) {
                            $response['refresh_type'] = 'rounds_list';
                            $response['survey_id'] = $test->survey_id;
                        }
                        
                        return response()->json($response);
                    }
                    
                    if (count($copied) === 1) {
                        $copy = $copied[0];
                        $test = \App\Models\Test::find($copy['id']);
                        $response = [
                            'message' => 'Round copied successfully.' . $warningMessage . $errorMessage,
                            'route' => $test ? route('survey2.update', [$test->survey, $test]) : null,
                            'link_text' => $copy['name'],
                        ];
                        
                        // Add refresh_type to trigger rounds list update
                        if ($test) {
                            $response['refresh_type'] = 'rounds_list';
                            $response['survey_id'] = $test->survey_id;
                        }
                        
                        return response()->json($response);
                    }
                    
                    if (!empty($errors)) {
                        return response()->json(['error' => 'Failed to copy rounds: ' . implode(', ', $errors)], 400);
                    }
                    
                    return response()->json(['error' => 'No rounds were copied.'], 400);
                } catch (\Exception $e) {
                    \Log::error('Round copy exception', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return response()->json(['error' => $e->getMessage()], 400);
                } catch (\Throwable $t) {
                    \Log::error('Round copy throwable', [
                        'error' => $t->getMessage(),
                        'trace' => $t->getTraceAsString()
                    ]);
                    return response()->json(['error' => $t->getMessage()], 500);
                }
            },

            'app_round_move', 'act_round_move', 'fluent_round_move' => function() use ($data) {
                try {
                    $user = Auth::user();
                    \Log::info('Round move action handler called', ['data' => $data]);
                    
                    $result = MoveRound::move($user, $data);
                    \Log::info('MoveRound result', [
                        'moved_count' => $result['total_moved'],
                        'skipped_count' => $result['total_skipped'],
                        'errors_count' => $result['total_errors']
                    ]);
                    
                    $moved = $result['items'];
                    $skipped = $result['skipped'];
                    $errors = $result['errors'];
                    
                    // Build warning message for skipped items
                    $warningMessage = '';
                    if (!empty($skipped)) {
                        $limit = AiUsagePolicy::getActionLimit($user);
                        $warningMessage = AiUsagePolicy::getSkippedItemsWarning($user, count($skipped), $skipped, 'round');
                    }
                    
                    // Add error messages if any
                    $errorMessage = '';
                    if (!empty($errors)) {
                        $errorMessage = "\n\nErrors:\n" . implode("\n", $errors);
                    }
                    
                    if (count($moved) > 1) {
                        return response()->json([
                            'message' => 'Rounds moved successfully.' . $warningMessage . $errorMessage,
                            'links' => collect($moved)->map(function($move) {
                                $test = \App\Models\Test::find($move['id']);
                                return [
                                    'name' => $move['name'] . ' (moved to ' . $move['to_survey'] . ')',
                                    'route' => $test ? route('survey2.update', [$test->survey, $test]) : null,
                                ];
                            })->values(),
                        ]);
                    }
                    
                    if (count($moved) === 1) {
                        $move = $moved[0];
                        $test = \App\Models\Test::find($move['id']);
                        return response()->json([
                            'message' => 'Round moved successfully from ' . $move['from_survey'] . ' to ' . $move['to_survey'] . '.' . $warningMessage . $errorMessage,
                            'route' => $test ? route('survey2.update', [$test->survey, $test]) : null,
                            'link_text' => $move['name'],
                        ]);
                    }
                    
                    if (!empty($errors)) {
                        return response()->json(['error' => 'Failed to move rounds: ' . implode(', ', $errors)], 400);
                    }
                    
                    return response()->json(['error' => 'No rounds were moved.'], 400);
                } catch (\Exception $e) {
                    \Log::error('Round move exception', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return response()->json(['error' => $e->getMessage()], 400);
                } catch (\Throwable $t) {
                    \Log::error('Round move throwable', [
                        'error' => $t->getMessage(),
                        'trace' => $t->getTraceAsString()
                    ]);
                    return response()->json(['error' => $t->getMessage()], 500);
                }
            },

            'app_questionnaire_update', 'act_questionnaire_update', 'update_questionnaire' => function() use ($data) {
                try {
                    $user = Auth::user();
                    Log::info('Questionnaire update action handler called', ['data' => $data]);
                    
                    $result = UpdateQuestionnaire::update($user, $data);
                    Log::info('UpdateQuestionnaire result', [
                        'updated_count' => $result['total_updated'],
                        'error_count' => $result['total_errors']
                    ]);
                    
                    $updated = $result['updated'];
                    $errors = $result['errors'];
                    
                    // Add error messages if any
                    $errorMessage = '';
                    if (!empty($errors)) {
                        $errorMessage = "\n\nErrors:\n" . implode("\n", $errors);
                    }
                    
                    // Use AI-generated message if available, otherwise create user-friendly message
                    $successMessage = $data['message'] ?? 'Questionnaire styles updated successfully.';
                    
                    // If we have the AI message, convert it from question to statement (remove ? and make past tense)
                    if (isset($data['message']) && str_ends_with($successMessage, '?')) {
                        $successMessage = rtrim($successMessage, '?');
                        // Convert to past tense - handle common patterns
                        $successMessage = preg_replace('/\b(Change|Update|Set)\b/i', 'Updated', $successMessage);
                        // Handle "Background color of primes updated to orange?" -> "Background color of primes updated to orange."
                        // (already in past tense, just remove ?)
                        if (!preg_match('/\b(Updated|Changed|Set)\b/i', $successMessage)) {
                            // If no past tense verb found, try to add one
                            $successMessage = preg_replace('/\b(to|to be)\b/i', 'updated to', $successMessage);
                        }
                        $successMessage = trim($successMessage) . '.';
                    } elseif (!isset($data['message']) && count($updated) > 0) {
                        // Fallback: Create user-friendly message from updated styles
                        $styleMessages = collect($updated)->map(function($style) {
                            return self::formatStyleUpdate($style['variable'], $style['value']);
                        })->unique()->take(5)->toArray();
                        
                        if (count($updated) > 5) {
                            $styleMessages[] = 'and ' . (count($updated) - 5) . ' more';
                        }
                        
                        $successMessage = implode(', ', $styleMessages) . '.';
                    }
                    
                    return response()->json([
                        'message' => $successMessage . $errorMessage,
                        'refresh_type' => 'styles',
                    ]);
                } catch (\Exception $e) {
                    Log::error('Questionnaire update exception', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return response()->json(['error' => $e->getMessage()], 400);
                } catch (\Throwable $t) {
                    Log::error('Questionnaire update throwable', [
                        'error' => $t->getMessage(),
                        'trace' => $t->getTraceAsString()
                    ]);
                    return response()->json(['error' => $t->getMessage()], 500);
                }
            },

            'app_questionnaire_update_primes', 'act_questionnaire_update_primes', 'app_questionnaire_primes_update', 'act_questionnaire_primes_update', 'update_questionnaire_primes' => function() use ($data) {
                try {
                    $user = Auth::user();
                    Log::info('Questionnaire primes update action handler called', ['data' => $data]);
                    
                    $result = UpdateQuestionnairePrimes::update($user, $data);
                    
                    // If there are errors, only show error message with error emoji
                    if (!empty($result['errors'])) {
                        $errorMessage = "❌ " . implode("\n❌ ", $result['errors']);
                        return response()->json([
                            'message' => $errorMessage,
                            'test_id' => $result['test_id'],
                            'survey_id' => $result['survey_id'],
                            'refresh_type' => 'test_data',
                        ]);
                    }
                    
                    $successMessage = $data['message'] ?? 'Primes updated successfully.';
                    if (isset($data['message']) && str_ends_with($successMessage, '?')) {
                        $successMessage = self::convertToPastTense($successMessage);
                    }
                    
                    return response()->json([
                        'message' => $successMessage,
                        'test_id' => $result['test_id'],
                        'survey_id' => $result['survey_id'],
                        'refresh_type' => 'test_data',
                    ]);
                } catch (\Exception $e) {
                    Log::error('Questionnaire primes update exception', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return response()->json(['error' => $e->getMessage()], 400);
                }
            },

            'app_questionnaire_create_primes', 'act_questionnaire_create_primes', 'app_questionnaire_primes_create', 'act_questionnaire_primes_create', 'create_questionnaire_primes' => function() use ($data) {
                try {
                    $user = Auth::user();
                    Log::info('Questionnaire primes create action handler called', ['data' => $data]);
                    
                    $result = CreateQuestionnairePrimes::create($user, $data);
                    
                    // If there are errors, only show error message with error emoji
                    if (!empty($result['errors'])) {
                        $errorMessage = "❌ " . implode("\n❌ ", $result['errors']);
                        return response()->json([
                            'message' => $errorMessage,
                            'test_id' => $result['test_id'],
                            'survey_id' => $result['survey_id'],
                            'refresh_type' => 'test_data',
                        ]);
                    }
                    
                    $successMessage = $data['message'] ?? 'Primes created successfully.';
                    if (isset($data['message']) && str_ends_with($successMessage, '?')) {
                        $successMessage = self::convertToPastTense($successMessage);
                    }
                    
                    return response()->json([
                        'message' => $successMessage,
                        'test_id' => $result['test_id'],
                        'survey_id' => $result['survey_id'],
                        'refresh_type' => 'test_data',
                    ]);
                } catch (\Exception $e) {
                    Log::error('Questionnaire primes create exception', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return response()->json(['error' => $e->getMessage()], 400);
                }
            },

            'app_questionnaire_update_targets', 'act_questionnaire_update_targets', 'app_questionnaire_targets_update', 'act_questionnaire_targets_update', 'update_questionnaire_targets' => function() use ($data) {
                try {
                    $user = Auth::user();
                    Log::info('Questionnaire targets update action handler called', ['data' => $data]);
                    
                    $result = UpdateQuestionnaireTargets::update($user, $data);
                    
                    // If there are errors, only show error message with error emoji
                    if (!empty($result['errors'])) {
                        $errorMessage = "❌ " . implode("\n❌ ", $result['errors']);
                        return response()->json([
                            'message' => $errorMessage,
                            'test_id' => $result['test_id'],
                            'survey_id' => $result['survey_id'],
                            'refresh_type' => 'test_data',
                        ]);
                    }
                    
                    $successMessage = $data['message'] ?? 'Targets updated successfully.';
                    if (isset($data['message']) && str_ends_with($successMessage, '?')) {
                        $successMessage = self::convertToPastTense($successMessage);
                    }
                    
                    return response()->json([
                        'message' => $successMessage,
                        'test_id' => $result['test_id'],
                        'survey_id' => $result['survey_id'],
                        'refresh_type' => 'test_data',
                    ]);
                } catch (\Exception $e) {
                    Log::error('Questionnaire targets update exception', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return response()->json(['error' => $e->getMessage()], 400);
                }
            },

            'app_questionnaire_create_targets', 'act_questionnaire_create_targets', 'app_questionnaire_targets_create', 'act_questionnaire_targets_create', 'create_questionnaire_targets' => function() use ($data) {
                try {
                    $user = Auth::user();
                    Log::info('Questionnaire targets create action handler called', ['data' => $data]);
                    
                    $result = CreateQuestionnaireTargets::create($user, $data);
                    
                    // If there are errors, only show error message with error emoji
                    if (!empty($result['errors'])) {
                        $errorMessage = "❌ " . implode("\n❌ ", $result['errors']);
                        return response()->json([
                            'message' => $errorMessage,
                            'test_id' => $result['test_id'],
                            'survey_id' => $result['survey_id'],
                            'refresh_type' => 'test_data',
                        ]);
                    }
                    
                    $successMessage = $data['message'] ?? 'Targets created successfully.';
                    if (isset($data['message']) && str_ends_with($successMessage, '?')) {
                        $successMessage = self::convertToPastTense($successMessage);
                    }
                    
                    return response()->json([
                        'message' => $successMessage,
                        'test_id' => $result['test_id'],
                        'survey_id' => $result['survey_id'],
                        'refresh_type' => 'test_data',
                    ]);
                } catch (\Exception $e) {
                    Log::error('Questionnaire targets create exception', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return response()->json(['error' => $e->getMessage()], 400);
                }
            },

            'app_questionnaire_update_instructions', 'act_questionnaire_update_instructions', 'app_questionnaire_instructions_update', 'act_questionnaire_instructions_update', 'update_questionnaire_instructions' => function() use ($data) {
                try {
                    $user = Auth::user();
                    Log::info('Questionnaire instructions update action handler called', ['data' => $data]);
                    
                    $result = UpdateQuestionnaireInstructions::update($user, $data);
                    
                    // If there are errors, only show error message with error emoji
                    if (!empty($result['errors'])) {
                        $errorMessage = "❌ " . implode("\n❌ ", $result['errors']);
                        return response()->json([
                            'message' => $errorMessage,
                            'test_id' => $result['test_id'],
                            'survey_id' => $result['survey_id'],
                            'refresh_type' => 'test_data',
                        ]);
                    }
                    
                    $successMessage = $data['message'] ?? 'Instructions updated successfully.';
                    if (isset($data['message']) && str_ends_with($successMessage, '?')) {
                        $successMessage = self::convertToPastTense($successMessage);
                    }
                    
                    return response()->json([
                        'message' => $successMessage,
                        'test_id' => $result['test_id'],
                        'survey_id' => $result['survey_id'],
                        'refresh_type' => 'test_data',
                    ]);
                } catch (\Exception $e) {
                    Log::error('Questionnaire instructions update exception', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return response()->json(['error' => $e->getMessage()], 400);
                }
            },

            default => null
        };

        return is_callable($action) ? $action() : response()->json(['message' => 'Invalid action'], 400);
    }

    /**
     * Convert action message to past tense
     * 
     * @param string $message The message to convert
     * @return string Message in past tense
     */
    private static function convertToPastTense(string $message): string
    {
        // Remove question mark if present
        $message = rtrim($message, '?');
        
        // Convert common verbs to past tense
        $verbReplacements = [
            '/\bAdd\b/i' => 'Added',
            '/\bCreate\b/i' => 'Created',
            '/\bUpdate\b/i' => 'Updated',
            '/\bChange\b/i' => 'Changed',
            '/\bModify\b/i' => 'Modified',
            '/\bEdit\b/i' => 'Edited',
            '/\bSet\b/i' => 'Set',
            '/\bMake\b/i' => 'Made',
        ];
        
        foreach ($verbReplacements as $pattern => $replacement) {
            $message = preg_replace($pattern, $replacement, $message);
        }
        
        return trim($message) . '.';
    }

    /**
     * Format a CSS variable and value into a user-friendly message
     * 
     * @param string $variable The CSS variable name (e.g., "--prime-bg-color")
     * @param string $value The CSS value (e.g., "orange")
     * @return string User-friendly message (e.g., "Background color of primes updated to orange")
     */
    private static function formatStyleUpdate(string $variable, string $value): string
    {
        // Remove leading -- from variable name
        $varName = ltrim($variable, '-');
        
        // Map CSS variables to user-friendly descriptions
        $variableMap = [
            'font-family' => 'Font family',
            'header-font-size' => 'Header font size',
            'header-font-weight' => 'Header font weight',
            'header-font-color' => 'Header font color',
            'header-bg-color' => 'Header background color',
            'target-font-size' => 'Target font size',
            'target-font-weight' => 'Target font weight',
            'target-font-color' => 'Target font color',
            'target-bg-color' => 'Background color of targets',
            'prime-font-size' => 'Prime font size',
            'prime-font-weight' => 'Prime font weight',
            'prime-font-color' => 'Prime font color',
            'prime-bg-color' => 'Background color of primes',
            'button-font-size' => 'Button font size',
            'button-font-weight' => 'Button font weight',
            'button-font-color' => 'Button font color',
            'button-bg-color' => 'Button background color',
            'bottom-text-font-size' => 'Footer font size',
            'bottom-text-font-weight' => 'Footer font weight',
            'bottom-text-font-color' => 'Footer font color',
            'bottom-text-color' => 'Footer font color',
            'bottom-text-font-style' => 'Footer font style',
            'bottom-text-line-height' => 'Footer line height',
            'bottom-text-width' => 'Footer width',
            'descriptive-font-size' => 'Descriptive text font size',
            'descriptive-font-weight' => 'Descriptive text font weight',
            'descriptive-font-color' => 'Descriptive text font color',
            'descriptive-bg-color' => 'Descriptive text background color',
        ];
        
        // Get user-friendly name or generate from variable
        $friendlyName = $variableMap[$varName] ?? self::generateFriendlyName($varName);
        
        return ucfirst($friendlyName) . ' updated to ' . $value;
    }

    /**
     * Generate a user-friendly name from a CSS variable name
     * 
     * @param string $varName The variable name without --
     * @return string User-friendly name
     */
    private static function generateFriendlyName(string $varName): string
    {
        // Split by hyphens and capitalize
        $parts = explode('-', $varName);
        
        // Handle common patterns
        if (count($parts) >= 2) {
            $lastPart = end($parts);
            $firstParts = array_slice($parts, 0, -1);
            
            // Map common suffixes
            $suffixMap = [
                'size' => 'size',
                'weight' => 'weight',
                'color' => 'color',
                'bg-color' => 'background color',
                'font-color' => 'font color',
            ];
            
            $suffix = $suffixMap[$lastPart] ?? $lastPart;
            $target = implode(' ', $firstParts);
            
            return $target . ' ' . $suffix;
        }
        
        return str_replace('-', ' ', $varName);
    }
}