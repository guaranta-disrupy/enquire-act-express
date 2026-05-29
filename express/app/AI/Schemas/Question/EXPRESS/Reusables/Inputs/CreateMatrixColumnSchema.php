<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables\Inputs;

use Prism\Prism\Schema\StringSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateInputSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateRandomChoiceSchema;

class CreateMatrixColumnSchema extends CreateInputSchema
{
    protected function getInputType(): string
    {
        return 'column';
    }

    protected function getExtraProperties(): array
    {   
        $properties = [
            new StringSchema(
                name: 'width',
                description: 'The width of the column. If this has not been specified, use "20%" as default.',
            ),
            (new CreateRandomChoiceSchema())->getSchema()
        ];

        $requiredFields = ['width', 'random_choice'];

        return [$properties, $requiredFields];
    }
}
