<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use Prism\Prism\Schema\NumberSchema;

abstract class CreateCharacterBoundarySchema 
{
    abstract protected function getBoundaryType(): string;
    abstract protected function getDescription(): array;

    public function getSchema(): NumberSchema
    {   
        $boundary = $this->getBoundaryType();
        $description = $this->getDescription();
        
        return new NumberSchema(
            name: "{$boundary}_character",
            description: "{$description[0]}The {$boundary} number of characters required for the question. If this has not been specified, set to {$description[1]}.",
            nullable: true
        );
    }
}