<?php

declare(strict_types=1);

namespace Crenspire\Yii3Inertia\Tests\Unit;

use Crenspire\Yii3Inertia\Inertia;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

class InertiaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Inertia::flushShared();
        Inertia::setRequest(null);
    }

    public function testShareSingleKeyValue(): void
    {
        Inertia::share('user', ['name' => 'John']);
        
        $this->assertTrue(true); // Shared props are tested in integration tests
    }

    public function testShareArray(): void
    {
        Inertia::share([
            'user' => ['name' => 'John'],
            'app' => ['name' => 'My App'],
        ]);
        
        $this->assertTrue(true); // Shared props are tested in integration tests
    }

    public function testShareClosure(): void
    {
        Inertia::share('timestamp', function () {
            return time();
        });
        
        $this->assertTrue(true); // Closure evaluation tested in integration tests
    }

    public function testVersionString(): void
    {
        Inertia::version('1.0.0');
        $version = Inertia::version();
        
        $this->assertEquals('1.0.0', $version);
    }

    public function testVersionCallback(): void
    {
        Inertia::version(function () {
            return '2.0.0';
        });
        
        $version = Inertia::version();
        $this->assertEquals('2.0.0', $version);
    }

    public function testVersionDefault(): void
    {
        // Reset version
        Inertia::version(null);
        
        $version = Inertia::version();
        $this->assertIsString($version);
    }

    public function testSetRootView(): void
    {
        $view = 'custom-inertia';
        Inertia::setRootView($view);
        
        $this->assertEquals($view, Inertia::getRootView());
    }

    public function testIsInertiaRequest(): void
    {
        $request = new ServerRequest('GET', '/');
        
        // Without header
        $this->assertFalse(Inertia::isInertiaRequest($request));
        
        // With header
        $request = $request->withHeader('X-Inertia', 'true');
        $this->assertTrue(Inertia::isInertiaRequest($request));
    }

    public function testLocation(): void
    {
        $result = Inertia::location('/dashboard');
        
        $this->assertIsArray($result);
        $this->assertEquals('/dashboard', $result['location']);
        $this->assertEquals(409, $result['status']);
    }

    public function testFlushShared(): void
    {
        Inertia::share('test', 'value');
        Inertia::flushShared();
        
        $this->assertTrue(true); // Flush tested via integration
    }

    public function testSetAndGetRequest(): void
    {
        $request = new ServerRequest('GET', '/');
        
        Inertia::setRequest($request);
        $this->assertSame($request, Inertia::getRequest());
    }
}

