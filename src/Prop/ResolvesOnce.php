<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Prop;

use BackedEnum;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use UnitEnum;

/**
 * @internal
 */
trait ResolvesOnce
{
    private bool $once = false;
    private bool $refresh = false;
    private ?int $ttl = null;
    private ?string $key = null;

    public function once(bool $value = true, BackedEnum|UnitEnum|string|null $as = null, DateTimeInterface|DateInterval|int|null $until = null): static
    {
        $this->once = $value;

        if ($as !== null) {
            $this->as($as);
        }

        if ($until !== null) {
            $this->until($until);
        }

        return $this;
    }

    public function shouldResolveOnce(): bool
    {
        return $this->once;
    }

    public function shouldBeRefreshed(): bool
    {
        return $this->refresh;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * Share the remembered value between pages under a custom key.
     */
    public function as(BackedEnum|UnitEnum|string $key): static
    {
        $this->key = match (true) {
            $key instanceof BackedEnum => (string) $key->value,
            $key instanceof UnitEnum => $key->name,
            default => $key,
        };

        return $this;
    }

    /**
     * Force the value to be sent again even if the client already has it.
     */
    public function fresh(bool $value = true): static
    {
        $this->refresh = $value;

        return $this;
    }

    /**
     * Expire the remembered value after a number of seconds, an interval, or at a point in time.
     */
    public function until(DateTimeInterface|DateInterval|int $delay): static
    {
        $now = time();

        $this->ttl = match (true) {
            $delay instanceof DateTimeInterface => max(0, $delay->getTimestamp() - $now),
            $delay instanceof DateInterval => max(0, (new DateTimeImmutable())->add($delay)->getTimestamp() - $now),
            default => max(0, $delay),
        };

        return $this;
    }

    public function expiresAt(): ?int
    {
        return $this->ttl === null ? null : (time() + $this->ttl) * 1000;
    }
}
