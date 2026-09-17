<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Prop;

use Closure;
use Crenspire\Inertia\Header;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Data\Paginator\PaginatorInterface;

/**
 * A paginated prop for the client's infinite scroll component.
 *
 * The value is resolved to `[$wrapper => items]` and merged into the list the client already shows.
 */
final class ScrollProp implements Deferrable, Mergeable
{
    use DefersProps;
    use MergesProps;
    use ResolvesCallables;

    private bool $isResolved = false;
    private mixed $resolved = null;
    private readonly ProvidesScrollMetadata|Closure|null $metadata;

    /**
     * @param mixed $value The paginated value, or a callable returning it.
     * @param string $wrapper Key that holds the items inside the prop.
     * @param ProvidesScrollMetadata|callable(mixed): ProvidesScrollMetadata|null $metadata Metadata, a callback
     * receiving the resolved value, or null to read it from a yiisoft/data paginator.
     */
    public function __construct(
        private readonly mixed $value,
        private readonly string $wrapper = 'data',
        ProvidesScrollMetadata|callable|null $metadata = null,
        private readonly string $pageName = 'page',
    ) {
        $this->merge = true;
        $this->metadata = is_callable($metadata) ? $metadata(...) : $metadata;
    }

    /**
     * @internal
     */
    public function configureMergeIntent(ServerRequestInterface $request): static
    {
        if ($this->appendsAtPaths() !== [] || $this->prependsAtPaths() !== []) {
            return $this;
        }

        return $request->getHeaderLine(Header::INFINITE_SCROLL_MERGE_INTENT) === 'prepend'
            ? $this->prepend($this->wrapper)
            : $this->append($this->wrapper);
    }

    /**
     * @return array{pageName: string, previousPage: int|string|null, nextPage: int|string|null, currentPage: int|string|null}
     */
    public function metadata(): array
    {
        $provider = match (true) {
            $this->metadata instanceof ProvidesScrollMetadata => $this->metadata,
            $this->metadata instanceof Closure => ($this->metadata)($this->resolveValue()),
            default => ScrollMetadata::fromPaginator($this->resolveValue(), $this->pageName),
        };

        return [
            'pageName' => $provider->getPageName(),
            'previousPage' => $provider->getPreviousPage(),
            'nextPage' => $provider->getNextPage(),
            'currentPage' => $provider->getCurrentPage(),
        ];
    }

    public function __invoke(): mixed
    {
        $value = $this->resolveValue();

        if ($value instanceof PaginatorInterface) {
            $items = $value->read();

            // Readers keep the offsets as keys; re-index so the items are sent as a JSON list.
            return [$this->wrapper => is_array($items) ? array_values($items) : iterator_to_array($items, false)];
        }

        return $value;
    }

    private function resolveValue(): mixed
    {
        if (!$this->isResolved) {
            $this->resolved = $this->resolveCallable($this->value);
            $this->isResolved = true;
        }

        return $this->resolved;
    }
}
