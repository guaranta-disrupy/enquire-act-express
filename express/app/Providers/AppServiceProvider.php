<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Services\ConversationMemoryService;
use App\Services\MongoConversationMemoryService;
use App\Services\Contracts\ConversationMemoryServiceInterface;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register conversation memory service based on configuration
        $this->app->singleton(ConversationMemoryServiceInterface::class, function ($app) {
            $storage = config('app.conversation_memory_storage', env('CONVERSATION_MEMORY_STORAGE', 'sql'));
            
            if ($storage === 'mongodb') {
                return $app->make(MongoConversationMemoryService::class);
            }
            
            // Default to SQL implementation
            return $app->make(ConversationMemoryService::class);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}
