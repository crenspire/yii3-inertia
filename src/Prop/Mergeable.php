<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Prop;

/**
 * A prop that the client merges with its existing value instead of replacing it.
 */
interface Mergeable
{
    public function merge(): static;

    public function shouldMerge(): bool;

    public function shouldDeepMerge(): bool;

    /**
     * @return list<string>
     */
    public function matchesOn(): array;

    public function appendsAtRoot(): bool;

    public function prependsAtRoot(): bool;

    /**
     * @return list<string>
     */
    public function appendsAtPaths(): array;

    /**
     * @return list<string>
     */
    public function prependsAtPaths(): array;
}
