<?php

declare(strict_types=1);

namespace Crenspire\Yii3Inertia\Tests\Unit;

use Crenspire\Yii3Inertia\ResponseFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

class ResponseFactoryTest extends TestCase
{
    private ResponseFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $psr17Factory = new Psr17Factory();
        $this->factory = new ResponseFactory($psr17Factory, $psr17Factory);
    }

    public function testJsonResponse(): void
    {
        $payload = [
            'component' => 'TestComponent',
            'props' => ['test' => 'value'],
            'url' => '/test',
            'version' => '1.0',
        ];

        $response = $this->factory->json($payload);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('true', $response->getHeaderLine('X-Inertia'));
        
        $body = (string) $response->getBody();
        $data = json_decode($body, true);
        $this->assertEquals('TestComponent', $data['component']);
        $this->assertEquals(['test' => 'value'], $data['props']);
    }

    public function testHtmlResponse(): void
    {
        $payload = [
            'component' => 'TestComponent',
            'props' => ['test' => 'value'],
            'url' => '/test',
            'version' => '1.0',
        ];

        $response = $this->factory->html($payload, 'inertia');
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/html; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        
        $body = (string) $response->getBody();
        $this->assertStringContainsString('<div id="app"', $body);
        $this->assertStringContainsString('TestComponent', $body);
    }
}

