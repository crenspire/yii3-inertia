<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Prop;

/**
 * A prop whose resolution failure is logged and reported to the client instead of failing the response.
 */
interface Rescuable
{
    public function shouldRescue(): bool;
}
