<?php

namespace Devvir\InstantApi;

use Devvir\InstantApi\Config\Resource;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class InstantApi
{
    public function __construct(protected Resolver $resolver) {}

    public function create(): void
    {
        collect($this->resolver->getConfig())->each($this->registerRoutes(...));
    }

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

    private function registerRoutes(Resource $config): void
    {
        Route::apiResource($config->name, ResourceController::class);
    }
}
