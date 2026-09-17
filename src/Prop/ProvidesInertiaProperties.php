<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Prop;

/**
 * An object that contributes several props at once. Pass it in the props array under a numeric key.
 */
interface ProvidesInertiaProperties
{
    /**
     * @return iterable<string, mixed>
     */
    public function toInertiaProperties(RenderContext $context): iterable;
}
