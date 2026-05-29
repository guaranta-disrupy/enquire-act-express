<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

class CreateMinimumCharacterWriteInSchema extends CreateMinimumCharacterSchema
{
    protected function getDescription(): array
    {
        return [
            'THIS SHOULD ONLY BE FILLED IN IF ENABLED IS SET TO TRUE, OTHERWISE SET TO NULL. ',
            '1'
        ];
    }
}