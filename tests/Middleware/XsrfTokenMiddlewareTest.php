<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Tests\Middleware;

use Crenspire\Inertia\Middleware\XsrfTokenMiddleware;
use Crenspire\Inertia\Tests\Support\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Csrf\StubCsrfToken;

final class XsrfTokenMiddlewareTest extends TestCase
{
    public function testCopiesClientHeaderAndSetsCookie(): void
    {
        $handler = $this->handler();

        $response = (new XsrfTokenMiddleware(new StubCsrfToken('token/1')))->process(
            $this->request('POST', 'http://example.com/', ['X-XSRF-TOKEN' => 'client']),
            $handler,
        );

        $this->assertSame('client', $handler->request?->getHeaderLine('X-CSRF-Token'));
        $this->assertSame(['XSRF-TOKEN=token%2F1; Path=/; SameSite=Lax'], $response->getHeader('Set-Cookie'));
    }

    public function testExistingCsrfHeaderIsKeptAndSecureCookieOnHttps(): void
    {
        $handler = $this->handler();

        $response = (new XsrfTokenMiddleware(new StubCsrfToken('t')))->process(
            $this->request('POST', 'https://example.com/', ['X-XSRF-TOKEN' => 'client', 'X-CSRF-Token' => 'form']),
            $handler,
        );

        $this->assertSame('form', $handler->request?->getHeaderLine('X-CSRF-Token'));
        $this->assertStringEndsWith('; Secure', $response->getHeaderLine('Set-Cookie'));
    }

    /**
     * @return RequestHandlerInterface&object{request: ?ServerRequestInterface}
     */
    private function handler(): RequestHandlerInterface
    {
        return new class ($this->factory->createResponse()) implements RequestHandlerInterface {
            public ?ServerRequestInterface $request = null;

            public function __construct(private readonly ResponseInterface $response)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->request = $request;

                return $this->response;
            }
        };
    }
}
