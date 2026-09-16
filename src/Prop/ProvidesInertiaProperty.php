<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Prop;

/**
 * An object that resolves its own prop value.
 */
interface ProvidesInertiaProperty
{
    public function toInertiaProperty(PropertyContext $context): mixed;
}
