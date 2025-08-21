<?php

namespace Devvir\InstantApi;

use BadMethodCallException;
use Devvir\InstantApi\Config\Resource;
use Devvir\InstantApi\Controllers\ApiResourceController;
use Devvir\InstantApi\Controllers\BladeResourceController;
use Devvir\InstantApi\Controllers\InertiaResourceController;
use Illuminate\Routing\PendingResourceRegistration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class InstantApi
{
    public const TYPE_API     = 'api';
    public const TYPE_BLADE   = 'blade';
    public const TYPE_INERTIA = 'inertia';

    public function __construct(protected Resolver $resolver) {}

    public function create(string $apiType = self::TYPE_API): Collection
    {
        $resourcesConfig = collect($this->resolver->getConfig());

        return $resourcesConfig->map(
            fn (Resource $resourceConfig) => $this->registerRoutes($apiType, $resourceConfig)
        );
    }

    /**
     * Discover all existing Eloquent Models (resources).
     */
    public static function discover(?string $path = null): array
    {
        $modelPaths = File::glob($path ?? app_path("Models/*.php"));

        foreach ($modelPaths as $modelPath) {
            $pattern  = '#^' . preg_quote(app_path(), '#') . '/?(.+?)\.php#';
            $subPath  = preg_replace($pattern, '\1', $modelPath);
            $models[] = app()->getNamespace() . str_replace('/', '\\', $subPath);
        }

        return $models ?? [];
    }

    private function registerRoutes(string $type, Resource $config): PendingResourceRegistration
    {
        return match ($type) {
            self::TYPE_API     => Route::apiResource($config->name, ApiResourceController::class),
            self::TYPE_BLADE   => Route::resource($config->name, BladeResourceController::class),
            self::TYPE_INERTIA => Route::resource($config->name, InertiaResourceController::class),

            default => throw new BadMethodCallException("Invalid API type `$type`"),
        };
    }
}
