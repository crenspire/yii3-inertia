<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Tests\Prop;

use ArrayIterator;
use Crenspire\Inertia\Header;
use Crenspire\Inertia\Inertia;
use Crenspire\Inertia\Prop\PropertyContext;
use Crenspire\Inertia\Prop\ProvidesInertiaProperties;
use Crenspire\Inertia\Prop\ProvidesInertiaProperty;
use Crenspire\Inertia\Prop\RenderContext;
use Crenspire\Inertia\Tests\Support\CallCounter;
use Crenspire\Inertia\Tests\Support\TestCase;
use JsonSerializable;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Stringable;

final class PropsResolutionTest extends TestCase
{
    public function testClosuresAreResolved(): void
    {
        $page = $this->createInertia()->createPage($this->request(), 'Home', [
            'value' => static fn (): int => 42,
            'nested' => ['inner' => static fn (): string => 'x'],
        ]);

        $this->assertSame(42, $page->props['value']);
        $this->assertSame(['inner' => 'x'], $page->props['nested']);
    }

    public function testJsonSerializableTraversableAndPropertyProviders(): void
    {
        $serializable = new class () implements JsonSerializable {
            /** @return array<string, int> */
            public function jsonSerialize(): array
            {
                return ['a' => 1];
            }
        };
        $provider = new class () implements ProvidesInertiaProperty {
            public function toInertiaProperty(PropertyContext $context): mixed
            {
                return $context->key . ':' . count($context->props);
            }
        };

        $page = $this->createInertia()->createPage($this->request(), 'Home', [
            'serializable' => $serializable,
            'iterator' => new ArrayIterator(['x' => 1]),
            'provider' => $provider,
        ]);

        $this->assertSame(['a' => 1], $page->props['serializable']);
        $this->assertSame(['x' => 1], $page->props['iterator']);
        $this->assertSame('provider:4', $page->props['provider']);
    }

    public function testPropertiesProviderContributesSeveralProps(): void
    {
        $provider = new class () implements ProvidesInertiaProperties {
            public function toInertiaProperties(RenderContext $context): iterable
            {
                yield 'component' => $context->component;
                yield 'path' => $context->request->getUri()->getPath();
            }
        };

        $page = $this->createInertia()->createPage($this->request(uri: '/p'), 'Home', [$provider, 'own' => true]);

        $this->assertSame(['errors' => [], 'component' => 'Home', 'path' => '/p', 'own' => true], $this->pageArray($page)['props']);
    }

    public function testDotNotationKeysAreUnpacked(): void
    {
        $page = $this->createInertia()->createPage($this->request(), 'Home', [
            'user' => static fn (): array => ['name' => 'Ann'],
            'user.role' => 'admin',
            'settings.theme.color' => 'dark',
        ]);

        $this->assertSame(['name' => 'Ann', 'role' => 'admin'], $page->props['user']);
        $this->assertSame(['theme' => ['color' => 'dark']], $page->props['settings']);
    }

    public function testPartialReloadOnlyResolvesRequestedProps(): void
    {
        $skipped = new CallCounter();

        $page = $this->createInertia()->createPage(
            $this->partialRequest('Users', [Header::PARTIAL_ONLY => 'users, filters.status']),
            'Users',
            [
                'users' => ['Ann'],
                'filters' => ['status' => 'active', 'role' => 'admin'],
                'stats' => $skipped,
            ],
        );

        $this->assertSame(['errors' => [], 'users' => ['Ann'], 'filters' => ['status' => 'active']], $this->pageArray($page)['props']);
        $this->assertSame(0, $skipped->calls);
    }

    public function testPartialReloadExcept(): void
    {
        $page = $this->createInertia()->createPage(
            $this->partialRequest('Users', [Header::PARTIAL_EXCEPT => 'stats']),
            'Users',
            ['users' => [], 'stats' => 1],
        );

        $this->assertSame(['errors', 'users'], array_keys($page->props));
    }

    public function testPartialHeadersForAnotherComponentAreIgnored(): void
    {
        $page = $this->createInertia()->createPage(
            $this->partialRequest('Other', [Header::PARTIAL_ONLY => 'a']),
            'Users',
            ['a' => 1, 'b' => 2],
        );

        $this->assertSame(['errors', 'a', 'b'], array_keys($page->props));
    }

    public function testChildrenOfResolvedClosureBypassPartialFilter(): void
    {
        $page = $this->createInertia()->createPage(
            $this->partialRequest('Users', [Header::PARTIAL_ONLY => 'data.a']),
            'Users',
            ['data' => static fn (): array => ['a' => 1, 'b' => 2]],
        );

        $this->assertSame(['a' => 1, 'b' => 2], $page->props['data']);
    }

    public function testAlwaysPropIsIncludedInPartialReloads(): void
    {
        $page = $this->createInertia()->createPage(
            $this->partialRequest('Users', [Header::PARTIAL_ONLY => 'users']),
            'Users',
            ['users' => [], 'auth' => Inertia::always(static fn (): string => 'user'), 'other' => 1],
        );

        $this->assertSame(['errors', 'users', 'auth'], array_keys($page->props));
        $this->assertSame('user', $page->props['auth']);
    }

    public function testOptionalPropOnlyResolvesWhenRequested(): void
    {
        $counter = new CallCounter('heavy');
        $inertia = $this->createInertia();
        $props = ['optional' => Inertia::optional($counter)];

        $full = $inertia->createPage($this->inertiaRequest(), 'Stats', $props);
        $this->assertArrayNotHasKey('optional', $full->props);
        $this->assertArrayNotHasKey('deferredProps', $full->metadata);
        $this->assertSame(0, $counter->calls);

        $partial = $inertia->createPage($this->partialRequest('Stats', [Header::PARTIAL_ONLY => 'optional']), 'Stats', $props);
        $this->assertSame('heavy', $partial->props['optional']);
        $this->assertSame(1, $counter->calls);
    }

    public function testDeferredPropsAreGroupedAndLoadedLater(): void
    {
        $counter = new CallCounter([1, 2]);
        $inertia = $this->createInertia();
        $props = [
            'permissions' => Inertia::defer($counter),
            'teams' => Inertia::defer(static fn (): array => [], 'sidebar'),
            'messages' => Inertia::defer(static fn (): array => [], 'sidebar'),
        ];

        $full = $inertia->createPage($this->request(), 'Dashboard', $props);
        $this->assertSame(['errors'], array_keys($full->props));
        $this->assertSame(['default' => ['permissions'], 'sidebar' => ['teams', 'messages']], $full->metadata['deferredProps']);
        $this->assertSame(0, $counter->calls);

        $partial = $inertia->createPage(
            $this->partialRequest('Dashboard', [Header::PARTIAL_ONLY => 'permissions']),
            'Dashboard',
            $props,
        );
        $this->assertSame([1, 2], $partial->props['permissions']);
        $this->assertArrayNotHasKey('deferredProps', $partial->metadata);
    }

    public function testClosureReturningPropTypeIsUnwrapped(): void
    {
        $page = $this->createInertia()->createPage($this->request(), 'Home', [
            'lazy' => static fn () => Inertia::defer(static fn (): int => 1),
        ]);

        $this->assertArrayNotHasKey('lazy', $page->props);
        $this->assertSame(['default' => ['lazy']], $page->metadata['deferredProps']);
    }

    public function testRescuedDeferredPropIsLoggedAndReported(): void
    {
        $logger = new class () extends AbstractLogger {
            /** @var list<string> */
            public array $messages = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->messages[] = $level . ':' . $context['prop'];
            }
        };

        $page = $this->createInertia(logger: $logger)->createPage(
            $this->partialRequest('Home', [Header::PARTIAL_ONLY => 'broken,ok']),
            'Home',
            [
                'broken' => Inertia::defer(static fn () => throw new RuntimeException('Boom'), rescue: true),
                'ok' => Inertia::defer(static fn (): int => 1),
            ],
        );

        $this->assertSame(['errors', 'ok'], array_keys($page->props));
        $this->assertSame(['broken'], $page->metadata['rescuedProps']);
        $this->assertSame(['error:broken'], $logger->messages);
    }

    public function testDeferredPropWithoutRescueThrows(): void
    {
        $this->expectException(RuntimeException::class);

        $this->createInertia()->createPage(
            $this->partialRequest('Home', [Header::PARTIAL_ONLY => 'broken']),
            'Home',
            ['broken' => Inertia::defer(static fn () => throw new RuntimeException('Boom'))],
        );
    }

    public function testMergeMetadata(): void
    {
        $page = $this->createInertia()->createPage($this->inertiaRequest(), 'Feed', [
            'posts' => Inertia::merge(['p1']),
            'notifications' => Inertia::merge(['n1'])->prepend(),
            'settings' => Inertia::deepMerge(['a' => ['b' => 1]]),
            'users' => Inertia::merge(['data' => []])->append('data', 'id')->prepend('pinned'),
            'tags' => Inertia::merge([])->matchOn('slug'),
        ]);

        $this->assertSame(['posts', 'users.data', 'tags'], $page->metadata['mergeProps']);
        $this->assertSame(['notifications', 'users.pinned'], $page->metadata['prependProps']);
        $this->assertSame(['settings'], $page->metadata['deepMergeProps']);
        $this->assertSame(['users.data.id', 'tags.slug'], $page->metadata['matchPropsOn']);
    }

    public function testResetHeaderSkipsMergeMetadata(): void
    {
        $page = $this->createInertia()->createPage(
            $this->partialRequest('Feed', [Header::PARTIAL_ONLY => 'posts', Header::RESET => 'posts']),
            'Feed',
            ['posts' => Inertia::merge(['p1'])],
        );

        $this->assertSame(['p1'], $page->props['posts']);
        $this->assertArrayNotHasKey('mergeProps', $page->metadata);
    }

    public function testDeferredMergePropAnnouncesMergeOnFirstLoad(): void
    {
        $page = $this->createInertia()->createPage($this->request(), 'Feed', [
            'posts' => Inertia::defer(static fn (): array => [])->merge(),
        ]);

        $this->assertSame(['posts'], $page->metadata['mergeProps']);
        $this->assertSame(['default' => ['posts']], $page->metadata['deferredProps']);
    }

    public function testOncePropIsSkippedWhenClientAlreadyHasIt(): void
    {
        $counter = new CallCounter(['plans']);
        $inertia = $this->createInertia();
        $props = ['plans' => Inertia::once($counter)->until(60)];

        $first = $inertia->createPage($this->inertiaRequest(), 'Billing', $props);
        $this->assertSame(['plans'], $first->props['plans']);
        $this->assertSame('plans', $first->metadata['onceProps']['plans']['prop']);
        $this->assertGreaterThan(time() * 1000, $first->metadata['onceProps']['plans']['expiresAt']);

        $again = $inertia->createPage($this->inertiaRequest(headers: [Header::EXCEPT_ONCE_PROPS => 'plans']), 'Billing', $props);
        $this->assertArrayNotHasKey('plans', $again->props);
        $this->assertArrayHasKey('plans', $again->metadata['onceProps']);
        $this->assertSame(1, $counter->calls);

        $fresh = $inertia->createPage(
            $this->inertiaRequest(headers: [Header::EXCEPT_ONCE_PROPS => 'plans']),
            'Billing',
            ['plans' => Inertia::once($counter)->fresh()],
        );
        $this->assertSame(['plans'], $fresh->props['plans']);
    }

    public function testOncePropCustomKeyAndNoExpiry(): void
    {
        $page = $this->createInertia()->createPage($this->inertiaRequest(), 'Billing', [
            'countries' => Inertia::once(static fn (): array => [])->as('country-list'),
        ]);

        $this->assertSame(['country-list' => ['prop' => 'countries', 'expiresAt' => null]], $page->metadata['onceProps']);
    }

    public function testOncePropIsResolvedOnFirstVisitEvenIfHeaderIsSent(): void
    {
        $page = $this->createInertia()->createPage(
            $this->request(headers: [Header::EXCEPT_ONCE_PROPS => 'plans']),
            'Billing',
            ['plans' => Inertia::once(static fn (): int => 1)],
        );

        $this->assertSame(1, $page->props['plans']);
    }
}
