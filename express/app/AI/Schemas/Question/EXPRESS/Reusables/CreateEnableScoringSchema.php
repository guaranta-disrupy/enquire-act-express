<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use Prism\Prism\Schema\BooleanSchema;

class CreateEnableScoringSchema
{
    public function getSchema(): BooleanSchema
    {
        return new BooleanSchema(
            name: 'enable_scoring',
            description: 'This defines if scoring is enabled for the question. If this has not been specified, use false.',
        );
    }
}