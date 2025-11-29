<?php

declare(strict_types=1);

use App\Controller\DashboardController;
use App\Controller\HomeController;
use Crenspire\Yii3Inertia\ConfigProvider;
use Crenspire\Yii3Inertia\Inertia;
use Crenspire\Yii3Inertia\Middleware\InertiaMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

require __DIR__ . '/../vendor/autoload.php';

// Create DI container with ConfigProvider
$container = new Container(
    ContainerConfig::create()
        ->withDefinitions([
            // Include Inertia ConfigProvider for auto-configuration
            ConfigProvider::class,
            // PSR factories
            Psr\Http\Message\ResponseFactoryInterface::class => Psr17Factory::class,
            Psr\Http\Message\StreamFactoryInterface::class => Psr17Factory::class,
            // Controllers
            HomeController::class => HomeController::class,
            DashboardController::class => DashboardController::class,
        ])
);

// Setup shared props (optional - can also use Bootstrap helper)
Inertia::share('app', [
    'name' => 'Yii3 Inertia Example',
    'version' => '1.0.0',
]);

// Create request
$psr17Factory = new Psr17Factory();
$creator = new ServerRequestCreator(
    $psr17Factory,
    $psr17Factory,
    $psr17Factory,
    $psr17Factory
);
$request = $creator->fromGlobals();

// Get middleware from container (auto-configured by ConfigProvider)
$inertiaMiddleware = $container->get(InertiaMiddleware::class);

// Simple router handler
$handler = new class($container) implements RequestHandlerInterface {
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        
        // Simple routing
        if ($path === '/' || $path === '') {
            $controller = $this->container->get(HomeController::class);
            return $controller->index($request);
        }
        
        if ($path === '/dashboard') {
            $controller = $this->container->get(DashboardController::class);
            return $controller->index($request);
        }
        
        // 404
        $responseFactory = $this->container->get(\Crenspire\Yii3Inertia\ResponseFactory::class);
        return $responseFactory->json(['error' => 'Not Found'])
            ->withStatus(404);
    }
};

// Process through middleware stack
$response = $inertiaMiddleware->process($request, $handler);

// Send response
http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}
echo $response->getBody();

