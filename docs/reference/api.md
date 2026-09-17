# API reference

All classes are in the `Crenspire\Inertia` namespace.

## Inertia

The main service. It keeps no per-request state.

```php
final class Inertia
```

### Constructor

```php
public function __construct(
    ResponseFactoryInterface $responseFactory,
    StreamFactoryInterface $streamFactory,
    View\RootViewRendererInterface $rootViewRenderer,
    Version\VersionProviderInterface $version = new Version\StaticVersion(),
    ?Flash\FlashStoreInterface $flashStore = null,
    ?Ssr\GatewayInterface $ssrGateway = null,
    ?LoggerInterface $logger = null,
    array $sharedProps = [],
    bool $encryptHistory = false,
    bool $allErrors = false,
)
```

### Rendering

| Method | Description |
|---|---|
| `render(ServerRequestInterface $request, string $component, array $props = []): ResponseInterface` | JSON for Inertia visits, the root view otherwise |
| `createPage(ServerRequestInterface $request, string $component, array $props = []): Page` | Resolves props and returns the page object |
| `getVersion(): string` | The current asset version |

`render()` and `createPage()` throw `InvalidArgumentException` when the component name is empty.

### Redirects

| Method | Description |
|---|---|
| `redirect(string\|UriInterface $url, int $status = 302): ResponseInterface` | Redirect with a `Location` header |
| `back(ServerRequestInterface $request, string $fallback = '/', int $status = 302): ResponseInterface` | Redirect to the `Referer` header, or to `$fallback` |
| `location(ServerRequestInterface $request, string\|UriInterface $url): ResponseInterface` | Full page visit: `409` with `X-Inertia-Location` for Inertia visits, `302` otherwise |

### Immutable configuration

Each method returns a new instance.

| Method | Description |
|---|---|
| `withSharedProps(array $props): self` | Adds props shared with every page |
| `withEncryptHistory(bool $encrypt = true): self` | Enables or disables history encryption |
| `withAllErrors(bool $allErrors = true): self` | Sends every validation message per field |
| `withUrlResolver(callable $resolver): self` | Sets `fn (ServerRequestInterface $request): string` that returns the page URL |

### Request helpers

Static methods that return a new request. Pass that request on to the next handler or to `render()`.

| Method | Description |
|---|---|
| `isInertiaRequest(ServerRequestInterface $request): bool` | Whether the request has the `X-Inertia` header |
| `share(ServerRequestInterface $request, string\|array\|ProvidesInertiaProperties $key, mixed $value = null): ServerRequestInterface` | Shares props for this request |
| `withErrors(ServerRequestInterface $request, array $errors, string $bag = 'default'): ServerRequestInterface` | Adds validation errors for this request |
| `encryptHistory(ServerRequestInterface $request, bool $encrypt = true): ServerRequestInterface` | Overrides history encryption for this request |
| `clearHistory(ServerRequestInterface $request): ServerRequestInterface` | Clears history on this render |

### Prop factories

| Method | Returns |
|---|---|
| `optional(callable $callback)` | `Prop\OptionalProp` |
| `defer(callable $callback, string $group = 'default', bool $rescue = false)` | `Prop\DeferProp` |
| `merge(mixed $value)` | `Prop\MergeProp` |
| `deepMerge(mixed $value)` | `Prop\MergeProp` |
| `always(mixed $value)` | `Prop\AlwaysProp` |
| `once(callable $callback)` | `Prop\OnceProp` |
| `scroll(mixed $value, string $wrapper = 'data', ProvidesScrollMetadata\|callable\|null $metadata = null, string $pageName = 'page')` | `Prop\ScrollProp` |

### Constants

| Constant | Value |
|---|---|
| `SHARED_ATTRIBUTE` | `inertia.shared` |
| `ERRORS_ATTRIBUTE` | `inertia.errors` |
| `ENCRYPT_HISTORY_ATTRIBUTE` | `inertia.encryptHistory` |
| `CLEAR_HISTORY_ATTRIBUTE` | `inertia.clearHistory` |

## Page

The resolved page object.

```php
final class Page implements JsonSerializable
{
    public readonly string $component;
    public readonly array $props;
    public readonly string $url;
    public readonly string $version;
    public readonly array $metadata;

    public function toArray(): array;
    public function jsonSerialize(): array;
}
```

`jsonSerialize()` encodes empty props as an object.

## Middleware

### Middleware\InertiaMiddleware

```php
public function __construct(Inertia $inertia, ResponseFactoryInterface $responseFactory)
```

PSR-15 middleware that applies the protocol rules. See [Protocol support](./protocol).

### Middleware\XsrfTokenMiddleware

```php
public function __construct(
    Yiisoft\Csrf\CsrfTokenInterface $token,
    string $csrfHeaderName = 'X-CSRF-Token',
    string $cookieName = 'XSRF-TOKEN',
    string $clientHeaderName = 'X-XSRF-TOKEN',
    string $cookiePath = '/',
    string $sameSite = 'Lax',
)
```

Requires `yiisoft/csrf`. See [CSRF protection](../guide/csrf).

## Props

All prop types are in the `Crenspire\Inertia\Prop` namespace.

| Class | Implements | Created by |
|---|---|---|
| `AlwaysProp` | | `Inertia::always()` |
| `OptionalProp` | `IgnoreFirstLoad`, `Onceable` | `Inertia::optional()` |
| `DeferProp` | `Deferrable`, `IgnoreFirstLoad`, `Mergeable`, `Onceable`, `Rescuable` | `Inertia::defer()` |
| `MergeProp` | `Mergeable`, `Onceable` | `Inertia::merge()`, `Inertia::deepMerge()` |
| `OnceProp` | `Onceable` | `Inertia::once()` |
| `ScrollProp` | `Deferrable`, `Mergeable` | `Inertia::scroll()` |

### Mergeable methods

Available on `DeferProp`, `MergeProp` and `ScrollProp`.

| Method | Description |
|---|---|
| `merge(): static` | Merge on partial reloads |
| `deepMerge(): static` | Merge nested objects recursively |
| `append(bool\|string\|array $path = true, ?string $matchOn = null): static` | Append at the root, at a path, or at several paths |
| `prepend(bool\|string\|array $path = true, ?string $matchOn = null): static` | Prepend at the root, at a path, or at several paths |
| `matchOn(string\|array $matchOn): static` | Keys used to match existing items |

### Onceable methods

Available on `OptionalProp`, `DeferProp`, `MergeProp` and `OnceProp`.

| Method | Description |
|---|---|
| `once(bool $value = true, BackedEnum\|UnitEnum\|string\|null $as = null, DateTimeInterface\|DateInterval\|int\|null $until = null): static` | Remember the value on the client |
| `as(BackedEnum\|UnitEnum\|string $key): static` | Share the remembered value under a custom key |
| `until(DateTimeInterface\|DateInterval\|int $delay): static` | Expire after seconds, an interval, or at a time |
| `fresh(bool $value = true): static` | Send the value even if the client has it |

### Deferrable methods

Available on `DeferProp` and `ScrollProp`.

| Method | Description |
|---|---|
| `defer(?string $group = null): static` | Load the prop after the page renders |

### Scroll metadata

```php
final class ScrollMetadata implements ProvidesScrollMetadata
{
    public function __construct(
        string $pageName,
        int|string|null $previousPage = null,
        int|string|null $nextPage = null,
        int|string|null $currentPage = null,
    );

    public static function fromPaginator(mixed $paginator, string $pageName = 'page'): self;
}
```

`fromPaginator()` accepts `yiisoft/data` paginators and throws `InvalidArgumentException` for other values.

### Prop providers

```php
interface ProvidesInertiaProperty
{
    public function toInertiaProperty(PropertyContext $context): mixed;
}

interface ProvidesInertiaProperties
{
    /** @return iterable<string, mixed> */
    public function toInertiaProperties(RenderContext $context): iterable;
}
```

| Context | Properties |
|---|---|
| `PropertyContext` | `string $key` (dot path), `array $props` (siblings), `ServerRequestInterface $request` |
| `RenderContext` | `string $component`, `ServerRequestInterface $request` |

## Flash

### Flash\InertiaFlash

```php
public function __construct(Flash\FlashStoreInterface $store)
```

| Method | Description |
|---|---|
| `errors(array $errors, string $bag = 'default'): self` | Validation errors for the next render |
| `flash(string\|array $key, mixed $value = null): self` | Data exposed as `page.flash` |
| `clearHistory(): self` | Clear encrypted history on the next render |
| `preserveFragment(): self` | Keep the URL fragment across the next redirect |

### Flash stores

```php
interface FlashStoreInterface
{
    public function set(string $key, mixed $value): void;
    public function get(string $key): mixed;
    public function pull(string $key): mixed;
}
```

| Implementation | Description |
|---|---|
| `Flash\SessionFlashStore` | Uses `Yiisoft\Session\SessionInterface` |
| `Flash\ArrayFlashStore` | In memory, for tests |

## View

### View\RootViewRendererInterface

```php
public function render(View\InertiaView $inertia, ServerRequestInterface $request): string;
```

### View\PhpRootViewRenderer

```php
public function __construct(string $template, array $parameters = [])
```

Renders a PHP template with `$inertia`, `$request` and the entries of `$parameters`. Throws `RuntimeException` when
the template doesn't exist.

### View\InertiaView

| Member | Description |
|---|---|
| `Page $page` | The page object |
| `?SsrResponse $ssr` | The SSR result, if any |
| `body(string $id = 'app'): string` | Page script element and root element, or the SSR body |
| `legacyBody(string $id = 'app'): string` | Root element with a `data-page` attribute, or the SSR body |
| `head(): string` | SSR head tags, or an empty string |
| `pageJson(): string` | Page JSON that is safe to embed in HTML |

## Versions

```php
interface Version\VersionProviderInterface
{
    public function getVersion(): string;
}
```

| Implementation | Constructor |
|---|---|
| `Version\StaticVersion` | `(string $version = '')` |
| `Version\CallbackVersion` | `(callable $callback)` |
| `Version\ManifestVersion` | `(string $manifestPath)` |

## Vite

### Vite\Vite

```php
public function __construct(
    string $publicPath,
    string $buildDirectory = 'build',
    string $manifest = '.vite/manifest.json',
    ?string $devServerUrl = null,
    string $hotFile = 'hot',
    string $baseUrl = '/',
)
```

| Method | Description |
|---|---|
| `tags(string\|array $entries): string` | Script, stylesheet and preload tags |
| `reactRefresh(): string` | React Fast Refresh preamble in dev mode |
| `asset(string $path): string` | URL of a processed asset |
| `isRunningHot(): bool` | Whether the dev server is used |
| `getManifestPath(): string` | Absolute path of the manifest |

`tags()` and `asset()` throw `RuntimeException` when the manifest or an entry is missing.

## Server-side rendering

```php
interface Ssr\GatewayInterface
{
    public function dispatch(Page $page, ServerRequestInterface $request): ?Ssr\SsrResponse;
}
```

### Ssr\HttpGateway

```php
public function __construct(
    ClientInterface $client,
    RequestFactoryInterface $requestFactory,
    StreamFactoryInterface $streamFactory,
    string $url = 'http://127.0.0.1:13714/render',
    array $except = [],
    bool $throwOnError = false,
    ?LoggerInterface $logger = null,
)
```

### Ssr\SsrResponse

```php
final class SsrResponse
{
    public readonly string $head;
    public readonly string $body;
}
```

## Header

`Header` defines constants for the protocol headers: `INERTIA`, `VERSION`, `LOCATION`, `REDIRECT`,
`PARTIAL_COMPONENT`, `PARTIAL_ONLY`, `PARTIAL_EXCEPT`, `RESET`, `ERROR_BAG`, `INFINITE_SCROLL_MERGE_INTENT`,
`EXCEPT_ONCE_PROPS` and `PURPOSE`.
