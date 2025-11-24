<?php

declare(strict_types=1);

namespace Crenspire\Yii3Inertia\Tests\Integration;

use Crenspire\Yii3Inertia\Inertia;
use Crenspire\Yii3Inertia\Middleware\InertiaMiddleware;
use Crenspire\Yii3Inertia\ResponseFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class InertiaIntegrationTest extends TestCase
{
    private ResponseFactory $responseFactory;
    private InertiaMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $psr17Factory = new Psr17Factory();
        $this->responseFactory = new ResponseFactory($psr17Factory, $psr17Factory);
        $this->middleware = new InertiaMiddleware($this->responseFactory, $psr17Factory);
        Inertia::flushShared();
    }

    public function testRenderReturnsPayloadForInertiaRequest(): void
    {
        $request = (new ServerRequest('GET', '/'))
            ->withHeader('X-Inertia', 'true');
        
        Inertia::setRequest($request);
        
        $payload = Inertia::render('TestComponent', ['test' => 'value']);
        
        $this->assertEquals('TestComponent', $payload['component']);
        $this->assertEquals('value', $payload['props']['test']);
        $this->assertArrayHasKey('url', $payload);
        $this->assertArrayHasKey('version', $payload);
    }

    public function testSharedPropsAreIncluded(): void
    {
        Inertia::share('shared', 'shared-value');
        
        $request = (new ServerRequest('GET', '/'))
            ->withHeader('X-Inertia', 'true');
        
        Inertia::setRequest($request);
        
        $payload = Inertia::render('TestComponent', ['local' => 'local-value']);
        
        $this->assertEquals('shared-value', $payload['props']['shared']);
        $this->assertEquals('local-value', $payload['props']['local']);
    }

    public function testSharedPropsClosureIsEvaluated(): void
    {
        $timestamp = time();
        Inertia::share('timestamp', function () use ($timestamp) {
            return $timestamp;
        });
        
        $request = (new ServerRequest('GET', '/'))
            ->withHeader('X-Inertia', 'true');
        
        Inertia::setRequest($request);
        
        $payload = Inertia::render('TestComponent', []);
        
        $this->assertEquals($timestamp, $payload['props']['timestamp']);
    }

    public function testPartialReloadFiltersProps(): void
    {
        Inertia::share('shared', 'shared-value');
        
        $request = (new ServerRequest('GET', '/'))
            ->withHeader('X-Inertia', 'true')
            ->withHeader('X-Inertia-Partial-Component', 'TestComponent')
            ->withHeader('X-Inertia-Partial-Data', 'local');
        
        Inertia::setRequest($request);
        
        $payload = Inertia::render('TestComponent', [
            'local' => 'local-value',
            'excluded' => 'excluded-value',
        ]);
        
        // Shared props should always be included
        $this->assertArrayHasKey('shared', $payload['props']);
        // Requested partial prop should be included
        $this->assertArrayHasKey('local', $payload['props']);
        // Non-requested prop should be excluded
        $this->assertArrayNotHasKey('excluded', $payload['props']);
    }

    public function testVersionIsIncludedInPayload(): void
    {
        Inertia::version('test-version-123');
        
        $request = (new ServerRequest('GET', '/'))
            ->withHeader('X-Inertia', 'true');
        
        Inertia::setRequest($request);
        
        $payload = Inertia::render('TestComponent', []);
        
        $this->assertEquals('test-version-123', $payload['version']);
    }

    public function testMiddlewareProcessesInertiaRequest(): void
    {
        $request = (new ServerRequest('GET', '/'))
            ->withHeader('X-Inertia', 'true')
            ->withAttribute('inertia_payload', [
                'component' => 'TestComponent',
                'props' => ['test' => 'value'],
                'url' => '/',
                'version' => '1.0',
            ]);
        
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
                return $factory->createResponse(200);
            }
        };
        
        $response = $this->middleware->process($request, $handler);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('true', $response->getHeaderLine('X-Inertia'));
    }

    public function testVersionMismatchReturnsLocationRedirect(): void
    {
        Inertia::version('current-version');
        
        $request = (new ServerRequest('GET', '/'))
            ->withHeader('X-Inertia', 'true')
            ->withHeader('X-Inertia-Version', 'old-version');
        
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
                return $factory->createResponse(200);
            }
        };
        
        $response = $this->middleware->process($request, $handler);
        
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('X-Inertia-Location'));
    }

    public function testLocationReturns409ForInertiaRequest(): void
    {
        $request = (new ServerRequest('GET', '/'))
            ->withHeader('X-Inertia', 'true');
        
        Inertia::setRequest($request);
        
        $location = Inertia::location('/dashboard');
        
        $this->assertEquals(409, $location['status']);
        $this->assertEquals('/dashboard', $location['location']);
    }

    public function testLocationReturns302ForNonInertiaRequest(): void
    {
        $request = (new ServerRequest('GET', '/'));
        
        Inertia::setRequest($request);
        
        $location = Inertia::location('/dashboard');
        
        $this->assertEquals(302, $location['status']);
        $this->assertEquals('/dashboard', $location['location']);
    }

    public function testUrlIncludesQueryString(): void
    {
        $request = (new ServerRequest('GET', '/test'))
            ->withHeader('X-Inertia', 'true');
        $request = $request->withUri($request->getUri()->withQuery('param=value'));
        
        Inertia::setRequest($request);
        
        $payload = Inertia::render('TestComponent', []);
        
        $this->assertStringContainsString('param=value', $payload['url']);
    }

    public function testEmptyPartialDataReturnsAllProps(): void
    {
        Inertia::share('shared', 'shared-value');
        
        $request = (new ServerRequest('GET', '/'))
            ->withHeader('X-Inertia', 'true')
            ->withHeader('X-Inertia-Partial-Component', 'TestComponent')
            ->withHeader('X-Inertia-Partial-Data', '');
        
        Inertia::setRequest($request);
        
        $payload = Inertia::render('TestComponent', [
            'local' => 'local-value',
        ]);
        
        $this->assertArrayHasKey('shared', $payload['props']);
        $this->assertArrayHasKey('local', $payload['props']);
    }

    public function testRenderThrowsExceptionForEmptyComponent(): void
    {
        $request = new ServerRequest('GET', '/');
        Inertia::setRequest($request);
        
        $this->expectException(\InvalidArgumentException::class);
        Inertia::render('', []);
    }
}

