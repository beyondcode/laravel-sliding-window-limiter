<?php

namespace BeyondCode\SlidingWindowLimiter;

use Illuminate\Support\ServiceProvider;

class SlidingWindowLimiterServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/sliding-limiter.php' => config_path('sliding-limiter.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sliding-limiter.php', 'sliding-limiter');
    }
}
