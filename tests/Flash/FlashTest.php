<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Tests\Flash;

use Crenspire\Inertia\Flash\ArrayFlashStore;
use Crenspire\Inertia\Flash\InertiaFlash;
use Crenspire\Inertia\Flash\SessionFlashStore;
use PHPUnit\Framework\TestCase;
use Yiisoft\Session\SessionInterface;

final class FlashTest extends TestCase
{
    public function testInertiaFlashAccumulatesValues(): void
    {
        $store = new ArrayFlashStore();
        $flash = new InertiaFlash($store);

        $flash->errors(['a' => 'A'])->errors(['b' => 'B'], 'login')->errors(['c' => 'C']);
        $flash->flash('one', 1)->flash(['two' => 2]);

        $this->assertSame(['default' => ['c' => 'C'], 'login' => ['b' => 'B']], $store->pull(InertiaFlash::ERRORS));
        $this->assertSame(['one' => 1, 'two' => 2], $store->pull(InertiaFlash::FLASH));
        $this->assertNull($store->pull(InertiaFlash::FLASH));
    }

    public function testSessionFlashStoreDelegatesToSession(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())->method('set')->with('k', 'v');
        $session->expects($this->once())->method('get')->with('k')->willReturn('v');
        $session->expects($this->once())->method('pull')->with('k')->willReturn('v');

        $store = new SessionFlashStore($session);
        $store->set('k', 'v');

        $this->assertSame('v', $store->get('k'));
        $this->assertSame('v', $store->pull('k'));
    }
}
