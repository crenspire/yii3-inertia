<?php

declare(strict_types=1);

namespace Crenspire\Yii3Inertia;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Global helper function for Inertia
 * 
 * Provides the same API as Inertia::render() but returns response directly.
 * Note: This requires the request to be set via InertiaMiddleware.
 * 
 * @param string $component The Inertia component name
 * @param array<string, mixed> $props Props to pass to the component
 * @param ServerRequestInterface|null $request Optional request object
 * @return array<string, mixed> Payload array (use with ResponseFactory)
 */
function inertia(string $component, array $props = [], ?ServerRequestInterface $request = null): array
{
    if ($request !== null) {
        Inertia::setRequest($request);
    }
    
    return Inertia::render($component, $props);
}

