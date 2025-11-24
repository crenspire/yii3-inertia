<?php

declare(strict_types=1);

/**
 * Default Inertia configuration for Yii3
 * 
 * You can override these values in your application configuration
 * by merging this config or setting values directly.
 */

return [
    // Root view template path or name
    'root_view' => 'inertia',
    
    // Asset version callback or string
    // Default: uses manifest.json mtime if it exists, otherwise '1'
    'version' => null,
    
    // Shared props (can be set via Inertia::share())
    'shared' => [],
];

