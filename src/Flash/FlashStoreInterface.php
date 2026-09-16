<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Flash;

/**
 * Stores values between requests, typically in the session.
 */
interface FlashStoreInterface
{
    public function set(string $key, mixed $value): void;

    public function get(string $key): mixed;

    /**
     * Return a value and remove it from the store.
     */
    public function pull(string $key): mixed;
}
