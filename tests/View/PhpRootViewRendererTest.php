<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Tests\View;

use Crenspire\Inertia\Page;
use Crenspire\Inertia\View\InertiaView;
use Crenspire\Inertia\View\PhpRootViewRenderer;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PhpRootViewRendererTest extends TestCase
{
    private string $template;

    protected function setUp(): void
    {
        $this->template = tempnam(sys_get_temp_dir(), 'inertia') . '.php';
    }

    protected function tearDown(): void
    {
        @unlink($this->template);
    }

    public function testRendersTemplateWithVariables(): void
    {
        file_put_contents($this->template, '<?= $title ?>|<?= $inertia->page->component ?>|<?= $request->getMethod() ?>');

        $html = (new PhpRootViewRenderer($this->template, ['title' => 'App', 'inertia' => 'ignored']))->render(
            new InertiaView(new Page('Home', [], '/', '')),
            new ServerRequest('GET', '/'),
        );

        $this->assertSame('App|Home|GET', $html);
    }

    public function testMissingTemplateFails(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        (new PhpRootViewRenderer('/missing/template.php'))->render(
            new InertiaView(new Page('Home', [], '/', '')),
            new ServerRequest('GET', '/'),
        );
    }

    public function testOutputBuffersAreCleanedOnError(): void
    {
        file_put_contents($this->template, '<?php echo "partial"; throw new RuntimeException("fail");');
        $level = ob_get_level();

        try {
            (new PhpRootViewRenderer($this->template))->render(
                new InertiaView(new Page('Home', [], '/', '')),
                new ServerRequest('GET', '/'),
            );
            $this->fail('Exception expected.');
        } catch (RuntimeException $e) {
            $this->assertSame('fail', $e->getMessage());
        }

        $this->assertSame($level, ob_get_level());
    }
}
