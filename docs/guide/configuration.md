# Configuration

In Yii3, configure the package through the `crenspire/yii3-inertia` params, usually in `config/web/params.php`.
Your values are merged over the package defaults, so you only need to set what you change.

```php
// config/web/params.php
return [
    'crenspire/yii3-inertia' => [
        'rootView' => '@src/views/inertia.php',
        'sharedProps' => [
            'appName' => 'My App',
        ],
        'vite' => [
            'devServerUrl' => $_ENV['VITE_DEV_SERVER_URL'] ?? null,
        ],
    ],
];
```

The [params reference](../reference/params) lists every option with its default.

## Common tasks

### Pass variables to the root view

`viewParameters` are extra variables for the root view template. A `Closure` is called with the DI container, so
you can pull services:

```php
use App\ApplicationParams;
use Psr\Container\ContainerInterface;

return [
    'crenspire/yii3-inertia' => [
        'viewParameters' => [
            'locale' => 'en',
            'applicationParams' => static fn (ContainerInterface $container): ApplicationParams
                => $container->get(ApplicationParams::class),
        ],
    ],
];
```

The template always receives `$inertia`, `$request` and `$vite`.

### Use a different build directory

```php
'vite' => [
    'buildDirectory' => 'dist',
    // Vite 5 and later write the manifest to .vite/manifest.json unless build.manifest is a file name.
    'manifest' => 'manifest.json',
],
```

Keep `build.outDir`, `base` and `build.manifest` in `vite.config.js` in sync with these values.

### Set the asset version explicitly

```php
'version' => '2026.09.17',
// or
'version' => static fn (): string => (string) filemtime(dirname(__DIR__, 2) . '/public/build/.vite/manifest.json'),
```

### Enable server-side rendering

```php
'ssr' => [
    'enabled' => true,
    'url' => 'http://127.0.0.1:13714/render',
],
```

See [Server-side rendering](./ssr).

## Overriding services

The params cover most needs. To replace a service entirely, define it in your own DI configuration. Your
definitions take precedence over the package's:

```php
// config/web/di/inertia.php
use Crenspire\Inertia\View\RootViewRendererInterface;

return [
    RootViewRendererInterface::class => App\Inertia\TwigRootViewRenderer::class,
];
```

| Interface | Default implementation |
|---|---|
| `View\RootViewRendererInterface` | `View\PhpRootViewRenderer` |
| `Version\VersionProviderInterface` | `Version\ManifestVersion`, or `StaticVersion`/`CallbackVersion` from `version` |
| `Flash\FlashStoreInterface` | `Flash\SessionFlashStore` when `yiisoft/session` is installed |
| `Ssr\GatewayInterface` | `Ssr\HttpGateway` when SSR is enabled |
