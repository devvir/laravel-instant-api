<?php

namespace Devvir\InstantApi\Config;

use Closure;
use Illuminate\Support\Str;
use stdClass;

class Resource
{
    protected object $config;

    public function __construct(
        public readonly string $model,
        array $config,
        array $defaults,
    ) {
        $this->storeConfig($config, $defaults);
    }

    public function actions()
    {

    }

    public function middleware()
    {

    }

    public function policy(string $action): Closure | bool | null
    {
        $policy = $this->config->policies[$action] ?? null;

        return match (true) {
            is_callable($policy) => $policy,
            is_null($policy)     => $policy,
            default              => (bool) $policy,
        };
    }

    public function policies()
    {

    }

    public function __get(string $attribute): mixed
    {
        return $this->config->$attribute ?? null;
    }

    private function storeConfig(array $specs): void
    {
        $this->config = new stdClass;
        $this->config->name = $specs['name'] ?? Str::plural(strtolower(class_basename($this->model)));
        $this->config->policies = $specs['policies'] ?? null;

        // TODO : add/normalize middleware, policies, only/except, requests, etc.
    }
}
