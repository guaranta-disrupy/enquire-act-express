<?php

namespace App\AI\Schemas\Question\EXPRESS\Types;

use Prism\Prism\Schema\ObjectSchema;
use App\AI\Schemas\Contracts\SchemaInterface;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateBaseQuestionSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateRequiredSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateMaximumCharacterOpenEndedSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateMinimumCharacterOpenEndedSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEditLayoutWidthSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEditTextboxDimensionSchema;

class CreateOpenEndedQuestionSchema implements SchemaInterface
{
    /**
     * Get the schema definition.
     *
     * @return \Prism\Prism\Schema\ObjectSchema
     */
    
    public function getSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'create_open_ended_question',
            description: 'The open-ended question to create. UNLESS THE USER HAS ASKED YOU TO, DO NOT MAKE YOUR OWN.',
            properties: [
                (new CreateBaseQuestionSchema())->getSchema(),
                (new CreateRequiredSchema())->getSchema(),
                (new CreateMinimumCharacterOpenEndedSchema())->getSchema(),
                (new CreateMaximumCharacterOpenEndedSchema())->getSchema(),
                (new CreateEditLayoutWidthSchema())->getSchema(),
                (new CreateEditTextboxDimensionSchema())->getSchema()
            ],
            requiredFields: ['title_and_question_text', 'required', 'minimum_character', 'maximum_character', 'edit_layout_width', 'edit_textbox_dimension']
        );
    }
}