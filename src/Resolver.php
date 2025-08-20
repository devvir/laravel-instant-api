<?php

namespace Devvir\InstantApi;

use Devvir\InstantApi\Config\Config;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\ResourceRegistrar;
use LogicException;

class Resolver
{
    /**
     * Relevant data extracted from the current Route (name, base, action, config, model).
     */
    public readonly object $route; // TODO : extract to DTOs to expose structure

    public function __construct(
        protected ResourceRegistrar $registrar,
        protected Request $request,
        protected Config $config,
    ) {}

    public function hydrate(): void
    {
        $this->fetchRouteData();
        $this->fetchModelData();
        $this->applyRouteModelBinding();
    }

    public function addConfig(array $config): self
    {
        $this->config->addConfig($config);

        return $this;
    }

    /**
     * Get the list of resource configs, indexed by the Model's FQNs.
     */
    public function getConfig(): array
    {
        return $this->config->get();
    }

    public function parameters(string $method): array
    {
        // TODO : switch for configured FormRequest if given
        $request = app(FormRequest::class);

        $model = $this->route->model;

        return match($method) {
            'index'   => [$request, $model->class],
            'show'    => [$request, $model->instance],
            'store'   => [$request, $model->class],
            'update'  => [$request, $model->instance],
            'destroy' => [$request, $model->class],
        };
    }

    public function __get(string $attribute): mixed
    {
        if (! $this->request->route()) {
            throw new LogicException('Access to route-specific attribute when not in a Request');
        }

        return $this->route->config->$attribute;
    }

    public function __call($name, $arguments): mixed
    {
        if (! $this->request->route()) {
            throw new LogicException('Access to route-specific attribute when not in a Request');
        }

        return match ($name) {
            'policy' => $this->route->config->$name($this->route->action, ...$arguments),
            default => throw new LogicException("Invalid Resolver pseudo-method `$name`"),
        };

        return $this->route->config->$name(...$arguments);
    }

    /**
     * Extract relevant route information for the current request.
     */
    private function fetchRouteData()
    {
        $name   = $this->request->route()->action['as'];
        $config = $this->config->findByRouteName($name);

        [$base, $action] = array_slice(explode('.', $name), -2);

        $this->route = (object) compact('name', 'base', 'action', 'config');
    }

    /**
     * Extract relevant model (resource) information for the current request.
     */
    private function fetchModelData()
    {
        $class = $this->route->config->model;
        $param = $this->registrar->getResourceWildcard(last(explode('.', $this->route->base)));
        $key   = $this->request->route()->parameters[$param] ?? null;

        $instance = $key ? $class::findOrFail($key) : null;

        $this->route->model = (object) compact('class', 'param', 'key', 'instance');
    }

    private function applyRouteModelBinding(): void
    {
        if ($model = $this->route->model->instance) {
            $this->request->route()->setParameter($this->route->model->param, $model);
        }
    }
}
