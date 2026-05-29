<?php

namespace App\AI\Schemas\Question\EXPRESS\Types;

class CreateBarChartQuestionSchema extends CreateChartQuestionSchema
{
    protected function getType(): string
    {
        return 'bar';
    }
}

