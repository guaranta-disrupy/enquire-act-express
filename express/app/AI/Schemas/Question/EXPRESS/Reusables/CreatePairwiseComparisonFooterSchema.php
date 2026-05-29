<?php

namespace App\AI\Schemas\Question\EXPRESS\Reusables;

use Prism\Prism\Schema\StringSchema;

class CreatePairwiseComparisonFooterSchema extends CreateBaseFooterSchema
{
    protected function getDefault(): string
    {
        return '<p>Click <strong>Statement A</strong> or <strong>Statement B</strong> to choose your preference</p>';
    }
    
    public function getSchema(): StringSchema
    {
        $default = $this->getDefault();
        return new StringSchema(
            name: 'footer',
            description: "Specifies the HTML text to be displayed in the footer. Must be wrapped in <p> tags. Use <strong> tags for emphasis. Unless otherwise directed, use: {$default}",
            nullable: true
        );
    }
}

