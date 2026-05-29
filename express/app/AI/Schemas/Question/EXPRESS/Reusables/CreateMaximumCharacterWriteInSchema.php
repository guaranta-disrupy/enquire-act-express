<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

class CreateMaximumCharacterWriteInSchema extends CreateMaximumCharacterSchema
{
    protected function getDescription(): array
    {
        return [
            'THIS SHOULD ONLY BE FILLED IN IF ENABLED IS SET TO TRUE, OTHERWISE SET TO NULL. ',
            '10'
        ];
    }
}