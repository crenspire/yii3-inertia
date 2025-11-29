<?php

declare(strict_types=1);

namespace App\Controller;

use Crenspire\Yii3Inertia\ControllerTrait;
use Crenspire\Yii3Inertia\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Example HomeController using ControllerTrait
 */
class HomeController
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
        return $this->inertiaRender('Home', [
            'title' => 'Welcome to Yii3 + Inertia.js',
            'message' => 'This is a full Yii3 web application example',
        ], $request);
    }
}

