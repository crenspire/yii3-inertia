<?php

declare(strict_types=1);

use Crenspire\Yii3Inertia\ConfigProvider;
use Crenspire\Yii3Inertia\Inertia;
use Crenspire\Yii3Inertia\Middleware\InertiaMiddleware;
use Crenspire\Yii3Inertia\ResponseFactory;
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
            // Include Inertia ConfigProvider
            ConfigProvider::class,
            // PSR factories
            Psr\Http\Message\ResponseFactoryInterface::class => Psr17Factory::class,
            Psr\Http\Message\StreamFactoryInterface::class => Psr17Factory::class,
        ])
);

// Create request
$psr17Factory = new Psr17Factory();
$creator = new ServerRequestCreator(
    $psr17Factory,
    $psr17Factory,
    $psr17Factory,
    $psr17Factory
);
$request = $creator->fromGlobals();

// Get middleware from container
$inertiaMiddleware = $container->get(InertiaMiddleware::class);

// Simple handler
$handler = new class($container) implements RequestHandlerInterface {
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        
        if ($path === '/' || $path === '') {
            // Set request in Inertia
            Inertia::setRequest($request);
            
            // Render Inertia page
            $payload = Inertia::render('Home', [
                'title' => 'Welcome to Minimal Yii3 + Inertia',
                'message' => 'This is a minimal setup example',
            ]);
            
            // Get ResponseFactory from container
            $responseFactory = $this->container->get(ResponseFactory::class);
            
            if (Inertia::isInertiaRequest($request)) {
                return $responseFactory->json($payload);
            }
            
            return $responseFactory->html($payload, Inertia::getRootView());
        }
        
        // 404
        $responseFactory = $this->container->get(ResponseFactory::class);
        return $responseFactory->json(['error' => 'Not Found'])
            ->withStatus(404);
    }
};

// Process through middleware
$response = $inertiaMiddleware->process($request, $handler);

// Send response
http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}
echo $response->getBody();

