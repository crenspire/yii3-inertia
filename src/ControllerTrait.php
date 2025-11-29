<?php

declare(strict_types=1);

namespace Crenspire\Inertia;

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
     * This method tries to resolve ResponseFactory from DI container if available,
     * otherwise it should be overridden or injected via constructor.
     * 
     * @return ResponseFactory
     */
    protected function getResponseFactory(): ResponseFactory
    {
        // Try to resolve from DI container if available
        if (method_exists($this, 'getContainer')) {
            $container = $this->getContainer();
            if ($container instanceof \Psr\Container\ContainerInterface) {
                try {
                    return $container->get(ResponseFactory::class);
                } catch (\Psr\Container\NotFoundExceptionInterface | \Psr\Container\ContainerExceptionInterface $e) {
                    // Container doesn't have ResponseFactory, fall through
                }
            }
        }
        
        // Try to get from property if injected via constructor
        if (property_exists($this, 'responseFactory') && $this->responseFactory instanceof ResponseFactory) {
            return $this->responseFactory;
        }
        
        // Last resort: throw exception with helpful message
        throw new \RuntimeException(
            'ResponseFactory must be injected via DI or constructor. ' .
            'Options: 1) Include ConfigProvider in your Yii3 config, ' .
            '2) Inject ResponseFactory in controller constructor, ' .
            '3) Override getResponseFactory() method.'
        );
    }
}

