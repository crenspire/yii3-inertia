# Introduction

**Yii3 Inertia** is the server-side adapter that connects [Inertia.js](https://inertiajs.com) to
[Yii3](https://www.yiiframework.com) and to any application built on PSR-7 and PSR-15.

Inertia lets you build a single-page application with React, Vue or Svelte without building an API. Your Yii
actions keep handling routing, authorization, validation and data loading, and return a page component with its
props instead of HTML. Inertia's client library renders the component and turns links and form submissions into
background requests, so the app feels like an SPA.

## How it works

1. **The first visit** returns a full HTML document. The adapter renders your root view, which loads your
   JavaScript bundle and contains the page object: the component name, its props, the URL and the asset version.
2. **Following visits** are made by the Inertia client with the `X-Inertia` header. The adapter answers them with
   the page object as JSON, and the client swaps the page component without a full reload.
3. **The middleware** enforces the protocol rules: full reloads when assets change, `303` redirects after `PUT`,
   `PATCH` and `DELETE`, and correct caching headers.

```php
return $inertia->render($request, 'Users/Index', [
    'users' => fn () => $users->findAll(),
]);
```

The same call returns HTML or JSON depending on the request, so actions never deal with the protocol.

## Features

- Inertia.js 3 protocol, with support for Inertia.js 1 and 2 clients
- Shared data from configuration or per request
- Partial reloads, and deferred, optional, always, merge, once and infinite scroll props
- Validation errors with error bags, flash data and history encryption
- CSRF protection for `yiisoft/csrf`
- Vite tags for the dev server and production builds, with automatic asset versioning
- Server-side rendering through a PSR-18 HTTP client
- Automatic configuration in Yii3 through `yiisoft/config`
- No static state, safe for RoadRunner, Swoole and FrankenPHP workers

## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.2 – 8.5 |
| PSR-17 HTTP factories | Any implementation, for example `httpsoft/http-message` or `nyholm/psr7` |
| Inertia.js client | 3.x recommended; 1.x and 2.x work with [`legacyBody()`](./root-view#inertia-js-1-and-2-clients) |

Optional packages add features:

| Package | Enables |
|---|---|
| `yiisoft/config` | Automatic service registration |
| `yiisoft/aliases` | Aliases such as `@root` in the params |
| `yiisoft/session` | Validation errors and flash data across redirects |
| `yiisoft/csrf` | CSRF protection with `XsrfTokenMiddleware` |
| `yiisoft/data` | Infinite scroll from Yii data paginators |
| `psr/http-client` implementation | Server-side rendering |

## Next steps

- [Install the package in a Yii3 application](./installation)
- [Set up the frontend](./client-setup)
- [Use it without Yii3](./psr15)
