<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/projects';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            // List of endpoints to exclude from rate limiting
            $excludedEndpoints = [
                'api/f8208271af/show2',
                'api/f8208271af/store'
                // Add more endpoints as needed
            ];

            // Check if the current request path matches any excluded endpoint
            if (in_array($request->path(), $excludedEndpoints)) {
                return Limit::perMinute(200)->by($request?->id ?: $request->ip());
            }

            return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
        });
    }
}
