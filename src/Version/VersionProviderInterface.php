<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Version;

/**
 * Provides the current asset version. When it changes, clients do a full page reload to pick up new assets.
 */
interface VersionProviderInterface
{
    public function getVersion(): string;
}
