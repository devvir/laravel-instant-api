<?php

namespace Devvir\InstantApi\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class WebResourceController extends ResourceController
{
    protected function executeAction(string $action, FormRequest $request, Model|string $model): View | Response | RedirectResponse
    {
        $resource = class_basename($model);
        $singular = strtolower($resource);
        $plural   = Str::plural($singular);

        return match($action) {
            'index'   => $this->render([ $plural => $model::paginate() ]),
            'show'    => $this->render([ $singular => $model ]),
            'create'  => $this->render(),
            'edit'    => $this->render([ $singular => $model ]),

            'store', 'update', 'destroy' => $this->redirect($action, $request, $model),

            default => throw new NotFoundHttpException("Invalid action $action on resource $resource"),
        };
    }

    /**
     * Handle 'redirect methods' (those with side effects: store, update, destroy).
     */
    protected function redirect(string $action, FormRequest $request, string|Model $model): RedirectResponse
    {
        $resource = class_basename($model);
        $routeBase = $this->resolver->route->prefix;

        [$instance, $message] = match ($action) {
            'store'   => [$model::create($request->validated()), "The $resource was created!"],
            'update'  => [$model, "The $resource updated!", $model->updateOrFail($request->validated())],
            'destroy' => [$model, "The $resource was deleted!", $model->deleteOrFail()],
        };

        $route = ($action === 'destroy') ? route("$routeBase.index") : route("$routeBase.show", $instance->getKey());

        return redirect()->to($route)->with('success', $message);
    }
}
