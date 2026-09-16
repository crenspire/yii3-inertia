<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Prop;

use Closure;

/**
 * A prop that is only resolved when a partial reload explicitly asks for it.
 */
final class OptionalProp implements IgnoreFirstLoad, Onceable
{
    use ResolvesCallables;
    use ResolvesOnce;

    private readonly Closure $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback(...);
    }

    public function __invoke(): mixed
    {
        return $this->resolveCallable($this->callback);
    }
}
