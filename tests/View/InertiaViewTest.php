<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Tests\View;

use Crenspire\Inertia\Page;
use Crenspire\Inertia\Ssr\SsrResponse;
use Crenspire\Inertia\View\InertiaView;
use PHPUnit\Framework\TestCase;

final class InertiaViewTest extends TestCase
{
    public function testBodyEmbedsPageInScriptElement(): void
    {
        $view = new InertiaView(new Page('Home', ['html' => '</script><script>alert(1)</script>', 'url' => '/a'], '/a', 'v1'));

        $body = $view->body();

        $this->assertStringStartsWith('<script data-page="app" type="application/json">{', $body);
        $this->assertStringEndsWith('</script><div id="app"></div>', $body);
        $this->assertSame(1, substr_count($body, '</script>'));
        $this->assertStringContainsString('\/a', $body);

        preg_match('#<script[^>]*>(.*)</script>#', $body, $matches);
        $this->assertSame('</script><script>alert(1)</script>', json_decode($matches[1], true)['props']['html']);
    }

    public function testLegacyBodyUsesDataPageAttribute(): void
    {
        $view = new InertiaView(new Page('Home', ['quote' => '"x" & \'y\''], '/', 'v1'));

        $body = $view->legacyBody('root');

        $this->assertMatchesRegularExpression('#^<div id="root" data-page="([^"]*)"></div>$#', $body);
        preg_match('#data-page="([^"]*)"#', $body, $matches);
        $page = json_decode(html_entity_decode($matches[1], ENT_QUOTES), true);
        $this->assertSame('"x" & \'y\'', $page['props']['quote']);
    }

    public function testSsrResponseReplacesBodyAndProvidesHead(): void
    {
        $view = new InertiaView(new Page('Home', [], '/', ''), new SsrResponse('<title>t</title>', '<div id="app">ssr</div>'));

        $this->assertSame('<title>t</title>', $view->head());
        $this->assertSame('<div id="app">ssr</div>', $view->body());
        $this->assertSame('<div id="app">ssr</div>', $view->legacyBody());
        $this->assertSame('', (new InertiaView(new Page('Home', [], '/', '')))->head());
    }
}
