<?php

namespace App\AI\Schemas\Question\EXPRESS\Types;

use App\AI\Schemas\Question\EXPRESS\Reusables\CreateBaseQuestionSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateButtonDelaySchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEditLayoutWidthSchema;
use App\AI\Enums\LayoutDescriptive;
use App\AI\Schemas\Contracts\SchemaInterface;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\EnumSchema;

class CreateDescriptiveQuestionSchema implements SchemaInterface
{
    /**
     * Get the schema definition.
     *
     * @return \Prism\Prism\Schema\ObjectSchema
     */
    
    public function getSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'create_descriptive_question',
            description: 'The descriptive question to create',
            properties: [
                (new CreateBaseQuestionSchema())->getSchema(),
                new EnumSchema(
                    name: 'change_layout',
                    description: 'The layout type for the question. If this has not been specified, use "default". Choose "image left" if the user wants the image to be on the left side of the question text, "image right" for the image to be on the right side, and "image top" for the image to be above the question text.',
                    options: LayoutDescriptive::values()
                ),
                (new CreateButtonDelaySchema())->getSchema(),
                (new CreateEditLayoutWidthSchema())->getSchema()
            ],
            requiredFields: ['title_and_question_text', 'change_layout', 'button_delay', 'edit_layout_width']
        );
    }
}