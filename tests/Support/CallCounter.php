<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Tests\Support;

final class CallCounter
{
    public int $calls = 0;

    public function __construct(private readonly mixed $value = 'value')
    {
    }

    public function __invoke(): mixed
    {
        $this->calls++;

        return $this->value;
    }
}
