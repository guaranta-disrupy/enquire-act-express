<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use App\Models\Questionnaire;
use App\Models\Question;
use App\Services\AITranslationService;

class AutoTranslateQuestionController extends Controller
{
    // Detect questions that need translation (missing translations for any language)
    public function detectUntranslatedQuestions(Request $request, $questionnaire_code)
    {
        try {
            $questionnaire = Questionnaire::whereCode($questionnaire_code)->with('questions')->first();
            
            if (!$questionnaire) {
                return response()->json([
                    'success' => false,
                    'error' => 'Questionnaire not found'
                ], 404);
            }

            Gate::authorize('update', $questionnaire->parent_survey);

            $setup = $questionnaire->setup ?? [];
            $languages = $setup['language'] ?? [];
            
            $languageCodes = array_filter(array_keys($languages), function($code) {
                return $code !== ' ' && !empty(trim($code));
            });

            if (empty($languageCodes)) {
                return response()->json([
                    'success' => true,
                    'untranslated_questions' => [],
                    'count' => 0
                ]);
            }

            $untranslatedQuestions = [];
            $variable_start = collect($setup)->get('variable_start', 1);

            foreach ($questionnaire->questions as $question) {
                $questionSetup = $question->setup ?? [];
                $questionLanguages = $questionSetup['language'] ?? [];
                
                $missingLanguages = [];
                foreach ($languageCodes as $langCode) {
                    if (!isset($questionLanguages[$langCode]) || 
                        empty($questionLanguages[$langCode]) || 
                        !isset($questionLanguages[$langCode]['question']) || 
                        empty($questionLanguages[$langCode]['question'])) {
                        $missingLanguages[] = $langCode;
                    }
                }

                if (!empty($missingLanguages)) {
                    $questionKey = 'Q' . ($question->order + ($variable_start - 1));
                    $untranslatedQuestions[] = [
                        'id' => $question->id,
                        'order' => $question->order,
                        'key' => $questionKey,
                        'missing_languages' => $missingLanguages
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'untranslated_questions' => $untranslatedQuestions,
                'count' => count($untranslatedQuestions)
            ]);

        } catch (\Exception $e) {
            Log::error('Detect untranslated questions failed', [
                'questionnaire_code' => $questionnaire_code,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Detection failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // Translate multiple questions to all existing languages
    public function translateMultipleQuestions(Request $request, $questionnaire_code)
    {
        try {
            $questionnaire = Questionnaire::whereCode($questionnaire_code)->with('questions')->first();
            
            if (!$questionnaire) {
                return response()->json([
                    'success' => false,
                    'error' => 'Questionnaire not found'
                ], 404);
            }

            Gate::authorize('update', $questionnaire->parent_survey);

            $questionIds = $request->question_ids ?? [];
            
            if (empty($questionIds) || !is_array($questionIds)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Question IDs array is required'
                ], 400);
            }

            $setup = $questionnaire->setup ?? [];
            $languages = $setup['language'] ?? [];
            
            $languageCodes = array_filter(array_keys($languages), function($code) {
                return $code !== ' ' && !empty(trim($code));
            });

            if (empty($languageCodes)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No languages found in questionnaire. Please add languages first.',
                    'translated_count' => 0
                ], 400);
            }

            $languageJsonPath = resource_path('json/language.json');
            $languageData = [];
            if (file_exists($languageJsonPath)) {
                $languageJson = json_decode(file_get_contents($languageJsonPath), true);
                $languageList = $languageJson['languages'] ?? [];
                foreach ($languageList as $lang) {
                    $languageData[$lang['Code']] = $lang['EnglishName'];
                }
            }

            $variable_start = collect($setup)->get('variable_start', 1);
            $translationService = new AITranslationService();
            $totalTranslated = 0;
            $totalFailed = 0;
            $results = [];

            $forceRetranslate = $request->force_retranslate ?? false;
            
            foreach ($questionIds as $questionId) {
                $question = Question::find($questionId);
                
                if (!$question || $question->questionnaire_id !== $questionnaire->id) {
                    continue;
                }

                $questionKey = 'Q' . ($question->order + ($variable_start - 1));
                $questionSetup = $question->setup ?? [];
                $questionLanguages = $questionSetup['language'] ?? [];
                
                $translatedLanguages = [];
                $failedLanguages = [];

                foreach ($languageCodes as $languageCode) {
                    if (!$forceRetranslate && 
                        isset($questionLanguages[$languageCode]) && 
                        !empty($questionLanguages[$languageCode]) &&
                        isset($questionLanguages[$languageCode]['question']) &&
                        !empty($questionLanguages[$languageCode]['question'])) {
                        continue;
                    }

                    try {
                        $languageName = $languageData[$languageCode] ?? $languageCode;
                        
                        $translatedData = $translationService->translateSingleQuestion(
                            $questionnaire,
                            $questionKey,
                            $languageCode,
                            $languageName
                        );

                        if (!empty($translatedData)) {
                            $this->saveQuestionTranslation(
                                $questionnaire,
                                $question,
                                $languageCode,
                                $questionKey,
                                $translatedData
                            );
                            
                            $translatedLanguages[] = $languageCode;
                            $totalTranslated++;
                        } else {
                            $failedLanguages[] = $languageCode;
                            $totalFailed++;
                        }
                    } catch (\Exception $e) {
                        $failedLanguages[] = $languageCode;
                        $totalFailed++;
                        Log::error('Failed to translate question', [
                            'question_id' => $questionId,
                            'question_key' => $questionKey,
                            'language_code' => $languageCode,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                $results[] = [
                    'question_id' => $questionId,
                    'question_key' => $questionKey,
                    'translated_count' => count($translatedLanguages),
                    'failed_count' => count($failedLanguages)
                ];
            }

            return response()->json([
                'success' => true,
                'message' => "Translated {$totalTranslated} language(s) across " . count($questionIds) . " question(s)",
                'total_translated' => $totalTranslated,
                'total_failed' => $totalFailed,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Batch translation failed', [
                'questionnaire_code' => $questionnaire_code,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Translation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // Translate a newly added question to all existing languages in the questionnaire
    public function translateNewQuestion(Request $request, $questionnaire_code, $question_id)
    {
        try {
            $questionnaire = Questionnaire::whereCode($questionnaire_code)->with('questions')->first();
            
            if (!$questionnaire) {
                return response()->json([
                    'success' => false,
                    'error' => 'Questionnaire not found'
                ], 404);
            }

            Gate::authorize('update', $questionnaire->parent_survey);

            $question = Question::find($question_id);
            
            if (!$question || $question->questionnaire_id !== $questionnaire->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Question not found'
                ], 404);
            }

            $setup = $questionnaire->setup ?? [];
            $languages = $setup['language'] ?? [];
            
            $languageCodes = array_filter(array_keys($languages), function($code) {
                return $code !== ' ' && !empty(trim($code));
            });

            if (empty($languageCodes)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No languages found in questionnaire. Please add languages first.',
                    'translated_count' => 0
                ], 400);
            }

            $languageJsonPath = resource_path('json/language.json');
            $languageData = [];
            if (file_exists($languageJsonPath)) {
                $languageJson = json_decode(file_get_contents($languageJsonPath), true);
                $languageList = $languageJson['languages'] ?? [];
                foreach ($languageList as $lang) {
                    $languageData[$lang['Code']] = $lang['EnglishName'];
                }
            }

            $variable_start = collect($setup)->get('variable_start', 1);
            $questionKey = 'Q' . ($question->order + ($variable_start - 1));

            Log::info('Starting auto-translation for new question', [
                'questionnaire_code' => $questionnaire_code,
                'question_id' => $question_id,
                'question_key' => $questionKey,
                'languages_count' => count($languageCodes),
                'languages' => $languageCodes,
                'user_id' => Auth::id()
            ]);

            $translationService = new AITranslationService();
            $translatedLanguages = [];
            $failedLanguages = [];

            foreach ($languageCodes as $languageCode) {
                try {
                    $languageName = $languageData[$languageCode] ?? $languageCode;
                    
                    Log::info('Translating question to language', [
                        'question_key' => $questionKey,
                        'language_code' => $languageCode,
                        'language_name' => $languageName
                    ]);

                    $translatedData = $translationService->translateSingleQuestion(
                        $questionnaire,
                        $questionKey,
                        $languageCode,
                        $languageName
                    );

                    if (!empty($translatedData)) {
                        $this->saveQuestionTranslation(
                            $questionnaire,
                            $question,
                            $languageCode,
                            $questionKey,
                            $translatedData
                        );
                        
                        $translatedLanguages[] = $languageCode;
                        
                        Log::info('Question translated successfully', [
                            'question_key' => $questionKey,
                            'language_code' => $languageCode
                        ]);
                    } else {
                        $failedLanguages[] = [
                            'code' => $languageCode,
                            'reason' => 'Empty translation data returned'
                        ];
                        
                        Log::warning('Translation returned empty data', [
                            'question_key' => $questionKey,
                            'language_code' => $languageCode
                        ]);
                    }
                } catch (\Exception $e) {
                    $failedLanguages[] = [
                        'code' => $languageCode,
                        'reason' => $e->getMessage()
                    ];
                    
                    Log::error('Failed to translate question to language', [
                        'question_key' => $questionKey,
                        'language_code' => $languageCode,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            $successCount = count($translatedLanguages);
            $totalCount = count($languageCodes);

            Log::info('Auto-translation completed', [
                'questionnaire_code' => $questionnaire_code,
                'question_id' => $question_id,
                'question_key' => $questionKey,
                'total_languages' => $totalCount,
                'translated_count' => $successCount,
                'failed_count' => count($failedLanguages),
                'translated_languages' => $translatedLanguages,
                'failed_languages' => $failedLanguages
            ]);

            return response()->json([
                'success' => true,
                'message' => "Translated question to {$successCount} out of {$totalCount} language(s)",
                'translated_count' => $successCount,
                'total_count' => $totalCount,
                'translated_languages' => $translatedLanguages,
                'failed_languages' => $failedLanguages
            ]);

        } catch (\Exception $e) {
            Log::error('Auto-translation endpoint failed', [
                'questionnaire_code' => $questionnaire_code,
                'question_id' => $question_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Translation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // Save question translation (similar to ManageTranslationController::saveQuestionTranslation)
    private function saveQuestionTranslation(
        Questionnaire $questionnaire,
        Question $question,
        string $language,
        string $questionKey,
        array $questionData
    ) {
        $question->refresh();
        $questionDataArray = $question->toArray();
        
        if (!isset($questionDataArray['setup'])) {
            $questionDataArray['setup'] = [];
        }
        
        if (!isset($questionDataArray['setup']['language'])) {
            $questionDataArray['setup']['language'] = [];
        }
        
        if (!isset($questionDataArray['setup']['language'][$language])) {
            $questionDataArray['setup']['language'][$language] = [];
        }
        
        if (!empty($questionData['trans'])) {
            $questionDataArray['setup']['language'][$language]['question'] = $questionData['trans'];
        }
        
        if (($question->question_type_id == 2 || 
             $question->question_type_id == 3 || 
             $question->question_type_id == 20) && 
            !empty($questionData['options'])) {
            
            if (!isset($questionDataArray['choices']) || !is_array($questionDataArray['choices'])) {
                $questionDataArray['choices'] = [];
            }
            
            foreach ($questionData['options'] as $optionIndex => $option) {
                if (!isset($questionDataArray['choices'][$optionIndex])) {
                    $questionDataArray['choices'][$optionIndex] = [];
                }
                
                if (!isset($questionDataArray['choices'][$optionIndex]['language'])) {
                    $questionDataArray['choices'][$optionIndex]['language'] = [];
                }
                
                if (!empty($option['trans'])) {
                    $questionDataArray['choices'][$optionIndex]['language'][$language] = [
                        'name' => $option['trans']
                    ];
                }
            }
        }
        
        if (!empty($questionData['items'])) {
            $itemQuestionTypes = [5, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19];
            
            if (in_array($question->question_type_id, $itemQuestionTypes)) {
                if (!isset($questionDataArray['items']) || !is_array($questionDataArray['items'])) {
                    $questionDataArray['items'] = [];
                }
                
                foreach ($questionData['items'] as $itemIndex => $item) {
                    if (!isset($questionDataArray['items'][$itemIndex])) {
                        $questionDataArray['items'][$itemIndex] = [];
                    }
                    
                    if (!isset($questionDataArray['items'][$itemIndex]['language'])) {
                        $questionDataArray['items'][$itemIndex]['language'] = [];
                    }
                    
                    if (!empty($item['trans'])) {
                        if ($question->question_type_id == 16 && isset($item['statementB'])) {
                            $questionDataArray['items'][$itemIndex]['language'][$language] = [
                                'statementA' => $item['trans'],
                                'statementB' => $item['statementB']
                            ];
                        } else {
                            $questionDataArray['items'][$itemIndex]['language'][$language] = [
                                'name' => $item['trans']
                            ];
                        }
                    }
                }
            }
        }
        
        if (!empty($questionData['values'])) {
            $valueQuestionTypes = [8, 15, 16];
            
            if (in_array($question->question_type_id, $valueQuestionTypes)) {
                if (!isset($questionDataArray['values']) || !is_array($questionDataArray['values'])) {
                    $questionDataArray['values'] = [];
                }
                
                foreach ($questionData['values'] as $valueIndex => $value) {
                    if (!isset($questionDataArray['values'][$valueIndex])) {
                        $questionDataArray['values'][$valueIndex] = [];
                    }
                    
                    if (!isset($questionDataArray['values'][$valueIndex]['language'])) {
                        $questionDataArray['values'][$valueIndex]['language'] = [];
                    }
                    
                    if (!empty($value['trans'])) {
                        $questionDataArray['values'][$valueIndex]['language'][$language] = [
                            'name' => $value['trans']
                        ];
                    }
                }
            }
        }

        // Handle choices for slider questions (type 8) - these are the scale labels/choices
        if (!empty($questionData['choices']) && $question->question_type_id == 8) {
            \Log::info('Processing choices for slider question in AutoTranslateQuestionController', [
                'question_id' => $question->id,
                'question_key' => $questionKey,
                'choices_received_count' => count($questionData['choices']),
                'choices_received' => $questionData['choices']
            ]);
            
            if (!isset($questionDataArray['choices']) || !is_array($questionDataArray['choices'])) {
                $questionDataArray['choices'] = [];
            }
            
            foreach ($questionData['choices'] as $choiceIndex => $choice) {
                if (!isset($questionDataArray['choices'][$choiceIndex])) {
                    $questionDataArray['choices'][$choiceIndex] = [];
                }
                
                if (!isset($questionDataArray['choices'][$choiceIndex]['language'])) {
                    $questionDataArray['choices'][$choiceIndex]['language'] = [];
                }
                
                if (!empty($choice['trans'])) {
                    $questionDataArray['choices'][$choiceIndex]['language'][$language] = [
                        'name' => $choice['trans']
                    ];
                    \Log::info('Choice translation assigned in AutoTranslateQuestionController', [
                        'question_id' => $question->id,
                        'choice_index' => $choiceIndex,
                        'language' => $language,
                        'translated_name' => $choice['trans']
                    ]);
                }
            }
        }

        // Handle columns for matrix questions (type 11)
        if (!empty($questionData['columns']) && $question->question_type_id == 11) {
            if (!isset($questionDataArray['columns']) || !is_array($questionDataArray['columns'])) {
                $questionDataArray['columns'] = [];
            }
            
            foreach ($questionData['columns'] as $columnIndex => $column) {
                if (isset($questionDataArray['columns'][$columnIndex])) {
                    if (!isset($questionDataArray['columns'][$columnIndex]['language'])) {
                        $questionDataArray['columns'][$columnIndex]['language'] = [];
                    }
                    
                    if (!empty($column['trans'])) {
                        $questionDataArray['columns'][$columnIndex]['language'][$language] = [
                            'name' => $column['trans']
                        ];
                    }
                }
            }
        }

        // Handle rows for matrix questions (type 11)
        if (!empty($questionData['rows']) && $question->question_type_id == 11) {
            if (!isset($questionDataArray['rows']) || !is_array($questionDataArray['rows'])) {
                $questionDataArray['rows'] = [];
            }
            
            foreach ($questionData['rows'] as $rowIndex => $row) {
                if (isset($questionDataArray['rows'][$rowIndex])) {
                    if (!isset($questionDataArray['rows'][$rowIndex]['language'])) {
                        $questionDataArray['rows'][$rowIndex]['language'] = [];
                    }
                    
                    if (!empty($row['trans'])) {
                        $questionDataArray['rows'][$rowIndex]['language'][$language] = [
                            'name' => $row['trans']
                        ];
                    }
                }
            }
        }
        
        if (isset($questionData['header']) && $question->question_type_id == 15) {
            $questionDataArray['setup']['language'][$language]['header'] = $questionData['header']['trans'] ?? '';
        }
        
        if (isset($questionData['footer']) && $question->question_type_id == 15) {
            $questionDataArray['setup']['language'][$language]['footer'] = $questionData['footer']['trans'] ?? '';
        }
        
        if (isset($questionData['labelA']) && 
            ($question->question_type_id == 16 || $question->question_type_id == 20)) {
            if (!isset($questionDataArray['setup']['language'][$language]['labels'])) {
                $questionDataArray['setup']['language'][$language]['labels'] = [];
            }
            $questionDataArray['setup']['language'][$language]['labels']['statementA'] = $questionData['labelA']['trans'] ?? '';
        }
        
        if (isset($questionData['labelB']) && 
            ($question->question_type_id == 16 || $question->question_type_id == 20)) {
            if (!isset($questionDataArray['setup']['language'][$language]['labels'])) {
                $questionDataArray['setup']['language'][$language]['labels'] = [];
            }
            $questionDataArray['setup']['language'][$language]['labels']['statementB'] = $questionData['labelB']['trans'] ?? '';
        }
        
        $updateData = [
            'updated_by' => Auth::id(),
            'setup' => $questionDataArray['setup'],
        ];
        
        if (isset($questionDataArray['choices']) && is_array($questionDataArray['choices'])) {
            $updateData['choices'] = $questionDataArray['choices'];
        }
        
        if (isset($questionDataArray['items']) && is_array($questionDataArray['items'])) {
            $updateData['items'] = $questionDataArray['items'];
        }
        
        if (isset($questionDataArray['values']) && is_array($questionDataArray['values'])) {
            $updateData['values'] = $questionDataArray['values'];
        }

        if (isset($questionDataArray['columns']) && is_array($questionDataArray['columns'])) {
            $updateData['columns'] = $questionDataArray['columns'];
        }

        if (isset($questionDataArray['rows']) && is_array($questionDataArray['rows'])) {
            $updateData['rows'] = $questionDataArray['rows'];
        }
        
        $question->update($updateData);
    }
}

