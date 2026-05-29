<?php

namespace App\AI\Schemas\Contracts;

use Prism\Prism\Schema\ObjectSchema;

interface SchemaInterface
{
    public function getSchema(): ObjectSchema;
}