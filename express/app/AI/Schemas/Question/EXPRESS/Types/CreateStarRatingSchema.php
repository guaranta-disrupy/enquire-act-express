<?php

namespace App\AI\Schemas\Question\EXPRESS\Types;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\NumberSchema;
use App\AI\Schemas\Contracts\SchemaInterface;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateBaseQuestionSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateStarRatingItemSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateRequiredSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEditLayoutWidthSchema;

class CreateStarRatingSchema implements SchemaInterface
{
    /**
     * Get the schema definition.
     *
     * @return \Prism\Prism\Schema\ObjectSchema
     */
    
    public function getSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'create_button_rating_question',
            description: 'The button rating question to create',
            properties: [
                (new CreateBaseQuestionSchema())->getSchema(),
                new ArraySchema(
                    name: 'items',
                    description: 'The list of items that the respondent will answer for. If the user has specified items, use them exactly. If the user has NOT specified items, you MUST generate 3-6 appropriate items based on the question context. Each item should be a descriptive statement that represents a specific level or state related to the question topic (e.g., "I feel unwell most of the time", "I generally feel well with minor issues", "I feel very healthy, balanced, and well"). The items should be contextually relevant to the question and describe how the respondent feels or their situation. DO NOT use generic satisfaction labels like "Very Satisfied", "Satisfied", "Neutral", "Dissatisfied", or "Very Dissatisfied" as these are not appropriate for star rating questions.',
                    items: (new CreateStarRatingItemSchema())->getSchema(),
                ),
                (new CreateRequiredSchema())->getSchema(),
                new NumberSchema(
                    name: 'number_of_rating_stars',
                    description: 'Specifies the number of rating stars. If not specified by the user, use 5 as default'
                ),
                (new CreateEditLayoutWidthSchema())->getSchema(),
                new \Prism\Prism\Schema\BooleanSchema(
                    name: 'random_items',
                    description: 'This defines if the items should be presented in a random order. If this has not been specified, use false.'
                )
            ],
            requiredFields: ['title_and_question_text', 'items', 'required', 'number_of_rating_stars', 'edit_layout_width', 'random_items']
        );
    }
}