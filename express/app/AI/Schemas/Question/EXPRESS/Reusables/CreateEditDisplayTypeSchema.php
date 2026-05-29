<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;   
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\NumberSchema;

class CreateEditDisplayTypeSchema 
{
    public function getSchema(): ObjectSchema
    {   
        $deviceSchemas = ['desktop', 'tablet', 'mobile'];
        $columnDefaults = [
            'desktop' => 5,
            'tablet' => 3,
            'mobile' => 2
        ];

        $deviceObjects = [];

        foreach ($deviceSchemas as $device) {
            $deviceObjects[] = new ObjectSchema(
                name: $device,
                description: "The properties for the edit display type on {$device} devices",
                properties: [
                    new EnumSchema(
                        name: "{$device}_view",
                        description: "The view type for the edit display on {$device} devices. If not specified, use 'grid' as default. Choose 'grid' for a grid view or 'list' for a list view.",
                        options: ['grid', 'list']
                    ),
                    new NumberSchema(
                        name: "{$device}_columns",
                        description: "The number of columns for the edit view on {$device} devices. If this has not been specified, use {$columnDefaults[$device]}"
                    )
                ],
                requiredFields: ["{$device}_view","{$device}_columns"]
            );
        }

        return new ObjectSchema(
            name: 'edit_display_type',
            description: 'The display type for the edit view of the question.',
            properties: $deviceObjects,
            requiredFields: $deviceSchemas
        );
    }
}