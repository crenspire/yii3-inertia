<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Tests\Middleware;

use Crenspire\Inertia\Header;
use Crenspire\Inertia\Middleware\InertiaMiddleware;
use Crenspire\Inertia\Tests\Support\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class InertiaMiddlewareTest extends TestCase
{
    public function testRegularRequestPassesThroughWithVary(): void
    {
        $handler = $this->handler(fn (): ResponseInterface => $this->factory->createResponse(302)->withHeader('Location', '/x#y'));

        $response = $this->middleware()->process($this->request('PUT'), $handler);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(Header::INERTIA, $response->getHeaderLine('Vary'));
        $this->assertSame(1, $handler->calls);
    }

    public function testVersionMismatchOnGetForcesFullReload(): void
    {
        $handler = $this->handler();

        $response = $this->middleware('v2')->process(
            $this->inertiaRequest(uri: 'https://example.com/users?page=2'),
            $handler,
        );

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('https://example.com/users?page=2', $response->getHeaderLine(Header::LOCATION));
        $this->assertSame('v2', $response->getHeaderLine(Header::VERSION));
        $this->assertSame(Header::INERTIA, $response->getHeaderLine('Vary'));
        $this->assertSame(0, $handler->calls);
    }

    public function testVersionMismatchIsIgnoredForNonGetRequests(): void
    {
        $handler = $this->handler();

        $response = $this->middleware('v2')->process($this->inertiaRequest('POST'), $handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $handler->calls);
    }

    public function testMatchingVersionPassesThrough(): void
    {
        $response = $this->middleware()->process($this->inertiaRequest(), $this->handler());

        $this->assertSame('ok', (string) $response->getBody());
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function redirectMethods(): iterable
    {
        yield 'POST' => ['POST', 302];
        yield 'PUT' => ['PUT', 303];
        yield 'PATCH' => ['PATCH', 303];
        yield 'DELETE' => ['DELETE', 303];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('redirectMethods')]
    public function testRedirectStatusAfterMutatingRequests(string $method, int $expected): void
    {
        $handler = $this->handler(fn (): ResponseInterface => $this->factory->createResponse(302)->withHeader('Location', '/users'));

        $response = $this->middleware()->process($this->inertiaRequest($method), $handler);

        $this->assertSame($expected, $response->getStatusCode());
        $this->assertSame('/users', $response->getHeaderLine('Location'));
    }

    public function testRedirectWithFragmentUsesInertiaRedirect(): void
    {
        $handler = $this->handler(fn (): ResponseInterface => $this->factory->createResponse(302)->withHeader('Location', '/docs#install'));

        $response = $this->middleware()->process($this->inertiaRequest('PUT'), $handler);

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('/docs#install', $response->getHeaderLine(Header::REDIRECT));
        $this->assertSame(Header::INERTIA, $response->getHeaderLine('Vary'));
    }

    public function testPrefetchKeepsFragmentRedirect(): void
    {
        $handler = $this->handler(fn (): ResponseInterface => $this->factory->createResponse(302)->withHeader('Location', '/docs#install'));

        $response = $this->middleware()->process($this->inertiaRequest(headers: [Header::PURPOSE => 'prefetch']), $handler);

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testEmptyResponseRedirectsBack(): void
    {
        $handler = $this->handler(fn (): ResponseInterface => $this->factory->createResponse());

        $response = $this->middleware()->process($this->inertiaRequest('PATCH', headers: ['Referer' => '/form']), $handler);

        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('/form', $response->getHeaderLine('Location'));
    }

    public function testExistingVaryIsExtended(): void
    {
        $handler = $this->handler(fn (): ResponseInterface => $this->factory->createResponse()->withHeader('Vary', 'Accept-Encoding'));
        $response = $this->middleware()->process($this->request(), $handler);
        $this->assertSame(['Accept-Encoding', Header::INERTIA], $response->getHeader('Vary'));

        $handler = $this->handler(fn (): ResponseInterface => $this->factory->createResponse()->withHeader('Vary', 'accept, x-inertia'));
        $response = $this->middleware()->process($this->request(), $handler);
        $this->assertSame(['accept, x-inertia'], $response->getHeader('Vary'));
    }

    public function testRenderedInertiaResponseHasSingleVary(): void
    {
        $inertia = $this->createInertia();
        $handler = $this->handler(static fn (ServerRequestInterface $request): ResponseInterface => $inertia->render($request, 'Home'));

        $response = $this->middleware()->process($this->inertiaRequest(), $handler);

        $this->assertSame([Header::INERTIA], $response->getHeader('Vary'));
    }

    private function middleware(string $version = 'v1'): InertiaMiddleware
    {
        return new InertiaMiddleware($this->createInertia($version), $this->factory);
    }

    /**
     * @param (callable(ServerRequestInterface): ResponseInterface)|null $callback
     */
    private function handler(?callable $callback = null): RequestHandlerInterface
    {
        $callback ??= fn (): ResponseInterface => $this->factory->createResponse()->withBody($this->factory->createStream('ok'));

        return new class ($callback) implements RequestHandlerInterface {
            public int $calls = 0;

            /** @var callable(ServerRequestInterface): ResponseInterface */
            private $callback;

            public function __construct(callable $callback)
            {
                $this->callback = $callback;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->calls++;

                return ($this->callback)($request);
            }
        };
    }
}
