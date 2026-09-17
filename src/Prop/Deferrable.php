<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Prop;

/**
 * A prop that can be left out of the initial response and loaded by the client afterwards.
 */
interface Deferrable
{
    public function shouldDefer(): bool;

    /**
     * The group of deferred props that the client loads in a single request.
     */
    public function group(): string;
}
