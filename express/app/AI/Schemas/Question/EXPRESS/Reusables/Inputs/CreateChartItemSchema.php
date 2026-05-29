<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables\Inputs;

use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateInputSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateRandomChoiceSchema;

class CreateChartItemSchema extends CreateInputSchema
{
    protected function getInputType(): string
    {
        return 'item';
    }

    protected function getExtraProperties(): array
    {   
        $properties = [
            (new CreateRandomChoiceSchema())->getSchema()
        ];

        $requiredFields = ['random_choice'];

        return [$properties, $requiredFields];
    }
}