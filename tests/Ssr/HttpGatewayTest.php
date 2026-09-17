<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Tests\Ssr;

use Crenspire\Inertia\Page;
use Crenspire\Inertia\Ssr\HttpGateway;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Stringable;

final class HttpGatewayTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    public function testSendsPageAndReturnsRenderedHtml(): void
    {
        $client = $this->client(fn (): ResponseInterface => $this->json(200, '{"head":["<title>a</title>","<meta>"],"body":"<div>b</div>"}'));

        $response = $this->gateway($client)->dispatch(new Page('Home', ['x' => 1], '/', 'v'), new ServerRequest('GET', '/'));

        $this->assertNotNull($response);
        $this->assertSame("<title>a</title>\n<meta>", $response->head);
        $this->assertSame('<div>b</div>', $response->body);
        $this->assertSame('POST', $client->request?->getMethod());
        $this->assertSame('http://ssr.test/render', (string) $client->request?->getUri());
        $this->assertSame('{"component":"Home","props":{"x":1},"url":"\/","version":"v"}', (string) $client->request?->getBody());
    }

    public function testExceptedPathIsNotRendered(): void
    {
        $client = $this->client(fn (): ResponseInterface => $this->json(200, '{}'));

        $response = $this->gateway($client, except: ['/admin'])->dispatch(new Page('Home', [], '/', ''), new ServerRequest('GET', '/admin/users'));

        $this->assertNull($response);
        $this->assertNull($client->request);
    }

    public function testFailuresFallBackToClientRendering(): void
    {
        $logger = new class () extends AbstractLogger {
            public int $count = 0;

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->count++;
            }
        };

        $page = new Page('Home', [], '/', '');
        $request = new ServerRequest('GET', '/');

        $this->assertNull($this->gateway($this->client(fn (): ResponseInterface => $this->json(500, 'err')), logger: $logger)->dispatch($page, $request));
        $this->assertNull($this->gateway($this->client(fn (): ResponseInterface => $this->json(200, 'not json')), logger: $logger)->dispatch($page, $request));
        $this->assertNull($this->gateway($this->client(static fn () => throw new class ('down') extends RuntimeException implements ClientExceptionInterface {}), logger: $logger)->dispatch($page, $request));
        $this->assertSame(3, $logger->count);
    }

    public function testThrowOnError(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP 500');

        $this->gateway($this->client(fn (): ResponseInterface => $this->json(500, 'err')), throwOnError: true)
            ->dispatch(new Page('Home', [], '/', ''), new ServerRequest('GET', '/'));
    }

    /**
     * @param list<string> $except
     */
    private function gateway(ClientInterface $client, array $except = [], bool $throwOnError = false, ?AbstractLogger $logger = null): HttpGateway
    {
        return new HttpGateway($client, $this->factory, $this->factory, 'http://ssr.test/render', $except, $throwOnError, $logger);
    }

    private function json(int $status, string $body): ResponseInterface
    {
        return $this->factory->createResponse($status)->withBody($this->factory->createStream($body));
    }

    /**
     * @param callable(): ResponseInterface $respond
     * @return ClientInterface&object{request: ?RequestInterface}
     */
    private function client(callable $respond): ClientInterface
    {
        return new class ($respond) implements ClientInterface {
            public ?RequestInterface $request = null;

            /** @var callable(): ResponseInterface */
            private $respond;

            public function __construct(callable $respond)
            {
                $this->respond = $respond;
            }

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->request = $request;

                return ($this->respond)();
            }
        };
    }
}
