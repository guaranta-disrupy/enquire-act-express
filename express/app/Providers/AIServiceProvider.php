<?php

namespace App\Providers;

use App\AI\Repositories\Contracts\SchemaRepositoryInterface;
use App\AI\Repositories\SchemaRepository;
use Illuminate\Support\ServiceProvider;

class AIServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(SchemaRepositoryInterface::class, SchemaRepository::class);
    }
}