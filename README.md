<p align="center">
  <img src="docs/public/logo.svg" width="80" height="80" alt="">
</p>

<h1 align="center">Inertia.js adapter for Yii3</h1>

<p align="center">
  <a href="https://github.com/crenspire/yii3-inertia/actions/workflows/ci.yml"><img src="https://github.com/crenspire/yii3-inertia/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
  <a href="https://packagist.org/packages/crenspire/yii3-inertia"><img src="https://img.shields.io/packagist/v/crenspire/yii3-inertia" alt="Latest version"></a>
  <a href="https://packagist.org/packages/crenspire/yii3-inertia"><img src="https://img.shields.io/packagist/php-v/crenspire/yii3-inertia" alt="PHP version"></a>
  <a href="LICENSE"><img src="https://img.shields.io/packagist/l/crenspire/yii3-inertia" alt="License"></a>
</p>

<p align="center">
  <strong><a href="https://crenspire.github.io/yii3-inertia/">Documentation</a></strong> ·
  <a href="https://crenspire.github.io/yii3-inertia/guide/installation">Installation</a> ·
  <a href="https://crenspire.github.io/yii3-inertia/reference/api">API reference</a> ·
  <a href="https://crenspire.github.io/yii3-inertia/guide/upgrade">Upgrade from 1.x</a>
</p>

A server-side [Inertia.js](https://inertiajs.com) adapter for [Yii3](https://www.yiiframework.com) and any other
PSR-7/PSR-15 application. Build single-page apps with React, Vue or Svelte while keeping routing, controllers and
validation in PHP.

## Features

- Inertia.js 3 protocol, with support for Inertia.js 1 and 2 clients
- Partial reloads, and deferred, optional, always, merge, once and infinite scroll props
- Validation errors with error bags, flash data and history encryption
- CSRF protection for `yiisoft/csrf`
- Vite tags for the dev server and production builds, with automatic asset versioning
- Server-side rendering through a PSR-18 HTTP client
- Zero-config setup in Yii3 through `yiisoft/config`
- Stateless services, safe for RoadRunner, Swoole and FrankenPHP workers

## Requirements

- PHP 8.2 – 8.5
- A PSR-17 HTTP factory implementation

## Installation

```bash
composer require crenspire/yii3-inertia:^2.0
```

In a Yii3 application, the services are registered automatically. Add the middleware before the router in
`config/web/di/application.php`:

```php
SessionMiddleware::class,
\Crenspire\Inertia\Middleware\XsrfTokenMiddleware::class, // when using yiisoft/csrf
CsrfTokenMiddleware::class,
\Crenspire\Inertia\Middleware\InertiaMiddleware::class,
Router::class,
```

Create the root view at `resources/views/inertia.php`:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title data-inertia>My App</title>
    <?= $vite->reactRefresh() ?>
    <?= $vite->tags('resources/js/app.jsx') ?>
    <?= $inertia->head() ?>
</head>
<body>
    <?= $inertia->body() ?>
</body>
</html>
```

Then set up the frontend as described in [Client-side setup](https://crenspire.github.io/yii3-inertia/guide/client-setup).

## Usage

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
            'stats' => Inertia::defer(fn () => $this->users->statistics()),
        ]);
    }
}
```

```jsx
import { Deferred } from '@inertiajs/react'

export default function Index({ users, stats }) {
  return (
    <>
      <UserTable users={users} />
      <Deferred data="stats" fallback={<p>Loading…</p>}>
        <Stats stats={stats} />
      </Deferred>
    </>
  )
}
```

Read the [documentation](https://crenspire.github.io/yii3-inertia/) for forms and validation, shared data, all prop
types, Vite, server-side rendering, usage without Yii3, and more.

## Examples

- [`examples/psr15`](examples/psr15): a runnable application built only from PSR components
- [`examples/yii3`](examples/yii3): files for an application created from `yiisoft/app`

## Upgrading from 1.x

Version 2.0 is a rewrite with a new API. See the [upgrade guide](UPGRADE.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). The documentation lives in [`docs`](docs) and is published with GitHub
Pages.

## License

MIT. See [LICENSE](LICENSE).
