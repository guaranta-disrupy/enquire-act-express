<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables\Inputs;

use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateInputSchema;
use Prism\Prism\Schema\StringSchema;

class CreateSegmentationChoicesSchema extends CreateInputSchema
{
    protected function getExtraProperties(): array
    {   
        $properties = [
            new StringSchema(
                name: 'value',
                description: 'The label for the choice. THIS SHOULD BE SPECIFIED BY THE USER, DO NOT WRITE YOUR OWN. If this has not been specified, leave as null.',
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