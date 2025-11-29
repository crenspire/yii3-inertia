# Yii3 Web Application Inertia Example

This example shows how to integrate `crenspire/yii3-inertia` into a full Yii3 web application with controllers, views, and proper middleware stack.

## Installation

```bash
cd examples/yii3-web
composer install
```

## Quick Start

1. **Install the package:**
   ```bash
   composer require crenspire/yii3-inertia
   ```

2. **Include ConfigProvider in your Yii3 config:**
   ```php
   // config/web.php
   return [
       // Include Inertia ConfigProvider for auto-configuration
       \Crenspire\Yii3Inertia\ConfigProvider::class,
       
       // ... other config
   ];
   ```

3. **Add middleware to your middleware stack:**
   ```php
   // config/web.php
   return [
       'middleware' => [
           // Error handling
           // Authentication
           \Crenspire\Yii3Inertia\Middleware\InertiaMiddleware::class, // ← Add here
           // Routing
           // Controllers
       ],
   ];
   ```

4. **Use in controllers:**
   ```php
   use Crenspire\Yii3Inertia\ControllerTrait;
   use Crenspire\Yii3Inertia\ResponseFactory;
   
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

5. **Setup shared props (optional):**
   ```php
   // In your application bootstrap
   use Crenspire\Yii3Inertia\Inertia;
   
   Inertia::share('user', function () use ($userService) {
       return $userService->getCurrentUser();
   });
   ```

## Running

```bash
php -S localhost:8000 -t public
```

Visit `http://localhost:8000` in your browser.

## Features Demonstrated

- ConfigProvider auto-configuration
- Middleware stack integration
- ControllerTrait usage
- DI container integration
- View renderer integration (if yiisoft/view installed)
- Shared props setup

