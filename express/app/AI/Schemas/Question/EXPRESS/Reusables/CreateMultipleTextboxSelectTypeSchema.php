<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use App\AI\Schemas\Question\EXPRESS\Reusables\CreateMinimumCharacterMultipleTextboxSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateMaximumCharacterMultipleTextboxSchema;

class CreateMultipleTextboxSelectTypeSchema extends CreateBaseSelectTypeSchema
{
    protected function getExtraProperties(): array
    {
        $properties = [
            (new CreateMinimumCharacterMultipleTextboxSchema())->getSchema(),
            (new CreateMaximumCharacterMultipleTextboxSchema())->getSchema()
        ];

        $requiredFields = ['minimum_character', 'maximum_character'];

        return [$properties, $requiredFields];
    }
}
