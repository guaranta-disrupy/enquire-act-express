<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables\Inputs;

use Prism\Prism\Schema\BooleanSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateWriteInSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateInputSchema;

class CreateMatrixRowSchema extends CreateInputSchema
{
    protected function getExtraProperties(): array
    {
        $properties = [
            (new CreateWriteInSchema())->getSchema(),
            new BooleanSchema(
                name: 'optional_row',
                description: 'Specifies if answering the row is optional. If not specified, use false as default'
            )
        ];

        $requiredFields = ['write_in', 'optional_row'];

        return [$properties, $requiredFields];  
    }
}