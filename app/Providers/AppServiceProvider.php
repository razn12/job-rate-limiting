<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
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
    public function boot(RateLimiter $rateLimiter): void
    {
        // Define the rate limiter for the batch API
        $rateLimiter->for('batch-api', function () {
            return Limit::perHour(50);
        });
        $rateLimiter->for('single-api', function () {
            return Limit::perHour(3600);
        });
    }
}
