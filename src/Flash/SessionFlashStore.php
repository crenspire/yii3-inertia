<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Flash;

use Yiisoft\Session\SessionInterface;

/**
 * Flash store backed by yiisoft/session.
 */
final class SessionFlashStore implements FlashStoreInterface
{
    public function __construct(private readonly SessionInterface $session)
    {
    }

    public function set(string $key, mixed $value): void
    {
        $this->session->set($key, $value);
    }

    public function get(string $key): mixed
    {
        return $this->session->get($key);
    }

    public function pull(string $key): mixed
    {
        return $this->session->pull($key);
    }
}
