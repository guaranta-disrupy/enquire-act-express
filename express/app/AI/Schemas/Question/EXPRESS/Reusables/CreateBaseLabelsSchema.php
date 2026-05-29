<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use Prism\Prism\Schema\ObjectSchema;

abstract class CreateBaseLabelsSchema
{   
    abstract protected function getList(): array;

    public function getSchema(): ObjectSchema
    {  
        [$properties, $requiredFields] = $this->getList();
        return new ObjectSchema(
            name: 'labels',
            description: 'Specifies the labels to be displayed at the top of the screen',
            properties: $properties,
            requiredFields: $requiredFields
        );
    }
}
