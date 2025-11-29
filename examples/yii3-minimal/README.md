# Yii3 Minimal Inertia Example

This example shows how to use `crenspire/yii3-inertia` with a minimal Yii3 setup (DI + Router only, no full web application).

## Installation

```bash
cd examples/yii3-minimal
composer install
```

## Setup

1. Install the package:
   ```bash
   composer require crenspire/yii3-inertia
   ```

2. Include ConfigProvider in your config:
   ```php
   // config/web.php
   return [
       \Crenspire\Yii3Inertia\ConfigProvider::class,
       // ... other config
   ];
   ```

3. Register middleware in your application:
   ```php
   $middleware = $container->get(\Crenspire\Yii3Inertia\Middleware\InertiaMiddleware::class);
   // Add to your middleware stack
   ```

4. Use in your actions:
   ```php
   use Crenspire\Yii3Inertia\Inertia;
   use Crenspire\Yii3Inertia\ResponseFactory;

   $payload = Inertia::render('Home', ['title' => 'Welcome']);
   $responseFactory = $container->get(ResponseFactory::class);
   return $responseFactory->html($payload, Inertia::getRootView());
   ```

## Running

```bash
php -S localhost:8000 -t public
```

Visit `http://localhost:8000` in your browser.

