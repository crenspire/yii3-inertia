<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Prop;

interface ProvidesScrollMetadata
{
    /**
     * Name of the query parameter that carries the page.
     */
    public function getPageName(): string;

    public function getPreviousPage(): int|string|null;

    public function getNextPage(): int|string|null;

    public function getCurrentPage(): int|string|null;
}
