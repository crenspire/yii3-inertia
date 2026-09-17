<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Tests\Support;

use Crenspire\Inertia\Flash\FlashStoreInterface;
use Crenspire\Inertia\Header;
use Crenspire\Inertia\Inertia;
use Crenspire\Inertia\Page;
use Crenspire\Inertia\Ssr\GatewayInterface;
use Crenspire\Inertia\Version\StaticVersion;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected Psr17Factory $factory;
    protected RecordingRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new Psr17Factory();
        $this->renderer = new RecordingRenderer();
    }

    /**
     * @param array<array-key, mixed> $sharedProps
     */
    protected function createInertia(
        string $version = 'v1',
        ?FlashStoreInterface $flashStore = null,
        array $sharedProps = [],
        ?GatewayInterface $ssrGateway = null,
        ?LoggerInterface $logger = null,
    ): Inertia {
        return new Inertia(
            responseFactory: $this->factory,
            streamFactory: $this->factory,
            rootViewRenderer: $this->renderer,
            version: new StaticVersion($version),
            flashStore: $flashStore,
            ssrGateway: $ssrGateway,
            logger: $logger,
            sharedProps: $sharedProps,
        );
    }

    /**
     * @param array<string, string> $headers
     */
    protected function request(string $method = 'GET', string $uri = 'https://example.com/', array $headers = []): ServerRequest
    {
        return new ServerRequest($method, $uri, $headers);
    }

    /**
     * @param array<string, string> $headers
     */
    protected function inertiaRequest(string $method = 'GET', string $uri = 'https://example.com/', array $headers = []): ServerRequest
    {
        return $this->request($method, $uri, [Header::INERTIA => 'true', Header::VERSION => 'v1', ...$headers]);
    }

    /**
     * @param array<string, string> $headers
     */
    protected function partialRequest(string $component, array $headers = []): ServerRequest
    {
        return $this->inertiaRequest(headers: [Header::PARTIAL_COMPONENT => $component, ...$headers]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function pageArray(Page $page): array
    {
        return json_decode(json_encode($page, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeJson(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }
}
