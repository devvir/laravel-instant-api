<?php

use Devvir\InstantApi\InstantApi;
use Devvir\InstantApi\Resolver;

function createInstantApi(?array $config = null, string $type = InstantApi::TYPE_API): void
{
    // TODO : move to app provider
    $resolver = app(Resolver::class);
    app()->singleton(Resolver::class, fn () => $resolver);

    app(Resolver::class)->addConfig($config);

    app(InstantApi::class)->create($type);
}
