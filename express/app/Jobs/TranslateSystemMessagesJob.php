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

class TranslateSystemMessagesJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;

    public function __construct(
        public string $questionnaireCode,
        public string $targetLanguageCode,
        public string $targetLanguageName,
        public int $rtl
    ) {
        $this->onConnection('database');
        $this->onQueue('translations');
    }

    public function handle(AITranslationService $translationService): void
    {
        Log::info("System messages translation job started", [
            'questionnaire_code' => $this->questionnaireCode,
            'language' => $this->targetLanguageCode,
            'language_name' => $this->targetLanguageName,
            'rtl' => $this->rtl
        ]);

        try {
            $questionnaire = Questionnaire::whereCode($this->questionnaireCode)->first();
            
            if (!$questionnaire) {
                Log::error("Questionnaire not found for system messages translation", [
                    'questionnaire_code' => $this->questionnaireCode
                ]);
                throw new \Exception("Questionnaire not found: {$this->questionnaireCode}");
            }

            $systemMessages = $translationService->translateSystemMessagesOnly(
                $questionnaire,
                $this->targetLanguageCode,
                $this->targetLanguageName
            );

            if (!empty($systemMessages)) {
                $this->saveSystemMessagesTranslation($questionnaire, $systemMessages);
                
                Log::info("System messages translation job completed", [
                    'questionnaire_code' => $this->questionnaireCode,
                    'language' => $this->targetLanguageCode,
                    'messages_count' => count($systemMessages)
                ]);
            } else {
                Log::warning("Empty system messages translation data", [
                    'questionnaire_code' => $this->questionnaireCode,
                    'language' => $this->targetLanguageCode
                ]);
            }
        } catch (\Exception $e) {
            Log::error("System messages translation job failed", [
                'questionnaire_code' => $this->questionnaireCode,
                'language' => $this->targetLanguageCode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function saveSystemMessagesTranslation(Questionnaire $questionnaire, array $systemMessages): void
    {
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
        
        $setup = $questionnaire->setup ?? [];
        
        if (!isset($setup['language'])) {
            $setup['language'] = [];
        }
        
        if (!isset($setup['language'][$this->targetLanguageCode])) {
            $setup['language'][$this->targetLanguageCode] = [];
        }
        
        foreach ($systemMessages as $index => $msg) {
            if (isset($messageKeys[$index]) && !empty($msg['trans'])) {
                $setup['language'][$this->targetLanguageCode][$messageKeys[$index]] = $msg['trans'];
            }
        }
        
        $setup['language'][$this->targetLanguageCode]['rtl'] = $this->rtl;
        $questionnaire->update(['setup' => $setup]);
    }
}

