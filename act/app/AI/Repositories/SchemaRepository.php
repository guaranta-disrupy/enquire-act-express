<?php

namespace App\AI\Repositories;

use App\AI\Repositories\Contracts\SchemaRepositoryInterface;
use Prism\Prism\Schema\ObjectSchema;
use Illuminate\Support\Str;

class SchemaRepository implements SchemaRepositoryInterface
{
    /**
     * Get the schema for a given platform, scope, and intent
     * Loads schemas by convention from App\AI\Schemas\{Platform}\{Scope}\{Intent}Schema
     *
     * @param string $platform
     * @param string $scope
     * @param string $intent
     * @return ObjectSchema|null
     */
    public function getSchema(string $platform, string $scope, string $intent): ?ObjectSchema
    {
        // Map platform to schema directory
        $platformMap = [
            'act' => 'App',
        ];
        
        $platformDirectory = $platformMap[strtolower($platform)] ?? Str::studly($platform);
        $scopeStudly = Str::studly($scope);
        $intentStudly = Str::studly($intent);
        
        // Construct the schema class name by convention
        // e.g., App\AI\Schemas\App\Round\CreateRoundSchema
        $schemaClass = "App\\AI\\Schemas\\{$platformDirectory}\\{$scopeStudly}\\{$intentStudly}{$scopeStudly}Schema";
        
        // Check if the schema class exists
        if (!class_exists($schemaClass)) {
            \Log::warning("Schema class not found: {$schemaClass}", [
                'platform' => $platform,
                'scope' => $scope,
                'intent' => $intent,
                'attempted_class' => $schemaClass,
            ]);
            return null;
        }
        
        // Instantiate and return the schema
        try {
            $schemaInstance = new $schemaClass();
            \Log::info("✅ Schema loaded successfully: {$schemaClass}");
            return $schemaInstance->getSchema();
        } catch (\Throwable $e) {
            \Log::error("Failed to instantiate schema: {$schemaClass}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'platform' => $platform,
                'scope' => $scope,
                'intent' => $intent,
            ]);
            return null;
        }
    }
}