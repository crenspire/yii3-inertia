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

## Yii3 Quick Start (Plug-and-Play)

For Yii3 applications, the package provides automatic configuration via ConfigProvider:

### 1. Include ConfigProvider in Your Yii3 Config

```php
// config/web.php or your main config file
return [
    // Include Inertia ConfigProvider for auto-configuration
    \Crenspire\Inertia\ConfigProvider::class,
    
    // ... your other config
];
```

### 2. Add Middleware to Your Middleware Stack

```php
// config/web.php
return [
    'middleware' => [
        // Error handling middleware
        // Authentication middleware
        \Crenspire\Inertia\Middleware\InertiaMiddleware::class, // ← Add here
        // Routing middleware
        // Controller/Action execution
    ],
];
```

### 3. Use in Your Controllers

```php
use Crenspire\Inertia\ControllerTrait;
use Crenspire\Inertia\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HomeController
{
    use ControllerTrait;
    
    public function __construct(
        private ResponseFactory $responseFactory
    ) {}
    
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

That's it! The ConfigProvider automatically configures all services. See [examples/yii3-web](examples/yii3-web) for a complete example.

## Quick Start (Manual Setup)

### 1. Register Middleware

Register the Inertia middleware in your application's middleware stack:

```php
use Crenspire\Inertia\Middleware\InertiaMiddleware;
use Crenspire\Inertia\ResponseFactory;
use Nyholm\Psr7\Factory\Psr17Factory;

$psr17Factory = new Psr17Factory();
$responseFactory = new ResponseFactory($psr17Factory, $psr17Factory);
$inertiaMiddleware = new InertiaMiddleware($responseFactory, $psr17Factory);

// Add to your middleware stack
// Note: Middleware should be registered early in the stack to set up the request
```

### 2. Use in Actions/Controllers

```php
use Crenspire\Inertia\Inertia;
use Crenspire\Inertia\ResponseFactory;
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

For easier usage in controllers (works with Yii3 DI):

```php
use Crenspire\Inertia\ControllerTrait;
use Crenspire\Inertia\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HomeController
{
    use ControllerTrait;
    
    public function __construct(
        private ResponseFactory $responseFactory
    ) {}
    
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

**Note:** With Yii3 ConfigProvider, `ResponseFactory` is automatically injected via DI container.

### 4. Using InertiaAction Base Class (Recommended for Actions)

For actions, extend the `InertiaAction` base class to eliminate boilerplate:

```php
use Crenspire\Inertia\Action\InertiaAction;
use Psr\Http\Message\ResponseInterface;

class HomeAction extends InertiaAction
{
    public function __invoke(): ResponseInterface
    {
        // Helper methods available:
        // - $this->render() - Render Inertia page
        // - $this->getRequest() - Get current request
        // - $this->getQueryParam() - Get query parameter
        // - $this->getBodyParam() - Get body parameter
        // - $this->redirect() - Create redirect response
        // - $this->isInertiaRequest() - Check if Inertia request
        
        return $this->render('Home', [
            'title' => 'Welcome',
            'page' => $this->getQueryParam('page', 1),
        ]);
    }
}
```

**With DI Container (Yii3):**

```php
// Action is automatically instantiated with request and ResponseFactory
$action = $container->get(HomeAction::class);
return $action();
```

**Manual instantiation:**

```php
$action = new HomeAction($request, $responseFactory);
return $action();
```

The base class automatically:
- Sets request in Inertia service
- Resolves ResponseFactory from DI container (if available)
- Provides helper methods for common operations

### 5. Setup Frontend

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

### InertiaAction Base Class

The `InertiaAction` base class provides a convenient way to create Inertia actions with automatic request handling and helper methods.

**Available Helper Methods:**

- `render(string $component, array $props = []): ResponseInterface` - Render an Inertia page
- `getRequest(): ServerRequestInterface` - Get the current request
- `getQueryParam(string $name, $default = null)` - Get a query parameter
- `getQueryParams(): array` - Get all query parameters
- `getBodyParam(string $name, $default = null)` - Get a request body parameter
- `getParsedBody(): array` - Get parsed request body
- `getAttribute(string $name, $default = null)` - Get a request attribute
- `getMethod(): string` - Get request method
- `isGet(): bool` - Check if request is GET
- `isPost(): bool` - Check if request is POST
- `isInertiaRequest(): bool` - Check if request is an Inertia request
- `redirect(string $url): ResponseInterface` - Create an Inertia redirect response
- `getResponseFactory(): ResponseFactory` - Get the ResponseFactory instance

**Example:**

```php
use Crenspire\Inertia\Action\InertiaAction;
use Psr\Http\Message\ResponseInterface;

class UserAction extends InertiaAction
{
    public function __invoke(): ResponseInterface
    {
        if ($this->isPost()) {
            // Handle POST request
            $name = $this->getBodyParam('name');
            // ... save user
            return $this->redirect('/users');
        }
        
        // Handle GET request
        $userId = (int) $this->getQueryParam('id', 0);
        return $this->render('User', [
            'userId' => $userId,
        ]);
    }
}
```

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

### Asset Configuration (AssetConfig)

Configure Vite dev server and production asset paths via Yii3 params:

```php
// config/params.php
return [
    'inertia' => [
        'assetConfig' => [
            'viteHost' => 'localhost',           // Vite dev server host
            'vitePort' => 5173,                  // Vite dev server port
            'viteEntryPath' => 'src/main.jsx',   // Entry point for Vite dev server
            'manifestEntryKey' => 'src/main.jsx', // Manifest entry key (matches vite.config.js input)
            'publicPath' => 'public',             // Public directory path
            'buildOutputDir' => 'dist',           // Build output directory
            'manifestFileName' => 'manifest.json', // Manifest file name
        ],
    ],
];
```

The `AssetConfig` is automatically resolved from params when using ConfigProvider. You can also inject it directly:

```php
use Crenspire\Inertia\AssetConfig;

// Get from container
$assetConfig = $container->get(AssetConfig::class);

// Or create manually
$assetConfig = new AssetConfig(
    viteHost: 'localhost',
    vitePort: 5173,
    viteEntryPath: 'src/main.jsx',
    manifestEntryKey: 'src/main.jsx',
    publicPath: 'public',
    buildOutputDir: 'dist',
    manifestFileName: 'manifest.json'
);
```

### Root View Path

You can configure the root view path:

```php
Inertia::setRootView('custom-inertia');
```

### Dependency Injection (DI) Container

**For Yii3 applications, use ConfigProvider (recommended):**

```php
// config/web.php
return [
    // ConfigProvider automatically configures all services
    \Crenspire\Inertia\ConfigProvider::class,
];
```

**Manual DI configuration (if not using ConfigProvider):**

```php
use Crenspire\Inertia\Middleware\InertiaMiddleware;
use Crenspire\Inertia\ResponseFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

// In your DI container configuration
$container->set(ResponseFactory::class, function ($container) {
    $responseFactory = $container->get(ResponseFactoryInterface::class);
    $streamFactory = $container->get(StreamFactoryInterface::class);
    
    // Required: provide a view renderer callback
    // With Yii3 View (recommended):
    $view = $container->get(\Yiisoft\View\WebView::class);
    $viewRenderer = \Crenspire\Inertia\ResponseFactory::createViewRenderer($view);
    
    // Or custom view renderer:
    // $viewRenderer = function (string $view, array $payload): string {
    //     return $yourViewRenderer->render($view, ['page' => $payload]);
    // };
    
    return new ResponseFactory($responseFactory, $streamFactory, $viewRenderer);
});

$container->set(InertiaMiddleware::class, function ($container) {
    $responseFactory = $container->get(ResponseFactory::class);
    $psrResponseFactory = $container->get(ResponseFactoryInterface::class);
    return new InertiaMiddleware($responseFactory, $psrResponseFactory);
});
```

### View Renderer Integration

**Important:** `ResponseFactory` now requires a view renderer. The ConfigProvider automatically configures it using Yii3's `WebView` or `View` if available.

**Manual configuration:**

```php
use Crenspire\Inertia\ResponseFactory;
use Crenspire\Inertia\ViewRenderer;

// With Yii3 View
$viewRenderer = ResponseFactory::createViewRenderer($yii3View);

// Or custom view renderer
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

**Note:** If `yiisoft/view` is not installed, you must provide a custom view renderer. The ConfigProvider will throw an exception if no view renderer is available.

### Bootstrap/Initialization

For shared props that should be available on every page, set them in your application bootstrap:

**Using Inertia::share() directly (recommended):**

```php
use Crenspire\Inertia\Inertia;

// In your application bootstrap or middleware
Inertia::share('user', function () use ($userService) {
    return $userService->getCurrentUser();
});

Inertia::share('app', [
    'name' => 'My App',
    'version' => '1.0.0',
]);
```

**Using Bootstrap helper (optional convenience):**

```php
use Crenspire\Inertia\Bootstrap;

// In your application bootstrap
Bootstrap::setupSharedProps($userService, $flashService);
Bootstrap::setupVersion('/path/to/manifest.json');
Bootstrap::setupRootView('inertia');

// Or use the complete setup method
Bootstrap::setup([
    'userService' => $userService,
    'flashService' => $flashService,
    'manifestPath' => '/path/to/manifest.json',
    'rootView' => 'inertia',
    'shared' => [
        'app' => ['name' => 'My App'],
    ],
]);
```

**Note:** The Bootstrap helper is completely optional. It provides convenience methods but has zero overhead if not used. You can use `Inertia::share()` directly for the same result.

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

## Examples

The repository includes several example applications:

### Yii3 Web Application Example

Full Yii3 web application with ConfigProvider, controllers, and middleware:

```bash
cd examples/yii3-web
composer install
php -S localhost:8000 -t public
```

See [examples/yii3-web/README.md](examples/yii3-web/README.md) for details.

### Yii3 Minimal Example

Minimal Yii3 setup (DI + Router only):

```bash
cd examples/yii3-minimal
composer install
php -S localhost:8000 -t public
```

See [examples/yii3-minimal/README.md](examples/yii3-minimal/README.md) for details.

### Basic PSR Example

Basic PSR-7/PSR-15 example (for non-Yii3 frameworks):

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

**Note:** For Yii3 applications, use the Yii3 examples above instead.

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

