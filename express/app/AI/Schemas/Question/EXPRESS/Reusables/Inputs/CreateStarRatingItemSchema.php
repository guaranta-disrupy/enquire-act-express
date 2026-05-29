<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables\Inputs;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class CreateStarRatingItemSchema extends CreateInputSchema
{
    protected function getInputType(): string
    {
        return 'item';
    }

    protected function getExtraProperties(): array
    {   
        $properties = [
            new \Prism\Prism\Schema\BooleanSchema(
                name: 'random_item',
                description: 'Indicates whether the specific item should be displayed randomly. If this has not been specified, use false.'
            )
        ];

        $requiredFields = ['random_item'];

        return [$properties, $requiredFields];
    }

    public function getSchema(): ObjectSchema
    {
        $inputType = $this->getInputType();
        $properties = [
            new StringSchema(
                name: 'text',
                description: "A descriptive statement that represents a specific level or state related to the question topic. The text should be contextually relevant to the question and describe how the respondent feels or their situation. For example, if the question is about wellness, items might be 'I feel unwell most of the time', 'I often struggle with my well-being', 'My well-being is manageable but not optimal', 'I generally feel well with minor issues', 'I feel very healthy, balanced, and well'. Each item should be a complete, meaningful statement that relates to the question context, NOT generic satisfaction labels like 'Very Satisfied' or 'Satisfied'.",
                nullable: true
            ),
        ];
        $requiredFields = ['text'];

        [$extraProperties, $extraRequiredFields] = $this->getExtraProperties();

        $properties = array_merge($properties, $extraProperties);
        $requiredFields = array_merge($requiredFields, $extraRequiredFields);
        
        return new ObjectSchema(
            name: "{$inputType}",
            description: "A star rating item that represents a specific level or state related to the question topic. Each item should contain a descriptive statement that is contextually relevant to the question being asked.",
            properties: $properties,
            requiredFields: $requiredFields
        );
    }
}