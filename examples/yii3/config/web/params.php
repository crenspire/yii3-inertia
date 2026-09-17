<?php

declare(strict_types=1);

return [
    'crenspire/yii3-inertia' => [
        'vite' => [
            // Set VITE_DEV_SERVER_URL=http://localhost:5173 while "npm run dev" is running.
            'devServerUrl' => $_ENV['VITE_DEV_SERVER_URL'] ?? null,
        ],
        'sharedProps' => [
            'appName' => 'Yii3 + Inertia',
        ],
    ],
];
