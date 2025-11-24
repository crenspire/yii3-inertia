# Yii3 Inertia.js Adapter

[![CI](https://github.com/crenspire/yii3-inertia/workflows/CI/badge.svg)](https://github.com/crenspire/yii3-inertia/actions)

An Inertia.js adapter for Yii3 framework, providing a seamless bridge between your Yii3 backend and modern JavaScript frontend frameworks (React, Vue, Svelte).

## Features

- 🚀 **Simple API**: Match the developer experience of `inertia-laravel`
- 📦 **Shared Props**: Share data across all Inertia responses
- 🔄 **Partial Reloads**: Support for partial page updates
- 🎯 **Asset Versioning**: Automatic version management for cache busting
- 🔌 **PSR-15 Middleware**: Standard middleware implementation
- 🧪 **Well Tested**: Comprehensive unit and integration tests
- 📚 **Full Documentation**: Complete usage examples and guides

## Installation

Install via Composer:

```bash
composer require crenspire/yii3-inertia
```

## Quick Start

### 1. Register Middleware

Register the Inertia middleware in your application's middleware stack:

```php
use Crenspire\Yii3Inertia\Middleware\InertiaMiddleware;
use Crenspire\Yii3Inertia\ResponseFactory;
use Nyholm\Psr7\Factory\Psr17Factory;

$psr17Factory = new Psr17Factory();
$responseFactory = new ResponseFactory($psr17Factory, $psr17Factory);
$inertiaMiddleware = new InertiaMiddleware($responseFactory, $psr17Factory);

// Add to your middleware stack
// Note: Middleware should be registered early in the stack to set up the request
```

### 2. Use in Actions/Controllers

```php
use Crenspire\Yii3Inertia\Inertia;
use Crenspire\Yii3Inertia\ResponseFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class HomeAction
{
    public function __invoke(
        ServerRequestInterface $request,
        ResponseFactory $responseFactory
    ): ResponseInterface {
        Inertia::setRequest($request);
        
        $payload = Inertia::render('Home', [
            'title' => 'Welcome',
            'user' => $user,
        ]);
        
        if (Inertia::isInertiaRequest($request)) {
            return $responseFactory->json($payload);
        }
        
        return $responseFactory->html($payload, Inertia::getRootView());
    }
}
```

### 3. Using Controller Trait

For easier usage in controllers:

```php
use Crenspire\Yii3Inertia\ControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HomeController
{
    use ControllerTrait;
    
    private ResponseFactory $responseFactory;
    
    public function __construct(ResponseFactory $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }
    
    protected function getResponseFactory(): ResponseFactory
    {
        return $this->responseFactory;
    }
    
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        return $this->inertiaRender('Home', [
            'title' => 'Welcome',
        ], $request);
    }
}
```

### 4. Setup Frontend

Install Inertia.js and your frontend framework:

```bash
npm install @inertiajs/inertia @inertiajs/inertia-react react react-dom
```

Create `src/main.jsx`:

```jsx
import React from 'react';
import ReactDOM from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/inertia-react';
import Home from './pages/Home';

createInertiaApp({
  resolve: (name) => {
    const pages = { Home };
    return pages[name];
  },
  setup({ el, App, props }) {
    ReactDOM.createRoot(el).render(<App {...props} />);
  },
});
```

## API Reference

### Inertia::render()

Render an Inertia page:

```php
$payload = Inertia::render('Dashboard', [
    'users' => $users,
]);
```

### Inertia::share()

Share data with all Inertia responses:

```php
// Single key-value
Inertia::share('appName', 'My App');

// Multiple values
Inertia::share([
    'user' => $user,
    'flash' => $flash,
]);

// Using closures
Inertia::share('timestamp', function () {
    return time();
});
```

### Inertia::version()

Set or get the asset version:

```php
// String version
Inertia::version('1.0.0');

// Callback version
Inertia::version(function () {
    return filemtime('/path/to/manifest.json');
});

// Get current version
$version = Inertia::version();
```

### Inertia::location()

Create an Inertia redirect response:

```php
$location = Inertia::location('/dashboard');
// Returns: ['location' => '/dashboard', 'status' => 409 or 302]
```

The status code depends on the request type:
- **Inertia requests**: Returns 409 (Conflict) status
- **Regular requests**: Returns 302 (Found) status

You can use this in your actions:

```php
public function store(ServerRequestInterface $request): ResponseInterface
{
    // ... save data
    
    $location = Inertia::location('/dashboard');
    $response = $responseFactory->responseFactory->createResponse($location['status']);
    
    if ($location['status'] === 409) {
        return $response->withHeader('X-Inertia-Location', $location['location']);
    }
    
    return $response->withHeader('Location', $location['location']);
}
```

### Global Helper

You can also use the global `inertia()` helper function:

```php
$payload = inertia('Home', ['title' => 'Welcome'], $request);
```

## Partial Reloads

Inertia supports partial reloads for better performance. The client can request only specific props:

```php
// Client sends: X-Inertia-Partial-Component: Dashboard
// Client sends: X-Inertia-Partial-Data: users,stats

// Only 'users' and 'stats' props will be returned (plus shared props)
$payload = Inertia::render('Dashboard', [
    'users' => $users,
    'stats' => $stats,
    'other' => $other, // This will be excluded
]);
```

## Configuration

### Root View Path

You can configure the root view path:

```php
Inertia::setRootView('custom-inertia');
```

### Dependency Injection (DI) Container

For Yii3 applications using DI containers, you can configure the middleware and response factory:

```php
use Crenspire\Yii3Inertia\Middleware\InertiaMiddleware;
use Crenspire\Yii3Inertia\ResponseFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

// In your DI container configuration
$container->set(ResponseFactory::class, function ($container) {
    $responseFactory = $container->get(ResponseFactoryInterface::class);
    $streamFactory = $container->get(StreamFactoryInterface::class);
    
    // Optional: provide a view renderer callback
    $viewRenderer = function ($view, $payload) {
        // Your view rendering logic
        return renderView($view, ['page' => $payload]);
    };
    
    return new ResponseFactory($responseFactory, $streamFactory, $viewRenderer);
});

$container->set(InertiaMiddleware::class, function ($container) {
    $responseFactory = $container->get(ResponseFactory::class);
    $psrResponseFactory = $container->get(ResponseFactoryInterface::class);
    return new InertiaMiddleware($responseFactory, $psrResponseFactory);
});
```

### View Renderer Integration

You can provide a custom view renderer to the ResponseFactory:

```php
$viewRenderer = function (string $view, array $payload): string {
    // Use your view rendering system (Twig, Blade, etc.)
    return $yourViewRenderer->render($view, ['page' => $payload]);
};

$responseFactory = new ResponseFactory(
    $psr17Factory,
    $psr17Factory,
    $viewRenderer
);
```

### Bootstrap/Initialization

For shared props that should be available on every page, set them in your application bootstrap:

```php
use Crenspire\Yii3Inertia\Inertia;

// In your application bootstrap or middleware
Inertia::share('user', function () use ($userService) {
    return $userService->getCurrentUser();
});

Inertia::share('app', [
    'name' => 'My App',
    'version' => '1.0.0',
]);
```

## Version Management

Inertia.js uses version checking to ensure the frontend and backend stay in sync. When the client's version doesn't match the server's version, a full page reload is triggered.

### Automatic Version Detection

By default, the version is automatically detected from your `manifest.json` file:

```php
// Automatically uses manifest.json mtime if it exists
$version = Inertia::version();
```

### Custom Version

You can set a custom version:

```php
// String version
Inertia::version('1.0.0');

// Callback version (evaluated on each request)
Inertia::version(function () {
    return filemtime('/path/to/manifest.json');
});
```

### Version Mismatch Handling

When a client sends an `X-Inertia-Version` header that doesn't match the current version, the middleware automatically returns a location redirect (409 status) to trigger a full page reload. This ensures users always have the latest assets.

## Middleware Registration Order

The `InertiaMiddleware` should be registered early in your middleware stack, but after any authentication/authorization middleware that sets up the user context. This ensures:

1. The request is available to the Inertia service
2. Shared props can access authenticated user data
3. Version checking happens before processing

Example middleware stack order:

```php
1. Error handling middleware
2. Authentication middleware
3. InertiaMiddleware  ← Register here
4. Routing middleware
5. Controller/Action execution
```

## Running the Example

The repository includes a complete example application. To run it:

```bash
# Install dependencies
cd examples/basic
composer install

# Install frontend dependencies
cd vite
npm install

# Build frontend assets
npm run build

# Or run dev server
npm run dev

# Start PHP server
cd ../public
php -S localhost:8000
```

Visit `http://localhost:8000` in your browser.

## Troubleshooting

### Version Mismatch Issues

If you're experiencing frequent full page reloads:
1. Check your version callback returns a stable value
2. Verify the `manifest.json` file exists and is accessible
3. Ensure file permissions allow reading the manifest file

### Middleware Not Working

If the middleware isn't processing requests correctly:
1. Verify middleware is registered in your middleware stack
2. Check that `Inertia::setRequest()` is called (middleware does this automatically)
3. Ensure the middleware receives both `ResponseFactory` and `ResponseFactoryInterface`

### Redirect Not Working

If redirects aren't working as expected:
1. Ensure you're using `Inertia::location()` and handling the response correctly
2. Check that the request has the `X-Inertia` header for Inertia requests
3. Verify the response status code (409 for Inertia, 302 for regular)

### Actions Not Setting Request

If you get "Request not set" errors:
1. Ensure `InertiaMiddleware` is registered and processes requests
2. Or manually call `Inertia::setRequest($request)` in your actions
3. Check middleware execution order

## Testing

Run the test suite:

```bash
composer install
vendor/bin/phpunit
```

## Requirements

- PHP ^8.1
- PSR-7, PSR-15 compatible framework

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes.

