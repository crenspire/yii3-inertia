<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Flash;

/**
 * In-memory flash store for tests and applications without sessions.
 */
final class ArrayFlashStore implements FlashStoreInterface
{
    /** @var array<string, mixed> */
    private array $values = [];

    public function set(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    public function get(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }

    public function pull(string $key): mixed
    {
        $value = $this->values[$key] ?? null;
        unset($this->values[$key]);

        return $value;
    }
}
