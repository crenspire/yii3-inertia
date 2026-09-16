<?php

declare(strict_types=1);

namespace App\Web\Shared;

use Crenspire\Inertia\Inertia;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Shares request-specific props with every page. Replace the value with your current user.
 */
final readonly class ShareAuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = Inertia::share($request, 'auth', [
            'user' => null,
        ]);

        return $handler->handle($request);
    }
}
