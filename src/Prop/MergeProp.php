<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Prop;

/**
 * A prop that the client merges with the value it already has during partial reloads.
 */
final class MergeProp implements Mergeable, Onceable
{
    use MergesProps;
    use ResolvesCallables;
    use ResolvesOnce;

    public function __construct(private readonly mixed $value)
    {
        $this->merge = true;
    }

    public function __invoke(): mixed
    {
        return $this->resolveCallable($this->value);
    }
}
