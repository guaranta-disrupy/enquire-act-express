<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use Prism\Prism\Schema\BooleanSchema;

class CreateMultipleChoiceSelectionsSchema extends CreateBaseSelectionsSchema
{
    protected function getExtraProperties(): array
    {
        $properties = [
            new BooleanSchema(
                name: 'apply_multiple_selection',
                description: 'This defines if more than one choice can be selected. If this has not been specified, use false.'
            )
        ];

        $requiredFields = ['apply_multiple_selection'];

        return [$properties, $requiredFields];
    }
}
