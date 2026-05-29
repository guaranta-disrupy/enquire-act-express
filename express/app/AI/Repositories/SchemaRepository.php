<?php

namespace App\AI\Repositories;

use App\AI\Repositories\Contracts\SchemaRepositoryInterface;
use Prism\Prism\Schema\ObjectSchema;
use Illuminate\Support\Arr;

class SchemaRepository implements SchemaRepositoryInterface
{
    protected array $schemas = [
        'express' => [
            'descriptive' => [
                'create' => \App\AI\Schemas\Question\EXPRESS\Types\CreateDescriptiveQuestionSchema::class,
            ],
            'video_player' => [
                'create' => \App\AI\Schemas\Question\EXPRESS\Types\CreateVideoPlayerQuestionSchema::class,
            ],
            'text_selection' => [
                'create' => \App\AI\Schemas\Question\EXPRESS\Types\CreateTextSelectionQuestionSchema::class,
            ],
            'image_selection' => [
                'create' => \App\AI\Schemas\Question\EXPRESS\Types\CreateImageSelectionQuestionSchema::class,
            ],
            'single_textbox' => [
                'create' => \App\AI\Schemas\Question\EXPRESS\Types\CreateSingleTextboxSchema::class,
            ],
            'multiple_textbox' => [
                'create' => \App\AI\Schemas\Question\EXPRESS\Types\CreateMultipleTextboxQuestionSchema::class,
            ],
            'open_ended' => [
                'create' => \App\AI\Schemas\Question\EXPRESS\Types\CreateOpenEndedQuestionSchema::class,
            ],
            'pie_chart' => [
                'create' => \App\AI\Schemas\Question\EXPRESS\Types\CreatePieChartQuestionSchema::class,
            ],
            'bar_chart' => [
                'create' => \App\AI\Schemas\Question\EXPRESS\Types\CreateBarChartQuestionSchema::class,
            ],
            'slider' => [
                'create' => \App\AI\Schemas\Question\EXPRESS\Types\CreateSliderQuestionSchema::class,
            ],
            'ranking' => [
                'create' => \App\AI\Schemas\Question\EXPRESS\Types\CreateRankingQuestionSchema::class,
            ],
            'star_rating' => [
                'create' => \App\AI\Schemas\Question\EXPRESS\Types\CreateStarRatingSchema::class,
            ],
            'button_rating' => [
                'create' => \App\AI\Schemas\Question\EXPRESS\Types\CreateButtonRatingQuestionSchema::class,
            ],
            'segmentation' => [
                'create' => \App\AI\Schemas\Question\EXPRESS\Types\CreateSegmentationQuestionSchema::class,
            ],
            'matrix' => [
                'create' => \App\AI\Schemas\Question\EXPRESS\Types\CreateMatrixQuestionSchema::class,
            ],
            'pairwise_comparison' => [
                'create' => \App\AI\Schemas\Question\EXPRESS\Types\CreatePariwiseComparisonQuestionSchema::class,
            ],
            'best-worst_scale' => [
                'create' => \App\AI\Schemas\Question\EXPRESS\Types\CreateBestWorstScaleQuestionSchema::class,
            ],
        ],
    ];
    
    public function getSchema(string $platform, string $scope, string $intent): ?ObjectSchema
    {
        $schemaClass = Arr::get($this->schemas, "$platform.$scope.$intent");
        
        if (!$schemaClass) {
            return null;
        }
        
        return (new $schemaClass)->getSchema();
    }
}