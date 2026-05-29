<?php

namespace App\AI\Schemas\Question\EXPRESS\Types;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\ArraySchema;
use App\AI\Schemas\Contracts\SchemaInterface;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateABLabelsSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateBaseQuestionSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\Inputs\CreatePairwiseComparisonStatementSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateRequiredSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreatePairwiseComparisonFooterSchema;
use App\AI\Schemas\Question\EXPRESS\Reusables\CreateEditLayoutWidthSchema;

class CreatePariwiseComparisonQuestionSchema implements SchemaInterface
{
    /**
     * Get the schema definition.
     *
     * @return \Prism\Prism\Schema\ObjectSchema
     */
    
    public function getSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'create_pairwise_comparison_question',
            description: 'The pairwise comparison question to create',
            properties: [
                (new CreateBaseQuestionSchema())->getSchema(),
                (new CreateABLabelsSchema())->getSchema(),
                new ArraySchema(
                    name: 'items',
                    description: 'CRITICAL: First identify 3-5 base options/items from the user\'s topic. Then generate ALL pairwise combinations of these base options. For n base options, create n*(n-1)/2 items. Each base option must have a unique sequential value (1, 2, 3, etc.). Example: If base options are "Option A", "Option B", "Option C" (values 1, 2, 3), generate: (Option A vs Option B), (Option A vs Option C), (Option B vs Option C). Index must start at 1 and increment sequentially. UNLESS THE USER HAS ASKED YOU TO, DO NOT MAKE YOUR OWN.',
                    items: (new CreatePairwiseComparisonStatementSchema())->getSchema()
                ),
                (new CreateRequiredSchema())->getSchema(),
                (new CreatePairwiseComparisonFooterSchema())->getSchema(),
                (new CreateEditLayoutWidthSchema())->getSchema()
            ],
            requiredFields: ['title_and_question_text', 'labels', 'items', 'required', 'footer', 'edit_layout_width']
        );
    }
}