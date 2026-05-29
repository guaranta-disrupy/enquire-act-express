<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables\Inputs;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class CreateInputSchema
{
    protected function getExtraProperties(): array
    {
        return [[], []];
    }

    protected function getInputType(): string
    {
        return 'choice';
    }
    
    public function getSchema(): ObjectSchema
    {   
        $inputType = $this->getInputType();
        $properties = [
            new StringSchema(
                name: 'text',
                description: "The text of the {$inputType}",
                nullable: true
            ),
            new StringSchema(
                name: 'equivalent',
                description: "The text associated with the {$inputType}, used for data processing. If not specified, leave as null",
                nullable: true
            )
        ];
        $requiredFields = ['text', 'equivalent'];

        [$extraProperties, $extraRequiredFields] = $this->getExtraProperties();

        $properties = array_merge($properties, $extraProperties);
        $requiredFields = array_merge($requiredFields, $extraRequiredFields);
        
        return new ObjectSchema(
            name: "{$inputType}",
            description: "A {$inputType} for a question, which can be used in various question types.",
            properties: $properties,
            requiredFields: $requiredFields
        );
    }
}