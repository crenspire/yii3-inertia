<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Prop;

use InvalidArgumentException;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PaginatorInterface;

final class ScrollMetadata implements ProvidesScrollMetadata
{
    public function __construct(
        private readonly string $pageName,
        private readonly int|string|null $previousPage = null,
        private readonly int|string|null $nextPage = null,
        private readonly int|string|null $currentPage = null,
    ) {
    }

    /**
     * Build metadata from a yiisoft/data paginator.
     */
    public static function fromPaginator(mixed $paginator, string $pageName = 'page'): self
    {
        if ($paginator instanceof OffsetPaginator) {
            $current = $paginator->getCurrentPage();

            return new self(
                $pageName,
                $paginator->isOnFirstPage() ? null : $current - 1,
                $paginator->isOnLastPage() ? null : $current + 1,
                $current,
            );
        }

        if ($paginator instanceof PaginatorInterface) {
            return new self(
                $pageName,
                $paginator->getPreviousToken()?->value,
                $paginator->getNextToken()?->value,
                $paginator->getToken()?->value,
            );
        }

        throw new InvalidArgumentException(
            'The scroll prop value is not a yiisoft/data paginator. Pass a metadata callback or a ProvidesScrollMetadata instance.'
        );
    }

    public function getPageName(): string
    {
        return $this->pageName;
    }

    public function getPreviousPage(): int|string|null
    {
        return $this->previousPage;
    }

    public function getNextPage(): int|string|null
    {
        return $this->nextPage;
    }

    public function getCurrentPage(): int|string|null
    {
        return $this->currentPage;
    }
}
