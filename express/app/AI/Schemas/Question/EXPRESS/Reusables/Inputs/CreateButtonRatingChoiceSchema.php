<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables\Inputs;

use Prism\Prism\Schema\StringSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateInputSchema;

class CreateButtonRatingChoiceSchema extends CreateInputSchema
{
    protected function getExtraProperties(): array
    {   
        $properties = [
            new StringSchema(
                name: 'value',
                description: 'The label for the choice. ALWAYS depends on the question: for binary questions use "Yes" or "No"; for scale questions use text labels (e.g. "Very satisfied", "Satisfied", "Neutral"). Use human-readable words with spaces and proper case, never underscores. THIS SHOULD BE SPECIFIED BY THE USER, DO NOT WRITE YOUR OWN. If this has not been specified, leave as null.',
                nullable: true
            ),
            new StringSchema(
                name: 'colour',
                description: 'The hexadecimal colour for the choice. THIS SHOULD BE SPECIFIED BY THE USER, DO NOT WRITE YOUR OWN. If this has not been specified, leave as null.',
                nullable: true
            )
        ];

        $requiredFields = ['value', 'colour'];

        return [$properties, $requiredFields];
    }
}