<?php

declare(strict_types=1);

namespace Crenspire\Inertia;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Yiisoft\Di\ContainerConfig;

/**
 * ConfigProvider for Yii3 Inertia.js adapter
 * 
 * This class provides automatic DI container configuration for Yii3 applications.
 * Simply include this class in your Yii3 config to auto-configure all Inertia services.
 * 
 * Usage in your Yii3 config:
 * ```php
 * return [
 *     // ... other config
 *     Crenspire\Inertia\ConfigProvider::class,
 * ];
 * ```
 */
final class ConfigProvider
{
    /**
     * Get DI container definitions
     * 
     * @return array<string, mixed>
     */
    public function getDefinitions(): array
    {
        return [
            // AssetConfig - can be overridden in app config via params
            AssetConfig::class => static function (ContainerInterface $container): AssetConfig {
                // Try to get from params if configured
                // Params are typically accessed via ApplicationParams or similar
                // For now, we'll check if there's a way to access params
                try {
                    // Try to get params from container if available
                    if ($container->has('params')) {
                        $params = $container->get('params');
                        $inertiaConfig = $params['inertia'] ?? null;
                        if ($inertiaConfig !== null && isset($inertiaConfig['assetConfig'])) {
                            $config = $inertiaConfig['assetConfig'];
                            return new AssetConfig(
                                $config['viteHost'] ?? null,
                                $config['vitePort'] ?? null,
                                $config['viteEntryPath'] ?? null,
                                $config['manifestEntryKey'] ?? null,
                                $config['publicPath'] ?? null,
                                $config['buildOutputDir'] ?? null,
                                $config['manifestFileName'] ?? null
                            );
                        }
                    }
                } catch (\Throwable $e) {
                    // Params not available, use defaults
                }
                // Return default config
                return new AssetConfig();
            },
            
            // ResponseFactory with automatic PSR factory injection
            ResponseFactory::class => static function (ContainerInterface $container): ResponseFactory {
                $responseFactory = $container->get(ResponseFactoryInterface::class);
                $streamFactory = $container->get(StreamFactoryInterface::class);
                
                // View renderer is required - try WebView first (for web), then View (for console)
                $viewRenderer = null;
                if ($container->has(\Yiisoft\View\WebView::class)) {
                    try {
                        $view = $container->get(\Yiisoft\View\WebView::class);
                        $viewRenderer = static function (string $viewName, array $payload) use ($view): string {
                            return ViewRenderer::render($view, $viewName, $payload);
                        };
                    } catch (\Throwable $e) {
                        // View not available
                    }
                } elseif ($container->has(\Yiisoft\View\View::class)) {
                    try {
                        $view = $container->get(\Yiisoft\View\View::class);
                        $viewRenderer = static function (string $viewName, array $payload) use ($view): string {
                            return ViewRenderer::render($view, $viewName, $payload);
                        };
                    } catch (\Throwable $e) {
                        // View not available
                    }
                }
                
                if ($viewRenderer === null) {
                    throw new \RuntimeException(
                        'View renderer is not configured. Please ensure yiisoft/view is installed and WebView is configured in your DI container.'
                    );
                }
                
                return new ResponseFactory($responseFactory, $streamFactory, $viewRenderer);
            },
            
            // InertiaMiddleware with ResponseFactory dependency
            Middleware\InertiaMiddleware::class => static function (ContainerInterface $container): Middleware\InertiaMiddleware {
                $responseFactory = $container->get(ResponseFactory::class);
                $psrResponseFactory = $container->get(ResponseFactoryInterface::class);
                
                return new Middleware\InertiaMiddleware($responseFactory, $psrResponseFactory);
            },
        ];
    }

    /**
     * Get container configuration
     * 
     * This method is called by Yii3's config system to register services
     * 
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            ContainerConfig::class => [
                'definitions' => $this->getDefinitions(),
            ],
        ];
    }
}

