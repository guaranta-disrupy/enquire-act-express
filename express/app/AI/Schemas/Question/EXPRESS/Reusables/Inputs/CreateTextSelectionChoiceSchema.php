<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables\Inputs;

use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\BooleanSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateInputSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateWriteInSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateRandomChoiceSchema;

class CreateTextSelectionChoiceSchema extends CreateInputSchema
{
    protected function getExtraProperties(): array
    {   
        $properties = [
            new StringSchema(
                name: 'value',
                description: 'The label for the choice. THIS SHOULD BE SPECIFIED BY THE USER, DO NOT WRITE YOUR OWN. If this has not been specified, leave as null.',
                nullable: true
            ),
            new NumberSchema(
                name: 'points',
                description: 'The points associated with the choice. THIS SHOULD BE SPECIFIED BY THE USER, DO NOT WRITE YOUR OWN. If this has not been specified, use 0.'
            ),
            (new CreateWriteInSchema())->getSchema(),
            (new CreateRandomChoiceSchema())->getSchema(), 
            new BooleanSchema(
                name: 'select_all_choice',
                description: 'Indicates whether the choice should be selected automatically with other choices. If this has not been specified, use false.'
            ),
            new BooleanSchema(
                name: 'unselect_all_choice',
                description: 'Indicates whether all other choices should be unselected when this choice is picked. If this has not been specified, use false.'
            )
        ];

        $requiredFields = ['value', 'points', 'write_in', 'random_choice', 'select_all_choice', 'unselect_all_choice'];

        return [$properties, $requiredFields];
    }
}