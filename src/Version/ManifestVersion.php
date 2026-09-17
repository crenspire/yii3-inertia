<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Version;

/**
 * Uses a hash of a build manifest (for example Vite's manifest.json) as the asset version.
 *
 * The hash is recomputed only when the file changes, so it is safe for long-running workers.
 */
final class ManifestVersion implements VersionProviderInterface
{
    private ?string $signature = null;
    private string $version = '';

    public function __construct(private readonly string $manifestPath)
    {
    }

    public function getVersion(): string
    {
        clearstatcache(true, $this->manifestPath);

        if (!is_file($this->manifestPath)) {
            $this->signature = null;

            return $this->version = '';
        }

        $signature = filemtime($this->manifestPath) . ':' . filesize($this->manifestPath);

        if ($signature !== $this->signature) {
            $this->version = (string) hash_file('xxh128', $this->manifestPath);
            $this->signature = $signature;
        }

        return $this->version;
    }
}
