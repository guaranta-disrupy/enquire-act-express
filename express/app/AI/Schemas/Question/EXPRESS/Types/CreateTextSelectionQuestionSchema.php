<?php

namespace App\AI\Schemas\Question\EXPRESS\Types;

use App\AI\Schemas\Contracts\SchemaInterface;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\BooleanSchema;
use Prism\Prism\Schema\StringSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateBaseQuestionSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateTextSelectionChoiceSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateRequiredSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEnableScoringSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateRandomChoicesSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateMultipleChoiceSelectionsSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEditLayoutWidthSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEditColumnsSchema;

class CreateTextSelectionQuestionSchema implements SchemaInterface
{
    /**
     * Get the schema definition.
     *
     * @return \Prism\Prism\Schema\ObjectSchema
     */
    
    public function getSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'create_text_selection_question',
            description: 'The text selection question to create',
            properties: [
                (new CreateBaseQuestionSchema())->getSchema(),
                new ArraySchema(
                    name: 'text_options',
                    description: 'The text options for the selection question. UNLESS THE USER HAS ASKED YOU TO, DO NOT MAKE YOUR OWN.',
                    items: (new CreateTextSelectionChoiceSchema())->getSchema()
                ),
                (new CreateRequiredSchema())->getSchema(),
                (new CreateEnableScoringSchema())->getSchema(),
                (new CreateRandomChoicesSchema())->getSchema(),
                (new CreateMultipleChoiceSelectionsSchema())->getSchema(),
                new BooleanSchema(
                    name: 'dropdown',
                    description: 'Whether the text selection question should be displayed as a dropdown. If this has not been specified, use false as default.'
                ),
                new StringSchema(
                    name: 'dropdown_placeholder',
                    description: 'This is the placeholder text to be displayed in the dropdown. If this has not been specified by the user, use "Select Choice" as default.'
                ),
                (new CreateEditColumnsSchema())->getSchema(),
                (new CreateEditLayoutWidthSchema())->getSchema(),
            ],
            requiredFields: ['title_and_question_text', 'text_options', 'required', 'enable_scoring', 'random_choices', 'selections', 'dropdown', 'dropdown_placeholder', 'edit_columns', 'edit_layout_width']
        );
    }
}