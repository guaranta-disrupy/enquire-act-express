<?php

namespace App\AI\Schemas\Question\EXPRESS\Types;

class CreatePieChartQuestionSchema extends CreateChartQuestionSchema
{
    protected function getType(): string
    {
        return 'pie';
    }
}
