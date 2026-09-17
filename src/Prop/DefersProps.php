<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Prop;

/**
 * @internal
 */
trait DefersProps
{
    private bool $deferred = false;
    private ?string $deferGroup = null;

    public function defer(?string $group = null): static
    {
        $this->deferred = true;
        $this->deferGroup = $group;

        return $this;
    }

    public function shouldDefer(): bool
    {
        return $this->deferred;
    }

    public function group(): string
    {
        return $this->deferGroup ?? 'default';
    }
}
