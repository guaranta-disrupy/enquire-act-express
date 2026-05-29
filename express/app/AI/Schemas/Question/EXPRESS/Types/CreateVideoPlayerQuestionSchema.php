<?php

namespace App\AI\Schemas\Question\EXPRESS\Types;

use App\AI\Schemas\Question\EXPRESS\Reusables\CreateBaseQuestionSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateButtonDelaySchema;

use App\AI\Schemas\Contracts\SchemaInterface;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateUnitsSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\EnumSchema;

// NOTE THAT THIS SCHEMA MAY NOT BE COMPLETE YET - NEED TO DEAL WITH VIDEO SOURCE SOMEHOW

class CreateVideoPlayerQuestionSchema implements SchemaInterface
{
    /**
     * Get the schema definition.
     *
     * @return \Prism\Prism\Schema\ObjectSchema
     */

    public function getSchema(): ObjectSchema
    {
        $dimensionSchemas = ['width', 'height'];
        $unitDefaults = [
            'width' => 'vw',
            'height' => 'vh'
        ];

        $dimensionObjects = [];

        foreach ($dimensionSchemas as $dimension) {
            $unitDimensions = ['percentage', 'vmin', $unitDefaults[$dimension], 'auto'];
            $dimensionObjects[] = new ObjectSchema(
                name: $dimension,
                description: "The {$dimension} of the video player.",
                properties: [
                    new NumberSchema(
                        name: "{$dimension}_value",
                        description: "The value of the {$dimension} for the video player. If this has not been specified, use 95. "
                    ), 
                    new EnumSchema(
                        name: 'units',
                        description: "The units for the {$dimension} of the video player. If this has not been specified, use {$unitDefaults[$dimension]}. Choose 'percentage' for percentage, {$unitDefaults[$dimension]} for viewport width/height, and 'px' for pixels.",
                        options: $unitDimensions
                    )
                ],
                requiredFields: ["{$dimension}_value", 'units']
            );
        }

        return new ObjectSchema(
            name: 'create_video_player_question',
            description: 'The video player question to create',
            properties: [
                (new CreateBaseQuestionSchema())->getSchema(),
                new ObjectSchema(
                    name: 'layout_width',
                    description: 'The width of the layout',
                    properties: [
                        new NumberSchema(
                            name: 'width',
                            description: 'The width of the question box. If this has not been specified, use 60.',
                        ),
                        (new CreateUnitsSchema())->getSchema(),
                    ],
                    requiredFields: ['width', 'units']
                ),
                // DO WE NEED TO INSERT A VIDEO SOURCE?
                ...$dimensionObjects,
                (new CreateButtonDelaySchema())->getSchema(),
            ],
            requiredFields: ['title_and_question_text', 'layout_width', 'width', 'height', 'button_delay']   
        );
    }
}