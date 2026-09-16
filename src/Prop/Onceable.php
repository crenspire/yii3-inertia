<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Prop;

use BackedEnum;
use DateInterval;
use DateTimeInterface;
use UnitEnum;

/**
 * A prop that the client remembers and does not request again until it expires.
 */
interface Onceable
{
    public function once(bool $value = true): static;

    public function shouldResolveOnce(): bool;

    public function shouldBeRefreshed(): bool;

    public function getKey(): ?string;

    public function as(BackedEnum|UnitEnum|string $key): static;

    public function until(DateTimeInterface|DateInterval|int $delay): static;

    /**
     * Expiration as a Unix timestamp in milliseconds, or null if the prop never expires.
     */
    public function expiresAt(): ?int;
}
