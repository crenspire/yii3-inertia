<?php

declare(strict_types=1);

namespace Crenspire\Yii3Inertia;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Trait for controllers to easily render Inertia pages
 * 
 * Usage:
 * ```php
 * class HomeController {
 *     use ControllerTrait;
 *     
 *     public function index(ServerRequestInterface $request): ResponseInterface
 *     {
 *         return $this->inertiaRender('Home', ['title' => 'Welcome']);
 *     }
 * }
 * ```
 */
trait ControllerTrait
{
    /**
     * Render an Inertia page
     * 
     * @param string $component Component name
     * @param array<string, mixed> $props Props
     * @param ServerRequestInterface $request Request object
     * @return ResponseInterface
     */
    protected function inertiaRender(
        string $component,
        array $props = [],
        ?ServerRequestInterface $request = null
    ): ResponseInterface {
        if ($request === null && method_exists($this, 'getRequest')) {
            $request = $this->getRequest();
        }

        if ($request === null) {
            throw new \RuntimeException('Request object is required');
        }

        // Set request in Inertia service
        Inertia::setRequest($request);

        // Get payload
        $payload = Inertia::render($component, $props);

        // Store payload in request attribute for middleware
        $request = $request->withAttribute('inertia_payload', $payload);

        // Create response using ResponseFactory
        // This would typically be injected via DI
        $responseFactory = $this->getResponseFactory();
        $isInertiaRequest = Inertia::isInertiaRequest($request);

        if ($isInertiaRequest) {
            return $responseFactory->json($payload);
        }

        return $responseFactory->html($payload, Inertia::getRootView());
    }

    /**
     * Get the response factory instance
     * 
     * This method should be overridden or the factory should be injected via DI
     * 
     * @return ResponseFactory
     */
    protected function getResponseFactory(): ResponseFactory
    {
        // This should be injected via DI in production
        // For now, throw an exception to encourage proper DI setup
        throw new \RuntimeException(
            'ResponseFactory must be injected via DI. Override getResponseFactory() or inject ResponseFactory in your controller.'
        );
    }
}

