<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Version;

final class StaticVersion implements VersionProviderInterface
{
    public function __construct(private readonly string $version = '')
    {
    }

    public function getVersion(): string
    {
        return $this->version;
    }
}
