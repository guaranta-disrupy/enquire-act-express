<?php

namespace App\AI\Schemas\Question\EXPRESS\Types;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\ArraySchema;
use App\AI\Schemas\Contracts\SchemaInterface;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateBaseQuestionSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateABLabelsSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateSegmentationStatementPairSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateSegmentationChoicesSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateRequiredSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEditLayoutWidthSchema;

class CreateSegmentationQuestionSchema implements SchemaInterface
{
    /**
     * Get the schema definition.
     *
     * @return \Prism\Prism\Schema\ObjectSchema
     */
    
    public function getSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'create_segmentation_question',
            description: 'The segmentation question to create',
            properties:[
                (new CreateBaseQuestionSchema())->getSchema(),
                (new CreateABLabelsSchema())->getSchema(),
                new ArraySchema(
                    name: 'statements',
                    description: 'The list of statement pairs that the respondent will answer for. If the user has specified statements, use them exactly. If the user has NOT specified statements, you MUST generate 3-6 appropriate statement pairs based on the question context. Each pair should have meaningful statements A and B that relate to the question topic.',
                    items: (new CreateSegmentationStatementPairSchema())->getSchema()
                ),
                new ArraySchema(
                    name: 'choices',
                    description: 'The list of choices that a respondent can choose from for each statement pair. If the user has specified choices, use them exactly. If the user has NOT specified choices, you MUST generate 3-5 appropriate rating choices with meaningful labels and colors based on the question context.',
                    items: (new CreateSegmentationChoicesSchema())->getSchema()
                ),
                (new CreateRequiredSchema())->getSchema(),
                (new CreateEditLayoutWidthSchema())->getSchema(),
                new \Prism\Prism\Schema\BooleanSchema(
                    name: 'random_items',
                    description: 'This defines if the items should be presented in a random order. If this has not been specified, use false.'
                )
            ],
            requiredFields: ['title_and_question_text', 'labels', 'statements', 'choices', 'required', 'edit_layout_width', 'random_items']
        );
    }
}