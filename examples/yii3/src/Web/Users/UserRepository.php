<?php

declare(strict_types=1);

namespace App\Web\Users;

use Yiisoft\Session\SessionInterface;

/**
 * Keeps users in the session to keep the example free of a database.
 */
final readonly class UserRepository
{
    public function __construct(
        private SessionInterface $session,
    ) {}

    /**
     * @return list<array{name: string, email: string}>
     */
    public function findAll(): array
    {
        return $this->session->get('users') ?? [
            ['name' => 'Ada Lovelace', 'email' => 'ada@example.com'],
            ['name' => 'Alan Turing', 'email' => 'alan@example.com'],
        ];
    }

    public function add(string $name, string $email): void
    {
        $this->session->set('users', [...$this->findAll(), ['name' => $name, 'email' => $email]]);
    }
}
