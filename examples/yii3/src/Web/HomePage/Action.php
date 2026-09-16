<?php

declare(strict_types=1);

namespace App\Web\HomePage;

use Crenspire\Inertia\Inertia;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class Action
{
    public function __construct(
        private Inertia $inertia,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->inertia->render($request, 'Home', [
            'phpVersion' => PHP_VERSION,
        ]);
    }
}
