<?php

namespace Devvir\InstantApi;

use Devvir\InstantApi\Config\Config;
use Illuminate\Contracts\Container\BindingResolutionException;
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

    /**
     * Fetch relevant properties of the current route and request parameters.
     */
    public function hydrate(): self
    {
        $this->fetchRouteData();
        $this->fetchModelData();
        $this->applyRouteModelBinding();

        return $this;
    }

    /**
     * Add configuration options (will be merged with any existing configuration).
     */
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
        $request = $this->resolveRequestObject($method);

        $model = $this->route->model;

        return match($method) {
            'index'       => [$request, $model->class],
            'show'        => [$request, $model->instance],
            'create'      => [$request, $model->class],
            'store'       => [$request, $model->class],
            'edit'        => [$request, $model->instance],
            'update'      => [$request, $model->instance],
            'destroy'     => [$request, $model->instance],
            'restore'     => [$request, $model->instance],
            'forceDelete' => [$request, $model->instance],
        };
    }

    /**
     * Simple proxied attribute reads from the Config.
     */
    public function __get(string $attribute): mixed
    {
        if (! $this->request->route()) {
            throw new LogicException('Access to route-specific attribute when not in a Request');
        }

        return $this->route->config->$attribute;
    }

    /**
     * Simple proxied calls to Config methods.
     */
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
        $prefix = join('.', array_slice(explode('.', $name), 0, -1));
        $config = $this->config->findByRouteName($name);

        [$base, $action] = array_slice(explode('.', $name), -2);

        $this->route = (object) compact('name', 'prefix', 'base', 'action', 'config');
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
        $basename = class_basename($class);

        $this->route->model = (object) compact('class', 'basename', 'param', 'key', 'instance');
    }

    /**
     * For actions with an associated resource instance, resolve the identifying
     * parameter to an instance of the resource (as in native route-model binding).
     */
    private function applyRouteModelBinding(): void
    {
        if ($model = $this->route->model->instance) {
            $this->request->route()->setParameter($this->route->model->param, $model);
        }
    }

    /**
     * Create an instance of the corresponding FormRequest object, if
     * it exists; otherwise, return a plain FormRequest object.
     */
    private function resolveRequestObject(string $method): ?FormRequest
    {
        $action = ucfirst($method);
        $model  = $this->route->model->basename;
        $class  = "App\\Http\\Requests\\{$action}{$model}Request";

        try {
            if (is_a($class, FormRequest::class, true)) {
                $request = app($class);
            }
        } catch (BindingResolutionException $_) {}

        return $request ?? app(FormRequest::class);
    }
}
