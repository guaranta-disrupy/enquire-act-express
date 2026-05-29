<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

class CreateMaximumCharacterMultipleTextboxSchema extends CreateMaximumCharacterSchema
{
    protected function getDescription(): array
    {
        return [
            'THIS SHOULD ONLY BE FILLED IN IF SELECT TYPE IS SET TO OPEN TEXT, LETTERS ONLY, ALPHANUMERIC, WHOLE NUMBER OR DECIMAL NUMBER, OTHERWISE SET TO NULL. ',
            '5'
        ];
    }
}





