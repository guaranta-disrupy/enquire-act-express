<?php

namespace App\AI\Schemas\Question\EXPRESS\Types;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use App\AI\Schemas\Contracts\SchemaInterface;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateBaseQuestionSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateRequiredSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateSingleTextboxSelectTypeSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEditTextboxDimensionSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEditLayoutWidthSchema;

class CreateSingleTextboxSchema implements SchemaInterface
{
    /**
     * Get the schema definition.
     *
     * @return \Prism\Prism\Schema\ObjectSchema
     */
    
    public function getSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'create_single_textbox_question',
            description: 'The single textbox question to create',
            properties: [
                (new CreateBaseQuestionSchema())->getSchema(),
                (new CreateRequiredSchema())->getSchema(),
                new StringSchema(
                    name: 'before_textbox',
                    description: 'Indicates the text that should be displayed before the textbox. If this has not been specified, use "I am".',
                ),
                new StringSchema(
                    name: 'after_textbox',
                    description: 'Indicates the text that should be displayed after the textbox. If this has not been specified, use "years old".',
                ),
                (new CreateSingleTextboxSelectTypeSchema())->getSchema(),
                (new CreateEditTextboxDimensionSchema())->getSchema(),
                (new CreateEditLayoutWidthSchema())->getSchema()
            ],
            requiredFields: ['title_and_question_text', 'required', 'before_textbox', 'after_textbox', 'select_type', 'edit_textbox_dimension', 'edit_layout_width']
        );
    }
}