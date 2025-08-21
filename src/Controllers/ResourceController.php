<?php

namespace Devvir\InstantApi\Controllers;

use Devvir\InstantApi\Resolver;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Response;

/**
 * TODO : features for flexibility
 *
 *  ~ Middleware:
 *      ~ $api->middleware(<string|array middleware>)->create()
 *      ~ $api->create(['middleware' => <string|array middleware>, ...])
 *      ~ Config: ['middlware' => <string|array middlware>, 'resources' => [...]]
 *      ~ Config: [User::class => ['middleware' => <string|array middleware>], ...]
 *      ~ Config: [User::class => ['destroy' => ['middleware' => <string|array middleware>], ...]]
 *  ~ Policies
 *      ~ Automatic (e.g. if UserPolicy exists, apply to User API endpoints)
 *      ~ $api->policy(UserPolicy::class)->create()
 *      ~ $api->create(['policy' => UserPolicy::class, ...])
 *      ~ Config: [User::class => ['policy' => UserPolicy::class], ...]
 *      ~ Config: [User::class => ['destroy' => ['policy' => UserPolicy::class], ...]]
 *  ~ Scopes
 *      ~ Config: [User::class => ['scope' => 'admin', ...]]
 *      ~ Config: [User::class => ['scopes' => ['admin', 'active'], ...]]
 *      ~ Config: [User::class => ['index' => ['scope' => 'active', ...], ...]
 *      ~ Config: [User::class => ['index' => ['scopes' => ['active', 'admin'], ...]]
 *  ~ Events
 *      ~ Config: [User::class => ['destroy' => ['events' => UserDeletedEvent::class, ...]]]
 *  ~ Relations
 *      ~ Config: [User::class => ['with' => <string|array relations>, ...]]
 *      ~ Config: [User::class => ['show' => ['with' => <string|array relations>, ...], ...]]
 *  ~ Validation
 *      ~ Automatic if FormRequest class exists? (in App\Http\Requests\InstantApi?)
 *      ~ Config: [User::class => ['validation' => 'path/to/requests/folder']]
 *      ~ Config: [User::class => ['validation' => 'path/to/requests/folder']]
 *      ~ Config: [User::class => ['store' => ['validation' => UserStoreRequest::class]]]
 *  ~ Resources
 *      ~ Automatic if the corresponding resource exists in \App\Http\Resources
 *          ~ To "turn it off":
 *              ~ $api->withoutJsonResources()->create()
 *              ~ Config: ['jsonResources' => false]
 *      ~ Config: ['jsonResources' => 'custom/path/to/resources', 'resources' => ]
 *      ~ Config: [User::class => ['jsonResource' => UserResourceWithCustomPath::class]]
 *
 *  ~ Only / Except
 *      ~ $api->only('index', 'show')->create()
 *      ~ $api->only(['index', 'show'])->create()
 *      ~ Config: [User::class => ['only' => ['index', 'show']]]
 *      ~ All the same for `except`
 *
 *  ~ Hooks
 *      ~ Config: [User::class => ['before' => fn (Request $request, ?User $user) => ...]]
 *      ~ Config: [User::class => ['after' => fn (Request $request, ?User $user) => ...]]
 *      ~ Config: [User::class => ['index' => ['before' => fn (Request $request, ?User $user) => ...]]]
 *      ~ Config: [User::class => ['index' => ['after' => fn (Request $request, ?User $user) => ...]]]
 *      ~ If the function returns non-null, use that as the response, otherwise continue
 *      ~ This can be used to either replace the endpoint, or to change it (i.e. add relations, filters, checks, events, throw, etc.)
 */
abstract class ResourceController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected Resolver $resolver)
    {
        if (! Route::currentRouteAction()) {
            return;
        }

        $this->resolver->hydrate();

        $this->handleAuthorization();
    }

    /**
     * Resolve and apply policies and auth middleware according to the current configuration.
     */
    private function handleAuthorization(): void
    {
        $inlinePolicy  = $this->resolver->policy();
        $routeResource = $this->resolver->route->model;

        isset($inlinePolicy)
            ? Gate::allowIf($inlinePolicy)
            : $this->authorizeResource($routeResource->class, $routeResource->param);
    }

    /**
     * Execute an action on the controller.
     *
     * @override
     */
    public function callAction($action, $parameters): JsonResponse | View | Response | RedirectResponse
    {
        $parameters = $this->resolver->parameters($action);

        return $this->executeAction($action, ...$parameters);
    }

    /**
     * Rendering handler to be implemented on each concrete API.
     *
     * @return JsonResponse | View | Response
     */
    abstract protected function render(...$args);
}
