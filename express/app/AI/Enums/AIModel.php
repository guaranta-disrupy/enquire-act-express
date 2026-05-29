<?php
namespace App\AI\Enums;
use Prism\Prism\Enums\Provider;

enum AIModel: string
{
    case GEMINI = 'gemini-2.5-flash-lite';
    case OPENAI_4 = 'gpt-4o-mini';
    case OPENAI = 'gpt-5-mini';
    case OPENAI_EMBEDDING_SMALL = 'text-embedding-3-small';
    
    public function getProvider(): Provider
    {
        return match($this) {
            self::GEMINI => Provider::Gemini,
            self::OPENAI => Provider::OpenAI,
            self::OPENAI_4 => Provider::OpenAI,
            self::OPENAI_EMBEDDING_SMALL => Provider::OpenAI,
        };
    }
    
    public static function defaultModel(): array
    {
        return [
            'provider' => self::OPENAI->getProvider(),
            'model' => self::OPENAI->value
        ];
    }

    public static function defaultModel4(): array
    {
        return [
            'provider' => self::OPENAI_4->getProvider(),
            'model' => self::OPENAI_4->value
        ];
    }

    public static function summaryModel(): array
    {
        return [
            'provider' => self::GEMINI->getProvider(),
            'model' => self::GEMINI->value
        ];
    }

    public static function embeddingModel(): array
    {
        return [
            'provider' => self::OPENAI_EMBEDDING_SMALL->getProvider(),
            'model' => self::OPENAI_EMBEDDING_SMALL->value
        ];
    }

    public static function getSummaryConfig(): array
    {
        $config = self::summaryModel();
        return [$config['provider'], $config['model']];
    }

    public static function getEmbeddingConfig(): array
    {
        $config = self::embeddingModel();
        return [$config['provider'], $config['model']];
    }

    public static function getDefaultConfig(): array
    {
        // temporary
        $config = self::defaultModel4();
        return [$config['provider'], $config['model']];
    }
    
    /**
     * Get all enum values as an array of strings
     *
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
