<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Prop;

/**
 * @internal
 */
trait ResolvesCallables
{
    private function resolveCallable(mixed $value): mixed
    {
        return is_object($value) && is_callable($value) ? $value() : $value;
    }
}
