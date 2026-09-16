# Inertia.js adapter for Yii3

[![CI](https://github.com/crenspire/yii3-inertia/actions/workflows/ci.yml/badge.svg)](https://github.com/crenspire/yii3-inertia/actions/workflows/ci.yml)

A server-side [Inertia.js](https://inertiajs.com) adapter for [Yii3](https://www.yiiframework.com) and any other
PSR-7/PSR-15 application. Build single-page apps with React, Vue or Svelte while keeping routing, controllers and
validation in PHP.

- Implements the Inertia.js 3 protocol, and works with Inertia.js 1 and 2 clients through `legacyBody()`
- Partial reloads, and deferred, optional, merge, once and infinite scroll props
- Validation errors, error bags, flash data and history encryption
- Asset versioning from the Vite manifest, plus a Vite tag helper for dev and production
- Optional server-side rendering
- Stateless services that are safe for RoadRunner, Swoole and FrankenPHP workers
- Zero-config setup in Yii3 through `yiisoft/config`

Upgrading from 1.x? See [UPGRADE.md](UPGRADE.md).

## Requirements

- PHP 8.2 or later
- A PSR-17 HTTP factory implementation, such as `nyholm/psr7` or `httpsoft/http-message`

## Installation

```bash
composer require crenspire/yii3-inertia
```

## Yii3 setup

The package ships `params.php` and `di-web.php` for `yiisoft/config`, so `Inertia`, `InertiaMiddleware`, the Vite
helper, the asset version and the session flash store are registered automatically.

### 1. Add the middleware

In `config/web/di/application.php`, add `InertiaMiddleware` before `Router`. If you use `yiisoft/csrf`, also add
`XsrfTokenMiddleware` between `SessionMiddleware` and `CsrfTokenMiddleware`:

```php
'withMiddlewares()' => [
    [
        ErrorCatcher::class,
        SessionMiddleware::class,
        \Crenspire\Inertia\Middleware\XsrfTokenMiddleware::class,
        CsrfTokenMiddleware::class,
        RequestCatcherMiddleware::class,
        \Crenspire\Inertia\Middleware\InertiaMiddleware::class,
        Router::class,
    ],
],
```

`XsrfTokenMiddleware` sets the `XSRF-TOKEN` cookie that the Inertia client sends back as `X-XSRF-TOKEN`, and copies
that header to the one `CsrfTokenMiddleware` checks.

### 2. Create the root view

Copy [`stubs/inertia.php`](stubs/inertia.php) to `resources/views/inertia.php`:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title data-inertia>Application</title>
    <?= $vite->reactRefresh() ?>
    <?= $vite->tags('resources/js/app.jsx') ?>
    <?= $inertia->head() ?>
</head>
<body>
    <?= $inertia->body() ?>
</body>
</html>
```

### 3. Render pages from actions

```php
use Crenspire\Inertia\Inertia;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class IndexAction
{
    public function __construct(
        private Inertia $inertia,
        private UserRepository $users,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->inertia->render($request, 'Users/Index', [
            'users' => fn () => $this->users->findAll(),
        ]);
    }
}
```

### 4. Set up the frontend

```bash
npm install @inertiajs/react react react-dom
npm install -D vite @vitejs/plugin-react
```

`vite.config.js`:

```js
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig(({ command }) => ({
  base: command === 'build' ? '/build/' : '/',
  plugins: [react()],
  publicDir: false,
  build: {
    outDir: 'public/build',
    manifest: true,
    rollupOptions: { input: 'resources/js/app.jsx' },
  },
}))
```

`resources/js/app.jsx`:

```jsx
import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'

createInertiaApp({
  resolve: (name) => {
    const pages = import.meta.glob('./Pages/**/*.jsx')
    return pages[`./Pages/${name}.jsx`]()
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />)
  },
})
```

Run `npm run build` for production. During development, run `npx vite` and set `vite.devServerUrl` in your params
(see below).

[`examples/yii3`](examples/yii3) contains a complete set of files for the `yiisoft/app` template.

### Configuration

Override any of these values in your application's `config/web/params.php`:

```php
return [
    'crenspire/yii3-inertia' => [
        'rootView' => '@root/resources/views/inertia.php',
        // Extra root view variables. A Closure value is called with the DI container.
        'viewParameters' => [],
        // A string, a callable, or null to hash the Vite manifest.
        'version' => null,
        'manifestPath' => null,
        'sharedProps' => [
            'appName' => 'My App',
        ],
        'encryptHistory' => false,
        'allErrors' => false,
        'vite' => [
            'publicPath' => '@public',
            'buildDirectory' => 'build',
            'manifest' => '.vite/manifest.json',
            'devServerUrl' => $_ENV['VITE_DEV_SERVER_URL'] ?? null,
            'hotFile' => 'hot',
            'baseUrl' => '@baseUrl',
        ],
        'ssr' => [
            'enabled' => false,
            'url' => 'http://127.0.0.1:13714/render',
            'except' => [],
            'throwOnError' => false,
        ],
    ],
];
```

## Usage without Yii3

Every service is a plain PSR component:

```php
use Crenspire\Inertia\Inertia;
use Crenspire\Inertia\Middleware\InertiaMiddleware;
use Crenspire\Inertia\Version\ManifestVersion;
use Crenspire\Inertia\View\PhpRootViewRenderer;
use Crenspire\Inertia\Vite\Vite;

$vite = new Vite(publicPath: __DIR__ . '/public');

$inertia = new Inertia(
    responseFactory: $psr17Factory,
    streamFactory: $psr17Factory,
    rootViewRenderer: new PhpRootViewRenderer(__DIR__ . '/resources/views/inertia.php', ['vite' => $vite]),
    version: new ManifestVersion($vite->getManifestPath()),
);

$middleware = new InertiaMiddleware($inertia, $psr17Factory);
```

[`examples/psr15`](examples/psr15) is a runnable application built this way.

## Responses

```php
// JSON for Inertia visits, the root view for the first visit.
return $inertia->render($request, 'Users/Show', ['user' => $user]);

// Redirects. After PUT, PATCH and DELETE the middleware turns 302 into 303.
return $inertia->redirect('/users');
return $inertia->back($request);

// Full page visit, for example to an external site or a non-Inertia page.
return $inertia->location($request, 'https://github.com/login');
```

To render the page object yourself, use `$inertia->createPage($request, $component, $props)`.

## Props

Props can be plain values, closures, `JsonSerializable` or `Traversable` objects, and the prop types below. Closures
are only called when the prop is sent, so wrap expensive values in `fn () => ...`. Dot-notation keys such as
`'user.name'` are expanded into nested arrays.

```php
return $inertia->render($request, 'Dashboard', [
    // Resolved on every visit that includes the prop.
    'stats' => fn () => $this->stats->summary(),

    // Loaded in a separate request right after the page renders. Props in the same group load together.
    'activity' => Inertia::defer(fn () => $this->activity->latest(), group: 'sidebar'),

    // Only resolved when a partial reload asks for it: router.reload({ only: ['report'] }).
    'report' => Inertia::optional(fn () => $this->reports->build()),

    // Sent even when a partial reload did not ask for it.
    'auth' => Inertia::always(fn () => $this->currentUser->toArray()),

    // Merged into the client's current value on partial reloads.
    'notifications' => Inertia::merge(fn () => $this->notifications->page($page)),
    'settings' => Inertia::deepMerge($settings),

    // Resolved once and remembered by the client, optionally until it expires.
    'countries' => Inertia::once(fn () => $this->countries->all())->until(3600),
]);
```

Merge props support `prepend()`, merging at nested paths with `append('data')`, and matching items with
`matchOn('id')`. Deferred props can also be merged and remembered: `Inertia::defer(...)->merge()->once()`. Pass
`rescue: true` to `defer()` to log a failing prop and report it to the client instead of failing the request.

### Infinite scroll

`Inertia::scroll()` works with the client's `<InfiniteScroll>` component. It reads pagination metadata from
`yiisoft/data` paginators:

```php
$paginator = (new OffsetPaginator($reader))
    ->withPageSize(20)
    ->withCurrentPage((int) ($request->getQueryParams()['page'] ?? 1));

return $inertia->render($request, 'Posts/Index', [
    'posts' => Inertia::scroll($paginator),
]);
```

For other data sources, pass the items and a metadata callback or a `ProvidesScrollMetadata` object:

```php
'posts' => Inertia::scroll(
    ['data' => $items],
    metadata: fn () => new ScrollMetadata('page', previousPage: $page - 1 ?: null, nextPage: $page + 1, currentPage: $page),
),
```

### Prop providers

Implement `ProvidesInertiaProperty` to let an object resolve its own value, or `ProvidesInertiaProperties` to
contribute several props at once. Pass the latter under a numeric key: `render($request, 'Page', [$provider])`.

## Shared props

Props shared with every page come from three places, in this order:

1. The `errors` prop, which is always present.
2. `sharedProps` in the params, or `$inertia->withSharedProps([...])`.
3. Props shared for the current request, typically from a middleware:

```php
final class ShareAuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly CurrentUser $user)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = Inertia::share($request, 'auth', fn () => [
            'userId' => $this->user->getId(),
        ]);

        return $handler->handle($request);
    }
}
```

Request data lives in request attributes, never in static state, so nothing leaks between requests in workers.

## Validation errors

Validation errors are shared as the `errors` prop, which `useForm()` picks up. To show them after a redirect, flash
them with `InertiaFlash`, which uses `yiisoft/session` in Yii3:

```php
public function __invoke(ServerRequestInterface $request): ResponseInterface
{
    $result = $this->validator->validate($data, $rules);

    if (!$result->isValid()) {
        $this->flash->errors($result->getErrorMessagesIndexedByProperty());

        return $this->inertia->back($request);
    }

    // ...
}
```

To render the page in the same request instead, use
`Inertia::withErrors($request, $errors)`. Only the first message per field is sent unless `allErrors` is enabled.
When a visit sets the `errorBag` option, the errors are nested under that name. For more than
one form, pass the bag name as the second argument: `$flash->errors($errors, 'login')`.

## Flash data and history

```php
$this->flash->flash('message', 'Profile updated.'); // page.flash.message on the next render
$this->flash->clearHistory();                        // clear encrypted history, e.g. after logout
$this->flash->preserveFragment();                    // keep the URL fragment across the next redirect

$request = Inertia::encryptHistory($request);        // encrypt this page's history state
$request = Inertia::clearHistory($request);
```

Without `yiisoft/session`, pass your own `FlashStoreInterface` implementation to `Inertia`.

## Asset versioning

When assets change, the middleware answers Inertia GET visits carrying an old version with `409 Conflict`, and the
client reloads the page. By default the version is a hash of the Vite manifest; it is recomputed only when the file
changes. Use `version` in the params, or `StaticVersion`, `CallbackVersion`, or your own
`VersionProviderInterface`.

## Vite

`Vite` renders the tags for your entry points:

- In production it reads the manifest and outputs stylesheets, module preloads and scripts.
- When `devServerUrl` is set, or the `hot` file exists in the web root, it points to the Vite dev server.

```php
<?= $vite->reactRefresh() ?>  <!-- only needed with @vitejs/plugin-react -->
<?= $vite->tags(['resources/js/app.jsx', 'resources/css/app.css']) ?>
<img src="<?= $vite->asset('resources/images/logo.svg') ?>">
```

## Root view

`PhpRootViewRenderer` renders a PHP template with these variables:

| Variable     | Description                                                              |
|--------------|--------------------------------------------------------------------------|
| `$inertia`   | `InertiaView`: `body()`, `head()`, `legacyBody()`, `pageJson()`, `page` |
| `$request`   | The current `ServerRequestInterface`                                     |
| `$vite`      | The `Vite` helper (Yii3 config only)                                     |
| custom       | Entries from `viewParameters`                                            |

Head elements with the `data-inertia` attribute are managed by the client: they are replaced by the elements of
the page's `<Head>` component, and removed on the first render if the page has none. Drop the attribute from
elements, such as `<title>`, that should stay when you do not use `<Head>`.

`body()` outputs the `<script type="application/json">` element and the `<div id="app">` root that Inertia.js 3
expects. For Inertia.js 1 or 2 clients, use `legacyBody()`, which puts the page in a `data-page` attribute.

To use another template engine, implement `RootViewRendererInterface`.

## Server-side rendering

Build your SSR bundle as described in the [Inertia.js docs](https://inertiajs.com/docs/v3/advanced/server-side-rendering),
start it with `node bootstrap/ssr/ssr.mjs`, and enable SSR:

```php
'ssr' => [
    'enabled' => true,
    'url' => 'http://127.0.0.1:13714/render',
],
```

This needs a PSR-18 `ClientInterface` and a PSR-17 `RequestFactoryInterface` in the container. `$inertia->head()`
and `$inertia->body()` then output the server-rendered HTML. If the SSR server is unavailable, the failure is logged
and the page is rendered on the client, unless `throwOnError` is enabled.

## Testing

```bash
composer install
composer test
composer analyse
```

## License

MIT. See [LICENSE](LICENSE).
