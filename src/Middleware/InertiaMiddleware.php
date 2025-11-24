<?php

declare(strict_types=1);

namespace Crenspire\Yii3Inertia\Middleware;

use Crenspire\Yii3Inertia\Inertia;
use Crenspire\Yii3Inertia\ResponseFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 Middleware for Inertia.js
 * 
 * This middleware should be registered in your application's middleware stack.
 * It handles Inertia requests and sets up the Inertia service with the current request.
 */
class InertiaMiddleware implements MiddlewareInterface
{
    private ResponseFactory $responseFactory;
    private ResponseFactoryInterface $psrResponseFactory;

    public function __construct(
        ResponseFactory $responseFactory,
        ResponseFactoryInterface $psrResponseFactory
    ) {
        $this->responseFactory = $responseFactory;
        $this->psrResponseFactory = $psrResponseFactory;
    }

    /**
     * Process an incoming server request and return a response
     * 
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Set the request in Inertia service
        Inertia::setRequest($request);

        // Check for version mismatch before processing
        if (Inertia::isInertiaRequest($request) && $this->hasVersionMismatch($request)) {
            $location = Inertia::location($request->getUri()->__toString());
            $response = $this->psrResponseFactory->createResponse($location['status']);
            if ($location['status'] === 409) {
                $response = $response->withHeader('X-Inertia-Location', $location['location']);
            } else {
                $response = $response->withHeader('Location', $location['location']);
            }
            return $response;
        }

        // Process the request
        $response = $handler->handle($request);
        
        // Check if controller set an Inertia payload in request attribute
        // This allows actions to set payload and let middleware handle response
        $payload = $request->getAttribute('inertia_payload');
        if ($payload !== null) {
            if (Inertia::isInertiaRequest($request)) {
                return $this->responseFactory->json($payload);
            }
            return $this->responseFactory->html($payload, Inertia::getRootView());
        }
        
        // If response is already set by action (direct return), use it
        // This supports both patterns: middleware handling or action handling
        return $response;
    }

    /**
     * Check if there's a version mismatch
     * 
     * @param ServerRequestInterface $request
     * @return bool
     */
    private function hasVersionMismatch(ServerRequestInterface $request): bool
    {
        if (!$request->hasHeader('X-Inertia-Version')) {
            return false;
        }

        $requestVersion = $request->getHeaderLine('X-Inertia-Version');
        $currentVersion = Inertia::version();

        return $requestVersion !== (string) $currentVersion;
    }
}

