<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use Prism\Prism\Schema\BooleanSchema;

class CreateRequiredSchema {
    public function getSchema(): BooleanSchema
    {
        return new BooleanSchema(
            name: 'required',
            description: 'This defines if the question is required. If this has not been specified, use true.',
        );
    }
}