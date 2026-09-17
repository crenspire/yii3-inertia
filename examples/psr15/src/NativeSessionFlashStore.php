<?php

declare(strict_types=1);

namespace App;

use Crenspire\Inertia\Flash\FlashStoreInterface;

/**
 * Flash store on top of PHP's native session. Yii3 applications use SessionFlashStore instead.
 */
final class NativeSessionFlashStore implements FlashStoreInterface
{
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    public function pull(string $key): mixed
    {
        $value = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);

        return $value;
    }
}
