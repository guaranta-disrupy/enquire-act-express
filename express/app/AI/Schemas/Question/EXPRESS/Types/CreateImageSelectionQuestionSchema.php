<?php

namespace App\AI\Schemas\Question\EXPRESS\Types;

use App\AI\Schemas\Contracts\SchemaInterface;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\ArraySchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateBaseQuestionSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateImageSelectionChoiceSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateRequiredSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEnableScoringSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateRandomChoicesSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateMultipleChoiceSelectionsSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEditDisplayTypeSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEditImageDimensionSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEditLayoutWidthSchema;

// NOTE THAT THIS IS NOT YET COMPLETE - NEED TO DEAL WITH IMAGE SOURCE

class CreateImageSelectionQuestionSchema implements SchemaInterface
{
    /**
     * Get the schema definition.
     *
     * @return \Prism\Prism\Schema\ObjectSchema
     */
    
    public function getSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'create_image_selection_question',
            description: 'The image selection question to create',
            properties: [
                (new CreateBaseQuestionSchema())->getSchema(),
                new ArraySchema(
                    name: 'image_options',
                    description: 'The text options for the selection question. UNLESS THE USER HAS ASKED YOU TO, DO NOT MAKE YOUR OWN.',
                    items: (new CreateImageSelectionChoiceSchema())->getSchema()
                ),
                (new CreateRequiredSchema())->getSchema(),
                (new CreateEnableScoringSchema())->getSchema(),
                (new CreateRandomChoicesSchema())->getSchema(),
                (new CreateMultipleChoiceSelectionsSchema())->getSchema(),
                (new CreateEditDisplayTypeSchema())->getSchema(),
                (new CreateEditImageDimensionSchema())->getSchema(),
                (new CreateEditLayoutWidthSchema())->getSchema(),
            ],
            requiredFields: ['title_and_question_text', 'image_options', 'required', 'enable_scoring', 'random_choices', 'selections', 'edit_layout_width'], 
        );
    }
}