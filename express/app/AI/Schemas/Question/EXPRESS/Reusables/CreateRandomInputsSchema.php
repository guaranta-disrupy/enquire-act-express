<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use Prism\Prism\Schema\BooleanSchema;

// NOTE THAT THIS IS DIFFERENT TO CREATERANDOMINPUTSCHEMA - THIS ONE APPLIES TO THE ENTIRE QUESTION, NOT SPECIFIC INPUTS

abstract class CreateRandomInputsSchema
{
    protected function getInputType(): string
    {
        return "choice";
    }

    public function getSchema(): BooleanSchema
    {
        $input = $this->getInputType();
        return new BooleanSchema(
            name: "random_{$input}s",
            description: "This defines if the {$input}s should be presented in a random order. If this has not been specified, use false."
        );
    }
}


class CreateRandomItemsSchema extends CreateRandomInputsSchema
{
    protected function getInputType(): string
    {
        return "item";
    }
}