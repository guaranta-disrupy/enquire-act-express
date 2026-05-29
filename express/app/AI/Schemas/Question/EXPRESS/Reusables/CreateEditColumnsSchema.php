<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\NumberSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateUnitsSchema;

class CreateEditColumnsSchema
{
    public function getSchema(): ObjectSchema
    {
        $deviceSchemas = ['desktop', 'tablet', 'mobile'];

        $deviceObjects = [];

        foreach ($deviceSchemas as $device) {
            $deviceObjects[] = new NumberSchema(
                name: $device,
                description: "The number of choice columns on {$device} devices. If this has not been specified, use 1.",
            );
        }

        return new ObjectSchema(
            name: 'edit_columns',
            description: 'This schema defines the columns that can be edited in the question editor.',
            properties: $deviceObjects,
            requiredFields: $deviceSchemas
        );
    }
}