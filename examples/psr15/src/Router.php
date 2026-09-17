<?php

declare(strict_types=1);

namespace App;

use Crenspire\Inertia\Flash\InertiaFlash;
use Crenspire\Inertia\Inertia;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Router implements RequestHandlerInterface
{
    public function __construct(
        private readonly Inertia $inertia,
        private readonly InertiaFlash $flash,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $route = $request->getMethod() . ' ' . rtrim($request->getUri()->getPath(), '/');

        return match ($route) {
            'GET ' => $this->inertia->render($request, 'Home', [
                'phpVersion' => PHP_VERSION,
            ]),
            'GET /users' => $this->inertia->render($request, 'Users/Index', [
                'count' => count($this->users()),
                // Loaded by the client in a second request after the page is shown.
                'users' => Inertia::defer(function (): array {
                    usleep(300_000);

                    return $this->users();
                }),
            ]),
            'GET /users/create' => $this->inertia->render($request, 'Users/Create'),
            'POST /users' => $this->storeUser($request),
            default => $this->responseFactory->createResponse(404)
                ->withBody($this->streamFactory->createStream('Not Found')),
        };
    }

    private function storeUser(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->input($request);
        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Enter a valid email address.';
        }

        if ($errors !== []) {
            $this->flash->errors($errors);

            return $this->inertia->back($request, '/users/create');
        }

        $_SESSION['users'][] = ['name' => $name, 'email' => $email];
        $this->flash->flash('message', "User {$name} was created.");

        return $this->inertia->redirect('/users');
    }

    /**
     * @return list<array{name: string, email: string}>
     */
    private function users(): array
    {
        return $_SESSION['users'] ??= [
            ['name' => 'Ada Lovelace', 'email' => 'ada@example.com'],
            ['name' => 'Alan Turing', 'email' => 'alan@example.com'],
        ];
    }

    /**
     * The Inertia client sends JSON unless the form contains files.
     *
     * @return array<string, mixed>
     */
    private function input(ServerRequestInterface $request): array
    {
        if (str_contains($request->getHeaderLine('Content-Type'), 'application/json')) {
            $data = json_decode((string) $request->getBody(), true);

            return is_array($data) ? $data : [];
        }

        $data = $request->getParsedBody();

        return is_array($data) ? $data : [];
    }
}
