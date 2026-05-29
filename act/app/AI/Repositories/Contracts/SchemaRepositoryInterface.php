<?php

namespace App\AI\Repositories\Contracts;

use Prism\Prism\Schema\ObjectSchema;

interface SchemaRepositoryInterface
{
    /**
     * Get the schema for a given platform, scope, and intent
     *
     * @param string $platform
     * @param string $scope
     * @param string $intent
     * @return ObjectSchema|null
     */
    public function getSchema(string $platform, string $scope, string $intent): ?ObjectSchema;
}