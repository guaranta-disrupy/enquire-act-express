<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables\Inputs;

use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateInputSchema;

class CreateButtonRatingItemSchema extends CreateInputSchema
{
    protected function getInputType(): string
    {
        return 'item';
    }

    protected function getExtraProperties(): array
    {   
        $properties = [
            new \Prism\Prism\Schema\BooleanSchema(
                name: 'random_item',
                description: 'Indicates whether the specific item should be displayed randomly. If this has not been specified, use false.'
            )
        ];

        $requiredFields = ['random_item'];

        return [$properties, $requiredFields];
    }
}