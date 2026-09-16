<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Tests\Prop;

use Crenspire\Inertia\Header;
use Crenspire\Inertia\Inertia;
use Crenspire\Inertia\Prop\ScrollMetadata;
use Crenspire\Inertia\Tests\Support\TestCase;
use InvalidArgumentException;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;

final class ScrollPropTest extends TestCase
{
    public function testOffsetPaginatorProvidesItemsAndMetadata(): void
    {
        $paginator = (new OffsetPaginator(new IterableDataReader(array_map(static fn (int $id): array => ['id' => $id], range(1, 25)))))
            ->withPageSize(10)
            ->withCurrentPage(2);

        $page = $this->createInertia()->createPage($this->inertiaRequest(), 'Posts', [
            'posts' => Inertia::scroll($paginator),
        ]);

        $this->assertSame(['data' => array_map(static fn (int $id): array => ['id' => $id], range(11, 20))], $page->props['posts']);
        $this->assertSame(
            ['posts' => ['pageName' => 'page', 'previousPage' => 1, 'nextPage' => 3, 'currentPage' => 2, 'reset' => false]],
            $page->metadata['scrollProps'],
        );
        $this->assertSame(['posts.data'], $page->metadata['mergeProps']);
    }

    public function testPrependIntentAndCustomMetadata(): void
    {
        $page = $this->createInertia()->createPage(
            $this->partialRequest('Chat', [
                Header::PARTIAL_ONLY => 'messages',
                Header::INFINITE_SCROLL_MERGE_INTENT => 'prepend',
                Header::RESET => 'messages',
            ]),
            'Chat',
            [
                'messages' => Inertia::scroll(
                    static fn (): array => ['items' => ['m1']],
                    'items',
                    static fn (array $value): ScrollMetadata => new ScrollMetadata('cursor', 'a', null, 'b'),
                ),
            ],
        );

        $this->assertSame(['items' => ['m1']], $page->props['messages']);
        $this->assertSame(
            ['pageName' => 'cursor', 'previousPage' => 'a', 'nextPage' => null, 'currentPage' => 'b', 'reset' => true],
            $page->metadata['scrollProps']['messages'],
        );
        $this->assertArrayNotHasKey('prependProps', $page->metadata);
    }

    public function testPrependIntentAddsPrependMetadata(): void
    {
        $page = $this->createInertia()->createPage(
            $this->inertiaRequest(headers: [Header::INFINITE_SCROLL_MERGE_INTENT => 'prepend']),
            'Chat',
            ['messages' => Inertia::scroll(['data' => []], metadata: new ScrollMetadata('page'))],
        );

        $this->assertSame(['messages.data'], $page->metadata['prependProps']);
    }

    public function testDeferredScrollProp(): void
    {
        $page = $this->createInertia()->createPage($this->request(), 'Posts', [
            'posts' => Inertia::scroll(['data' => []], metadata: new ScrollMetadata('page'))->defer(),
        ]);

        $this->assertArrayNotHasKey('posts', $page->props);
        $this->assertSame(['default' => ['posts']], $page->metadata['deferredProps']);
    }

    public function testNonPaginatorWithoutMetadataFails(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createInertia()->createPage($this->request(), 'Posts', ['posts' => Inertia::scroll(['data' => []])]);
    }
}
