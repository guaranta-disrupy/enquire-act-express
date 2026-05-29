<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

class CreateRandomValuesSchema extends CreateRandomInputsSchema
{
    protected function getInputType(): string
    {
        return "value";
    }
}
