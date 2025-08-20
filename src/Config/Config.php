<?php

namespace Devvir\InstantApi\Config;

use Illuminate\Support\Str;
use LogicException;

class Config
{
    /**
     * List of Resource Configuration objects.
     *
     * @var ResourceConfig[] $resources
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
     * @return Resource[]
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

    public function findByRouteName(string $routeName)
    {
        $routeBase = last(explode('.', $routeName, -1));

        foreach ($this->resources as $model => $config) {
            $name = $specs->name ?? Str::plural(strtolower(class_basename($model)));

            if ($name === $routeBase) {
                return $config;
            }
        }

        throw new LogicException('Cannot resolve the Resource for current route');
    }
}
