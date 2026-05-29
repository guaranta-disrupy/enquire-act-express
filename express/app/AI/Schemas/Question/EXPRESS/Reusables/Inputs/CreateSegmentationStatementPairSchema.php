<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables\Inputs;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Schema\BooleanSchema;

class CreateSegmentationStatementPairSchema
{
    public function getSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'statement_pair',
            description: 'A statement pair that the respondent will answer for',
            properties: [
                new StringSchema(
                    name: 'statement_A',
                    description: 'The text of statement A',
                ),
                new StringSchema(
                    name: 'equivalent_A',
                    description: 'The text associated with statement A, used for data processing. If not specified, leave as null',
                    nullable: true
                ),
                new StringSchema(
                    name: 'statement_B',
                    description: 'The text of statement B',
                ),
                new StringSchema(
                    name: 'equivalent_B',
                    description: 'The text associated with statement B, used for data processing. If not specified, leave as null',
                    nullable: true
                ),
                new BooleanSchema(
                    name: 'optional_item',
                    description: 'Specifies whether the respondent must answer the item to move onto the next part. If not specified, use false as default'
                ),
                new \Prism\Prism\Schema\BooleanSchema(
                    name: 'random_item',
                    description: 'Indicates whether the specific item should be displayed randomly. If this has not been specified, use false.'
                )
            ],
            requiredFields: ['statement_A', 'equivalent_A', 'statement_B', 'equivalent_B', 'optional_item', 'random_item']
        );
    }
}