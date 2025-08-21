<?php

namespace Devvir\InstantApi\Controllers;

use Illuminate\Contracts\View\View;

class BladeResourceController extends WebResourceController
{
    /**
     * Render the Blade template for the current request.
     *
     * @param array $data   Optional
     */
    protected function render(...$args): View
    {
        $route = $this->resolver->route;

        $data = $args[0] ?? [];
        $view = $route->base . '.' . $route->action;

        return view($view, $data);
    }
}
