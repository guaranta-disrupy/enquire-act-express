<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AITranslationService;
use Illuminate\Support\Facades\Log;

class TranslateQuestionGroup extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'translate:question-group 
                            {--questions= : Base64 encoded JSON array of questions}
                            {--file= : Path to JSON file containing questions (alternative to --questions)}
                            {--language= : Target language name}
                            {--group= : Group identifier (odd/even)}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Translate a group of questions (used for parallel processing)';

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
            Log::info('Translating question group via artisan command', [
                'group' => $group,
                'question_count' => count($questions),
                'question_keys' => $questionKeys,
                'language' => $language,
                'path' => "artisan_translate_{$group}_group"
            ]);

            $result = $translationService->translateQuestionGroup($questions, $language, $group);

            $jsonOutput = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            
            if ($jsonOutput === false) {
                throw new \Exception('Failed to encode result as JSON: ' . json_last_error_msg());
            }
            
            Log::info('Artisan command outputting result', [
                'group' => $group,
                'result_count' => count($result),
                'result_keys' => array_keys($result),
                'json_length' => strlen($jsonOutput)
            ]);
            
            echo $jsonOutput;
            
            return 0;
        } catch (\Exception $e) {
            Log::error('Question group translation command failed', [
                'group' => $group,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->getOutput()->write(json_encode([
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), false);
            
            return 1;
        }
    }
}
