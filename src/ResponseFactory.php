<?php

declare(strict_types=1);

namespace Crenspire\Yii3Inertia;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Factory for creating Inertia responses (JSON for Inertia requests, HTML for regular requests)
 */
class ResponseFactory
{
    private ResponseFactoryInterface $responseFactory;
    private StreamFactoryInterface $streamFactory;
    private ?callable $viewRenderer = null;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ?callable $viewRenderer = null
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
        // Use view renderer if provided, otherwise use default template
        if ($this->viewRenderer !== null) {
            try {
                $html = call_user_func($this->viewRenderer, $rootView, $payload);
            } catch (\Exception $e) {
                throw new \RuntimeException("View renderer failed: {$e->getMessage()}", 0, $e);
            }
        } else {
            $html = $this->renderRootView($payload, $rootView);
        }
        
        $stream = $this->streamFactory->createStream($html);

        $response = $this->responseFactory->createResponse(200)
            ->withBody($stream)
            ->withHeader('Content-Type', 'text/html; charset=UTF-8');

        return $response;
    }

    /**
     * Render the root view template
     * 
     * @param array<string, mixed> $payload
     * @param string $rootView
     * @return string
     */
    private function renderRootView(array $payload, string $rootView): string
    {
        // Simple template rendering
        // In production, you'd use a proper view renderer
        try {
            $page = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (\JsonException $e) {
            throw new \RuntimeException("Failed to encode payload for root view: {$e->getMessage()}", 0, $e);
        }
        
        // Escape the JSON for HTML attribute
        $pageEscaped = htmlspecialchars($page, ENT_QUOTES, 'UTF-8');
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inertia.js App</title>
    <script type="module" crossorigin src="/dist/assets/index.js"></script>
    <link rel="stylesheet" crossorigin href="/dist/assets/index.css">
</head>
<body>
    <div id="app" data-page="{$pageEscaped}"></div>
</body>
</html>
HTML;
    }
}

