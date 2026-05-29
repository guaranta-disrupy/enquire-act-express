<?php

namespace App\AI\Schemas\Question\EXPRESS\Types;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\BooleanSchema;
use App\AI\Schemas\Contracts\SchemaInterface;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateBaseQuestionSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateMatrixColumnSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreateMatrixRowSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateRequiredSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEditLayoutWidthSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateRandomChoicesSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateMatrixSelectionsSchema;

class CreateMatrixQuestionSchema implements SchemaInterface
{
    /**
     * Get the schema definition.
     *
     * @return \Prism\Prism\Schema\ObjectSchema
     */
    
    public function getSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'create_matrix_question',
            description: 'The matrix question to create',
            properties: [
                (new CreateBaseQuestionSchema())->getSchema(),
                new ArraySchema(
                    name: 'columns',
                    description: 'The list of columns that the respondent can answer with. Generate 3-7 appropriate column options based on the question topic. Columns represent the answer choices/options (e.g., for satisfaction: "Very Satisfied", "Satisfied", "Neutral", "Dissatisfied", "Very Dissatisfied"; for agreement: "Strongly Agree", "Agree", "Neutral", "Disagree", "Strongly Disagree"; for frequency: "Always", "Often", "Sometimes", "Rarely", "Never"). Columns should form a coherent set of answer options appropriate for rating the rows. If user mentions specific columns, use those. Otherwise, generate contextually appropriate columns based on the question topic.',
                    items: (new CreateMatrixColumnSchema())->getSchema(),
                ),
                new ArraySchema(
                    name: 'rows',
                    description: 'The list of rows that the respondent answers for. Generate 3-8 appropriate row items based on the question topic. Rows represent the statements/aspects being evaluated (e.g., for satisfaction: "Product quality", "Customer service", "Price value", "Ease of use"; for agreement: "The product meets my needs", "I would recommend this product", "The price is reasonable"). Rows should be coherent, related statements or aspects that form a logical set. Each row should be a complete statement or descriptive phrase. If user mentions specific rows, use those. Otherwise, generate contextually appropriate rows based on the question topic.',
                    items: (new CreateMatrixRowSchema())->getSchema()
                ), 
                (new CreateRequiredSchema())->getSchema(),
                (new CreateEditLayoutWidthSchema())->getSchema(),
                new BooleanSchema(
                    name: 'single_answer_per_column',
                    description: 'Specifies whether only one answer should be selected per column. If not specified, use false as default'
                ),
                (new CreateRandomChoicesSchema())->getSchema(),
                (new CreateMatrixSelectionsSchema())->getSchema()
            ],
            requiredFields: ['title_and_question_text', 'columns', 'rows', 'required', 'edit_layout_width', 'single_answer_per_column', 'random_choices', 'selections']
        );
    }
}