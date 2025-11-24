<?php

declare(strict_types=1);

use Crenspire\Yii3Inertia\Inertia;
use Crenspire\Yii3Inertia\ResponseFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** @var ServerRequestInterface $request */
$request = $GLOBALS['request'] ?? null;

if ($request === null) {
    throw new RuntimeException('Request not found');
}

// Set request in Inertia service
Inertia::setRequest($request);

// Get payload
$payload = Inertia::render('Home', [
    'title' => 'Welcome to Inertia.js with Yii3',
    'message' => 'This is the home page rendered with Inertia!',
]);

// Create response
$psr17Factory = new Psr17Factory();
$responseFactory = new ResponseFactory($psr17Factory, $psr17Factory);

if (Inertia::isInertiaRequest($request)) {
    return $responseFactory->json($payload);
}

return $responseFactory->html($payload, Inertia::getRootView());

