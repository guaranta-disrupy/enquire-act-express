<?php

namespace App\AI\Schemas\Question\EXPRESS\Types;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\BooleanSchema;
use App\AI\Schemas\Contracts\SchemaInterface;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateBaseQuestionSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateButtonRatingItemSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateButtonRatingChoiceSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateRequiredSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateRandomValuesSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEditLayoutWidthSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateHeaderSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateButtonRatingFooterSchema;

class CreateButtonRatingQuestionSchema implements SchemaInterface
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
            description: 'Button rating: the main question_text is an introduction or instruction; items are the questions/statements respondents rate; choices are the text scale labels for the buttons. Set hide_instruction true when items are self-explanatory and no intro is needed.',
            properties: [
                (new CreateBaseQuestionSchema())->getSchema(),
                new ArraySchema(
                    name: 'items',
                    description: 'The QUESTIONS or complete statements that the respondent will answer with the scale. Each item must be a full question or clear statement (e.g. "How satisfied are you with the product quality?", "How satisfied are you with customer service?"). Do NOT use single words or short phrases. Generate 3-6 items. If the user specified items, use them exactly; otherwise generate appropriate questions based on the context.',
                    items: (new CreateButtonRatingItemSchema())->getSchema()
                ),
                new ArraySchema(
                    name: 'choices',
                    description: 'The answer options shown on the buttons. ALWAYS match the question: for binary questions use "Yes" and "No" (2 choices); for rating/scale questions use TEXT scale labels (e.g. "Very satisfied", "Satisfied", "Neutral", "Dissatisfied", "Very dissatisfied")—never numbers alone. Generate 2 choices for Yes/No, or 3-5 for scales. Use proper case and no underscores. If the user specified choices, use them exactly.',
                    items: (new CreateButtonRatingChoiceSchema())->getSchema(),
                ),
                (new CreateRequiredSchema())->getSchema(),
                new \Prism\Prism\Schema\BooleanSchema(
                    name: 'random_items',
                    description: 'This defines if the items should be presented in a random order. If this has not been specified, use false.'
                ),
                (new CreateRandomValuesSchema())->getSchema(),
                (new CreateEditLayoutWidthSchema())->getSchema(),
                new BooleanSchema(
                    name: 'track_reaction_time',
                    description: 'Specifies whether to track reaction time. If not specified, use false as default'
                ),
                new BooleanSchema(
                    name: 'hide_instruction',
                    description: 'When true, the main question/instruction (title_and_question_text) is hidden and only the items are shown. Set to true when each item is a self-explanatory question and no intro is needed. Set to false when the question_text provides useful introduction (e.g. "For each of the following, indicate your level of satisfaction."). If not specified, use false.'
                ),
                (new CreateHeaderSchema())->getSchema(),
                (new CreateButtonRatingFooterSchema())->getSchema()
            ],
            requiredFields: ['title_and_question_text', 'items', 'choices', 'required', 'random_items','random_values', 'edit_layout_width', 'track_reaction_time', 'hide_instruction', 'header', 'footer']
        );
    }
}