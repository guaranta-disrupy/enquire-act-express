<?php

namespace App\AI\Schemas\Question\EXPRESS\Types;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\ArraySchema;
use App\AI\Schemas\Contracts\SchemaInterface;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateBaseQuestionSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateMultipleTextboxChoiceSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateRequiredSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEditColumnsSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateRandomChoicesSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEditLayoutWidthSchema;

class CreateMultipleTextboxQuestionSchema implements SchemaInterface
{
    /**
     * Get the schema definition.
     *
     * @return \Prism\Prism\Schema\ObjectSchema
     */

    public function getSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'create_multiple_textbox_question',
            description: 'The multiple textbox question to create',
            properties: [
                (new CreateBaseQuestionSchema())->getSchema(),
                new ArraySchema(
                    name: 'textboxes',
                    description: 'The textboxes for the question. If the user has specified textboxes, use them exactly. If the user has NOT specified textboxes, you MUST generate 2-5 appropriate textbox items based on the question context. Each textbox should have a meaningful label that relates to the question topic.',
                    items: (new CreateMultipleTextboxChoiceSchema())->getSchema()
                ),
                (new CreateRequiredSchema())->getSchema(),
                (new CreateEditColumnsSchema())->getSchema(),
                (new CreateRandomChoicesSchema())->getSchema(),
                (new CreateEditLayoutWidthSchema())->getSchema()
            ],
            requiredFields: ['title_and_question_text', 'textboxes', 'required','edit_columns', 'random_choices','edit_layout_width']
        );
    }
}