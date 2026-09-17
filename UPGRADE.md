# Upgrading from 1.x to 2.0

Version 2.0 is a rewrite. The 1.x API relied on static state, which leaks data between requests in long-running
workers, and several 1.x features did not work. There is no compatibility layer, so plan to update every place that
renders Inertia pages.

## Requirements

- PHP 8.2 or later (was 8.1).
- The package no longer requires `yiisoft/di` or `yiisoft/config`. It only needs PSR interfaces; Yii packages are
  optional.
- Client: the default root view targets Inertia.js 3 (`@inertiajs/react@^3`, `@inertiajs/vue3@^3` or
  `@inertiajs/svelte@^3`). Clients on 1.x or 2.x can keep working by using `$inertia->legacyBody()` in the root view.

## Removed classes

| 1.x                                                  | 2.0                                                                  |
|------------------------------------------------------|----------------------------------------------------------------------|
| Static `Inertia::render()` returning an array        | `$inertia->render($request, $component, $props)` returning a response |
| `ResponseFactory` (`json()`, `html()`)               | `Inertia::render()` picks the format; `Inertia::createPage()` for the raw page |
| `Inertia::setRequest()` / `getRequest()`             | Pass the request to `render()`                                       |
| `Inertia::share($key, $value)`                       | `sharedProps` param, `$inertia->withSharedProps()`, or `Inertia::share($request, $key, $value)` |
| `Inertia::version($value)`                           | `version` param or a `VersionProviderInterface` implementation      |
| `Inertia::setRootView()` / `getRootView()`           | `rootView` param or `PhpRootViewRenderer`                           |
| `Inertia::location()` returning an array             | `$inertia->location($request, $url)` returning a response           |
| `Inertia::flushShared()`                             | Not needed; nothing is stored statically                           |
| `ControllerTrait`, `Action\InertiaAction`            | Inject `Inertia` and call `render()`                               |
| `ConfigProvider`                                     | Automatic through `yiisoft/config` (`config/params.php`, `config/di-web.php`) |
| `AssetConfig`                                        | `vite` params and `Vite\Vite`                                      |
| `ViewRenderer`                                       | `View\PhpRootViewRenderer` or your own `RootViewRendererInterface` |
| `Bootstrap`, `Middleware\InertiaMiddlewareConfig`    | Params and middleware configuration                                  |
| `Crenspire\Inertia\inertia()` function               | `$inertia->render()`                                               |
| `config/inertia.php`, `config/inertia-web.php`       | `crenspire/yii3-inertia` params                                    |
| `stubs/index.php`                                    | `stubs/inertia.php`                                                 |

## Step by step

### 1. Configuration

Remove `ConfigProvider` from your configuration. With `yiisoft/config`, the package registers its services
automatically. Move settings into `config/web/params.php`:

```php
return [
    'crenspire/yii3-inertia' => [
        'rootView' => '@root/resources/views/inertia.php',
        'sharedProps' => ['appName' => 'My App'],
    ],
];
```

`InertiaMiddleware` now takes `Inertia` and a PSR-17 response factory. Keep it in the middleware stack before
`Router`, and add `XsrfTokenMiddleware` if you use `yiisoft/csrf` (see [CSRF protection](https://crenspire.github.io/yii3-inertia/guide/csrf)).

### 2. Actions and controllers

Before:

```php
class HomeController
{
    use ControllerTrait;

    public function __construct(private ResponseFactory $responseFactory) {}

    protected function getResponseFactory(): ResponseFactory
    {
        return $this->responseFactory;
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        return $this->inertiaRender('Home', ['title' => 'Welcome'], $request);
    }
}
```

After:

```php
final readonly class HomeAction
{
    public function __construct(private Inertia $inertia) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->inertia->render($request, 'Home', ['title' => 'Welcome']);
    }
}
```

`InertiaAction` helpers map to PSR-7 calls: `getQueryParam()` to `$request->getQueryParams()`, `redirect()` to
`$inertia->redirect()` or `$inertia->location()`.

### 3. Shared props

Replace application-wide `Inertia::share()` calls with the `sharedProps` param. Replace per-user values, such as the
current user, with a middleware that calls `Inertia::share($request, ...)` and passes the returned request on.

### 4. Root view

1.x passed the page as an HTML-escaped JSON string in `$page`, which the stub escaped a second time. Replace your
template with [`stubs/inertia.php`](https://github.com/crenspire/yii3-inertia/blob/develop/stubs/inertia.php) and output the page with `$inertia->body()`, or
`$inertia->legacyBody()` for Inertia.js 1 or 2 clients.

### 5. Redirects

1.x answered every redirect from `InertiaAction::redirect()` with `409 Conflict`, which forced a full page reload.
Use `$inertia->redirect()` or `$inertia->back()` for redirects inside the application, and `$inertia->location()`
only for external URLs.

### 6. Asset version

1.x returned `'1'` unless a version was set. 2.0 hashes the Vite manifest by default. Check that
`vite.publicPath`, `vite.buildDirectory` and `vite.manifest` point to your manifest, or set `version` explicitly.

## Behavior changes

- Inertia responses send `Vary: X-Inertia` (was `Vary: Accept`), so browsers do not show cached JSON after back
  navigation.
- Asset version mismatches return 409 only for GET requests.
- 302 redirects after PUT, PATCH and DELETE are converted to 303.
- Partial reloads only apply when `X-Inertia-Partial-Component` matches the rendered component, support
  `X-Inertia-Partial-Except`, and no longer always include shared props. Use `Inertia::always()` for props that must be
  included.
- Closures in page props are resolved lazily, so props that a partial reload skips are never computed.
- Empty props are sent as a JSON object, not an array.
- The `errors` prop is always present.
- JSON encoding errors are thrown instead of being turned into a generic 500 response.
