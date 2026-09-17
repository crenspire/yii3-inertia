# Shared data

Shared props are added to every page, for example the application name, the current user or flash messages.
There are three places to define them.

## Application-wide data

Set values that are the same for every request in the params:

```php
// config/web/params.php
return [
    'crenspire/yii3-inertia' => [
        'sharedProps' => [
            'appName' => 'My App',
            'locales' => ['en', 'de'],
        ],
    ],
];
```

Values can be closures and [prop types](./props). Closures run only when a page is rendered:

```php
'sharedProps' => [
    'version' => static fn (): string => App\Version::current(),
],
```

Outside of the params, create an instance with more shared props:

```php
$inertia = $inertia->withSharedProps(['appName' => 'My App']);
```

## Per-request data

Data that depends on the request, such as the current user, belongs in a middleware. `Inertia::share()` returns a
new request with the props attached; pass that request on:

```php
<?php

declare(strict_types=1);

namespace App\Web\Shared;

use Crenspire\Inertia\Inertia;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\User\CurrentUser;

final readonly class ShareInertiaData implements MiddlewareInterface
{
    public function __construct(
        private CurrentUser $currentUser,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = Inertia::share($request, 'auth', fn (): array => [
            'userId' => $this->currentUser->getId(),
            'isGuest' => $this->currentUser->isGuest(),
        ]);

        return $handler->handle($request);
    }
}
```

Register it after authentication and before the router:

```php
InertiaMiddleware::class,
ShareInertiaData::class,
Router::class,
```

`share()` accepts a key and a value, an array of props, or a
[`ProvidesInertiaProperties`](./props#prop-providers) object:

```php
$request = Inertia::share($request, ['locale' => 'en', 'timezone' => 'UTC']);
```

::: warning Don't store request data in services
Keep per-request data in request attributes, as `share()` does. Services are shared between requests, and in
[long-running workers](./workers) data stored on them would leak to other users.
:::

## Order and precedence

Props are merged in this order, with later values winning:

1. The `errors` prop, which is always present
2. `sharedProps` from the params or `withSharedProps()`
3. Props added with `Inertia::share($request, ...)`
4. The props passed to `render()`

The keys of shared props are listed in the page's `sharedProps` metadata.

## Using shared data on the client

Shared props are ordinary page props. Read them from any component:

::: code-group

```jsx [React]
import { usePage } from '@inertiajs/react'

export default function Header() {
  const { appName, auth } = usePage().props

  return <header>{appName} {auth.isGuest ? 'Guest' : `User #${auth.userId}`}</header>
}
```

```vue [Vue]
<script setup>
import { usePage } from '@inertiajs/vue3'

const page = usePage()
</script>

<template>
  <header>{{ page.props.appName }}</header>
</template>
```

:::
