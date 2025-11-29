<?php

declare(strict_types=1);

namespace Crenspire\Inertia;

/**
 * Configuration for Inertia asset paths
 * 
 * This class holds configuration for Vite dev server and production asset paths.
 * Configure it in your Yii3 params.php file.
 */
final class AssetConfig
{
    /**
     * Vite dev server host (default: localhost)
     */
    private string $viteHost = 'localhost';

    /**
     * Vite dev server port (default: 5173)
     */
    private int $vitePort = 5173;

    /**
     * Entry point path for Vite dev server (e.g., 'assets/react/src/main.jsx')
     */
    private string $viteEntryPath = 'src/main.jsx';

    /**
     * Manifest entry key for production builds (e.g., 'assets/react/src/main.jsx')
     * This should match the input path in your vite.config.js
     */
    private string $manifestEntryKey = 'src/main.jsx';

    /**
     * Public directory path relative to project root (default: 'public')
     */
    private string $publicPath = 'public';

    /**
     * Build output directory relative to public path (default: 'dist')
     */
    private string $buildOutputDir = 'dist';

    /**
     * Manifest file name (default: 'manifest.json')
     */
    private string $manifestFileName = 'manifest.json';

    public function __construct(
        ?string $viteHost = null,
        ?int $vitePort = null,
        ?string $viteEntryPath = null,
        ?string $manifestEntryKey = null,
        ?string $publicPath = null,
        ?string $buildOutputDir = null,
        ?string $manifestFileName = null
    ) {
        if ($viteHost !== null) {
            $this->viteHost = $viteHost;
        }
        if ($vitePort !== null) {
            $this->vitePort = $vitePort;
        }
        if ($viteEntryPath !== null) {
            $this->viteEntryPath = $viteEntryPath;
        }
        if ($manifestEntryKey !== null) {
            $this->manifestEntryKey = $manifestEntryKey;
        }
        if ($publicPath !== null) {
            $this->publicPath = $publicPath;
        }
        if ($buildOutputDir !== null) {
            $this->buildOutputDir = $buildOutputDir;
        }
        if ($manifestFileName !== null) {
            $this->manifestFileName = $manifestFileName;
        }
    }

    public function getViteHost(): string
    {
        return $this->viteHost;
    }

    public function getVitePort(): int
    {
        return $this->vitePort;
    }

    public function getViteEntryPath(): string
    {
        return $this->viteEntryPath;
    }

    public function getManifestEntryKey(): string
    {
        return $this->manifestEntryKey;
    }

    public function getPublicPath(): string
    {
        return $this->publicPath;
    }

    public function getBuildOutputDir(): string
    {
        return $this->buildOutputDir;
    }

    public function getManifestFileName(): string
    {
        return $this->manifestFileName;
    }

    /**
     * Get Vite dev server URL
     */
    public function getViteDevServerUrl(): string
    {
        return "http://{$this->viteHost}:{$this->vitePort}";
    }

    /**
     * Get Vite client URL
     */
    public function getViteClientUrl(): string
    {
        return "{$this->getViteDevServerUrl()}/@vite/client";
    }

    /**
     * Get Vite entry point URL
     */
    public function getViteEntryUrl(): string
    {
        $entryPath = ltrim($this->viteEntryPath, '/');
        return "{$this->getViteDevServerUrl()}/{$entryPath}";
    }

    /**
     * Get manifest file path
     */
    public function getManifestPath(?string $basePath = null): string
    {
        $base = $basePath ?? getcwd();
        return rtrim($base, '/') . '/' . trim($this->publicPath, '/') . '/' . trim($this->buildOutputDir, '/') . '/' . $this->manifestFileName;
    }
}

