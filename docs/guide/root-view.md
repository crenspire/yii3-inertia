# Root view

The root view is the HTML document returned for the first visit. It loads your assets and contains the page
object that the client boots from.

## Template variables

`PhpRootViewRenderer` renders a plain PHP template with these variables:

| Variable | Type | Description |
|---|---|---|
| `$inertia` | `Crenspire\Inertia\View\InertiaView` | The page and its HTML helpers |
| `$request` | `Psr\Http\Message\ServerRequestInterface` | The current request |
| `$vite` | `Crenspire\Inertia\Vite\Vite` | [Vite tags](./vite), available with the Yii3 configuration |
| custom | any | Entries of the `viewParameters` param |

## InertiaView

| Member | Description |
|---|---|
| `body(string $id = 'app')` | The page script element and the app root for Inertia.js 3, or the server-rendered HTML with [SSR](./ssr) |
| `legacyBody(string $id = 'app')` | The app root with a `data-page` attribute, for Inertia.js 1 and 2 |
| `head()` | Head tags from server-side rendering, or an empty string |
| `pageJson()` | The page object as JSON that is safe to embed in HTML |
| `page` | The `Page` object: `component`, `props`, `url`, `version`, `metadata` |

`body()` outputs:

```html
<script data-page="app" type="application/json">{"component":"Home",...}</script><div id="app"></div>
```

The JSON escapes `<`, `>` and `/`, so props can't close the script element.

## A complete template

```php
<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var Crenspire\Inertia\View\InertiaView $inertia
 * @var Crenspire\Inertia\Vite\Vite $vite
 * @var string $locale
 */
?>
<!DOCTYPE html>
<html lang="<?= Html::encode($locale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title data-inertia><?= Html::encode($inertia->page->component) ?> - My App</title>
    <link rel="icon" href="/favicon.svg">
    <?= $vite->reactRefresh() ?>
    <?= $vite->tags(['resources/js/app.jsx', 'resources/css/app.css']) ?>
    <?= $inertia->head() ?>
</head>
<body class="antialiased">
    <?= $inertia->body() ?>
</body>
</html>
```

## Head elements and data-inertia

Inertia's `<Head>` component manages elements that have the `data-inertia` attribute. When a page renders
`<Head>`, those elements are replaced by the page's own; when a page doesn't use `<Head>`, they are removed on the
first render.

- If your pages set the title with `<Head title="...">`, keep `data-inertia` on the `<title>` in the root view to
  avoid two titles.
- If your pages don't use `<Head>`, remove `data-inertia`, or the title disappears after the page loads.

## Inertia.js 1 and 2 clients

Clients before version 3 read the page from a `data-page` attribute. Use `legacyBody()` instead of `body()`:

```php
<body>
    <?= $inertia->legacyBody() ?>
</body>
```

## Custom root element ID

Pass the ID to `body()` and to `createInertiaApp`:

```php
<?= $inertia->body('root') ?>
```

```js
createInertiaApp({ id: 'root', /* ... */ })
```

## Using another template engine

Implement `RootViewRendererInterface` and register it in the DI container. For example, with `yiisoft/view`:

```php
<?php

declare(strict_types=1);

namespace App\Inertia;

use Crenspire\Inertia\View\InertiaView;
use Crenspire\Inertia\View\RootViewRendererInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\View\WebView;

final readonly class WebViewRootViewRenderer implements RootViewRendererInterface
{
    public function __construct(
        private WebView $view,
        private Aliases $aliases,
    ) {}

    public function render(InertiaView $inertia, ServerRequestInterface $request): string
    {
        // WebView expects a file path, so resolve the alias first.
        return $this->view
            ->withClearedState()
            ->render($this->aliases->get('@root/resources/views/inertia.php'), ['inertia' => $inertia]);
    }
}
```

```php
// config/web/di/inertia.php
return [
    Crenspire\Inertia\View\RootViewRendererInterface::class => App\Inertia\WebViewRootViewRenderer::class,
];
```

A custom renderer doesn't receive `$vite` automatically; inject `Crenspire\Inertia\Vite\Vite` and pass it to the
template.
