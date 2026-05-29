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
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
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
            $excludedEndpoints = [
                'api/v2/fetch',
                'api/v1/save',
                // Add more endpoints as needed
            ];
            // return Limit::none();
            // Check if the current request path matches any excluded endpoint
            if (in_array($request->path(), $excludedEndpoints)) {
                return Limit::perMinute(100)->by($request?->id ?: $request->ip());
            }
            // if ($request->user()->is_exempt_from_subscription || $request->user()->role === 'root') {
            //     return Limit::none();
            // }
            // return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
            return Limit::none();
        });
    }
}
