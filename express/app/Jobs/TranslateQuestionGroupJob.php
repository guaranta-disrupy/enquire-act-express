<?php

namespace App\Jobs;

use App\Models\Questionnaire;
use App\Services\AITranslationService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TranslateQuestionGroupJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;

    public function __construct(
        public array $questions,
        public string $targetLanguageCode,
        public string $targetLanguageName,
        public string $groupName,
        public ?string $questionnaireCode = null,
        public ?string $outputFile = null
    ) {
        $this->onConnection('database');
        $this->onQueue('translations');
    }

    public function handle(AITranslationService $translationService): void
    {
        Log::info("Translation job started", [
            'group' => $this->groupName,
            'question_count' => count($this->questions),
            'question_keys' => array_column($this->questions, 'key')
        ]);

        try {
            $result = $translationService->translateQuestionGroup(
                $this->questions,
                $this->targetLanguageName,
                $this->groupName
            );

            // Save translations directly to database if questionnaire code is provided
            // This allows the API to return immediately without waiting for job completion
            if ($this->questionnaireCode && !empty($result)) {
                try {
                    $questionnaire = Questionnaire::whereCode($this->questionnaireCode)->with('questions')->first();
                    
                    if ($questionnaire) {
                        $this->saveTranslationsDirectly($questionnaire, $this->targetLanguageCode, $result);
                        Log::info("Translations saved directly by job", [
                            'group' => $this->groupName,
                            'questionnaire_code' => $this->questionnaireCode,
                            'saved_keys' => array_keys($result),
                            'language' => $this->targetLanguageCode
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to save translations directly", [
                        'group' => $this->groupName,
                        'questionnaire_code' => $this->questionnaireCode,
                        'error' => $e->getMessage()
                    ]);
                    // Don't throw - let the job continue to write output file
                }
            }

            // Also write to output file for backward compatibility
            if ($this->outputFile) {
                $jsonData = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                file_put_contents($this->outputFile, $jsonData);
            }

            Log::info("Translation job completed", [
                'group' => $this->groupName,
                'translated_count' => count($result)
            ]);
        } catch (\Exception $e) {
            Log::error("Translation job failed", [
                'group' => $this->groupName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Save translations directly to the database
     */
    private function saveTranslationsDirectly(Questionnaire $questionnaire, string $language, array $translatedData): void
    {
        $variable_start = collect($questionnaire->setup)->get('variable_start', 1);
        $questions = collect($questionnaire->questions)->sortBy('order')->toArray();
        
        foreach ($translatedData as $questionKey => $questionData) {
            $questionNumber = intval(str_replace('Q', '', $questionKey)) - ($variable_start - 1);
            
            foreach ($questions as $key => $question) {
                if (intval($question['order']) === $questionNumber) {
                    if (!isset($questions[$key]['setup'])) {
                        $questions[$key]['setup'] = [];
                    }
                    
                    if (!isset($questions[$key]['setup']['language'])) {
                        $questions[$key]['setup']['language'] = [];
                    }
                    
                    if (!isset($questions[$key]['setup']['language'][$language])) {
                        $questions[$key]['setup']['language'][$language] = [];
                    }
                    
                    if (!empty($questionData['trans'])) {
                        $questions[$key]['setup']['language'][$language]['question'] = $questionData['trans'];
                    }
                    
                    // Handle options for choice questions (types 2, 3, 20)
                    if (($questions[$key]['question_type_id'] == 2 || 
                         $questions[$key]['question_type_id'] == 3 || 
                         $questions[$key]['question_type_id'] == 20) && 
                        !empty($questionData['options'])) {
                        
                        if (!isset($questions[$key]['choices']) || !is_array($questions[$key]['choices'])) {
                            $questions[$key]['choices'] = [];
                        }
                        
                        foreach ($questionData['options'] as $optionIndex => $option) {
                            if (!isset($questions[$key]['choices'][$optionIndex])) {
                                $questions[$key]['choices'][$optionIndex] = [];
                            }
                            
                            if (!isset($questions[$key]['choices'][$optionIndex]['language'])) {
                                $questions[$key]['choices'][$optionIndex]['language'] = [];
                            }
                            
                            if (!empty($option['trans'])) {
                                $questions[$key]['choices'][$optionIndex]['language'][$language] = [
                                    'name' => $option['trans']
                                ];
                            }
                        }
                    }
                    
                    // Handle items for matrix/grid questions
                    if (!empty($questionData['items'])) {
                        $itemQuestionTypes = [5, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19];
                        
                        if (in_array($questions[$key]['question_type_id'], $itemQuestionTypes)) {
                            if (!isset($questions[$key]['items']) || !is_array($questions[$key]['items'])) {
                                $questions[$key]['items'] = [];
                            }
                            
                            foreach ($questionData['items'] as $itemIndex => $item) {
                                if (!isset($questions[$key]['items'][$itemIndex])) {
                                    $questions[$key]['items'][$itemIndex] = [];
                                }
                                
                                if (!isset($questions[$key]['items'][$itemIndex]['language'])) {
                                    $questions[$key]['items'][$itemIndex]['language'] = [];
                                }
                                
                                if (!empty($item['trans'])) {
                                    if ($questions[$key]['question_type_id'] == 16 && isset($item['statementB'])) {
                                        $questions[$key]['items'][$itemIndex]['language'][$language] = [
                                            'statementA' => $item['trans'],
                                            'statementB' => $item['statementB']
                                        ];
                                    } else {
                                        $questions[$key]['items'][$itemIndex]['language'][$language] = [
                                            'name' => $item['trans']
                                        ];
                                    }
                                }
                            }
                        }
                    }
                    
                    // Save the question with updated translations
                    $questionModel = \App\Models\Question::find($questions[$key]['id']);
                    if ($questionModel) {
                        $questionModel->setup = $questions[$key]['setup'];
                        if (isset($questions[$key]['choices'])) {
                            $questionModel->choices = $questions[$key]['choices'];
                        }
                        if (isset($questions[$key]['items'])) {
                            $questionModel->items = $questions[$key]['items'];
                        }
                        $questionModel->save();
                    }
                    
                    break;
                }
            }
        }
    }
}
