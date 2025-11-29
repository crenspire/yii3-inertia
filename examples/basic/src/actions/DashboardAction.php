<?php

declare(strict_types=1);

use Crenspire\Yii3Inertia\Action\InertiaAction;
use Crenspire\Yii3Inertia\ResponseFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** @var ServerRequestInterface $request */
$request = $GLOBALS['request'] ?? null;

if ($request === null) {
    throw new RuntimeException('Request not found');
}

// Create response factory (in real app, this would come from DI container)
$psr17Factory = new Psr17Factory();
$responseFactory = new ResponseFactory($psr17Factory, $psr17Factory);

// Create action instance
$action = new class($request, $responseFactory) extends InertiaAction {
    public function __invoke(): ResponseInterface
    {
        return $this->render('Dashboard', [
            'title' => 'Dashboard',
            'stats' => [
                'users' => 1234,
                'revenue' => 56789,
                'orders' => 890,
            ],
        ]);
    }
};

return $action();
