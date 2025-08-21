<?php

namespace Devvir\InstantApi\Controllers;

use Inertia\Response;

class InertiaResourceController extends WebResourceController
{
    /**
     * Handle "read-only methods" (index, show, create, edit).
     *
     * @throws InvalidArgumentException if the view does not exist.
     */
    protected function render(...$args): Response
    {
        $route = $this->resolver->route;

        $data = $args[0] ?? [];
        $page = ucfirst($route->base) . '/' . ucfirst($route->action);

        return inertia($page, $data);
    }
}
