<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Tests\Support;

use Crenspire\Inertia\View\InertiaView;
use Crenspire\Inertia\View\RootViewRendererInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RecordingRenderer implements RootViewRendererInterface
{
    public ?InertiaView $view = null;

    public function render(InertiaView $inertia, ServerRequestInterface $request): string
    {
        $this->view = $inertia;

        return '<html>' . $inertia->body() . '</html>';
    }
}
