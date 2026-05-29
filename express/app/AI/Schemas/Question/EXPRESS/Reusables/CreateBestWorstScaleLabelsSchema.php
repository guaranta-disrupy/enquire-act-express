<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class CreateBestWorstScaleLabelsSchema extends CreateBaseLabelsSchema
{
    protected function getList():array
    {
        $labels = ['Label 1 (Best)', 'Label 2 (Worst)'];
        $defaults = [
            'Label 1 (Best)' => 'Most enjoyable (Best)',
            'Label 2 (Worst)' => 'Least enjoyable (Worst)'
        ];

        $properties = [];

        foreach ($labels as $label){
            $properties[] = new ObjectSchema(
                name: $label,
                description: "The properties for {$label}",
                properties: [
                    new StringSchema(
                        name: 'label',
                        description: "The label for label {$label[6]}. If not specified, use {$defaults[$label]} as default"
                    ),
                    new StringSchema(
                        name: 'colour',
                        description: 'The colour of the text for the label. If not specified, use null as default',
                        nullable: true
                    )
                ],
                requiredFields: ['label', 'colour']
            );
        }

        $requiredFields = $labels;

        return [$properties, $requiredFields];
    }
}
