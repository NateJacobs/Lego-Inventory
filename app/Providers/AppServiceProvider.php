<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Throttle BrickLink price lookups. Each RefreshCatalogItemPrice job
        // makes two API calls (new + used), so 30 jobs/minute is ~1 call/sec
        // and 2,000 jobs/day is ~4,000 calls — comfortably under BrickLink's
        // ~5,000/day cap. A larger collection simply spreads across days.
        RateLimiter::for('bricklink', fn () => [
            Limit::perMinute(30),
            Limit::perDay(2000),
        ]);
    }
}
