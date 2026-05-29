<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables\Inputs;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Schema\NumberSchema;

class CreatePairwiseComparisonStatementSchema
{
    public function getSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'pairwise_comparison_item',
            description: 'A pairwise comparison item containing two statements to compare. This must be one of the pairwise combinations from the base options identified.',
            properties: [
                new StringSchema(
                    name: 'statementA',
                    description: 'The HTML text of statement A wrapped in <p> tags. Example: "<p>citi hardware</p>". This must be one of the base options identified.',
                ),
                new StringSchema(
                    name: 'statementB',
                    description: 'The HTML text of statement B wrapped in <p> tags. Example: "<p>wilcon depot</p>". This must be one of the base options identified.',
                ),
                new NumberSchema(
                    name: 'valueA',
                    description: 'The unique sequential numeric value assigned to the base option used in statementA. Each base option gets a unique value (1, 2, 3, etc.). The same base option must always use the same value across all items.',
                ),
                new NumberSchema(
                    name: 'valueB',
                    description: 'The unique sequential numeric value assigned to the base option used in statementB. Each base option gets a unique value (1, 2, 3, etc.). The same base option must always use the same value across all items.',
                ),
                new NumberSchema(
                    name: 'index',
                    description: 'The index position of this comparison pair. Must start at 1 and increment sequentially (1, 2, 3, etc.) for each item.',
                ),
                new StringSchema(
                    name: 'equivalentA',
                    description: 'The plain text (without HTML) of the base option used in statementA. Example: if statementA is "<p>citi hardware</p>", equivalentA should be "citi hardware". This must match the same base option across all items that use it.',
                ),
                new StringSchema(
                    name: 'equivalentB',
                    description: 'The plain text (without HTML) of the base option used in statementB. Example: if statementB is "<p>wilcon depot</p>", equivalentB should be "wilcon depot". This must match the same base option across all items that use it.',
                )
            ],
            requiredFields: ['statementA', 'statementB', 'valueA', 'valueB', 'index', 'equivalentA', 'equivalentB']
        );
    }
}