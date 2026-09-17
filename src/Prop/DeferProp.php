<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Prop;

use Closure;

/**
 * A prop that is left out of the initial page and loaded by the client right after it renders.
 */
final class DeferProp implements Deferrable, IgnoreFirstLoad, Mergeable, Onceable, Rescuable
{
    use DefersProps;
    use MergesProps;
    use ResolvesCallables;
    use ResolvesOnce;

    private readonly Closure $callback;

    public function __construct(callable $callback, ?string $group = null, private readonly bool $rescue = false)
    {
        $this->callback = $callback(...);
        $this->defer($group);
    }

    public function __invoke(): mixed
    {
        return $this->resolveCallable($this->callback);
    }

    public function shouldRescue(): bool
    {
        return $this->rescue;
    }
}
