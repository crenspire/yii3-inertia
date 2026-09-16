<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Tests\Config;

use Crenspire\Inertia\Flash\ArrayFlashStore;
use Crenspire\Inertia\Flash\FlashStoreInterface;
use Crenspire\Inertia\Flash\InertiaFlash;
use Crenspire\Inertia\Header;
use Crenspire\Inertia\Inertia;
use Crenspire\Inertia\Middleware\InertiaMiddleware;
use Crenspire\Inertia\Version\VersionProviderInterface;
use Crenspire\Inertia\Vite\Vite;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

/**
 * Builds a yiisoft/di container from the package config files, as yiisoft/config does in an application.
 */
final class ConfigTest extends TestCase
{
    private const ROOT = __DIR__ . '/../..';

    public function testDefaultsWithAliases(): void
    {
        $container = $this->container();

        $inertia = $container->get(Inertia::class);
        $this->assertInstanceOf(Inertia::class, $inertia);
        $this->assertInstanceOf(InertiaMiddleware::class, $container->get(InertiaMiddleware::class));
        $this->assertSame(
            realpath(self::ROOT . '/tests/Fixtures/public/build/.vite/manifest.json'),
            realpath($container->get(Vite::class)->getManifestPath()),
        );
        $this->assertSame(
            hash_file('xxh128', self::ROOT . '/tests/Fixtures/public/build/.vite/manifest.json'),
            $container->get(VersionProviderInterface::class)->getVersion(),
        );
    }

    public function testFirstVisitRendersStubTemplate(): void
    {
        $container = $this->container(['sharedProps' => ['app' => 'Demo']]);
        $response = $this->handle($container, new ServerRequest('GET', 'https://example.com/users'));

        $html = (string) $response->getBody();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('<link rel="stylesheet" href="/build/assets/app-C7d8e9.css">', $html);
        $this->assertStringContainsString('<script type="module" src="/build/assets/app-B1a2c3.js"></script>', $html);
        $this->assertMatchesRegularExpression('#<script data-page="app" type="application/json">\{"component":"Users\\\\/Index".*"app":"Demo"#', $html);
        $this->assertStringContainsString('<div id="app"></div>', $html);
    }

    public function testInertiaVisitUsesVersionFromManifest(): void
    {
        $container = $this->container();
        $version = $container->get(VersionProviderInterface::class)->getVersion();

        $stale = $this->handle($container, new ServerRequest('GET', '/users', [Header::INERTIA => 'true', Header::VERSION => 'old']));
        $this->assertSame(409, $stale->getStatusCode());

        $current = $this->handle($container, new ServerRequest('GET', '/users', [Header::INERTIA => 'true', Header::VERSION => $version]));
        $this->assertSame('application/json', $current->getHeaderLine('Content-Type'));
        $this->assertSame('Users/Index', json_decode((string) $current->getBody(), true)['component']);
    }

    public function testParamsOverrides(): void
    {
        $container = $this->container([
            'version' => static fn (): string => 'from-callback',
            'encryptHistory' => true,
            'vite' => ['devServerUrl' => 'http://localhost:5173'],
            'viewParameters' => ['title' => static fn (ContainerInterface $container): string => 'Closure title'],
        ]);

        $this->assertSame('from-callback', $container->get(Inertia::class)->getVersion());

        $response = $this->handle($container, new ServerRequest('GET', '/'));
        $this->assertStringContainsString('http://localhost:5173/@vite/client', (string) $response->getBody());
        $this->assertStringContainsString('"encryptHistory":true', (string) $response->getBody());
    }

    public function testFlashStoreIsUsedWhenDefined(): void
    {
        $store = new ArrayFlashStore();
        $container = $this->container([], [FlashStoreInterface::class => $store]);
        $container->get(InertiaFlash::class)->errors(['name' => 'Required.']);

        $page = $container->get(Inertia::class)->createPage(new ServerRequest('GET', '/'), 'Form');

        $this->assertEquals((object) ['name' => 'Required.'], $page->props['errors']);
    }

    /**
     * @param array<string, mixed> $overrides
     * @param array<string, mixed> $definitions
     */
    private function container(array $overrides = [], array $definitions = []): Container
    {
        $params = require self::ROOT . '/config/params.php';
        $params['crenspire/yii3-inertia'] = array_replace_recursive(
            $params['crenspire/yii3-inertia'],
            ['rootView' => '@root/stubs/inertia.php', 'vite' => ['publicPath' => '@root/tests/Fixtures/public']],
            $overrides,
        );

        $packageDefinitions = (static fn (array $params): array => require self::ROOT . '/config/di-web.php')($params);
        // The test suite has yiisoft/session installed but no session configured.
        unset($packageDefinitions[FlashStoreInterface::class]);

        return new Container(ContainerConfig::create()->withDefinitions([
            ...$packageDefinitions,
            Aliases::class => new Aliases(['@root' => self::ROOT, '@public' => '@root/public', '@baseUrl' => '/']),
            ResponseFactoryInterface::class => Psr17Factory::class,
            StreamFactoryInterface::class => Psr17Factory::class,
            ...$definitions,
        ]));
    }

    private function handle(Container $container, ServerRequestInterface $request): ResponseInterface
    {
        $inertia = $container->get(Inertia::class);
        $handler = new class ($inertia) implements RequestHandlerInterface {
            public function __construct(private readonly Inertia $inertia)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->inertia->render($request, 'Users/Index', ['users' => []]);
            }
        };

        return $container->get(InertiaMiddleware::class)->process($request, $handler);
    }
}
