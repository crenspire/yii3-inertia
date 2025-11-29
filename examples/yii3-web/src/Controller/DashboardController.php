<?php

declare(strict_types=1);

namespace App\Controller;

use Crenspire\Yii3Inertia\ControllerTrait;
use Crenspire\Yii3Inertia\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Example DashboardController using ControllerTrait
 */
class DashboardController
{
    use ControllerTrait;

    public function __construct(
        private ResponseFactory $responseFactory
    ) {
    }

    protected function getResponseFactory(): ResponseFactory
    {
        return $this->responseFactory;
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        return $this->inertiaRender('Dashboard', [
            'title' => 'Dashboard',
            'stats' => [
                'users' => 1234,
                'revenue' => 56789,
                'orders' => 890,
            ],
        ], $request);
    }
}

