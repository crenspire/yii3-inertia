<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Version;

use Closure;

final class CallbackVersion implements VersionProviderInterface
{
    private readonly Closure $callback;

    /**
     * @param callable(): (string|int|null) $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback(...);
    }

    public function getVersion(): string
    {
        return (string) ($this->callback)();
    }
}
