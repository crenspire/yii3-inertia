# Pages and responses

## Rendering a page

`Inertia::render()` takes the request, the page component name and its props:

```php
public function __invoke(ServerRequestInterface $request): ResponseInterface
{
    return $this->inertia->render($request, 'Users/Index', [
        'filters' => $request->getQueryParams(),
        'users' => fn () => $this->users->findAll(),
    ]);
}
```

- For the first visit, it returns `200` with the [root view](./root-view) as HTML.
- For Inertia visits (requests with `X-Inertia`), it returns `200` with the page object as JSON and the
  `X-Inertia: true` header.

Both responses include `Vary: X-Inertia` so browsers and caches don't mix them up.

::: info Pass the current request
Always pass the request your action received. It carries the partial reload headers and
[request-scoped shared data](./shared-data#per-request-data) that middleware added.
:::

### Component names

The component name is passed to the client's `resolve` callback unchanged. With the usual setup,
`Users/Index` loads `resources/js/Pages/Users/Index.jsx`. The name can't be empty.

## The page object

Each response contains a page object:

```json
{
  "component": "Users/Index",
  "props": {
    "errors": {},
    "users": [{ "id": 1, "name": "Ada Lovelace" }]
  },
  "url": "/users?page=1",
  "version": "1b68733fbf9b84a73848c94474daf0ec",
  "sharedProps": ["errors"]
}
```

| Key | Description |
|---|---|
| `component` | The page component name |
| `props` | The resolved props, always an object |
| `url` | The request path and query string |
| `version` | The current [asset version](./asset-versioning) |

Depending on the props and request, the page also contains keys such as `deferredProps`, `mergeProps`,
`onceProps`, `scrollProps`, `flash`, `encryptHistory` and `clearHistory`. See
[Protocol support](../reference/protocol).

### Building the page without a response

`createPage()` resolves the props and returns a `Page` object. Use it in tests, or to return the page in a
custom response:

```php
$page = $this->inertia->createPage($request, 'Users/Index', ['users' => $users]);

$page->component; // 'Users/Index'
$page->props;     // resolved props
$page->toArray(); // the full page object
```

## Customizing the URL

The page URL is the request path plus its query string. When the application runs behind a proxy under a
sub-path, provide a resolver:

```php
$inertia = $inertia->withUrlResolver(
    static fn (ServerRequestInterface $request): string => '/app' . $request->getUri()->getPath(),
);
```

`withUrlResolver()`, like every `with*()` method, returns a new instance and leaves the original unchanged.

## Detecting Inertia requests

```php
if (Inertia::isInertiaRequest($request)) {
    // The request was made by the Inertia client.
}
```

## Status codes and headers

`render()` returns a PSR-7 response, so you can change it before returning it:

```php
return $this->inertia
    ->render($request, 'Error', ['status' => 404])
    ->withStatus(404);
```
