<?php

use Devvir\InstantApi\InstantApi;
use Devvir\InstantApi\Resolver;

function createInstantApi(?array $config = null, string $type = InstantApi::TYPE_API): void
{
    $config ??= config('instantApi', InstantApi::discover());

    app(Resolver::class)->addConfig($config);

    app(InstantApi::class)->create($type);
}
