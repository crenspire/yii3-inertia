<?php

declare(strict_types=1);

use Crenspire\Yii3Inertia\Middleware\InertiaMiddleware;
use Crenspire\Yii3Inertia\ResponseFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

require __DIR__ . '/../vendor/autoload.php';

// Create PSR-17 factories
$psr17Factory = new Psr17Factory();
$creator = new ServerRequestCreator(
    $psr17Factory, // ServerRequestFactory
    $psr17Factory, // UriFactory
    $psr17Factory, // UploadedFileFactory
    $psr17Factory  // StreamFactory
);

// Create request from globals
$request = $creator->fromGlobals();

// Create response factory
$responseFactory = new ResponseFactory($psr17Factory, $psr17Factory);

// Create middleware (needs both ResponseFactory and PSR ResponseFactory)
$inertiaMiddleware = new InertiaMiddleware($responseFactory, $psr17Factory);

// Simple request handler
$handler = new class($responseFactory) implements RequestHandlerInterface {
    private ResponseFactory $responseFactory;

    public function __construct(ResponseFactory $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Store request in globals for actions
        $GLOBALS['request'] = $request;
        
        $path = $request->getUri()->getPath();
        
        // Simple routing
        if ($path === '/' || $path === '') {
            return require __DIR__ . '/../src/actions/HomeAction.php';
        }
        
        if ($path === '/dashboard') {
            return require __DIR__ . '/../src/actions/DashboardAction.php';
        }
        
        // 404
        return $this->responseFactory->json(['error' => 'Not Found'])
            ->withStatus(404);
    }
};

// Process request through middleware
$response = $inertiaMiddleware->process($request, $handler);

// Send response
http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}
echo $response->getBody();

