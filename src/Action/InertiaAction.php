<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Action;

use Crenspire\Inertia\Inertia;
use Crenspire\Inertia\ResponseFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Base Inertia Action class
 *
 * Extend this class to create Inertia actions with automatic request handling,
 * response factory injection, and helpful helper methods.
 *
 * Usage:
 * ```php
 * class HomeAction extends InertiaAction
 * {
 *     public function __invoke(): ResponseInterface
 *     {
 *         return $this->render('Home', [
 *             'title' => 'Welcome',
 *         ]);
 *     }
 * }
 * ```
 */
abstract class InertiaAction
{
    protected ServerRequestInterface $request;
    protected ResponseFactory $responseFactory;
    protected ResponseFactoryInterface $psrResponseFactory;

    public function __construct(
        ServerRequestInterface $request,
        ?ResponseFactory $responseFactory = null,
        ?ContainerInterface $container = null,
        ?ResponseFactoryInterface $psrResponseFactory = null
    ) {
        $this->request = $request;

        // Set request in Inertia service automatically
        Inertia::setRequest($request);

        // Resolve ResponseFactory from DI container or use provided one
        $this->responseFactory = $responseFactory ?? $this->resolveResponseFactory($container);

        // Resolve PSR ResponseFactory for redirects
        if ($psrResponseFactory !== null) {
            $this->psrResponseFactory = $psrResponseFactory;
        } elseif ($container !== null && $container->has(ResponseFactoryInterface::class)) {
            $this->psrResponseFactory = $container->get(ResponseFactoryInterface::class);
        } else {
            throw new \RuntimeException(
                'ResponseFactoryInterface must be provided or available in DI container for redirects.'
            );
        }
    }

    /**
     * Render an Inertia page
     *
     * @param string $component Component name
     * @param array<string, mixed> $props Props to pass to component
     * @return ResponseInterface
     */
    protected function render(string $component, array $props = []): ResponseInterface
    {
        $payload = Inertia::render($component, $props);

        if (Inertia::isInertiaRequest($this->request)) {
            return $this->responseFactory->json($payload);
        }

        return $this->responseFactory->html($payload, Inertia::getRootView());
    }

    /**
     * Get the current request
     *
     * @return ServerRequestInterface
     */
    protected function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Get a request attribute
     *
     * @param string $name Attribute name
     * @param mixed $default Default value if attribute doesn't exist
     * @return mixed
     */
    protected function getAttribute(string $name, $default = null)
    {
        return $this->request->getAttribute($name, $default);
    }

    /**
     * Get a query parameter
     *
     * @param string $name Parameter name
     * @param mixed $default Default value if parameter doesn't exist
     * @return mixed
     */
    protected function getQueryParam(string $name, $default = null)
    {
        $params = $this->request->getQueryParams();
        return $params[$name] ?? $default;
    }

    /**
     * Get all query parameters
     *
     * @return array<string, mixed>
     */
    protected function getQueryParams(): array
    {
        return $this->request->getQueryParams();
    }

    /**
     * Get request method
     *
     * @return string
     */
    protected function getMethod(): string
    {
        return $this->request->getMethod();
    }

    /**
     * Check if request method is GET
     *
     * @return bool
     */
    protected function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    /**
     * Check if request method is POST
     *
     * @return bool
     */
    protected function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    /**
     * Check if request is an Inertia request
     *
     * @return bool
     */
    protected function isInertiaRequest(): bool
    {
        return Inertia::isInertiaRequest($this->request);
    }

    /**
     * Create an Inertia redirect response
     *
     * @param string $url URL to redirect to
     * @return ResponseInterface
     */
    protected function redirect(string $url): ResponseInterface
    {
        $location = Inertia::location($url);
        $response = $this->psrResponseFactory->createResponse($location['status']);

        if ($location['status'] === 409) {
            return $response->withHeader('X-Inertia-Location', $location['location']);
        }

        return $response->withHeader('Location', $location['location']);
    }

    /**
     * Get parsed request body
     *
     * @return array<string, mixed>
     */
    protected function getParsedBody(): array
    {
        $body = $this->request->getParsedBody();
        return is_array($body) ? $body : [];
    }

    /**
     * Get a request body parameter
     *
     * @param string $name Parameter name
     * @param mixed $default Default value if parameter doesn't exist
     * @return mixed
     */
    protected function getBodyParam(string $name, $default = null)
    {
        $body = $this->getParsedBody();
        return $body[$name] ?? $default;
    }

    /**
     * Get the ResponseFactory instance
     *
     * @return ResponseFactory
     */
    protected function getResponseFactory(): ResponseFactory
    {
        return $this->responseFactory;
    }

    /**
     * Resolve ResponseFactory from DI container
     *
     * @param ContainerInterface|null $container
     * @return ResponseFactory
     */
    private function resolveResponseFactory(?ContainerInterface $container): ResponseFactory
    {
        if ($container !== null && $container->has(ResponseFactory::class)) {
            try {
                return $container->get(ResponseFactory::class);
            } catch (\Psr\Container\ContainerExceptionInterface $e) {
                // Fall through to exception
            }
        }

        throw new \RuntimeException(
            'ResponseFactory must be provided or available in DI container. ' .
            'Options: 1) Pass ResponseFactory to constructor, ' .
            '2) Pass ContainerInterface to constructor (with ConfigProvider configured), ' .
            '3) Include ConfigProvider in your Yii3 config.'
        );
    }
}

