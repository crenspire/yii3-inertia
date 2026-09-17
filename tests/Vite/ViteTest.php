<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Tests\Vite;

use Crenspire\Inertia\Vite\Vite;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ViteTest extends TestCase
{
    private const PUBLIC_PATH = __DIR__ . '/../Fixtures/public';

    public function testProductionTagsIncludeCssPreloadsAndScripts(): void
    {
        $vite = new Vite(self::PUBLIC_PATH, hotFile: '');

        $this->assertFalse($vite->isRunningHot());
        $this->assertSame(
            implode("\n", [
                '<link rel="stylesheet" href="/build/assets/vendor-A0b1c2.css">',
                '<link rel="stylesheet" href="/build/assets/app-C7d8e9.css">',
                '<link rel="stylesheet" href="/build/assets/extra-E3f4a5.css">',
                '<link rel="modulepreload" href="/build/assets/vendor-D4e5f6.js">',
                '<script type="module" src="/build/assets/app-B1a2c3.js"></script>',
            ]),
            $vite->tags(['resources/js/app.jsx', 'resources/css/extra.css']),
        );
        $this->assertSame('', $vite->reactRefresh());
        $this->assertSame('/build/assets/logo-F6a7b8.svg', $vite->asset('resources/images/logo.svg'));
    }

    public function testBaseUrlIsPrepended(): void
    {
        $vite = new Vite(self::PUBLIC_PATH, baseUrl: '/sub/', hotFile: '');

        $this->assertStringContainsString('src="/sub/build/assets/app-B1a2c3.js"', $vite->tags('resources/js/app.jsx'));
    }

    public function testDevServerTags(): void
    {
        $vite = new Vite(self::PUBLIC_PATH, devServerUrl: 'http://localhost:5173/');

        $this->assertTrue($vite->isRunningHot());
        $this->assertSame(
            implode("\n", [
                '<script type="module" src="http://localhost:5173/@vite/client"></script>',
                '<script type="module" src="http://localhost:5173/resources/js/app.jsx"></script>',
                '<link rel="stylesheet" href="http://localhost:5173/resources/css/extra.css">',
            ]),
            $vite->tags(['resources/js/app.jsx', '/resources/css/extra.css']),
        );
        $this->assertStringContainsString('import RefreshRuntime from "http://localhost:5173/@react-refresh";', $vite->reactRefresh());
        $this->assertSame('http://localhost:5173/resources/images/logo.svg', $vite->asset('resources/images/logo.svg'));
    }

    public function testHotFileEnablesDevServer(): void
    {
        $publicPath = sys_get_temp_dir() . '/inertia-vite-' . bin2hex(random_bytes(4));
        mkdir($publicPath);
        file_put_contents($publicPath . '/hot', "http://127.0.0.1:5174\n");

        try {
            $vite = new Vite($publicPath);
            $this->assertStringContainsString('http://127.0.0.1:5174/@vite/client', $vite->tags('app.js'));
        } finally {
            unlink($publicPath . '/hot');
            rmdir($publicPath);
        }
    }

    public function testUnknownEntryFails(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Vite entry "missing.js" is not in the manifest');

        (new Vite(self::PUBLIC_PATH, hotFile: ''))->tags('missing.js');
    }

    public function testMissingManifestFails(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        (new Vite('/nonexistent', hotFile: ''))->tags('app.js');
    }
}
