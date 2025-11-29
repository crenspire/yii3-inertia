<?php

declare(strict_types=1);

namespace Crenspire\Inertia;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Crenspire\Inertia\ViewRenderer;

/**
 * Factory for creating Inertia responses (JSON for Inertia requests, HTML for regular requests)
 */
class ResponseFactory
{
    private ResponseFactoryInterface $responseFactory;
    private StreamFactoryInterface $streamFactory;
    /** @var callable */
    private $viewRenderer;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        callable $viewRenderer
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->viewRenderer = $viewRenderer;
    }

    /**
     * Create a JSON response for Inertia requests
     * 
     * @param array<string, mixed> $payload Inertia payload
     * @return ResponseInterface
     */
    public function json(array $payload): ResponseInterface
    {
        try {
            $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (\JsonException $e) {
            // Fallback to error response if JSON encoding fails
            $errorPayload = ['error' => 'Failed to encode response'];
            $json = json_encode($errorPayload, JSON_THROW_ON_ERROR);
            $stream = $this->streamFactory->createStream($json);
            return $this->responseFactory->createResponse(500)
                ->withBody($stream)
                ->withHeader('Content-Type', 'application/json');
        }
        
        $stream = $this->streamFactory->createStream($json);

        $response = $this->responseFactory->createResponse(200)
            ->withBody($stream)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('X-Inertia', 'true')
            ->withHeader('Vary', 'Accept');

        return $response;
    }

    /**
     * Create an HTML response with the root view
     * 
     * @param array<string, mixed> $payload Inertia payload
     * @param string $rootView Root view template path or name
     * @return ResponseInterface
     */
    public function html(array $payload, string $rootView): ResponseInterface
    {
        // View renderer is required (enforced in constructor) - no fallback HTML template
        try {
            $html = call_user_func($this->viewRenderer, $rootView, $payload);
        } catch (\Exception $e) {
            throw new \RuntimeException("View renderer failed: {$e->getMessage()}", 0, $e);
        }
        
        $stream = $this->streamFactory->createStream($html);

        $response = $this->responseFactory->createResponse(200)
            ->withBody($stream)
            ->withHeader('Content-Type', 'text/html; charset=UTF-8');

        return $response;
    }

    /**
     * Create a view renderer callback from Yii3 View instance
     * 
     * Helper method to create a view renderer callback for use with ResponseFactory.
     * This is automatically handled by ConfigProvider, but can be used manually if needed.
     * 
     * @param object $view Yii3 ViewInterface instance
     * @return callable View renderer callback
     */
    public static function createViewRenderer(object $view): callable
    {
        return static function (string $viewName, array $payload) use ($view): string {
            return ViewRenderer::render($view, $viewName, $payload);
        };
    }

}

