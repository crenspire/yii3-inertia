<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Prop;

/**
 * A prop that is included in every response, including partial reloads that did not ask for it.
 */
final class AlwaysProp
{
    use ResolvesCallables;

    public function __construct(private readonly mixed $value)
    {
    }

    public function __invoke(): mixed
    {
        return $this->resolveCallable($this->value);
    }
}
