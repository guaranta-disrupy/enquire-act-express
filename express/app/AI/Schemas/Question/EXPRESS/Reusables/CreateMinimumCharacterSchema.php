<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use Prism\Prism\Schema\NumberSchema;

abstract class CreateMinimumCharacterSchema extends CreateCharacterBoundarySchema
{
    protected function getBoundaryType(): string
    {
        return 'minimum';
    }
    
    protected function getDescription(): array
    {
        return [
            '',
            'null'
        ];
    }
}


