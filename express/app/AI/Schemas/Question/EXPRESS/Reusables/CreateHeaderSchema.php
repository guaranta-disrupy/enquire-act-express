<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use Prism\Prism\Schema\StringSchema;

class CreateHeaderSchema 
{
    public function getSchema(): StringSchema
    {
        return new StringSchema(
            name: 'header',
            description: 'Specifies the text to be displayed in the header. Unless otherwise directed, leave as null',
            nullable: true 
        );
    }
}