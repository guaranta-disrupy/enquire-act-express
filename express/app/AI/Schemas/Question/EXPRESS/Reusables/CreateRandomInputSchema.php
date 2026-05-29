<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use Prism\Prism\Schema\BooleanSchema;

// NOTE THAT THIS IS DIFFERENT TO CREATERANDOMINPUTSCHEMA - THIS ONE APPLIES TO SPECIFIC INPUTS, NOT THE ENTIRE QUESTION

abstract class CreateRandomInputSchema
{
    protected function getInfo(): array
    {
        return ["choice","false"];
    }
    public function getSchema(): BooleanSchema
    {
        [$input, $default] = $this->getInfo();

        return new BooleanSchema(
            name: "random_{$input}",
                description: "Indicates whether the specific {$input} should be displayed randomly. If this has not been specified, use {$default}."
        );
    }
}


class CreateRandomItemSchema extends CreateRandomInputSchema
{
    protected function getInfo(): array
    {
        return ["item","false"];
    }
}