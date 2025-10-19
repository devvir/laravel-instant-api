<?php

namespace Devvir\InstantApi\Config;

use Illuminate\Support\Arr;
use LogicException;

class Config
{
    /**
     * List of Resource Configuration objects.
     *
     * @var array<int, Resource> $resources
     */
    private array $resources;

    private object $config;

    public function __construct(?array $config = null)
    {
        $this->addConfig($config ?? config('instantapi', []));
    }

    public function addConfig(array $config): void
    {
        $resources = $config['resources'] ?? $config;
        $defaults  = isset($config['resources']) ? $config : [];

        unset($defaults['resources']);

        foreach ($resources as $model => $specs) {
            is_string($specs) && [$model, $specs] = [$specs, []];

            // TODO : should we be able to merge configs for the same resource?
            $this->resources[$model] = new Resource($model, $specs, $defaults);
        }
    }

    /**
     * Get the full list of managed Resources' configurations.
     *
     * @return array<int, Resource>
     */
    public function get(): array
    {
        return $this->resources;
    }

    /**
     * Get the configuration for a given Resource.
     */
    public function __invoke(string $resource): ?Resource
    {
        return $this->resources[$resource] ?? null;
    }

    /**
     * Find the corresponding Resource config for the currently executed Route.
     *
     * @throws LogicException if no Resource config is found.
     */
    public function findByRouteName(string $routeName): Resource
    {
        $routeBase = last(explode('.', $routeName, -1));

        return Arr::first(
            $this->resources,
            fn ($cfg) => $routeBase === $cfg->name,
            // TODO : allow "unconfigured" endpoints by providing a default Config when missing
            fn () => throw new LogicException('Cannot resolve the Resource for current route')
        );
    }
}
