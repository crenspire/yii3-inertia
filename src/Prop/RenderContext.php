<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Prop;

use Psr\Http\Message\ServerRequestInterface;

final class RenderContext
{
    public function __construct(
        public readonly string $component,
        public readonly ServerRequestInterface $request,
    ) {
    }
}
