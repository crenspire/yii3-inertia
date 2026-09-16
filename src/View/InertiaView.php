<?php

declare(strict_types=1);

namespace Crenspire\Inertia\View;

use Crenspire\Inertia\Page;
use Crenspire\Inertia\Ssr\SsrResponse;

/**
 * Available as `$inertia` in the root view template.
 *
 * ```php
 * <head><?= $inertia->head() ?></head>
 * <body><?= $inertia->body() ?></body>
 * ```
 */
final class InertiaView
{
    public function __construct(
        public readonly Page $page,
        public readonly ?SsrResponse $ssr = null,
    ) {
    }

    /**
     * Head tags produced by server-side rendering, or an empty string.
     */
    public function head(): string
    {
        return $this->ssr === null ? '' : $this->ssr->head;
    }

    /**
     * The app root element with the page object, as expected by Inertia.js 3.
     */
    public function body(string $id = 'app'): string
    {
        if ($this->ssr !== null) {
            return $this->ssr->body;
        }

        $id = htmlspecialchars($id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<script data-page="' . $id . '" type="application/json">' . $this->pageJson() . '</script>'
            . '<div id="' . $id . '"></div>';
    }

    /**
     * The app root element with the page object in a `data-page` attribute, as expected by Inertia.js 1 and 2.
     */
    public function legacyBody(string $id = 'app'): string
    {
        if ($this->ssr !== null) {
            return $this->ssr->body;
        }

        return '<div id="' . htmlspecialchars($id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" data-page="'
            . htmlspecialchars($this->pageJson(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"></div>';
    }

    /**
     * The page object as JSON that is safe to embed in a script element.
     */
    public function pageJson(): string
    {
        // Slashes stay escaped and "<" and ">" are hex-encoded so props cannot close the script element.
        return json_encode($this->page, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);
    }
}
