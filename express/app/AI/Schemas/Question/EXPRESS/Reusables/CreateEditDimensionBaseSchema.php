<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\EnumSchema;
use App\AI\Enums\Units;

abstract class CreateEditDimensionBaseSchema
{
    abstract protected function getType(): string;

    public function getSchema(): ObjectSchema
    {
        $type = $this->getType();
        $deviceSchemas = ['desktop', 'tablet', 'mobile'];
            
        $deviceObjects = [];

        foreach ($deviceSchemas as $device){
            $deviceObjects[] = new ObjectSchema(
                name: $device,
                description: "The image dimension on {$device} devices",
                properties: [
                    new NumberSchema(
                        name: "{$device}_dimension",
                        description: "The width of the image on {$device} devices. If this has not been specified, use 20."
                    ),
                    new EnumSchema(
                        name: 'units',
                        description: "The units for the image dimension on {$device} devices. If a unit is not specified, use 'vmin'. Choose 'percentage' for percentage, 'vmin' for viewport minimum, 'vw' for viewport width, and 'auto' for automatic sizing.",
                        options: Units::values()
                    )
                ],
                requiredFields: ["{$device}_dimension", 'units']
            );
        }

        return new ObjectSchema(
            name: "edit_{$type}_dimension", 
            description: "The {$type} dimension for the question.",
            properties: $deviceObjects,
            requiredFields: $deviceSchemas
        );
    } 
}
