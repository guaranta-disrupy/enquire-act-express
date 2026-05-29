<?php

namespace App\Services;

use App\AI\Repositories\Contracts\SchemaRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Prism;
use App\AI\Enums\AIModel;

/**
 * Generates an additional distinct question of the same type, avoiding duplication of existing questions.
 */
class DistinctQuestionGeneratorService
{
    public function __construct(
        protected SchemaRepositoryInterface $schemaRepository
    ) {}

    /**
     * Generate a new question that is different from the given existing questions but related to the topic.
     *
     * @param string $scope Question type scope (e.g. text_selection, descriptive)
     * @param string $topic Topic for the question (e.g. "favorite activities")
     * @param array<string> $existingQuestionTexts Question texts to avoid duplicating
     * @return array|null Structured parameters for the new question, or null on failure
     */
    public function generate(string $scope, string $topic, array $existingQuestionTexts): ?array
    {
        $schema = $this->schemaRepository->getSchema('express', $scope, 'create');
        if (!$schema) {
            Log::warning('[DistinctQuestionGenerator] No schema for scope', ['scope' => $scope]);
            return null;
        }

        $questionTypeDisplay = config("ai_express.question_type_display_names.{$scope}")
            ?? str_replace('_', ' ', $scope);

        $exclusionText = empty($existingQuestionTexts)
            ? ''
            : "\n\nCRITICAL: Do NOT create a question similar to these. Generate a DIFFERENT question:\n- " . implode("\n- ", $existingQuestionTexts);

        $systemPrompt = "You are an AI assistant for a survey platform. Generate a {$questionTypeDisplay} question about: \"{$topic}\". " .
            "The question MUST be distinct and different from any existing questions. " .
            "Use a different angle, focus, or aspect of the topic. Create different options/choices. " .
            "ALWAYS generate a meaningful, concise title (3-8 words) for the question." .
            $exclusionText;

        $prompt = "Create a different {$questionTypeDisplay} question about: {$topic}. " .
            "It must be distinct from existing questions - use a different angle or aspect. " .
            "Include appropriate question text and all necessary options/choices.";

        try {
            $response = Prism::structured()
                ->using(...AIModel::getDefaultConfig())
                ->withSchema($schema)
                ->withSystemPrompt($systemPrompt)
                ->withPrompt($prompt)
                ->usingTemperature(0.7) // Slightly higher for variety
                ->asStructured();

            $structured = $response->structured ?? null;
            if (!$structured || !is_array($structured)) {
                Log::warning('[DistinctQuestionGenerator] Empty or invalid structured response');
                return null;
            }

            Log::info('[DistinctQuestionGenerator] Generated distinct question', [
                'scope' => $scope,
                'topic' => $topic,
                'question_text' => $structured['title_and_question_text']['question_text'] ?? 'N/A',
            ]);

            return $structured;
        } catch (\Throwable $e) {
            Log::error('[DistinctQuestionGenerator] Generation failed', [
                'scope' => $scope,
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
