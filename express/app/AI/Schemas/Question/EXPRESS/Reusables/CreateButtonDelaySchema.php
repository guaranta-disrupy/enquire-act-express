<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use Prism\Prism\Schema\NumberSchema;

class CreateButtonDelaySchema
{
    public function getSchema(): NumberSchema
    {
        return new NumberSchema(
            name: 'button_delay',
            description: 'The length of time in seconds before the button appears after the question text. If this has not been specified, use 0.',
        );
    }
}