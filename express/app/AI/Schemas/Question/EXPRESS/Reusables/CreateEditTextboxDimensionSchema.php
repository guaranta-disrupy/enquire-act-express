<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

class CreateEditTextboxDimensionSchema extends CreateEditDimensionBaseSchema
{
    protected function getType(): string
    {
        return 'textbox';
    }
}
