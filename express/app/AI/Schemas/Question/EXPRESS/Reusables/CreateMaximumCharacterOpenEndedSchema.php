<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

class CreateMaximumCharacterOpenEndedSchema extends CreateMaximumCharacterSchema
{
    protected function getDescription(): array
    {
        return [
            'The maximum number of characters allowed for the open-ended question response. ',
            '200'
        ];
    }
}

