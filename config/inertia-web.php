<?php

declare(strict_types=1);

/**
 * Optional Yii3 Inertia configuration file
 * 
 * This file is optional - you can copy it to your application config directory
 * and include it in your main config, or configure services directly.
 * 
 * The ConfigProvider automatically configures DI services, so this file
 * is mainly for middleware stack configuration and additional settings.
 * 
 * Usage:
 * ```php
 * return [
 *     // Include ConfigProvider for auto-configuration
 *     \Crenspire\Yii3Inertia\ConfigProvider::class,
 *     
 *     // Optionally include this config for middleware and settings
 *     ...require __DIR__ . '/inertia-web.php',
 * ];
 * ```
 */

use Crenspire\Yii3Inertia\Middleware\InertiaMiddleware;

return [
    // Middleware stack configuration
    // Add InertiaMiddleware to your middleware stack
    // Recommended position: after authentication, before routing
    'middleware' => [
        // Example middleware stack order:
        // 1. Error handling middleware
        // 2. Authentication middleware
        // 3. InertiaMiddleware::class,  ← Add here
        // 4. Routing middleware
        // 5. Controller/Action execution
    ],

    // Default Inertia settings (optional)
    'inertia' => [
        // Root view template path or name
        'root_view' => 'inertia',
        
        // Asset version callback or string
        // Examples:
        // 'version' => '1.0.0',
        // 'version' => fn() => filemtime('/path/to/manifest.json'),
        'version' => null,
        
        // Shared props (can also be set via Inertia::share())
        'shared' => [],
    ],
];

