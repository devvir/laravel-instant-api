<?php

namespace Devvir\InstantApi\Providers;

use Devvir\InstantApi\Console\Discover;
use Devvir\InstantApi\InstantApi;
use Devvir\InstantApi\Resolver;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class InstantApiServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $resolver = app(Resolver::class);

        app()->singleton(Resolver::class, fn () => $resolver);

        $this->registerMacros();
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

    protected function registerMacros(): void
    {
        // TODO : make the macro'd methods' names configurable
        Router::macro('api', function (?array $config = null, string $type = InstantApi::TYPE_API) {
            createInstantApi($config, $type);
        });

        Router::macro('devApi', function () {
            if (app()->environment('production')) {
                return;
            }

            Route::name('dev.')->prefix('dev')->group(fn () => Route::api([
                'public'    => true,
                'resources' => InstantApi::discover(),
            ]));
        });

        Router::macro(
            'restApi',
            fn (?array $config = null) => Route::api($config)
        );

        Router::macro(
            'inertiaApi',
            fn (?array $config = null) => Route::api($config, InstantApi::TYPE_INERTIA)
        );

        Router::macro(
            'bladeApi',
            fn (?array $config = null, ) => Route::api($config, InstantApi::TYPE_BLADE)
        );
    }
}
