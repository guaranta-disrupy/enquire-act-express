<?php

namespace App\AI\Schemas\Question\EXPRESS\Types;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\StringSchema;
use App\AI\Schemas\Contracts\SchemaInterface;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateBaseQuestionSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateRankingItemSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateRequiredSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEditLayoutWidthSchema;

class CreateRankingQuestionSchema implements SchemaInterface
{
    /**
     * Get the schema definition.
     *
     * @return \Prism\Prism\Schema\ObjectSchema
     */
    
    public function getSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'create_ranking_question',
            description: 'The ranking question to create',
            properties: [
                (new CreateBaseQuestionSchema())->getSchema(),
                new ArraySchema(
                    name: 'items',
                    description: 'This is a list of items that the respondent will rank. If the user has specified items, use them exactly. If the user has NOT specified items, you MUST generate 3-6 appropriate ranking items based on the question context. Each item should have a meaningful label that relates to the question topic.',
                    items: (new CreateRankingItemSchema())->getSchema()
                ),
                (new CreateRequiredSchema())->getSchema(),
                new \Prism\Prism\Schema\BooleanSchema(
                    name: 'random_items',
                    description: 'This defines if the items should be presented in a random order. If this has not been specified, use false.'
                ),
                (new CreateEditLayoutWidthSchema())->getSchema(),
                new EnumSchema(
                    name: 'mode',
                    description: 'This specifies the mode of the question. If not specified by the user, use "sort". Use "sort" for sort, and use "rank" for rank',
                    options: ['rank', 'sort']
                ),
                new ObjectSchema(
                    name: 'rank_mode',
                    description: 'Defines the properties for rank mode. THESE PROPERTIES SHOULD ONLY BE FILLED IN IF MODE IS SET TO RANK, OTHERWISE LEAVE AS NULL',
                    properties: [
                        new EnumSchema(
                            name: 'display_position',
                            description: 'The position of the drop zone with respect to the item list. If not specified, use right as default. Use "right" to have the drop zone on the right, use "left" to have the drop zone on the left, use "bottom" to have the drop zone on the bottom, and use "top" to have the drop zone on the top',
                            options: ['right', 'left', 'bottom', 'top'],
                            nullable: true
                        ),
                        new NumberSchema(
                            name: 'maximum_item_selection',
                            description: 'This specifies the maximum number of items that can be placed into the drop zone. If not specified, use 0 as default',
                            nullable: true
                        ), 
                        new NumberSchema(
                            name: 'minimum_item_selection',
                            description: 'This specifies the maximum number of items that can be placed into the drop zone. If not specified, use 3 as default',
                            nullable: true
                        ),
                        new StringSchema(
                            name: 'limit_error_message',
                            description: 'The error message to be displayed if the number of items in the drop zone is outside the permissible range. If not specified, use "Select at least {{count}} item(s)" as default',
                            nullable: true
                        ) 
                    ],
                    requiredFields: ['display_position', 'maximum_item_selection', 'minimum_item_selection', 'limit_error_message']
                ),
                new ObjectSchema(
                    name: 'sort_mode',
                    description: 'Defines the properties for sort mode. THESE PROPERTIES SHOULD ONLY BE FILLED IN IF MODE IS SET TO RANK, OTHERWISE LEAVE AS NULL',
                    properties: [
                        new NumberSchema(
                            name: 'number_of_columns',
                            description: 'Specifies the number of columns. If not specified, use 2 as default',
                            nullable: true
                        )
                    ],
                    requiredFields: ['number_of_columns']
                ),
                new NumberSchema(
                    name: 'item_fixed_height',
                    description: 'Specifies the size of each item\'s box. If not specified by the user, use null as default',
                    nullable: true
                )
            ],
            requiredFields: ['title_and_question_text', 'items', 'required', 'random_items', 'edit_layout_width', 'mode', 'rank_mode', 'sort_mode', 'item_fixed_height']
        );
    }
}