<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Ssr;

use Crenspire\Inertia\Page;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Sends the page to the Inertia SSR server (`node bootstrap/ssr/ssr.mjs`) over HTTP.
 *
 * Failures are logged and the page falls back to client-side rendering unless `$throwOnError` is set.
 */
final class HttpGateway implements GatewayInterface
{
    /**
     * @param list<string> $except Request path prefixes that are never rendered on the server.
     */
    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $url = 'http://127.0.0.1:13714/render',
        private readonly array $except = [],
        private readonly bool $throwOnError = false,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function dispatch(Page $page, ServerRequestInterface $request): ?SsrResponse
    {
        $path = $request->getUri()->getPath();
        foreach ($this->except as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return null;
            }
        }

        try {
            $ssrRequest = $this->requestFactory->createRequest('POST', $this->url)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream(json_encode($page, JSON_THROW_ON_ERROR)));

            $response = $this->client->sendRequest($ssrRequest);
            $contents = (string) $response->getBody();

            if ($response->getStatusCode() >= 400) {
                throw new RuntimeException(
                    sprintf('The SSR server responded with HTTP %d: %s', $response->getStatusCode(), $contents)
                );
            }

            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                return null;
            }

            $head = $data['head'] ?? [];

            return new SsrResponse(
                is_array($head) ? implode("\n", $head) : (string) $head,
                (string) ($data['body'] ?? ''),
            );
        } catch (ClientExceptionInterface | JsonException | RuntimeException $e) {
            if ($this->throwOnError) {
                throw $e;
            }

            $this->logger?->warning('Inertia server-side rendering failed: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
                'component' => $page->component,
            ]);

            return null;
        }
    }
}
