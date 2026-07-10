<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // The throttle runs before auth.api-token resolves the user, so key
        // by the raw bearer token (falling back to IP for anonymous probes).
        // 120/min leaves ample headroom for the 4-second lobby polling.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->bearerToken() ?: $request->ip());
        });
    }
}
