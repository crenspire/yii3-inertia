<?php

declare(strict_types=1);

namespace Crenspire\Inertia\View;

use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;

/**
 * Renders a plain PHP template.
 *
 * The template receives `$inertia` (InertiaView), `$request` and every entry of `$parameters` as variables.
 */
final class PhpRootViewRenderer implements RootViewRendererInterface
{
    /**
     * @param array<string, mixed> $parameters Extra template variables, for example a Vite instance.
     */
    public function __construct(
        private readonly string $template,
        private readonly array $parameters = [],
    ) {
    }

    public function render(InertiaView $inertia, ServerRequestInterface $request): string
    {
        if (!is_file($this->template)) {
            throw new RuntimeException("Inertia root view template \"{$this->template}\" does not exist.");
        }

        $variables = [...$this->parameters, 'inertia' => $inertia, 'request' => $request];
        $level = ob_get_level();
        ob_start();

        try {
            (static function (string $__template, array $__variables): void {
                extract($__variables, EXTR_SKIP);
                require $__template;
            })($this->template, $variables);

            return (string) ob_get_clean();
        } catch (Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }
    }
}
