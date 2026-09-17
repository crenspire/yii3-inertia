<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Prop;

use Closure;

/**
 * A prop that is resolved once and then remembered by the client across visits.
 */
final class OnceProp implements Onceable
{
    use ResolvesCallables;
    use ResolvesOnce;

    private readonly Closure $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback(...);
        $this->once = true;
    }

    public function __invoke(): mixed
    {
        return $this->resolveCallable($this->callback);
    }
}
