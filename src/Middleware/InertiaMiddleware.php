<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Middleware;

use Crenspire\Inertia\Header;
use Crenspire\Inertia\Inertia;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Applies the Inertia.js protocol rules to requests and responses.
 *
 * - Answers GET visits with an outdated asset version with 409 so the client reloads the page.
 * - Adds `Vary: X-Inertia` so browsers do not serve cached JSON for a full page load.
 * - Turns 302 redirects after PUT, PATCH and DELETE into 303 so the browser follows them with GET.
 * - Answers redirects to a URL with a fragment with 409 and X-Inertia-Redirect so the fragment is kept.
 * - Redirects back when an Inertia visit gets an empty 200 response.
 */
final class InertiaMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Inertia $inertia,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!Inertia::isInertiaRequest($request)) {
            return $this->addVary($handler->handle($request));
        }

        $method = strtoupper($request->getMethod());
        $version = $this->inertia->getVersion();

        if ($method === 'GET' && $request->getHeaderLine(Header::VERSION) !== $version) {
            $response = $this->inertia
                ->location($request, (string) $request->getUri())
                ->withHeader(Header::VERSION, $version);

            return $this->addVary($response);
        }

        $response = $handler->handle($request);

        if ($response->getStatusCode() === 200 && $response->getBody()->getSize() === 0) {
            $response = $this->inertia->back($request);
        }

        if ($response->getStatusCode() === 302 && in_array($method, ['PUT', 'PATCH', 'DELETE'], true)) {
            $response = $response->withStatus(303);
        }

        $location = $response->getHeaderLine('Location');
        if (
            $this->isRedirect($response)
            && str_contains($location, '#')
            && $request->getHeaderLine(Header::PURPOSE) !== 'prefetch'
        ) {
            $response = $this->responseFactory->createResponse(409)->withHeader(Header::REDIRECT, $location);
        }

        return $this->addVary($response);
    }

    private function isRedirect(ResponseInterface $response): bool
    {
        return in_array($response->getStatusCode(), [301, 302, 303, 307, 308], true)
            && $response->hasHeader('Location');
    }

    private function addVary(ResponseInterface $response): ResponseInterface
    {
        foreach ($response->getHeader('Vary') as $line) {
            foreach (explode(',', $line) as $value) {
                $value = trim($value);
                if ($value === '*' || strcasecmp($value, Header::INERTIA) === 0) {
                    return $response;
                }
            }
        }

        return $response->withAddedHeader('Vary', Header::INERTIA);
    }
}
