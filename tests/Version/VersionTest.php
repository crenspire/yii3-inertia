<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Tests\Version;

use Crenspire\Inertia\Version\CallbackVersion;
use Crenspire\Inertia\Version\ManifestVersion;
use Crenspire\Inertia\Version\StaticVersion;
use PHPUnit\Framework\TestCase;

final class VersionTest extends TestCase
{
    public function testStaticAndCallbackVersions(): void
    {
        $this->assertSame('', (new StaticVersion())->getVersion());
        $this->assertSame('1.2', (new StaticVersion('1.2'))->getVersion());
        $this->assertSame('123', (new CallbackVersion(static fn (): int => 123))->getVersion());
        $this->assertSame('', (new CallbackVersion(static fn () => null))->getVersion());
    }

    public function testManifestVersionFollowsFileChanges(): void
    {
        $path = sys_get_temp_dir() . '/inertia-manifest-' . bin2hex(random_bytes(4)) . '.json';
        $version = new ManifestVersion($path);

        $this->assertSame('', $version->getVersion());

        file_put_contents($path, '{"a":1}');
        $first = $version->getVersion();
        $this->assertSame(hash('xxh128', '{"a":1}'), $first);
        $this->assertSame($first, $version->getVersion());

        file_put_contents($path, '{"a":22}');
        $this->assertSame(hash('xxh128', '{"a":22}'), $version->getVersion());

        unlink($path);
        $this->assertSame('', $version->getVersion());
    }
}
