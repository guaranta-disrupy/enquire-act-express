<?php

namespace App\Services;

use App\Models\Questionnaire;
use App\AI\Enums\AIModel;
use App\Jobs\TranslateQuestionGroupJob;
use App\Jobs\TranslateSystemMessagesJob;
use Prism\Prism\Prism;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Process;


class AITranslationService
{
    private const QUESTIONS_PER_BATCH = 3;
    private const CONCURRENT_BATCHES_PER_GROUP = 2;

    public function translateSingleQuestion(Questionnaire $questionnaire, string $questionKey, string $targetLanguageCode, string $targetLanguageName): array
    {
        Log::info('Starting single question translation', [
            'questionnaire_code' => $questionnaire->code,
            'question_key' => $questionKey,
            'target_language' => $targetLanguageCode,
            'target_language_name' => $targetLanguageName,
            'path' => "single_question_translation_{$questionKey}",
            'timestamp' => now()->toDateTimeString()
        ]);
        
        $translatableContent = $this->extractTranslatableContent($questionnaire);
        
        $question = null;
        foreach ($translatableContent['questions'] as $q) {
            if ($q['key'] === $questionKey) {
                $question = $q;
                break;
            }
        }
        
        if (!$question) {
            Log::warning('Question not found for translation', [
                'question_key' => $questionKey,
                'available_keys' => array_column($translatableContent['questions'], 'key')
            ]);
            return [];
        }
        
        $questionSize = strlen(json_encode($question));
        Log::info('Question data extracted', [
            'question_key' => $questionKey,
            'question_type_id' => $question['question_type_id'] ?? 'N/A',
            'has_options' => !empty($question['options']),
            'has_items' => !empty($question['items']),
            'has_values' => !empty($question['values']),
            'question_size_bytes' => $questionSize,
            'options_count' => count($question['options'] ?? []),
            'items_count' => count($question['items'] ?? []),
            'values_count' => count($question['values'] ?? [])
        ]);
        
        $content = ['questions' => [$question]];
        $prompt = $this->buildQuestionBatchPrompt($content, $targetLanguageCode, $targetLanguageName);
        $promptSize = strlen($prompt);
        
        Log::info('Translation prompt built', [
            'question_key' => $questionKey,
            'prompt_size_bytes' => $promptSize,
            'prompt_size_kb' => round($promptSize / 1024, 2)
        ]);
        
        try {
            $startTime = microtime(true);
            
            $response = Prism::text()
                ->using(...AIModel::defaultModel4())
                ->withPrompt($prompt)
                ->withMaxTokens(4096)
                ->asText();

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);
            
            Log::info('AI translation API call completed', [
                'question_key' => $questionKey,
                'duration_seconds' => $duration,
                'response_length' => strlen($response->text)
            ]);

            $result = $this->parseQuestionBatchResponse($response->text);
            $translatedData = $result[$questionKey] ?? [];
            
            Log::info('Question translation parsed successfully', [
                'question_key' => $questionKey,
                'has_translation' => !empty($translatedData),
                'translated_question' => !empty($translatedData['trans']),
                'translated_options_count' => count($translatedData['options'] ?? []),
                'translated_items_count' => count($translatedData['items'] ?? []),
                'translated_values_count' => count($translatedData['values'] ?? [])
            ]);
            
            return $translatedData;
        } catch (\Exception $e) {
            Log::error('Single question translation failed', [
                'question_key' => $questionKey,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    public function translateSystemMessagesOnly(Questionnaire $questionnaire, string $targetLanguageCode, string $targetLanguageName): array
    {
        Log::info('Starting system messages translation', [
            'questionnaire_code' => $questionnaire->code,
            'target_language' => $targetLanguageCode,
            'target_language_name' => $targetLanguageName,
            'timestamp' => now()->toDateTimeString()
        ]);
        
        $translatableContent = $this->extractTranslatableContent($questionnaire);
        
        if (empty($translatableContent['systemMessages'])) {
            Log::warning('No system messages found to translate');
            return [];
        }
        
        $messagesCount = count(array_filter($translatableContent['systemMessages'], fn($v) => !empty($v)));
        Log::info('System messages extracted', [
            'total_message_keys' => count($translatableContent['systemMessages']),
            'non_empty_messages' => $messagesCount,
            'message_keys' => array_keys(array_filter($translatableContent['systemMessages'], fn($v) => !empty($v)))
        ]);
        
        $startTime = microtime(true);
        $result = $this->translateSystemMessages($translatableContent['systemMessages'], $targetLanguageCode, $targetLanguageName);
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        Log::info('System messages translation completed', [
            'duration_seconds' => $duration,
            'translated_messages_count' => count($result)
        ]);
        
        return $result;
    }

    public function translateQuestionnaire(Questionnaire $questionnaire, string $targetLanguageCode, string $targetLanguageName): array
    {
        $translatableContent = $this->extractTranslatableContent($questionnaire);
        
        if (empty($translatableContent['questions']) && empty($translatableContent['systemMessages'])) {
            return [
                'groupedData' => [],
                'systemMessages' => []
            ];
        }

        $groupedData = [];
        
        if (!empty($translatableContent['questions'])) {
            $groupedData = $this->translateQuestionsInParallel($translatableContent['questions'], $targetLanguageCode, $targetLanguageName);
        }

        $systemMessages = [];
        if (!empty($translatableContent['systemMessages'])) {
            $systemMessages = $this->translateSystemMessages($translatableContent['systemMessages'], $targetLanguageCode, $targetLanguageName);
        }

        return [
            'groupedData' => $groupedData,
            'systemMessages' => $systemMessages
        ];
    }

    public function translateQuestionsInParallel(array $questions, string $targetLanguageCode, string $targetLanguageName, ?array $systemMessagesInfo = null, bool $asyncMode = false): array
    {
        $totalQuestions = count($questions);

        $oddQuestions = [];
        $evenQuestions = [];
        $oddKeys = [];
        $evenKeys = [];
        
        foreach ($questions as $index => $question) {
            $questionKey = $question['key'] ?? "Q{$index}";
            if ($index % 2 === 0) {
                $evenQuestions[] = $question;
                $evenKeys[] = $questionKey;
            } else {
                $oddQuestions[] = $question;
                $oddKeys[] = $questionKey;
            }
        }

        Log::info('Starting parallel translation - Question grouping', [
            'total_questions' => $totalQuestions,
            'async_mode' => $asyncMode,
            'odd_group' => [
                'count' => count($oddQuestions),
                'question_keys' => $oddKeys,
                'path' => 'odd_group'
            ],
            'even_group' => [
                'count' => count($evenQuestions),
                'question_keys' => $evenKeys,
                'path' => 'even_group'
            ],
            'all_question_keys' => array_column($questions, 'key')
        ]);

        $startTime = microtime(true);

        $oddResults = [];
        $evenResults = [];
        $errors = [];

        $questionnaireCode = $systemMessagesInfo['questionnaireCode'] ?? null;

        try {
            // Use Laravel storage path for temp files - ensures shared access across containers
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            $uniqueId = uniqid('trans_', true);
            $oddOutputFile = $tempDir . '/trans_odd_' . $uniqueId;
            $evenOutputFile = $tempDir . '/trans_even_' . $uniqueId;
            // Create empty files to ensure they exist
            touch($oddOutputFile);
            touch($evenOutputFile);

            // Output files are kept for backward compatibility
            $jobs = [
                new TranslateQuestionGroupJob($oddQuestions, $targetLanguageCode, $targetLanguageName, 'odd', $questionnaireCode, $oddOutputFile),
                new TranslateQuestionGroupJob($evenQuestions, $targetLanguageCode, $targetLanguageName, 'even', $questionnaireCode, $evenOutputFile)
            ];
            if ($systemMessagesInfo) {
                Log::info('System messages job added to batch for parallel execution', [
                    'questionnaire_code' => $systemMessagesInfo['questionnaireCode'],
                    'rtl' => $systemMessagesInfo['rtl']
                ]);
                
                $jobs[] = new TranslateSystemMessagesJob(
                    $systemMessagesInfo['questionnaireCode'],
                    $targetLanguageCode,
                    $targetLanguageName,
                    $systemMessagesInfo['rtl']
                );
            }

            $batch = Bus::batch($jobs)->onConnection('database')->onQueue('translations')->dispatch();

            // In async mode, return immediately after dispatching jobs
            // Jobs will save translations directly to DB and broadcast updates via websockets
            if ($asyncMode) {
                Log::info("Translation jobs dispatched in async mode - returning immediately", [
                    'batch_id' => $batch->id,
                    'questionnaire_code' => $questionnaireCode,
                    'odd_keys' => $oddKeys,
                    'even_keys' => $evenKeys
                ]);
                return []; // Return empty - frontend will receive updates via websockets
            }

            $batch = $batch->fresh();
            $timeout = 300;
            $elapsed = 0;
            while (!$batch->finished() && $elapsed < $timeout) {
                sleep(1);
                $elapsed++;
                $batch = $batch->fresh();
            }

            Log::info("Parallel jobs completed, processing results", [
                'batch_id' => $batch->id,
                'finished' => $batch->finished(),
                'failed_jobs' => $batch->failedJobs,
                'odd_keys_count' => count($oddKeys),
                'even_keys_count' => count($evenKeys),
                'system_messages_included' => $systemMessagesInfo !== null
            ]);
            // Note: System messages job runs in parallel with question translations when systemMessagesInfo is provided

            clearstatcache(true, $oddOutputFile);
            clearstatcache(true, $evenOutputFile);

            if (!$batch->finished() || $batch->failedJobs > 0) {
                $errors[] = [
                    'error' => 'Batch jobs did not complete successfully',
                    'batch_id' => $batch->id,
                    'failed_jobs' => $batch->failedJobs,
                    'finished' => $batch->finished()
                ];
            } else {
                if (file_exists($oddOutputFile)) {
                    $oddContent = file_get_contents($oddOutputFile);
                    if (!empty($oddContent)) {
                        $oddResults = json_decode($oddContent, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $errors[] = [
                                'group' => 'odd',
                                'question_keys' => $oddKeys,
                                'error' => 'Invalid JSON in odd results: ' . json_last_error_msg()
                            ];
                            $oddResults = [];
                        } elseif (empty($oddResults) || !is_array($oddResults)) {
                            $errors[] = [
                                'group' => 'odd',
                                'question_keys' => $oddKeys,
                                'error' => 'Empty or invalid odd results'
                            ];
                            $oddResults = [];
                        } else {
                            $translatedKeys = array_keys($oddResults);
                            Log::info("Parallel group translation completed", [
                                'group' => 'odd',
                                'path' => "parallel_odd_group",
                                'question_keys' => $oddKeys,
                                'translated_keys' => $translatedKeys,
                                'translated_count' => count($oddResults)
                            ]);
                        }
                    } else {
                        $errors[] = [
                            'group' => 'odd',
                            'question_keys' => $oddKeys,
                            'error' => 'Odd output file is empty'
                        ];
                        $oddResults = [];
                    }
                } else {
                    $errors[] = [
                        'group' => 'odd',
                        'question_keys' => $oddKeys,
                        'error' => 'Odd output file not found'
                    ];
                    $oddResults = [];
                }

                if (file_exists($evenOutputFile)) {
                    $evenContent = file_get_contents($evenOutputFile);
                    if (!empty($evenContent)) {
                        $evenResults = json_decode($evenContent, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $errors[] = [
                                'group' => 'even',
                                'question_keys' => $evenKeys,
                                'error' => 'Invalid JSON in even results: ' . json_last_error_msg()
                            ];
                            $evenResults = [];
                        } elseif (empty($evenResults) || !is_array($evenResults)) {
                            $errors[] = [
                                'group' => 'even',
                                'question_keys' => $evenKeys,
                                'error' => 'Empty or invalid even results'
                            ];
                            $evenResults = [];
                        } else {
                            $translatedKeys = array_keys($evenResults);
                            Log::info("Parallel group translation completed", [
                                'group' => 'even',
                                'path' => "parallel_even_group",
                                'question_keys' => $evenKeys,
                                'translated_keys' => $translatedKeys,
                                'translated_count' => count($evenResults)
                            ]);
                        }
                    } else {
                        $errors[] = [
                            'group' => 'even',
                            'question_keys' => $evenKeys,
                            'error' => 'Even output file is empty'
                        ];
                        $evenResults = [];
                    }
                } else {
                    $errors[] = [
                        'group' => 'even',
                        'question_keys' => $evenKeys,
                        'error' => 'Even output file not found'
                    ];
                    $evenResults = [];
                }
            }

            @unlink($oddOutputFile);
            @unlink($evenOutputFile);

            $failedGroups = [];
            if (empty($oddResults) && !empty($oddQuestions)) {
                $failedGroups[] = ['group' => 'odd', 'questions' => $oddQuestions, 'keys' => $oddKeys];
            }
            if (empty($evenResults) && !empty($evenQuestions)) {
                $failedGroups[] = ['group' => 'even', 'questions' => $evenQuestions, 'keys' => $evenKeys];
            }

            if (!empty($failedGroups)) {
                Log::warning('Some parallel groups failed, retranslating failed groups only', [
                    'errors' => $errors,
                    'failed_groups' => array_column($failedGroups, 'group'),
                    'odd_results_count' => count($oddResults),
                    'even_results_count' => count($evenResults)
                ]);

                foreach ($failedGroups as $failedGroup) {
                    $retranslated = $this->translateQuestionsSequentially($failedGroup['questions'], $targetLanguageCode, $targetLanguageName);
                    if ($failedGroup['group'] === 'odd') {
                        $oddResults = $retranslated;
                    } else {
                        $evenResults = $retranslated;
                    }
                }
            }

            if (empty($oddResults) && empty($evenResults)) {
                Log::warning('All parallel groups failed, falling back to full sequential', [
                    'errors' => $errors
                ]);
                return $this->translateQuestionsSequentially($questions, $targetLanguageCode, $targetLanguageName);
            }

        } catch (\Exception $e) {
            Log::warning('Parallel execution failed, falling back to sequential', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->translateQuestionsSequentially($questions, $targetLanguageCode, $targetLanguageName);
        }

        $groupedData = $this->mergeParallelResults($questions, $oddResults, $evenResults);

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        Log::info('Parallel translation completed', [
            'duration_seconds' => $duration,
            'total_questions' => $totalQuestions,
            'translated_count' => count($groupedData),
            'speedup' => '~2x (parallel)'
        ]);

        return $groupedData;
    }

    //Sequential translation fallback
    private function translateQuestionsSequentially(array $questions, string $targetLanguageCode, string $targetLanguageName): array
    {
        $groupedData = [];
        $questionBatches = array_chunk($questions, self::QUESTIONS_PER_BATCH);
        
        foreach ($questionBatches as $batchIndex => $batch) {
            $batchKeys = array_column($batch, 'key');
            Log::info("Translating question batch", [
                'batch' => $batchIndex + 1,
                'total_batches' => count($questionBatches),
                'questions_in_batch' => count($batch),
                'question_keys' => $batchKeys,
                'path' => "sequential_batch_{$batchIndex}"
            ]);
            
            $batchStartTime = microtime(true);
            $batchResult = $this->translateQuestionBatch($batch, $targetLanguageCode, $targetLanguageName, null);
            $batchDuration = round(microtime(true) - $batchStartTime, 2);
            
            Log::info("Question batch translation completed", [
                'batch' => $batchIndex + 1,
                'question_keys' => $batchKeys,
                'duration_seconds' => $batchDuration,
                'translated_count' => count($batchResult)
            ]);
            
            $groupedData = array_merge($groupedData, $batchResult);
            
            if ($batchIndex < count($questionBatches) - 1) {
                usleep(200000);
            }
        }

        return $groupedData;
    }

    //Merge parallel translation results maintaining original question order
    private function mergeParallelResults(array $originalQuestions, array $oddResults, array $evenResults): array
    {
        $merged = [];
        
        foreach ($originalQuestions as $index => $question) {
            $key = $question['key'] ?? '';
            
            if ($index % 2 === 0) {
                if (isset($evenResults[$key])) {
                    $merged[$key] = $evenResults[$key];
                }
            } else {
                if (isset($oddResults[$key])) {
                    $merged[$key] = $oddResults[$key];
                }
            }
        }

        return $merged;
    }

    //Translate a group of questions (used by parallel processes)
    public function translateQuestionGroup(array $questions, string $targetLanguageName, ?string $groupContext = null): array
    {
        $targetLanguageCode = $this->extractLanguageCodeFromName($targetLanguageName);
        
        $groupedData = [];
        $allQuestionKeys = array_column($questions, 'key');
        $totalQuestions = count($questions);
        // Always use sequential batches when called from queue jobs (groupContext is set)
        // Process::concurrently() doesn't work inside Docker queue workers
        // The parallel jobs (odd/even) already provide parallelism, so we don't need nested concurrency
        $useSequentialBatches = ($groupContext !== null) || $totalQuestions <= 5;
        
        Log::info("Processing question group with concurrent chunking", [
            'group_context' => $groupContext,
            'total_questions' => $totalQuestions,
            'question_keys' => $allQuestionKeys,
            'chunk_size' => self::QUESTIONS_PER_BATCH,
            'concurrent_batches' => $useSequentialBatches ? 1 : self::CONCURRENT_BATCHES_PER_GROUP,
            'path' => $groupContext ? "parallel_{$groupContext}_group" : 'sequential_group'
        ]);
        
        $questionBatches = array_chunk($questions, self::QUESTIONS_PER_BATCH);
        $totalBatches = count($questionBatches);
        
        if ($useSequentialBatches) {
            foreach ($questionBatches as $batchIndex => $batch) {
                $batchKeys = array_column($batch, 'key');
                Log::info("Processing batch sequentially within group", [
                    'group_context' => $groupContext,
                    'batch' => $batchIndex + 1,
                    'total_batches' => $totalBatches,
                    'question_keys' => $batchKeys,
                    'path' => $groupContext ? "parallel_{$groupContext}_sequential_batch_{$batchIndex}" : "sequential_batch_{$batchIndex}"
                ]);
                
                $batchStartTime = microtime(true);
                $result = $this->translateQuestionBatch($batch, $targetLanguageCode, $targetLanguageName, $groupContext);
                $batchDuration = round(microtime(true) - $batchStartTime, 2);
                
                if (is_array($result) && count($result) > 0) {
                    $groupedData = array_merge($groupedData, $result);
                }
                
                Log::info("Batch completed within group", [
                    'group_context' => $groupContext,
                    'batch' => $batchIndex + 1,
                    'duration_seconds' => $batchDuration,
                    'translated_count' => count($groupedData)
                ]);
            }
        } else {
            $batchIndex = 0;
            while ($batchIndex < $totalBatches) {
                $concurrentBatches = [];
                $batchKeys = [];
                
                for ($i = 0; $i < self::CONCURRENT_BATCHES_PER_GROUP && ($batchIndex + $i) < $totalBatches; $i++) {
                    $currentBatchIndex = $batchIndex + $i;
                    $batch = $questionBatches[$currentBatchIndex];
                    $concurrentBatches[] = $batch;
                    $batchKeys[] = [
                        'batch_index' => $currentBatchIndex + 1,
                        'question_keys' => array_column($batch, 'key')
                    ];
                }
                
                Log::info("Processing concurrent batches within group", [
                    'group_context' => $groupContext,
                    'batch_range' => ($batchIndex + 1) . '-' . min($batchIndex + count($concurrentBatches), $totalBatches),
                    'total_batches' => $totalBatches,
                    'concurrent_count' => count($concurrentBatches),
                    'batches' => $batchKeys,
                    'path' => $groupContext ? "parallel_{$groupContext}_concurrent_batches_{$batchIndex}" : "sequential_concurrent_batches_{$batchIndex}"
                ]);
                
                $concurrentStartTime = microtime(true);
                $concurrentResults = $this->translateBatchesConcurrently($concurrentBatches, $targetLanguageCode, $targetLanguageName, $groupContext);
                $concurrentDuration = round(microtime(true) - $concurrentStartTime, 2);
                
                foreach ($concurrentResults as $index => $result) {
                    if (is_array($result) && count($result) > 0) {
                        $groupedData = array_merge($groupedData, $result);
                    } else {
                        Log::warning("Concurrent batch returned empty result, processing sequentially", [
                            'group_context' => $groupContext,
                            'batch_index' => $batchIndex + $index + 1,
                            'question_keys' => array_column($concurrentBatches[$index], 'key')
                        ]);
                        $sequentialResult = $this->translateQuestionBatch($concurrentBatches[$index], $targetLanguageCode, $targetLanguageName, $groupContext);
                        $groupedData = array_merge($groupedData, $sequentialResult);
                    }
                }
                
                Log::info("Concurrent batches completed", [
                    'group_context' => $groupContext,
                    'batch_range' => ($batchIndex + 1) . '-' . min($batchIndex + count($concurrentBatches), $totalBatches),
                    'duration_seconds' => $concurrentDuration,
                    'translated_count' => count($groupedData)
                ]);
                
                $batchIndex += count($concurrentBatches);
            }
        }

        Log::info("Question group processing completed", [
            'group_context' => $groupContext,
            'total_questions' => count($questions),
            'question_keys' => $allQuestionKeys,
            'translated_count' => count($groupedData)
        ]);

        return $groupedData;
    }
    
    //Process multiple batches concurrently within a group
    private function translateBatchesConcurrently(array $batches, string $targetLanguageCode, string $targetLanguageName, ?string $groupContext = null): array
    {
        if (count($batches) === 1) {
            return [$this->translateQuestionBatch($batches[0], $targetLanguageCode, $targetLanguageName, $groupContext)];
        }
        
        $results = [];
        $useTempFiles = false;
        $tempFiles = [];
        
        foreach ($batches as $index => $batch) {
            $batchSize = strlen(json_encode($batch));
            if ($batchSize > 50000) {
                $useTempFiles = true;
                break;
            }
        }
        
        try {
            if ($useTempFiles) {
                foreach ($batches as $index => $batch) {
                    $tempFile = tempnam(sys_get_temp_dir(), "trans_batch_{$index}_");
                    file_put_contents($tempFile, json_encode($batch));
                    $tempFiles[] = $tempFile;
                }
                
                $processes = \Illuminate\Support\Facades\Process::concurrently(function ($pool) use ($tempFiles, $targetLanguageName, $groupContext) {
                    foreach ($tempFiles as $index => $tempFile) {
                        $pool->command('php', [
                            base_path('artisan'),
                            'translate:question-batch',
                            '--file=' . $tempFile,
                            '--language=' . $targetLanguageName,
                            '--group=' . ($groupContext ?? 'none')
                        ])->timeout(300);
                    }
                });
                
                foreach ($tempFiles as $tempFile) {
                    @unlink($tempFile);
                }
            } else {
                $processes = \Illuminate\Support\Facades\Process::concurrently(function ($pool) use ($batches, $targetLanguageName, $groupContext) {
                    foreach ($batches as $index => $batch) {
                        $pool->command('php', [
                            base_path('artisan'),
                            'translate:question-batch',
                            '--questions=' . base64_encode(json_encode($batch)),
                            '--language=' . $targetLanguageName,
                            '--group=' . ($groupContext ?? 'none')
                        ])->timeout(300);
                    }
                });
            }
            
            foreach ($processes as $index => $process) {
                $batchKeys = array_column($batches[$index], 'key');
                
                if ($process->successful()) {
                    $output = trim($process->output());
                    
                    if (empty($output)) {
                        Log::warning("Concurrent batch translation failed - empty output", [
                            'batch_index' => $index + 1,
                            'question_keys' => $batchKeys,
                            'exit_code' => $process->exitCode(),
                            'error_output' => $process->errorOutput()
                        ]);
                        $results[] = $this->translateQuestionBatch($batches[$index], $targetLanguageCode, $targetLanguageName, $groupContext);
                        continue;
                    }
                    
                    $result = json_decode($output, true);
                    
                    if (json_last_error() === JSON_ERROR_NONE && is_array($result) && !empty($result)) {
                        Log::info("Concurrent batch translation completed", [
                            'batch_index' => $index + 1,
                            'question_keys' => $batchKeys,
                            'translated_keys' => array_keys($result),
                            'path' => $groupContext ? "concurrent_batch_{$groupContext}_{$index}" : "concurrent_batch_{$index}"
                        ]);
                        $results[] = $result;
                    } else {
                        Log::warning("Concurrent batch translation failed - invalid JSON or empty result", [
                            'batch_index' => $index + 1,
                            'question_keys' => $batchKeys,
                            'error' => json_last_error_msg(),
                            'output_preview' => substr($output, 0, 200)
                        ]);
                        $results[] = $this->translateQuestionBatch($batches[$index], $targetLanguageCode, $targetLanguageName, $groupContext);
                    }
                } else {
                    Log::warning("Concurrent batch translation failed - process error", [
                        'batch_index' => $index + 1,
                        'question_keys' => $batchKeys,
                        'exit_code' => $process->exitCode(),
                        'error' => $process->errorOutput()
                    ]);
                    $results[] = $this->translateQuestionBatch($batches[$index], $targetLanguageCode, $targetLanguageName, $groupContext);
                }
            }
        } catch (\Exception $e) {
            Log::warning("Concurrent batch processing failed, falling back to sequential", [
                'error' => $e->getMessage(),
                'group_context' => $groupContext
            ]);
            
            foreach ($batches as $batch) {
                $results[] = $this->translateQuestionBatch($batch, $targetLanguageCode, $targetLanguageName, $groupContext);
            }
        }
        
        return $results;
    }

    public function translateQuestionBatch(array $questions, string $targetLanguageCode, string $targetLanguageName, ?string $groupContext = null): array
    {
        $questionKeys = array_column($questions, 'key');
        $content = ['questions' => $questions];
        $prompt = $this->buildQuestionBatchPrompt($content, $targetLanguageCode, $targetLanguageName, $groupContext);
        
        Log::info("Translating question batch via AI", [
            'question_keys' => $questionKeys,
            'question_count' => count($questions),
            'group_context' => $groupContext,
            'path' => $groupContext ? "ai_translate_{$groupContext}_batch" : 'ai_translate_batch',
            'prompt_size_bytes' => strlen($prompt)
        ]);
        
        try {
            $apiStartTime = microtime(true);
            $response = Prism::text()
                ->using(...AIModel::defaultModel4())
                ->withPrompt($prompt)
                ->withMaxTokens(4096)
                ->asText();
            $apiDuration = round(microtime(true) - $apiStartTime, 2);

            $result = $this->parseQuestionBatchResponse($response->text);
            $translatedKeys = array_keys($result);
            
            Log::info("AI batch translation completed", [
                'question_keys' => $questionKeys,
                'translated_keys' => $translatedKeys,
                'duration_seconds' => $apiDuration,
                'response_length' => strlen($response->text),
                'path' => $groupContext ? "ai_translate_{$groupContext}_batch_complete" : 'ai_translate_batch_complete'
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Question batch translation failed', [
                'error' => $e->getMessage(),
                'question_keys' => $questionKeys,
                'question_count' => count($questions),
                'group' => $groupContext,
                'path' => $groupContext ? "ai_translate_{$groupContext}_batch_error" : 'ai_translate_batch_error'
            ]);
            
            if (strpos($e->getMessage(), 'timed out') !== false || strpos($e->getMessage(), 'timeout') !== false) {
                throw new \Exception('Translation request timed out. Please try again or use CSV upload for large questionnaires.');
            }
            
            throw $e;
        }
    }

    private function translateSystemMessages(array $systemMessages, string $targetLanguageCode, string $targetLanguageName): array
    {
        $prompt = $this->buildSystemMessagesPrompt($systemMessages, $targetLanguageCode, $targetLanguageName);
        $promptSize = strlen($prompt);
        
        Log::info('System messages prompt built', [
            'prompt_size_bytes' => $promptSize,
            'prompt_size_kb' => round($promptSize / 1024, 2),
            'messages_count' => count(array_filter($systemMessages, fn($v) => !empty($v)))
        ]);
        
        try {
            $startTime = microtime(true);
            
            $response = Prism::text()
                ->using(...AIModel::defaultModel4())
                ->withPrompt($prompt)
                ->withMaxTokens(4096)
                ->asText();

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);
            
            Log::info('System messages AI API call completed', [
                'duration_seconds' => $duration,
                'response_length' => strlen($response->text)
            ]);

            return $this->parseSystemMessagesResponse($response->text);
        } catch (\Exception $e) {
            Log::error('System messages translation failed', [
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'prompt_size' => $promptSize
            ]);
            
            throw $e;
        }
    }

    private function buildQuestionBatchPrompt(array $content, string $targetLanguageCode, string $targetLanguageName, ?string $groupContext = null): string
    {
        $prompt = '';
        
        if ($this->isEnglishVariant($targetLanguageCode)) {
            $translationPrompt = config('ai_prompts.base_prompts.translation', '');
            if (!empty($translationPrompt)) {
                $prompt .= $translationPrompt . "\n\n";
            }
        }
        
        $prompt .= "Translate the following survey questions to {$targetLanguageName}. Maintain the exact same structure, thought and format. DO NOT translate brand names. Return ONLY a valid JSON object.\n\n";
        
        if ($groupContext) {
            $prompt .= "IMPORTANT: These questions are part of a larger survey being translated in parallel. Maintain consistent translation style, terminology, and tone across all questions. Use the same translation approach for similar terms and phrases.\n\n";
        }
        
        $prompt .= "Important: For questions with question_type_id 16, items must be objects with \"statementA\" and \"statementB\" properties.\n\n";
        $prompt .= "Important: For slider questions (question_type_id 8), translate the \"choices\" array which contains the scale labels/choices for the slider. If \"choices\" array is present in the input, you MUST include it in your response.\n\n";
        $prompt .= "Important: For matrix questions (question_type_id 11), translate both \"columns\" (answer choices/options) and \"rows\" (statements/aspects being evaluated). If \"columns\" or \"rows\" arrays are present in the input, you MUST include them in your response with translated values.\n\n";
        $prompt .= "JSON Structure:\n";
        $prompt .= "{\n";
        $prompt .= "  \"questions\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"key\": \"Q1\",\n";
        $prompt .= "      \"question\": \"translated text\",\n";
        $prompt .= "      \"options\": [\"option1\"],\n";
        $prompt .= "      \"items\": [\"item1\"],\n";
        $prompt .= "      \"items\" (for type 16): [{\"statementA\": \"text\", \"statementB\": \"text\"}],\n";
        $prompt .= "      \"values\": [\"value1\"],\n";
        $prompt .= "      \"choices\": [\"choice1\"],\n";
        $prompt .= "      \"columns\": [\"column1\"],\n";
        $prompt .= "      \"rows\": [\"row1\"],\n";
        $prompt .= "      \"header\": \"translated header\",\n";
        $prompt .= "      \"footer\": \"translated footer\",\n";
        $prompt .= "      \"labelA\": \"translated labelA\",\n";
        $prompt .= "      \"labelB\": \"translated labelB\"\n";
        $prompt .= "    }\n";
        $prompt .= "  ]\n";
        $prompt .= "}\n\n";
        $prompt .= "Questions to translate:\n\n";
        $prompt .= json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return $prompt;
    }

    private function buildSystemMessagesPrompt(array $systemMessages, string $targetLanguageCode, string $targetLanguageName): string
    {
        $prompt = '';
        
        // Add English variant localization instructions if translating between English variants
        if ($this->isEnglishVariant($targetLanguageCode)) {
            $translationPrompt = config('ai_prompts.base_prompts.translation', '');
            if (!empty($translationPrompt)) {
                $prompt .= $translationPrompt . "\n\n";
            }
        }
        
        $prompt .= "Translate the following system messages to {$targetLanguageName}. Maintain the exact same structure, thought and format. DO NOT translate brand names. Return ONLY a valid JSON object.\n\n";
        $prompt .= "JSON Structure:\n";
        $prompt .= "{\n";
        $prompt .= "  \"systemMessages\": {\n";
        $prompt .= "    \"loading\": \"translated text\",\n";
        $prompt .= "    \"saving\": \"translated text\",\n";
        $prompt .= "    ... (all other system messages)\n";
        $prompt .= "  }\n";
        $prompt .= "}\n\n";
        $prompt .= "System messages to translate:\n\n";
        $prompt .= json_encode(['systemMessages' => $systemMessages], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return $prompt;
    }

    private function parseQuestionBatchResponse(string $response): array
    {
        $response = trim($response);
        
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $response, $matches)) {
            $jsonString = $matches[1];
        } else {
            $jsonStart = strpos($response, '{');
            $jsonEnd = strrpos($response, '}');
            
            if ($jsonStart === false || $jsonEnd === false) {
                throw new \Exception('Invalid translation response format');
            }
            
            $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        }
        
        $translated = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse translation JSON: ' . json_last_error_msg());
        }

        $groupedData = [];
        foreach ($translated['questions'] ?? [] as $qData) {
            $key = $qData['key'] ?? '';
            if (empty($key)) continue;
            
            $groupedData[$key] = [
                'orig' => '',
                'trans' => $qData['question'] ?? '',
                'options' => [],
                'items' => [],
                'values' => [],
                'choices' => [],
                'columns' => [],
                'rows' => [],
            ];

            foreach ($qData['options'] ?? [] as $option) {
                $groupedData[$key]['options'][] = [
                    'orig' => '',
                    'trans' => $option
                ];
            }

            foreach ($qData['items'] ?? [] as $index => $item) {
                if (is_array($item)) {
                    $groupedData[$key]['items'][] = [
                        'orig' => '',
                        'trans' => $item['statementA'] ?? '',
                        'statementB' => $item['statementB'] ?? ''
                    ];
                } else {
                    $groupedData[$key]['items'][] = [
                        'orig' => '',
                        'trans' => $item
                    ];
                }
            }

            foreach ($qData['values'] ?? [] as $value) {
                $groupedData[$key]['values'][] = [
                    'orig' => '',
                    'trans' => $value
                ];
            }

            foreach ($qData['choices'] ?? [] as $choice) {
                $groupedData[$key]['choices'][] = [
                    'orig' => '',
                    'trans' => $choice
                ];
            }

            foreach ($qData['columns'] ?? [] as $column) {
                $groupedData[$key]['columns'][] = [
                    'orig' => '',
                    'trans' => $column
                ];
            }

            foreach ($qData['rows'] ?? [] as $row) {
                $groupedData[$key]['rows'][] = [
                    'orig' => '',
                    'trans' => $row
                ];
            }

            if (isset($qData['header'])) {
                $groupedData[$key]['header'] = [
                    'orig' => '',
                    'trans' => $qData['header']
                ];
            }

            if (isset($qData['footer'])) {
                $groupedData[$key]['footer'] = [
                    'orig' => '',
                    'trans' => $qData['footer']
                ];
            }

            if (isset($qData['labelA'])) {
                $groupedData[$key]['labelA'] = [
                    'orig' => '',
                    'trans' => $qData['labelA']
                ];
            }

            if (isset($qData['labelB'])) {
                $groupedData[$key]['labelB'] = [
                    'orig' => '',
                    'trans' => $qData['labelB']
                ];
            }
        }

        return $groupedData;
    }

    private function parseSystemMessagesResponse(string $response): array
    {
        $response = trim($response);
        
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $response, $matches)) {
            $jsonString = $matches[1];
        } else {
            $jsonStart = strpos($response, '{');
            $jsonEnd = strrpos($response, '}');
            
            if ($jsonStart === false || $jsonEnd === false) {
                throw new \Exception('Invalid translation response format');
            }
            
            $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        }
        
        $translated = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse translation JSON: ' . json_last_error_msg());
        }

        $systemMessages = [];
        $messageKeys = [
            'loading',
            'saving',
            'nextBtn',
            'prevBtn',
            'finalBtn',
            'input1_warning_min',
            'input1_warning_max',
            'input2_warning_minmax',
            'input2_warning_min',
            'input2_warning_max',
            'input9_warning_alphanumeric',
            'input8_warning_letters',
            'input4_warning_invalidemail',
            'required_warning',
            'complete',
            'qualityControl',
            'screenout',
            'connectionLostTitle',
            'connectionLostContent',
        ];

        foreach ($messageKeys as $key) {
            if (isset($translated['systemMessages'][$key])) {
                $systemMessages[] = [
                    'orig' => '',
                    'trans' => $translated['systemMessages'][$key]
                ];
            }
        }

        return $systemMessages;
    }

    public function extractQuestionsByKeys(Questionnaire $questionnaire, array $questionKeys): array
    {
        $translatableContent = $this->extractTranslatableContent($questionnaire);
        $questionKeysSet = array_flip($questionKeys);
        $questionsMap = [];
        
        foreach ($translatableContent['questions'] as $question) {
            if (isset($questionKeysSet[$question['key']])) {
                $questionsMap[$question['key']] = $question;
            }
        }
        
        $result = [];
        foreach ($questionKeys as $key) {
            if (isset($questionsMap[$key])) {
                $result[] = $questionsMap[$key];
            }
        }
        
        return $result;
    }

    private function extractTranslatableContent(Questionnaire $questionnaire): array
    {
        $variable_start = collect($questionnaire->setup)->get('variable_start', 1);
        $questions = collect($questionnaire->questions)->sortBy('order')->toArray();
        
        $content = [
            'questions' => [],
            'systemMessages' => []
        ];

        foreach ($questions as $question) {
            $number = $question['order'] + ($variable_start - 1);
            $qKey = 'Q' . $number;
            
            $questionData = [
                'key' => $qKey,
                'question' => $question['question'] ?? '',
                'question_type_id' => $question['question_type_id'],
                'options' => [],
                'items' => [],
                'values' => [],
                'choices' => [],
                'columns' => [],
                'rows' => [],
                'header' => null,
                'footer' => null,
                'labelA' => null,
                'labelB' => null,
            ];

            if ($question['question_type_id'] == 2 || $question['question_type_id'] == 3 || $question['question_type_id'] == 20) {
                foreach ($question['choices'] ?? [] as $choice) {
                    $questionData['options'][] = $choice['name'] ?? '';
                }
            }

            if ($question['question_type_id'] == 5 || $question['question_type_id'] == 7 || 
                $question['question_type_id'] == 8 || $question['question_type_id'] == 9 || 
                $question['question_type_id'] == 10 || $question['question_type_id'] == 11 || 
                $question['question_type_id'] == 12 || $question['question_type_id'] == 13 || 
                $question['question_type_id'] == 14 || $question['question_type_id'] == 15 || 
                $question['question_type_id'] == 16 || $question['question_type_id'] == 17 || 
                $question['question_type_id'] == 18 || $question['question_type_id'] == 19) {
                foreach ($question['items'] ?? [] as $item) {
                    if ($question['question_type_id'] == 16) {
                        $questionData['items'][] = [
                            'statementA' => $item['statementA'] ?? '',
                            'statementB' => $item['statementB'] ?? ''
                        ];
                    } else {
                        $questionData['items'][] = $item['name'] ?? '';
                    }
                }
            }

            if ($question['question_type_id'] == 8 || $question['question_type_id'] == 15 || $question['question_type_id'] == 16) {
                foreach ($question['values'] ?? [] as $value) {
                    $questionData['values'][] = $value['name'] ?? '';
                }
            }

            if ($question['question_type_id'] == 8) {
                foreach ($question['choices'] ?? [] as $choice) {
                    $questionData['choices'][] = $choice['name'] ?? '';
                }
            }

            if ($question['question_type_id'] == 11) {
                foreach ($question['columns'] ?? [] as $column) {
                    $questionData['columns'][] = $column['name'] ?? '';
                }
                foreach ($question['rows'] ?? [] as $row) {
                    $questionData['rows'][] = $row['name'] ?? '';
                }
            }

            if ($question['question_type_id'] == 15) {
                $questionData['header'] = $question['setup']['header'] ?? '';
                $questionData['footer'] = $question['setup']['footer'] ?? '';
            }

            if ($question['question_type_id'] == 16) {
                $questionData['labelA'] = $question['setup']['labels']['statementA'] ?? '';
                $questionData['labelB'] = $question['setup']['labels']['statementB'] ?? '';
            }

            if ($question['question_type_id'] == 20) {
                $questionData['labelA'] = $question['setup']['bestWostScale']['labelA'] ?? '';
                $questionData['labelB'] = $question['setup']['bestWostScale']['labelB'] ?? '';
            }

            $content['questions'][] = $questionData;
        }

        $messages = $questionnaire->setup['messages'] ?? [];
        $content['systemMessages'] = [
            'loading' => $messages['loading'] ?? '',
            'saving' => $messages['saving'] ?? '',
            'nextBtn' => $messages['nextBtn'] ?? '',
            'prevBtn' => $messages['prevBtn'] ?? '',
            'finalBtn' => $messages['finalBtn'] ?? '',
            'input1_warning_min' => $messages['input1_warning_min'] ?? '',
            'input1_warning_max' => $messages['input1_warning_max'] ?? '',
            'input2_warning_minmax' => $messages['input2_warning_minmax'] ?? '',
            'input2_warning_min' => $messages['input2_warning_min'] ?? '',
            'input2_warning_max' => $messages['input2_warning_max'] ?? '',
            'input9_warning_alphanumeric' => $messages['input9_warning_alphanumeric'] ?? '',
            'input8_warning_letters' => $messages['input8_warning_letters'] ?? '',
            'input4_warning_invalidemail' => $messages['input4_warning_invalidemail'] ?? '',
            'required_warning' => $messages['required_warning'] ?? '',
        ];

        $redirects = $questionnaire->setup['redirects'] ?? [];
        $content['systemMessages']['complete'] = $redirects['complete']['content'] ?? '';
        $content['systemMessages']['qualityControl'] = $redirects['qualityControl']['content'] ?? '';
        $content['systemMessages']['screenout'] = $redirects['screenout']['content'] ?? '';

        $modals = $questionnaire->setup['modals'] ?? [];
        $content['systemMessages']['connectionLostTitle'] = $modals['connectionLost']['title'] ?? '';
        $content['systemMessages']['connectionLostContent'] = $modals['connectionLost']['content'] ?? '';

        return $content;
    }

    /**
     * Check if the language code represents an English variant (US or UK)
     *
     * @param string $languageCode
     * @return bool
     */
    private function isEnglishVariant(string $languageCode): bool
    {
        return in_array($languageCode, ['en', 'en-GB', 'en-US'], true);
    }

    /**
     * Extract language code from language name
     * Attempts to infer the code from common English variant names
     *
     * @param string $languageName
     * @return string
     */
    public function extractLanguageCodeFromName(string $languageName): string
    {
        $name = strtolower(trim($languageName));
        
        // Check for English variants
        if (strpos($name, 'united kingdom') !== false || strpos($name, 'english united kingdom') !== false) {
            return 'en-GB';
        }
        if (strpos($name, 'united states') !== false || strpos($name, 'english united states') !== false) {
            return 'en';
        }
        if (strpos($name, 'australia') !== false || strpos($name, 'english australia') !== false) {
            return 'en-AU';
        }
        if (strpos($name, 'canada') !== false || strpos($name, 'english canada') !== false) {
            return 'en-CA';
        }
        if (strpos($name, 'english') !== false && !strpos($name, 'united') && !strpos($name, 'australia') && !strpos($name, 'canada')) {
            // Generic English, default to US
            return 'en';
        }
        
        // Return the name itself if we can't determine (for non-English languages)
        return $languageName;
    }
}
