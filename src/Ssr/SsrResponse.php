<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Ssr;

final class SsrResponse
{
    public function __construct(
        public readonly string $head,
        public readonly string $body,
    ) {
    }
}
