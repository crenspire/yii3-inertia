<?php

declare(strict_types=1);

use App\NativeSessionFlashStore;
use App\Router;
use Crenspire\Inertia\Flash\InertiaFlash;
use Crenspire\Inertia\Inertia;
use Crenspire\Inertia\Middleware\InertiaMiddleware;
use Crenspire\Inertia\Version\ManifestVersion;
use Crenspire\Inertia\View\PhpRootViewRenderer;
use Crenspire\Inertia\Vite\Vite;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

require dirname(__DIR__) . '/vendor/autoload.php';

// Let the PHP built-in server serve built assets directly.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

session_start();

$factory = new Psr17Factory();

$vite = new Vite(
    publicPath: __DIR__,
    // Set VITE_DEV_SERVER_URL=http://localhost:5173 while "npm run dev" is running.
    devServerUrl: getenv('VITE_DEV_SERVER_URL') ?: null,
);

$flashStore = new NativeSessionFlashStore();

$inertia = new Inertia(
    responseFactory: $factory,
    streamFactory: $factory,
    rootViewRenderer: new PhpRootViewRenderer(dirname(__DIR__) . '/resources/views/inertia.php', ['vite' => $vite]),
    version: new ManifestVersion($vite->getManifestPath()),
    flashStore: $flashStore,
    sharedProps: [
        'appName' => 'Inertia on PSR-15',
    ],
);

$request = (new ServerRequestCreator($factory, $factory, $factory, $factory))->fromGlobals();
$router = new Router($inertia, new InertiaFlash($flashStore), $factory, $factory);

$response = (new InertiaMiddleware($inertia, $factory))->process($request, $router);

http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header("{$name}: {$value}", false);
    }
}
echo $response->getBody();
