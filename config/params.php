<?php

declare(strict_types=1);

return [
    'crenspire/yii3-inertia' => [
        // PHP template rendered on the first visit. It receives $inertia, $request, $vite and viewParameters.
        'rootView' => '@root/resources/views/inertia.php',
        // Extra root view variables. A Closure value is called with the DI container to get the variable.
        'viewParameters' => [],
        // Asset version: a string, a callable returning a string, or null to hash the Vite manifest.
        'version' => null,
        // Manifest to hash when "version" is null. Null uses the manifest configured in "vite".
        'manifestPath' => null,
        // Props shared with every page. Values may be callables and prop types such as Inertia::always().
        'sharedProps' => [],
        'encryptHistory' => false,
        // Send all messages per field in the "errors" prop instead of only the first one.
        'allErrors' => false,
        'vite' => [
            'publicPath' => '@public',
            'buildDirectory' => 'build',
            'manifest' => '.vite/manifest.json',
            // Vite dev server URL, for example "http://localhost:5173". Null serves the built assets.
            'devServerUrl' => null,
            'hotFile' => 'hot',
            'baseUrl' => '@baseUrl',
        ],
        'ssr' => [
            // Requires PSR-18 ClientInterface and PSR-17 RequestFactoryInterface in the container.
            'enabled' => false,
            'url' => 'http://127.0.0.1:13714/render',
            // Request path prefixes that are never rendered on the server.
            'except' => [],
            'throwOnError' => false,
        ],
    ],
];
