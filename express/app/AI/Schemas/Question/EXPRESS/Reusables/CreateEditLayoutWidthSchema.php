<?php 

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use App\AI\Schemas\Question\EXPRESS\Reusables\CreateUnitsSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\NumberSchema;

class CreateEditLayoutWidthSchema
{
    protected function getWidthDefaults(): array
    {
        return [
            'desktop' => 60,
            'tablet' => 100,
            'mobile' => 100
        ];
    }
    
    public function getSchema(): ObjectSchema
    {   
        $widthDefaults = $this->getWidthDefaults();
        $deviceSchemas = ['desktop', 'tablet', 'mobile'];

        $unitsSchema = (new CreateUnitsSchema())->getSchema();

        $deviceObjects =[];

        foreach ($deviceSchemas as $device) {
            $deviceObjects[] = new ObjectSchema(
                name: $device,
                description: "The width of the layout on {$device} devices",
                properties: [
                    new NumberSchema(
                        name: "{$device} width",
                        description: "The width of the layout on {$device} devices. If this has not been specified, use {$widthDefaults[$device]}"
                    ),
                    $unitsSchema
                ],
                requiredFields: ["{$device} width", 'units']
            );
        }
        
        return new ObjectSchema(
            name: 'edit_layout_width',
            description: 'The width of the layout on different devices. If this has not been specified, use the default values for each device. Choose "desktop" for desktop devices, "tablet" for tablet devices, and "mobile" for mobile devices.',
            properties: $deviceObjects,
            requiredFields: $deviceSchemas
        );
    }
}