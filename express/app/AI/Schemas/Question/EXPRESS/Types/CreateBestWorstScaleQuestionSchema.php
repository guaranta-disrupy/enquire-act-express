<?php

namespace App\AI\Schemas\Question\EXPRESS\Types;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\NumberSchema;
use App\AI\Schemas\Contracts\SchemaInterface;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateBaseQuestionSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateBestWorstScaleLabelsSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEditLayoutWidthSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateBestWorstScaleStatementSchema;

class CreateBestWorstScaleQuestionSchema implements SchemaInterface
{
    /**
     * Get the schema definition.
     *
     * @return \Prism\Prism\Schema\ObjectSchema
     */
    
    public function getSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'create_best_worst_scale_question',
            description: 'The best-worst scale question to create',
            properties: [
                (new CreateBaseQuestionSchema())->getSchema(),
                (new CreateBestWorstScaleLabelsSchema())->getSchema(),
                new ArraySchema(
                    name: 'statements',
                    description: 'The list of statements for the respondent to evaluate. If the user has specified statements, use them exactly. If the user has NOT specified statements, you MUST generate 6-10 appropriate statements based on the question context. Each statement should be meaningful and relate to the question topic.',
                    items: (new CreateBestWorstScaleStatementSchema())->getSchema()
                ),
                new NumberSchema(
                    name: 'repetitions',
                    description: 'The number of times the question will be repeated. If not specified, use 5 as default'
                ),
                (new CreateEditLayoutWidthSchema())->getSchema()
            ],
            requiredFields: ['title_and_question_text', 'labels', 'statements', 'repetitions', 'edit_layout_width']
        );
    }
}