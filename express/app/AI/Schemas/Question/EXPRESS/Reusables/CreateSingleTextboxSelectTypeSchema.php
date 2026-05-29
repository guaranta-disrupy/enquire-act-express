<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use App\AI\Schemas\Question\EXPRESS\Reusables\CreateMinimumCharacterSingleTextboxSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateMaximumCharacterSingleTextboxSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEmailProviderSchema;

class CreateSingleTextboxSelectTypeSchema extends CreateBaseSelectTypeSchema
{
    protected function getExtraProperties(): array
    {
        $properties = [
            (new CreateMinimumCharacterSingleTextboxSchema())->getSchema(),
            (new CreateMaximumCharacterSingleTextboxSchema())->getSchema(),
            (new CreateEmailProviderSchema())->getSchema()
        ];

        $requiredFields = ['minimum_character', 'maximum_character', 'email_provider'];

        return [$properties, $requiredFields];
    }
}
