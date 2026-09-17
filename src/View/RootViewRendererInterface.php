<?php

declare(strict_types=1);

namespace Crenspire\Inertia\View;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Renders the HTML document for the first, non-Inertia visit.
 */
interface RootViewRendererInterface
{
    public function render(InertiaView $inertia, ServerRequestInterface $request): string;
}
