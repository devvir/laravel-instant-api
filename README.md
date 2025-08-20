# Laravel Instant API

Building APIs, even with such great tools as Laravel, often results in tons of scaffolding and code duplication. Building an API with basic CRUD for as little as three Models will easily result in a dozen files: routes file with one or more entries per Resource, 3 Controllers, 6+ FormRequests (or worse, a lot of boilerplate in the Controllers), Policies, Json Resources, Middleware, ...

We end up reinventing the wheel and writing the same code over and over, just to get a standard, conventional CRUD. While SRP is great, things end up so spread out that it's hard to track what happens for each endpoint. Do I put the middleware in the route? In the `api` middleware group? In the Controller constructor? Flexibility is great, but it's not really helpful here. A conventional API should be simple!

We may eventually realize how little changes from Resource to Resource, so we try to build some abstract base Controller class to deal with the small differences... just to end with some Frankenstein that nobody wants to maintain. We've all seen it.

This library aims to resolve these issues by automating the most common use cases with a configuration-based solution that exposes the whole birds-eye view of your API in a single place, and removing code repetion and indirection. You're still free to handle edge cases, add special-purpose endpoints as you normally would, handle middleware, policies, json resources, events, etc., as you see fit. But for the usual tasks, the boring stuff, things "just work", with the minimum amount of code.

As long as you follow conventions, the right Policy will be applied, validation will just happen, and JSON Resources will be returned. If you prefer to be radical on cutting indirection (which, IMHO, is not a crime when dealing with repetitive conventional flows) you can simply embed some or all of these in the API spec, either on the call to `createInstantApi()` or in its own config file under `config/instantapi.php`.

TIP: This is a configuration-based API, but we still favor [convention over configuration](https://en.wikipedia.org/wiki/Convention_over_configuration). If your use case is not that specific, you may find that all you need is the classnames of your Models to get everything up and running.


# Installation

```bash
composer install devvir/laravel-instant-api
```


# Documentation

```TODO```

# Examples

## Basic
Full CRUD for 3 Models, with Policies, JsonResources and FormRequests following conventions, and no additional Middlware other than the standard `api` middleware group.

```php
# routes/api.php

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;

createInstantApi([
    User::class,
    Post::class,
    Comment::class,
]);
```

## With a few more features

- Centrally defined Middleware to limit access to some endpoints by the authenticated User's role
- Exclude default apiResource endpoints (for Comment register `only` 'index' and 'show')

```php

# routes/api.php

use App\Http\Middleware\Auth\MustBeAdmin;
use App\Http\Middleware\Auth\MustBeSuperAdmin;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

createInstantApi([
    'resources' => [
        User::class,
        Post::class,
        Comment::class,
    ],
    'middleware' => [
        MustBeAdmin::class => [
            User::class => ['store', 'update', 'destroy'],
            Post::class => ['index', 'update'],
        ],
        MustBeSuperAdmin::class => [
            Post::class => ['store', 'destroy'],
        ],
    ],
    'only' => [
        Comment::class => ['index', 'show'],
    ],
]);
```

## Same as previous example, with rules applied by resource instead of centrally

```php
createInstantApi([
    User::class => [
        'middleware' => [
            MustBeAdmin::class => ['store', 'update', 'destroy'],
        ],
    ],
    Post::class => [
        'middleware' => [
            MustBeAdmin::class => ['index', 'update'],
            MustBeSuperAdmin::class => ['store', 'destroy'],
        ]
    ],
    Comment::class => [
        'only' => ['index', 'show'],
    ],
]);
```
