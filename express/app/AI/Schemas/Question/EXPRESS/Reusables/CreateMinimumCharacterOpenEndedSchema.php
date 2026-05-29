<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

class CreateMinimumCharacterOpenEndedSchema extends CreateMinimumCharacterSchema
{
    protected function getDescription(): array
    {
        return [
            'The minimum number of characters required for the open-ended question response. ',
            '1'
        ];
    }
}

