# Without Yii3

Every class in the package is a plain PSR component, so you can use it in any PSR-7/PSR-15 application: Mezzio,
Slim 4, a custom stack, or Yii3 without `yiisoft/config`.

## Install

```bash
composer require crenspire/yii3-inertia:^2.0
```

You also need a PSR-17 factory implementation, for example `nyholm/psr7`.

## Create the services

```php
use Crenspire\Inertia\Inertia;
use Crenspire\Inertia\Middleware\InertiaMiddleware;
use Crenspire\Inertia\Version\ManifestVersion;
use Crenspire\Inertia\View\PhpRootViewRenderer;
use Crenspire\Inertia\Vite\Vite;
use Nyholm\Psr7\Factory\Psr17Factory;

$factory = new Psr17Factory();

$vite = new Vite(
    publicPath: __DIR__ . '/public',
    devServerUrl: getenv('VITE_DEV_SERVER_URL') ?: null,
);

$inertia = new Inertia(
    responseFactory: $factory,
    streamFactory: $factory,
    rootViewRenderer: new PhpRootViewRenderer(__DIR__ . '/resources/views/inertia.php', ['vite' => $vite]),
    version: new ManifestVersion($vite->getManifestPath()),
    sharedProps: ['appName' => 'My App'],
);

$middleware = new InertiaMiddleware($inertia, $factory);
```

Register `$middleware` in your pipeline before routing, and make `$inertia` available to your request handlers,
typically through your container.

## Constructor arguments

| Argument | Type | Default | Description |
|---|---|---|---|
| `responseFactory` | `ResponseFactoryInterface` | required | Creates responses |
| `streamFactory` | `StreamFactoryInterface` | required | Creates response bodies |
| `rootViewRenderer` | `RootViewRendererInterface` | required | Renders the HTML document |
| `version` | `VersionProviderInterface` | `new StaticVersion()` | [Asset version](./asset-versioning) |
| `flashStore` | `?FlashStoreInterface` | `null` | Storage for [flash data](./flash-data) across redirects |
| `ssrGateway` | `?GatewayInterface` | `null` | [Server-side rendering](./ssr) |
| `logger` | `?LoggerInterface` | `null` | Logs rescued deferred props |
| `sharedProps` | `array` | `[]` | [Props shared](./shared-data) with every page |
| `encryptHistory` | `bool` | `false` | [History encryption](./history-encryption) |
| `allErrors` | `bool` | `false` | Send all validation messages per field |

## Flash data without yiisoft/session

`SessionFlashStore` requires `yiisoft/session`. With another session library, implement `FlashStoreInterface`.
For PHP's native session:

```php
use Crenspire\Inertia\Flash\FlashStoreInterface;

final class NativeSessionFlashStore implements FlashStoreInterface
{
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    public function pull(string $key): mixed
    {
        $value = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);

        return $value;
    }
}
```

Pass it as `flashStore` to `Inertia`, and to `new InertiaFlash($store)` to write errors and data.

## Complete example

[`examples/psr15`](https://github.com/crenspire/yii3-inertia/tree/develop/examples/psr15) is a runnable application
with a router, a form with validation, deferred props and flash messages, built only from PSR components.
