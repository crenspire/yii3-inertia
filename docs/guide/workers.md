# Long-running workers

Application servers such as RoadRunner, Swoole and FrankenPHP in worker mode handle many requests in the same PHP
process. Services live across requests, so any data they store leaks from one request to the next.

The adapter is designed for this:

- `Inertia` and `InertiaMiddleware` hold only configuration. `with*()` methods return new instances instead of
  changing the service.
- Request-specific data, such as [shared props](./shared-data#per-request-data), validation errors and history
  flags, is stored in request attributes.
- `ManifestVersion` and `Vite` cache the manifest and re-read it only when the file changes.
- `PhpRootViewRenderer` renders the template in an isolated scope.

## What to watch in your code

- Share per-user data with `Inertia::share($request, ...)` in a middleware, not with `withSharedProps()` on a shared
  service.
- Closures in `sharedProps` params run on every render, so they may read request-independent data but must not
  capture objects that hold request state.
- A custom `FlashStoreInterface` must store data per session, not in the object.
- A custom `RootViewRendererInterface` that uses a stateful view, such as `WebView`, should call
  `withClearedState()` before rendering.
