<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Ssr;

use Crenspire\Inertia\Page;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Renders a page on the server. Returning null falls back to client-side rendering.
 */
interface GatewayInterface
{
    public function dispatch(Page $page, ServerRequestInterface $request): ?SsrResponse;
}
