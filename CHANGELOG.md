# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - Unreleased

A rewrite of the adapter. See [UPGRADE.md](UPGRADE.md) for migration steps.

### Added

- Support for the Inertia.js 3 protocol, including the `<script type="application/json">` page element, and
  `legacyBody()` for Inertia.js 1 and 2 clients.
- `Inertia` service without static state, safe for long-running workers.
- Per-request shared props through `Inertia::share($request, ...)`.
- Deferred, optional, always, merge, deep merge, once and infinite scroll props, with prop providers and dot-notation keys.
- `X-Inertia-Partial-Except`, `X-Inertia-Reset`, `X-Inertia-Except-Once-Props` and infinite scroll merge intent headers.
- Validation errors with error bags, flash data, `clearHistory`, `encryptHistory` and `preserveFragment`.
- `InertiaFlash` and `SessionFlashStore` for `yiisoft/session`.
- `XsrfTokenMiddleware` for using `yiisoft/csrf` with the Inertia.js client.
- `Vite` helper for dev server and manifest-based tags.
- `ManifestVersion`, `StaticVersion` and `CallbackVersion` asset version providers.
- Server-side rendering through `HttpGateway` (PSR-18).
- Scroll metadata from `yiisoft/data` paginators.
- Automatic Yii3 configuration through `yiisoft/config`.
- Runnable PSR-15 example and a `yiisoft/app` example.
- Documentation site at https://crenspire.github.io/yii3-inertia/.
- PHPStan analysis in CI.

### Changed

- PHP 8.2 or later is required.
- `yiisoft/di` and `yiisoft/config` are no longer required.
- `InertiaMiddleware` now handles 303 redirects, fragment redirects, empty responses and `Vary: X-Inertia`, and checks
  the asset version only for GET requests.
- Partial reloads only apply to the component named in `X-Inertia-Partial-Component`.
- Empty props are encoded as a JSON object.

### Removed

- `ResponseFactory`, `ControllerTrait`, `Action\InertiaAction`, `ConfigProvider`, `AssetConfig`, `ViewRenderer`,
  `Bootstrap`, `Middleware\InertiaMiddlewareConfig` and the `inertia()` function.
- Static `Inertia` methods for rendering, sharing, versioning and the root view.

### Fixed

- The package could not be installed in CI because the `yiisoft/config` Composer plugin was not allowed.
- The test suite referenced a non-existent namespace and no test could run.
- The root view double-encoded the page, so the client could not boot.
- The documented `ConfigProvider` setup failed with "Key must be a string".
- Page data set by controllers in a request attribute was never read by the middleware.
- Shared props and the request leaked between requests in long-running workers.

## [1.0.0] - 2025-11-29

- Initial release.
