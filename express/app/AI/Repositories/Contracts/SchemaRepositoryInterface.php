<?php

namespace App\AI\Repositories\Contracts;

use Prism\Prism\Schema\ObjectSchema;

interface SchemaRepositoryInterface
{
    public function getSchema(string $platform, string $scope, string $intent): ?ObjectSchema;
}