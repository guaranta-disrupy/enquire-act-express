<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use Prism\Prism\Schema\StringSchema;

class CreateABLabelsSchema extends CreateBaseLabelsSchema
{
    protected function getList(): array
    {
        $labels = ['A', 'B'];

        $properties = [];
        $requiredFields = [];

        foreach ($labels as $label) {
            $name = "statement_[{$label}]_label";
            $properties[] = new StringSchema(
                name: $name,
                description: "Specifies the label for statement {$label}. This will be wrapped in <p> tags automatically. If not specified, use 'Statement {$label}' as default"
            );
            $requiredFields[] = $name;
        };

        return [$properties, $requiredFields];
    }
}
