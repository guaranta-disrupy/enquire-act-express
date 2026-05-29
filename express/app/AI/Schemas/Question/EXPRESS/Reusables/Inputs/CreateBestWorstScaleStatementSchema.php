<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables\Inputs;

class CreateBestWorstScaleStatementSchema extends CreateInputSchema
{
    protected function getInputType(): string
    {
        return 'statement';
    }
}