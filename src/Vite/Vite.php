<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Vite;

use JsonException;
use RuntimeException;

/**
 * Generates script and stylesheet tags for Vite entry points, from the dev server or from the build manifest.
 *
 * ```php
 * <?= $vite->reactRefresh() ?>
 * <?= $vite->tags('resources/js/app.jsx') ?>
 * ```
 */
final class Vite
{
    /** @var array<string, array<string, mixed>>|null */
    private ?array $manifestData = null;
    private ?string $manifestSignature = null;

    /**
     * @param string $publicPath Filesystem path of the web root.
     * @param string $buildDirectory Vite `build.outDir`, relative to the web root.
     * @param string $manifest Manifest file relative to the build directory. Vite 5+ writes ".vite/manifest.json".
     * @param string|null $devServerUrl Vite dev server URL, e.g. "http://localhost:5173". Null serves built assets.
     * @param string $hotFile File relative to the web root whose contents is the dev server URL. When it exists it
     * enables dev mode, which lets tooling such as laravel-vite-plugin switch modes automatically.
     * @param string $baseUrl URL under which the web root is served.
     */
    public function __construct(
        private readonly string $publicPath,
        private readonly string $buildDirectory = 'build',
        private readonly string $manifest = '.vite/manifest.json',
        private readonly ?string $devServerUrl = null,
        private readonly string $hotFile = 'hot',
        private readonly string $baseUrl = '/',
    ) {
    }

    public function isRunningHot(): bool
    {
        return $this->getDevServerUrl() !== null;
    }

    /**
     * Tags for one or more entry points, e.g. "resources/js/app.jsx".
     *
     * @param string|list<string> $entries
     */
    public function tags(string|array $entries): string
    {
        $entries = is_array($entries) ? $entries : [$entries];
        $devServerUrl = $this->getDevServerUrl();

        if ($devServerUrl !== null) {
            $tags = [$this->scriptTag($devServerUrl . '/@vite/client')];
            foreach ($entries as $entry) {
                $url = $devServerUrl . '/' . ltrim($entry, '/');
                $tags[] = $this->isCss($entry) ? $this->stylesheetTag($url) : $this->scriptTag($url);
            }

            return implode("\n", $tags);
        }

        $manifest = $this->getManifest();
        $preloads = [];
        $stylesheets = [];
        $scripts = [];

        foreach ($entries as $entry) {
            $chunk = $manifest[$entry] ?? throw new RuntimeException(
                "Vite entry \"{$entry}\" is not in the manifest {$this->getManifestPath()}."
            );

            $this->collectImports($manifest, $entry, $preloads, $stylesheets, []);

            $file = (string) $chunk['file'];
            if ($this->isCss($file)) {
                $stylesheets[$file] = true;
            } else {
                $scripts[$file] = true;
            }
        }

        $tags = [];
        foreach (array_keys($stylesheets) as $file) {
            $tags[] = $this->stylesheetTag($this->assetUrl($file));
        }
        foreach (array_keys($preloads) as $file) {
            if (!isset($scripts[$file])) {
                $tags[] = '<link rel="modulepreload" href="' . $this->encode($this->assetUrl($file)) . '">';
            }
        }
        foreach (array_keys($scripts) as $file) {
            $tags[] = $this->scriptTag($this->assetUrl($file));
        }

        return implode("\n", $tags);
    }

    /**
     * The preamble required by @vitejs/plugin-react when the page is not served by Vite. Empty in production.
     */
    public function reactRefresh(): string
    {
        $devServerUrl = $this->getDevServerUrl();
        if ($devServerUrl === null) {
            return '';
        }

        $url = json_encode($devServerUrl . '/@react-refresh', JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);

        return <<<HTML
            <script type="module">
            import RefreshRuntime from {$url};
            RefreshRuntime.injectIntoGlobalHook(window);
            window.\$RefreshReg\$ = () => {};
            window.\$RefreshSig\$ = () => (type) => type;
            window.__vite_plugin_react_preamble_installed__ = true;
            </script>
            HTML;
    }

    /**
     * URL of a file processed by Vite, e.g. an image imported by the frontend.
     */
    public function asset(string $path): string
    {
        $devServerUrl = $this->getDevServerUrl();
        if ($devServerUrl !== null) {
            return $devServerUrl . '/' . ltrim($path, '/');
        }

        $manifest = $this->getManifest();
        $chunk = $manifest[$path] ?? throw new RuntimeException(
            "Vite asset \"{$path}\" is not in the manifest {$this->getManifestPath()}."
        );

        return $this->assetUrl((string) $chunk['file']);
    }

    public function getManifestPath(): string
    {
        return rtrim($this->publicPath, '/') . '/' . trim($this->buildDirectory, '/') . '/' . ltrim($this->manifest, '/');
    }

    private function getDevServerUrl(): ?string
    {
        if ($this->devServerUrl !== null && $this->devServerUrl !== '') {
            return rtrim($this->devServerUrl, '/');
        }

        $hotFile = rtrim($this->publicPath, '/') . '/' . ltrim($this->hotFile, '/');
        clearstatcache(true, $hotFile);
        if ($this->hotFile !== '' && is_file($hotFile)) {
            $url = trim((string) file_get_contents($hotFile));

            return $url === '' ? null : rtrim($url, '/');
        }

        return null;
    }

    /**
     * @param array<string, array<string, mixed>> $manifest
     * @param array<string, true> $preloads
     * @param array<string, true> $stylesheets
     * @param array<string, true> $seen
     */
    private function collectImports(array $manifest, string $name, array &$preloads, array &$stylesheets, array $seen): void
    {
        if (isset($seen[$name]) || !isset($manifest[$name])) {
            return;
        }
        $seen[$name] = true;
        $chunk = $manifest[$name];

        foreach ((array) ($chunk['imports'] ?? []) as $import) {
            $import = (string) $import;
            $this->collectImports($manifest, $import, $preloads, $stylesheets, $seen);
            if (isset($manifest[$import]['file'])) {
                $preloads[(string) $manifest[$import]['file']] = true;
            }
        }

        foreach ((array) ($chunk['css'] ?? []) as $css) {
            $stylesheets[(string) $css] = true;
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getManifest(): array
    {
        $path = $this->getManifestPath();
        clearstatcache(true, $path);

        if (!is_file($path)) {
            throw new RuntimeException(
                "Vite manifest {$path} does not exist. Run \"vite build\" or configure the dev server URL."
            );
        }

        $signature = filemtime($path) . ':' . filesize($path);
        if ($this->manifestData === null || $signature !== $this->manifestSignature) {
            try {
                $manifest = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new RuntimeException("Vite manifest {$path} is not valid JSON.", 0, $e);
            }
            /** @var array<string, array<string, mixed>> $manifest */
            $this->manifestData = is_array($manifest) ? $manifest : [];
            $this->manifestSignature = $signature;
        }

        return $this->manifestData;
    }

    private function assetUrl(string $file): string
    {
        return rtrim($this->baseUrl, '/') . '/' . trim($this->buildDirectory, '/') . '/' . ltrim($file, '/');
    }

    private function isCss(string $path): bool
    {
        return (bool) preg_match('/\.(css|less|sass|scss|styl|stylus|pcss|postcss)(\?.*)?$/', $path);
    }

    private function scriptTag(string $url): string
    {
        return '<script type="module" src="' . $this->encode($url) . '"></script>';
    }

    private function stylesheetTag(string $url): string
    {
        return '<link rel="stylesheet" href="' . $this->encode($url) . '">';
    }

    private function encode(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
