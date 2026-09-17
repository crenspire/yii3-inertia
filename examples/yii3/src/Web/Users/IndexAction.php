<?php

declare(strict_types=1);

namespace App\Web\Users;

use Crenspire\Inertia\Inertia;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class IndexAction
{
    public function __construct(
        private Inertia $inertia,
        private UserRepository $users,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->inertia->render($request, 'Users/Index', [
            'count' => count($this->users->findAll()),
            // Loaded by the client in a second request after the page is shown.
            'users' => Inertia::defer(fn (): array => $this->users->findAll()),
        ]);
    }
}
