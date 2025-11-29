<?php

declare(strict_types=1);

namespace Crenspire\Yii3Inertia\Tests\Integration;

use Crenspire\Yii3Inertia\ConfigProvider;
use Crenspire\Yii3Inertia\Inertia;
use Crenspire\Yii3Inertia\Middleware\InertiaMiddleware;
use Crenspire\Yii3Inertia\ResponseFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

class Yii3IntegrationTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create DI container with ConfigProvider
        $this->container = new Container(
            ContainerConfig::create()
                ->withDefinitions([
                    ConfigProvider::class,
                    \Psr\Http\Message\ResponseFactoryInterface::class => Psr17Factory::class,
                    \Psr\Http\Message\StreamFactoryInterface::class => Psr17Factory::class,
                ])
        );
        
        Inertia::flushShared();
    }

    public function testConfigProviderRegistersResponseFactory(): void
    {
        $responseFactory = $this->container->get(ResponseFactory::class);
        
        $this->assertInstanceOf(ResponseFactory::class, $responseFactory);
    }

    public function testConfigProviderRegistersInertiaMiddleware(): void
    {
        $middleware = $this->container->get(InertiaMiddleware::class);
        
        $this->assertInstanceOf(InertiaMiddleware::class, $middleware);
    }

    public function testResponseFactoryCanBeResolvedFromContainer(): void
    {
        $responseFactory = $this->container->get(ResponseFactory::class);
        
        $payload = [
            'component' => 'Test',
            'props' => ['test' => 'value'],
            'url' => '/test',
            'version' => '1.0',
        ];
        
        $response = $responseFactory->json($payload);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('true', $response->getHeaderLine('X-Inertia'));
    }

    public function testMiddlewareCanBeResolvedFromContainer(): void
    {
        $middleware = $this->container->get(InertiaMiddleware::class);
        
        $request = (new ServerRequest('GET', '/'))
            ->withHeader('X-Inertia', 'true');
        
        $handler = new class implements \Psr\Http\Server\RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
                return $factory->createResponse(200);
            }
        };
        
        $response = $middleware->process($request, $handler);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testConfigProviderWorksWithMinimalSetup(): void
    {
        // Test that ConfigProvider works with minimal Yii3 setup (DI only)
        $container = new Container(
            ContainerConfig::create()
                ->withDefinitions([
                    ConfigProvider::class,
                    \Psr\Http\Message\ResponseFactoryInterface::class => Psr17Factory::class,
                    \Psr\Http\Message\StreamFactoryInterface::class => Psr17Factory::class,
                ])
        );
        
        $responseFactory = $container->get(ResponseFactory::class);
        $middleware = $container->get(InertiaMiddleware::class);
        
        $this->assertInstanceOf(ResponseFactory::class, $responseFactory);
        $this->assertInstanceOf(InertiaMiddleware::class, $middleware);
    }

    public function testResponseFactoryFallsBackToDefaultTemplate(): void
    {
        $responseFactory = $this->container->get(ResponseFactory::class);
        
        $payload = [
            'component' => 'Test',
            'props' => ['test' => 'value'],
            'url' => '/test',
            'version' => '1.0',
        ];
        
        $response = $responseFactory->html($payload, 'inertia');
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/html; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        
        $body = (string) $response->getBody();
        $this->assertStringContainsString('<div id="app"', $body);
        $this->assertStringContainsString('Test', $body);
    }

    public function testConfigProviderGracefullyHandlesMissingView(): void
    {
        // ConfigProvider should work even if yiisoft/view is not installed
        $container = new Container(
            ContainerConfig::create()
                ->withDefinitions([
                    ConfigProvider::class,
                    \Psr\Http\Message\ResponseFactoryInterface::class => Psr17Factory::class,
                    \Psr\Http\Message\StreamFactoryInterface::class => Psr17Factory::class,
                ])
        );
        
        $responseFactory = $container->get(ResponseFactory::class);
        
        // Should work with default template
        $payload = [
            'component' => 'Test',
            'props' => [],
            'url' => '/',
            'version' => '1.0',
        ];
        
        $response = $responseFactory->html($payload, 'inertia');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testMiddlewareProcessesInertiaRequestWithContainer(): void
    {
        $middleware = $this->container->get(InertiaMiddleware::class);
        
        $request = (new ServerRequest('GET', '/'))
            ->withHeader('X-Inertia', 'true')
            ->withAttribute('inertia_payload', [
                'component' => 'Test',
                'props' => ['test' => 'value'],
                'url' => '/',
                'version' => '1.0',
            ]);
        
        $handler = new class implements \Psr\Http\Server\RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
                return $factory->createResponse(200);
            }
        };
        
        $response = $middleware->process($request, $handler);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('true', $response->getHeaderLine('X-Inertia'));
    }
}

