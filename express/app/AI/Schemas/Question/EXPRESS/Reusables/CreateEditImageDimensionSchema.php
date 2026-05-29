<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

class CreateEditImageDimensionSchema extends CreateEditDimensionBaseSchema
{
    protected function getType(): string
    {
        return 'image';
    }
}
