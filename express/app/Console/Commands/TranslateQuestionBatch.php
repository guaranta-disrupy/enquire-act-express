<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AITranslationService;
use Illuminate\Support\Facades\Log;

class TranslateQuestionBatch extends Command
{
    protected $signature = 'translate:question-batch 
                            {--questions= : Base64 encoded JSON array of questions}
                            {--file= : Path to JSON file containing questions (alternative to --questions)}
                            {--language= : Target language name}
                            {--group= : Group identifier (odd/even/none)}
                            {--output= : Path to write JSON output}';

    protected $description = 'Translate a single batch of questions (used for concurrent batch processing)';

    public function handle(AITranslationService $translationService)
    {
        $questionsEncoded = $this->option('questions');
        $questionsFile = $this->option('file');
        $language = $this->option('language');
        $group = $this->option('group');

        if ((!$questionsEncoded && !$questionsFile) || !$language) {
            $this->error('Missing required options: (--questions or --file) and --language');
            return 1;
        }

        try {
            if ($questionsFile) {
                if (!file_exists($questionsFile)) {
                    throw new \Exception('Questions file not found: ' . $questionsFile);
                }
                $questionsJson = file_get_contents($questionsFile);
            } else {
                $questionsJson = base64_decode($questionsEncoded, true);
                if ($questionsJson === false) {
                    throw new \Exception('Failed to decode questions from base64');
                }
            }

            $questions = json_decode($questionsJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Failed to parse questions JSON: ' . json_last_error_msg());
            }

            if (!is_array($questions)) {
                throw new \Exception('Questions must be an array');
            }

            $questionKeys = array_column($questions, 'key');
            Log::info('Translating question batch via artisan command', [
                'group' => $group,
                'question_count' => count($questions),
                'question_keys' => $questionKeys,
                'language' => $language,
                'path' => "artisan_translate_batch_{$group}"
            ]);

            // Extract language code from language name for the API call
            $languageCode = $translationService->extractLanguageCodeFromName($language);
            
            $result = $translationService->translateQuestionBatch($questions, $languageCode, $language, $group);

            $jsonOutput = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($jsonOutput === false) {
                throw new \Exception('Failed to encode result as JSON: ' . json_last_error_msg());
            }

            $outputPath = $this->option('output');
            if ($outputPath) {
                file_put_contents($outputPath, $jsonOutput);
            } else {
                $this->line($jsonOutput);
            }

            return 0;
        } catch (\Exception $e) {
            Log::error('Question batch translation command failed', [
                'group' => $group,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->error('Translation failed: ' . $e->getMessage());
            return 1;
        }
    }
}
