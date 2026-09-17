<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Prop;

use Psr\Http\Message\ServerRequestInterface;

final class PropertyContext
{
    /**
     * @param string $key Dot-notation path of the prop.
     * @param array<array-key, mixed> $props Sibling props at the same level.
     */
    public function __construct(
        public readonly string $key,
        public readonly array $props,
        public readonly ServerRequestInterface $request,
    ) {
    }
}
