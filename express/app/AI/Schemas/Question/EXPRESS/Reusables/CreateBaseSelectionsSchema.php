<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use Prism\Prism\Schema\ObjectSchema; 
use Prism\Prism\Schema\BooleanSchema;

abstract class CreateBaseSelectionsSchema 
{   
    protected function getExtraProperties(): array
    {
        return [[],[]];
    }

    public function getSchema(): ObjectSchema
    {   
        $properties = [
            new BooleanSchema(
                name: 'apply_select_all',
                description: 'This defines if there are choices that can be automatically selected together. If this has not been specified, use false.',
            ),
            new BooleanSchema(
                name: 'apply_unselect_all',
                description: 'This defines if there are choices that, when selected, should unselect the rest of the choices. If this has not been specified, use false.',
            )
        ];

        $requiredFields = ['apply_select_all', 'apply_unselect_all'];

        [$extraProperties, $extraRequiredFields] = $this->getExtraProperties();

        $properties = array_merge($properties, $extraProperties);
        $requiredFields = array_merge($requiredFields, $extraRequiredFields);

        return new ObjectSchema(
            name: 'selections',
            description: 'The different options for selections that the user can specify.',
            properties: $properties,
            requiredFields: $requiredFields
        );
    }
}
