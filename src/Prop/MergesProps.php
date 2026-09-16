<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Prop;

/**
 * @internal
 */
trait MergesProps
{
    private bool $merge = false;
    private bool $deepMerge = false;
    /** @var list<string> */
    private array $matchOn = [];
    private bool $append = true;
    /** @var list<string> */
    private array $appendsAtPaths = [];
    /** @var list<string> */
    private array $prependsAtPaths = [];

    public function merge(): static
    {
        $this->merge = true;

        return $this;
    }

    public function deepMerge(): static
    {
        $this->deepMerge = true;

        return $this->merge();
    }

    /**
     * Keys used to match items when merging, e.g. "id" or "data.id".
     *
     * @param string|list<string> $matchOn
     */
    public function matchOn(string|array $matchOn): static
    {
        $this->matchOn = is_array($matchOn) ? array_values($matchOn) : [$matchOn];

        return $this;
    }

    public function shouldMerge(): bool
    {
        return $this->merge;
    }

    public function shouldDeepMerge(): bool
    {
        return $this->deepMerge;
    }

    public function matchesOn(): array
    {
        return $this->matchOn;
    }

    public function appendsAtRoot(): bool
    {
        return $this->append && $this->mergesAtRoot();
    }

    public function prependsAtRoot(): bool
    {
        return !$this->append && $this->mergesAtRoot();
    }

    /**
     * Append at the root (true), at a nested path, or at several paths given as a list or as path => matchOn pairs.
     *
     * @param bool|string|array<array-key, string> $path
     */
    public function append(bool|string|array $path = true, ?string $matchOn = null): static
    {
        if (is_bool($path)) {
            $this->append = $path;
        } elseif (is_string($path)) {
            $this->appendsAtPaths[] = $path;
            if ($matchOn !== null) {
                $this->matchOn[] = "{$path}.{$matchOn}";
            }
        } else {
            foreach ($path as $key => $value) {
                is_int($key) ? $this->append($value) : $this->append($key, $value);
            }
        }

        return $this;
    }

    /**
     * Prepend at the root (true), at a nested path, or at several paths given as a list or as path => matchOn pairs.
     *
     * @param bool|string|array<array-key, string> $path
     */
    public function prepend(bool|string|array $path = true, ?string $matchOn = null): static
    {
        if (is_bool($path)) {
            $this->append = !$path;
        } elseif (is_string($path)) {
            $this->prependsAtPaths[] = $path;
            if ($matchOn !== null) {
                $this->matchOn[] = "{$path}.{$matchOn}";
            }
        } else {
            foreach ($path as $key => $value) {
                is_int($key) ? $this->prepend($value) : $this->prepend($key, $value);
            }
        }

        return $this;
    }

    public function appendsAtPaths(): array
    {
        return $this->appendsAtPaths;
    }

    public function prependsAtPaths(): array
    {
        return $this->prependsAtPaths;
    }

    private function mergesAtRoot(): bool
    {
        return $this->appendsAtPaths === [] && $this->prependsAtPaths === [];
    }
}
