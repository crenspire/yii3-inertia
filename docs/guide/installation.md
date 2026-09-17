# Installation

This guide adds Inertia to an application created from the [`yiisoft/app`](https://github.com/yiisoft/app)
template. For other PSR-15 applications, see [Without Yii3](./psr15).

## 1. Install the package

```bash
composer require crenspire/yii3-inertia:^2.0
```

The package ships `config/params.php` and `config/di-web.php` for `yiisoft/config`. After installation, these
services are available through dependency injection:

| Service | Purpose |
|---|---|
| `Crenspire\Inertia\Inertia` | Renders pages and creates redirects |
| `Crenspire\Inertia\Middleware\InertiaMiddleware` | Applies the protocol rules |
| `Crenspire\Inertia\Middleware\XsrfTokenMiddleware` | Connects `yiisoft/csrf` to the Inertia client |
| `Crenspire\Inertia\Flash\InertiaFlash` | Flashes validation errors and data for the next page |
| `Crenspire\Inertia\Vite\Vite` | Generates script and stylesheet tags |

::: tip
If the services are not found, rebuild the configuration merge plan with `composer yii-config-rebuild`.
:::

## 2. Add the middleware

Open `config/web/di/application.php` and add the middleware to the dispatcher:

```php{3,5}
ErrorCatcher::class,
SessionMiddleware::class,
\Crenspire\Inertia\Middleware\XsrfTokenMiddleware::class,
CsrfTokenMiddleware::class,
\Crenspire\Inertia\Middleware\InertiaMiddleware::class,
RequestCatcherMiddleware::class,
Router::class,
```

- `InertiaMiddleware` must run before `Router`.
- `XsrfTokenMiddleware` must run after `SessionMiddleware` and before `CsrfTokenMiddleware`. Leave it out if you
  don't use `yiisoft/csrf`. See [CSRF protection](./csrf).

## 3. Create the root view

The root view is the HTML document returned on the first visit. Create
`resources/views/inertia.php`, the default location:

```php
<?php

declare(strict_types=1);

/**
 * @var Crenspire\Inertia\View\InertiaView $inertia
 * @var Crenspire\Inertia\Vite\Vite $vite
 */
?>
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

To keep the template elsewhere, set the `rootView` param:

```php
// config/web/params.php
return [
    'crenspire/yii3-inertia' => [
        'rootView' => '@src/views/inertia.php',
    ],
];
```

See [Root view](./root-view) for all template variables.

## 4. Render a page

Inject `Inertia` into an action and return `render()`:

```php
<?php

declare(strict_types=1);

namespace App\Web\HomePage;

use Crenspire\Inertia\Inertia;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class Action
{
    public function __construct(
        private Inertia $inertia,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->inertia->render($request, 'Home', [
            'message' => 'Hello from Yii3',
        ]);
    }
}
```

The second argument is the page component name, which the client resolves to a file such as
`resources/js/Pages/Home.jsx`.

## 5. Set up the frontend

Install the Inertia client and Vite, and create the entry point. This is covered in
[Client-side setup](./client-setup). The short version for React:

```bash
npm install @inertiajs/react react react-dom
npm install --save-dev vite @vitejs/plugin-react
```

Then build the assets and open the application:

```bash
npm run build
APP_ENV=dev ./yii serve
```

## Where things go

The defaults assume this layout, and every path can be changed in the [configuration](./configuration):

```
config/web/params.php          package params
public/build/                  Vite build output
resources/js/app.jsx           frontend entry point
resources/js/Pages/            page components
resources/views/inertia.php    root view
vite.config.js
```
