<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use Prism\Prism\Schema\NumberSchema;

abstract class CreateMaximumCharacterSchema extends CreateCharacterBoundarySchema
{
    protected function getBoundaryType(): string
    {
        return 'maximum';
    }
    
    protected function getDescription(): array
    {
        return [
            '',
            'null'
        ];
    }
}


