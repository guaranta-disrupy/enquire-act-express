<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables\Inputs;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Schema\NumberSchema;

class CreateSliderItemSchema extends CreateInputSchema
{
    protected function getInputType(): string
    {
        return 'item';
    }

    public function getSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'item',
            description: 'An item for a question, which can be used in various question types.',
            properties: [
                new NumberSchema(
                    name: 'index',
                    description: 'The index/order of the item, starting from 1.',
                    nullable: false
                ),
                new StringSchema(
                    name: 'name',
                    description: 'The text of the item, which may contain HTML formatting.',
                    nullable: true
                ),
                new StringSchema(
                    name: 'equivalent',
                    description: 'The plain text version of the item name, with all HTML tags removed. This should always match the name field content but without any HTML formatting. For example, if name is "<p>Impact on daily life</p>", equivalent should be "Impact on daily life".',
                    nullable: false
                ),
                new \Prism\Prism\Schema\BooleanSchema(
                    name: 'random_item',
                    description: 'Indicates whether the specific item should be displayed randomly. If this has not been specified, use false.'
                )
            ],
            requiredFields: ['index', 'name', 'equivalent', 'random_item']
        );
    }
}