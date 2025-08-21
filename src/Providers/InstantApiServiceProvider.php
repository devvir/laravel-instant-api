<?php

namespace Devvir\InstantApi\Providers;

use Devvir\InstantApi\Console\Discover;
use Illuminate\Support\ServiceProvider;

class InstantApiServiceProvider extends ServiceProvider
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
        if ($this->app->runningInConsole()) {
            $this->commands([
                Discover::class,
                // Create::class,
            ]);
        }
    }
}
