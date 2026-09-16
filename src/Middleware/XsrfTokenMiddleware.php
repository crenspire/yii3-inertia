<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Csrf\CsrfTokenInterface;

/**
 * Connects yiisoft/csrf with the CSRF handling of the Inertia.js client.
 *
 * The client reads the token from the `XSRF-TOKEN` cookie and sends it back in the `X-XSRF-TOKEN` header.
 * This middleware sets that cookie and copies the header to the one `CsrfTokenMiddleware` checks.
 * Place it after `SessionMiddleware` and before `CsrfTokenMiddleware`.
 */
final class XsrfTokenMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly CsrfTokenInterface $token,
        private readonly string $csrfHeaderName = 'X-CSRF-Token',
        private readonly string $cookieName = 'XSRF-TOKEN',
        private readonly string $clientHeaderName = 'X-XSRF-TOKEN',
        private readonly string $cookiePath = '/',
        private readonly string $sameSite = 'Lax',
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $clientToken = $request->getHeaderLine($this->clientHeaderName);
        if ($clientToken !== '' && !$request->hasHeader($this->csrfHeaderName)) {
            $request = $request->withHeader($this->csrfHeaderName, $clientToken);
        }

        $response = $handler->handle($request);

        $cookie = sprintf(
            '%s=%s; Path=%s; SameSite=%s',
            $this->cookieName,
            rawurlencode($this->token->getValue()),
            $this->cookiePath,
            $this->sameSite,
        );
        if ($request->getUri()->getScheme() === 'https') {
            $cookie .= '; Secure';
        }

        return $response->withAddedHeader('Set-Cookie', $cookie);
    }
}
