<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Tests;

use Crenspire\Inertia\Flash\ArrayFlashStore;
use Crenspire\Inertia\Flash\InertiaFlash;
use Crenspire\Inertia\Header;
use Crenspire\Inertia\Inertia;
use Crenspire\Inertia\Page;
use Crenspire\Inertia\Ssr\GatewayInterface;
use Crenspire\Inertia\Ssr\SsrResponse;
use Crenspire\Inertia\Tests\Support\TestCase;
use InvalidArgumentException;
use Nyholm\Psr7\Uri;
use Psr\Http\Message\ServerRequestInterface;

final class InertiaTest extends TestCase
{
    public function testInertiaVisitReturnsJsonPage(): void
    {
        $response = $this->createInertia()->render(
            $this->inertiaRequest(uri: 'https://example.com/users?page=2'),
            'Users/Index',
            ['users' => ['Ann'], 'path' => '/a/b'],
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame('true', $response->getHeaderLine(Header::INERTIA));
        $this->assertSame(Header::INERTIA, $response->getHeaderLine('Vary'));
        $this->assertStringContainsString('"path":"/a/b"', (string) $response->getBody());
        $this->assertSame([
            'component' => 'Users/Index',
            'props' => ['errors' => [], 'users' => ['Ann'], 'path' => '/a/b'],
            'url' => '/users?page=2',
            'version' => 'v1',
            'sharedProps' => ['errors'],
        ], $this->decodeJson($response));
    }

    public function testFirstVisitRendersRootView(): void
    {
        $response = $this->createInertia()->render($this->request(), 'Home', ['title' => '</script>']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertSame(Header::INERTIA, $response->getHeaderLine('Vary'));
        $this->assertFalse($response->hasHeader(Header::INERTIA));
        $this->assertNotNull($this->renderer->view);
        $this->assertSame('Home', $this->renderer->view->page->component);
        $this->assertStringNotContainsString('</script>"', (string) $response->getBody());
    }

    public function testEmptyPropsAndErrorsAreEncodedAsObjects(): void
    {
        $response = $this->createInertia()->render($this->inertiaRequest(), 'Home');

        $this->assertStringContainsString('"props":{"errors":{}}', (string) $response->getBody());
        $this->assertSame(
            '{"component":"Home","props":{},"url":"\/","version":""}',
            json_encode(new Page('Home', [], '/', '')),
        );
    }

    public function testEmptyComponentIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createInertia()->render($this->request(), '');
    }

    public function testUrlIsRelativeAndKeepsTrailingSlashAndQuery(): void
    {
        $inertia = $this->createInertia();

        $this->assertSame('/', $inertia->createPage($this->request(uri: 'https://example.com'), 'Home')->url);
        $this->assertSame('/docs/', $inertia->createPage($this->request(uri: 'https://example.com/docs/'), 'Home')->url);
        $this->assertSame('/a?b=1&c=2', $inertia->createPage($this->request(uri: '/a?b=1&c=2'), 'Home')->url);
    }

    public function testUrlResolverCanBeCustomized(): void
    {
        $inertia = $this->createInertia()->withUrlResolver(
            static fn (ServerRequestInterface $request): string => '/app' . $request->getUri()->getPath()
        );

        $this->assertSame('/app/users', $inertia->createPage($this->request(uri: '/users'), 'Home')->url);
    }

    public function testSharedPropsFromConfigAndRequestAreMerged(): void
    {
        $inertia = $this->createInertia(sharedProps: ['app' => 'Demo', 'user' => null])
            ->withSharedProps(['locale' => static fn (): string => 'en']);

        $request = Inertia::share($this->request(), 'user', ['name' => 'Ann']);
        $request = Inertia::share($request, ['flag' => true]);

        $page = $inertia->createPage($request, 'Home', ['title' => 'Hi', 'app' => 'Override']);

        $this->assertSame(
            ['errors' => [], 'app' => 'Override', 'user' => ['name' => 'Ann'], 'locale' => 'en', 'flag' => true, 'title' => 'Hi'],
            $this->pageArray($page)['props'],
        );
        $this->assertSame(['errors', 'app', 'user', 'locale', 'flag'], $page->metadata['sharedProps']);
    }

    public function testWithersDoNotMutateOriginal(): void
    {
        $inertia = $this->createInertia();
        $shared = $inertia->withSharedProps(['a' => 1])->withEncryptHistory()->withAllErrors();

        $this->assertArrayNotHasKey('a', $inertia->createPage($this->request(), 'Home')->props);
        $this->assertArrayNotHasKey('encryptHistory', $inertia->createPage($this->request(), 'Home')->metadata);
        $this->assertSame(1, $shared->createPage($this->request(), 'Home')->props['a']);
        $this->assertTrue($shared->createPage($this->request(), 'Home')->metadata['encryptHistory']);
    }

    public function testRequestAttributeErrorsUseFirstMessage(): void
    {
        $request = Inertia::withErrors($this->request(), ['name' => ['Required.', 'Too short.'], 'email' => 'Invalid.']);

        $page = $this->createInertia()->createPage($request, 'Form');

        $this->assertSame(['name' => 'Required.', 'email' => 'Invalid.'], $this->pageArray($page)['props']['errors']);
    }

    public function testAllErrorsSendsEveryMessage(): void
    {
        $request = Inertia::withErrors($this->request(), ['name' => ['Required.', 'Too short.'], 'email' => 'Invalid.']);

        $page = $this->createInertia()->withAllErrors()->createPage($request, 'Form');

        $this->assertSame(['name' => ['Required.', 'Too short.'], 'email' => ['Invalid.']], $this->pageArray($page)['props']['errors']);
    }

    public function testFlashedErrorsArePulledOnce(): void
    {
        $store = new ArrayFlashStore();
        (new InertiaFlash($store))->errors(['name' => 'Required.']);
        $inertia = $this->createInertia(flashStore: $store);

        $this->assertSame(['name' => 'Required.'], $this->pageArray($inertia->createPage($this->request(), 'Form'))['props']['errors']);
        $this->assertSame([], $this->pageArray($inertia->createPage($this->request(), 'Form'))['props']['errors']);
    }

    public function testErrorBagHeaderScopesDefaultBag(): void
    {
        $request = Inertia::withErrors(
            $this->inertiaRequest(headers: [Header::ERROR_BAG => 'login']),
            ['email' => 'Invalid.'],
        );

        $page = $this->createInertia()->createPage($request, 'Form');

        $this->assertSame(['login' => ['email' => 'Invalid.']], $this->pageArray($page)['props']['errors']);
    }

    public function testNamedBagsWithoutDefaultAreReturnedByName(): void
    {
        $request = Inertia::withErrors($this->request(), ['email' => 'Invalid.'], 'login');
        $request = Inertia::withErrors($request, ['name' => 'Taken.'], 'register');

        $page = $this->createInertia()->createPage($request, 'Form');

        $this->assertSame(
            ['login' => ['email' => 'Invalid.'], 'register' => ['name' => 'Taken.']],
            $this->pageArray($page)['props']['errors'],
        );
    }

    public function testErrorsAreIncludedInPartialReloadsForOtherProps(): void
    {
        $request = Inertia::withErrors($this->partialRequest('Form', [Header::PARTIAL_ONLY => 'other']), ['a' => 'b']);

        $page = $this->createInertia()->createPage($request, 'Form', ['other' => 1, 'skipped' => 2]);

        $this->assertSame(['errors' => ['a' => 'b'], 'other' => 1], $this->pageArray($page)['props']);
    }

    public function testHistoryAndFlashMetadata(): void
    {
        $store = new ArrayFlashStore();
        (new InertiaFlash($store))->flash('message', 'Saved')->flash(['level' => 'success'])->clearHistory()->preserveFragment();
        $inertia = $this->createInertia(flashStore: $store);

        $page = $inertia->createPage(Inertia::encryptHistory($this->request()), 'Home');

        $this->assertTrue($page->metadata['encryptHistory']);
        $this->assertTrue($page->metadata['clearHistory']);
        $this->assertTrue($page->metadata['preserveFragment']);
        $this->assertSame(['message' => 'Saved', 'level' => 'success'], $page->metadata['flash']);

        $next = $inertia->withEncryptHistory()->createPage(Inertia::encryptHistory($this->request(), false), 'Home');
        $this->assertSame(['sharedProps' => ['errors']], $next->metadata);
    }

    public function testClearHistoryRequestAttribute(): void
    {
        $page = $this->createInertia()->createPage(Inertia::clearHistory($this->request()), 'Home');

        $this->assertTrue($page->metadata['clearHistory']);
    }

    public function testLocationForInertiaVisitReturnsConflict(): void
    {
        $response = $this->createInertia()->location($this->inertiaRequest(), new Uri('https://other.test/x'));

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('https://other.test/x', $response->getHeaderLine(Header::LOCATION));
        $this->assertFalse($response->hasHeader('Location'));
    }

    public function testLocationForRegularRequestRedirects(): void
    {
        $response = $this->createInertia()->location($this->request(), '/login');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', $response->getHeaderLine('Location'));
    }

    public function testBackUsesRefererOrFallback(): void
    {
        $inertia = $this->createInertia();

        $this->assertSame('/form', $inertia->back($this->request(headers: ['Referer' => '/form']))->getHeaderLine('Location'));
        $this->assertSame('/home', $inertia->back($this->request(), '/home')->getHeaderLine('Location'));
        $this->assertSame(303, $inertia->redirect('/x', 303)->getStatusCode());
    }

    public function testSsrResponseIsPassedToView(): void
    {
        $gateway = new class () implements GatewayInterface {
            public function dispatch(Page $page, ServerRequestInterface $request): ?SsrResponse
            {
                return new SsrResponse('<title>SSR</title>', '<div id="app">' . $page->component . '</div>');
            }
        };

        $response = $this->createInertia(ssrGateway: $gateway)->render($this->request(), 'Home');

        $this->assertSame('<html><div id="app">Home</div></html>', (string) $response->getBody());
        $this->assertSame('<title>SSR</title>', $this->renderer->view?->head());
    }

    public function testSsrIsNotUsedForInertiaVisits(): void
    {
        $gateway = new class () implements GatewayInterface {
            public int $calls = 0;

            public function dispatch(Page $page, ServerRequestInterface $request): ?SsrResponse
            {
                $this->calls++;

                return null;
            }
        };

        $this->createInertia(ssrGateway: $gateway)->render($this->inertiaRequest(), 'Home');

        $this->assertSame(0, $gateway->calls);
    }

    public function testIsInertiaRequest(): void
    {
        $this->assertTrue(Inertia::isInertiaRequest($this->inertiaRequest()));
        $this->assertFalse(Inertia::isInertiaRequest($this->request()));
    }
}
