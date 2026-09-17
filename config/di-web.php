<?php

declare(strict_types=1);

use Crenspire\Inertia\Flash\FlashStoreInterface;
use Crenspire\Inertia\Flash\SessionFlashStore;
use Crenspire\Inertia\Inertia;
use Crenspire\Inertia\Ssr\GatewayInterface;
use Crenspire\Inertia\Ssr\HttpGateway;
use Crenspire\Inertia\Version\CallbackVersion;
use Crenspire\Inertia\Version\ManifestVersion;
use Crenspire\Inertia\Version\StaticVersion;
use Crenspire\Inertia\Version\VersionProviderInterface;
use Crenspire\Inertia\View\PhpRootViewRenderer;
use Crenspire\Inertia\View\RootViewRendererInterface;
use Crenspire\Inertia\Vite\Vite;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Session\SessionInterface;

/** @var array<string, mixed> $params */

$config = $params['crenspire/yii3-inertia'];

$alias = static function (ContainerInterface $container, string $path): string {
    if (!str_starts_with($path, '@') || !$container->has(Aliases::class)) {
        return $path;
    }

    return $container->get(Aliases::class)->get($path);
};

$optional = static fn (ContainerInterface $container, string $id): mixed => $container->has($id) ? $container->get($id) : null;

$definitions = [
    Vite::class => static function (ContainerInterface $container) use ($config, $alias): Vite {
        $vite = $config['vite'];

        return new Vite(
            publicPath: $alias($container, $vite['publicPath']),
            buildDirectory: $vite['buildDirectory'],
            manifest: $vite['manifest'],
            devServerUrl: $vite['devServerUrl'],
            hotFile: $vite['hotFile'],
            baseUrl: $alias($container, $vite['baseUrl']),
        );
    },

    VersionProviderInterface::class => static function (ContainerInterface $container) use ($config, $alias): VersionProviderInterface {
        $version = $config['version'];

        return match (true) {
            is_string($version) => new StaticVersion($version),
            is_callable($version) => new CallbackVersion($version),
            is_string($config['manifestPath']) => new ManifestVersion($alias($container, $config['manifestPath'])),
            default => new ManifestVersion($container->get(Vite::class)->getManifestPath()),
        };
    },

    RootViewRendererInterface::class => static function (ContainerInterface $container) use ($config, $alias): RootViewRendererInterface {
        $parameters = ['vite' => $container->get(Vite::class)];
        foreach ($config['viewParameters'] as $name => $value) {
            $parameters[$name] = $value instanceof Closure ? $value($container) : $value;
        }

        return new PhpRootViewRenderer($alias($container, $config['rootView']), $parameters);
    },

    Inertia::class => static function (ContainerInterface $container) use ($config, $optional): Inertia {
        return new Inertia(
            responseFactory: $container->get(ResponseFactoryInterface::class),
            streamFactory: $container->get(StreamFactoryInterface::class),
            rootViewRenderer: $container->get(RootViewRendererInterface::class),
            version: $container->get(VersionProviderInterface::class),
            flashStore: $optional($container, FlashStoreInterface::class),
            ssrGateway: $optional($container, GatewayInterface::class),
            logger: $optional($container, LoggerInterface::class),
            sharedProps: $config['sharedProps'],
            encryptHistory: $config['encryptHistory'],
            allErrors: $config['allErrors'],
        );
    },
];

if (interface_exists(SessionInterface::class)) {
    $definitions[FlashStoreInterface::class] = SessionFlashStore::class;
}

if ($config['ssr']['enabled']) {
    $definitions[GatewayInterface::class] = static function (ContainerInterface $container) use ($config, $optional): GatewayInterface {
        return new HttpGateway(
            client: $container->get(ClientInterface::class),
            requestFactory: $container->get(RequestFactoryInterface::class),
            streamFactory: $container->get(StreamFactoryInterface::class),
            url: $config['ssr']['url'],
            except: $config['ssr']['except'],
            throwOnError: $config['ssr']['throwOnError'],
            logger: $optional($container, LoggerInterface::class),
        );
    };
}

return $definitions;
