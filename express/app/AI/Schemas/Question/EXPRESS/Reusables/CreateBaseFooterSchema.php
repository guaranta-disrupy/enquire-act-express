<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use Prism\Prism\Schema\StringSchema;

abstract class CreateBaseFooterSchema
{
    protected function getDefault(): string
    {
        return 'null';
    }
    public function getSchema(): StringSchema
    {
        $default = $this->getDefault();
        return new StringSchema(
            name: 'footer',
            description: "Specifies the text to be displayed in the header. Unless otherwise directed, leave as {$default}",
            nullable: true
        );
    }
}