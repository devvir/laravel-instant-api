<?php

namespace Devvir\InstantApi\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiResourceController extends ResourceController
{
    protected function executeAction(string $action, FormRequest $request, Model|string $model): JsonResponse
    {
        $resource = class_basename($model);

        return match($action) {
            'index'   => $this->render($model::paginate()),
            'show'    => $this->render($model),
            'store'   => $this->render($model::create($request->validated()), Response::HTTP_CREATED),
            'update'  => $this->render($model->updateOrFail($request->validated())),
            'destroy' => $this->render($model->deleteOrFail(), Response::HTTP_NO_CONTENT),

            default => throw new NotFoundHttpException("Invalid action $action on resource $resource"),
        };
    }

    /**
     * Render the JSON response for the current request.
     *
     * @param array $data   Optional
     * @param int   $status Optional
     */
    protected function render(...$args): JsonResponse
    {
        [$data, $status] = $args + [[], Response::HTTP_OK];

        return new JsonResponse($data, $status);
    }
}
