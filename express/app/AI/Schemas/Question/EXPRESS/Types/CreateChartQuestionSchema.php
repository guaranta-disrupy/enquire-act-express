<?php

namespace App\AI\Schemas\Question\EXPRESS\Types;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Schema\BooleanSchema;
use App\AI\Schemas\Contracts\SchemaInterface;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateBaseQuestionSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateChartItemSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEditLayoutWidthSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateRandomChoicesSchema;

abstract class CreateChartQuestionSchema implements SchemaInterface
{
    /**
     * Get the schema definition.
     *
     * @return \Prism\Prism\Schema\ObjectSchema
     */

    abstract protected function getType(): string;

    public function getSchema(): ObjectSchema
    {
        $type = $this->getType();
        return new ObjectSchema(
            name: "create_{$type}_chart_question",
            description: "The {$type} chart question to create",
            properties: [
                (new CreateBaseQuestionSchema())->getSchema(),
                new ArraySchema(
                    name: 'chart_items',
                    description: 'The items for the chart question. If the user has specified items, use them exactly. If the user has NOT specified items, you MUST generate 3-6 appropriate chart items based on the question context. Each item should have a meaningful label that relates to the question topic.',
                    items: (new CreateChartItemSchema())->getSchema()
                ),
                new ObjectSchema(
                    name: 'slider',
                    description: 'This allows users to input values using a slider, rather than typing them in',
                    properties: [
                        new BooleanSchema(
                            name: 'slider_enabled',
                            description: 'Indicates whether the slide is enabled. If this has not been specified, set to false'
                        ),
                        new NumberSchema(
                            name: 'minimum_range',
                            description: 'Indicates the minimum value that can be set on the slider. If this has not been specified, set to 0'
                        ),
                        new NumberSchema(
                            name: 'maximum_range',
                            description: 'Indicates the maximum value that can be set on the slider. If this has not been specified, set to 100'
                        ),
                    ],
                    requiredFields: ['slider_enabled', 'minimum_range', 'maximum_range']
                ),
                (new CreateEditLayoutWidthSchema())->getSchema(),
                (new CreateRandomChoicesSchema())->getSchema(),
                new NumberSchema(
                    name: 'default_value_per_item',
                    description: 'Specifies the default value of each item, prior to the respondent selecting a number. If not specified, use 0.'
                ),
                new StringSchema(
                    name: 'prefix',
                    description: 'Specifies the text displayed before each value. If not specified, use null',
                    nullable: true
                ),
                new StringSchema(
                    name: 'suffix',
                    description: 'Specifies the text displayed after each value. If not specified, use %',
                    nullable: true
                ),
                new NumberSchema(
                    name: 'minimum_value_per_item',
                    description: 'Specifies the minimum value of each item. If not specified, use 0.'
                ),
                new StringSchema(
                    name: 'error_message_minimum',
                    description: 'Specifies the text displayed if any of the values are below the minimum value. If not specified, use "Each value must be greater than @min"'
                ),
                new BooleanSchema(
                    name: 'allow_decimal',
                    description: 'Specifies whether decimal values can be used by the respondent. If not specified, use false.'
                ),
                new ObjectSchema(
                    name: 'fixed_value',
                    description: 'This forces values to sum up to a fixed value',
                    properties: [
                        new BooleanSchema(
                            name: 'fixed_value_enabled',
                            description: 'Indicates whether fixed value is enabled. If this has not been specified, set to true'
                        ),
                        new NumberSchema(
                            name: 'total_value',
                            description: 'Indicates the total value that all values must sum up to. If this has not been specified, set to 100'
                        ),
                        new StringSchema(
                            name: 'error_message_maximum',
                            description: 'Specifies the text displayed if values do not sum up to the total. If not specified, use "Total must be @total%"'
                        ),
                    ],
                    requiredFields: ['fixed_value_enabled', 'total_value', 'error_message_maximum']
                ),
                new ObjectSchema(
                    name: 'display_total',
                    description: 'This displays the total sum of values to respondents',
                    properties: [
                        new BooleanSchema(
                            name: 'display_total_enabled',
                            description: 'Indicates whether display total is enabled. If this has not been specified, set to true'
                        ),
                        new StringSchema(
                            name: 'label',
                            description: 'Specifies the text used to label the display total. If not specified, use "Total"'
                        ),
                    ],
                    requiredFields: ['display_total_enabled', 'label']
                ),
            ],
            requiredFields: ['title_and_question_text', 'chart_items', 'slider', 'edit_layout_width', 'random_choices', 'default_value_per_item', 'prefix', 'suffix', 'minimum_value_per_item', 'error_message_minimum', 'allow_decimal', 'fixed_value', 'display_total']
        );
    }
}