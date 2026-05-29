<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables\Inputs;

use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateInputSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateMultipleTextboxSelectTypeSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateRandomChoiceSchema;

class CreateMultipleTextboxChoiceSchema extends CreateInputSchema
{
    protected function getInputType(): string
    {
        return 'textbox';
    }

    protected function getExtraProperties(): array
    {
        $properties = [
            (new CreateMultipleTextboxSelectTypeSchema())->getSchema(),
            (new CreateRandomChoiceSchema())->getSchema()
        ];

        $requiredFields = ['select_type', 'random_choice'];

        return [$properties, $requiredFields];
    }
}