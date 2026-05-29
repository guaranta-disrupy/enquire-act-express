<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables\Inputs;

use Prism\Prism\Schema\BooleanSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateTextSelectionChoiceSchema;

//NOTE THAT THIS FILE IS NOT YET COMPLETE

class CreateImageSelectionChoiceSchema extends CreateTextSelectionChoiceSchema
{
    protected function getExtraProperties(): array
    {
        [$baseProperties, $baseRequiredFields] = parent::getExtraProperties();

        $extraProperties = [
            // NEED TO DEAL WITH IMAGE SOURCE
            new BooleanSchema(
            name: 'hide_label',
            description: 'This defines if the label should be hidden for the image selection choice. If this has not been specified, use false.',
            )
        ];

        $extraRequiredFields = ['hide_label'];

        $properties = array_merge($baseProperties, $extraProperties);
        $requiredFields = array_merge($baseRequiredFields, $extraRequiredFields);

        return [$properties, $requiredFields];
    }
}