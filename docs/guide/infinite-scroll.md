# Infinite scroll

`Inertia::scroll()` provides paginated data for the client's `<InfiniteScroll>` component. It merges each page into
the existing list and sends the metadata the component needs to request the previous and next pages.

## With yiisoft/data paginators

```php
use Crenspire\Inertia\Inertia;
use Yiisoft\Data\Paginator\OffsetPaginator;

public function __invoke(ServerRequestInterface $request): ResponseInterface
{
    $paginator = (new OffsetPaginator($this->posts->reader()))
        ->withPageSize(20)
        ->withCurrentPage((int) ($request->getQueryParams()['page'] ?? 1));

    return $this->inertia->render($request, 'Posts/Index', [
        'posts' => Inertia::scroll($paginator),
    ]);
}
```

The prop contains the items under `data`, and the page contains its scroll metadata:

```json
{
  "props": { "posts": { "data": [{ "id": 21 }, { "id": 22 }] } },
  "scrollProps": {
    "posts": { "pageName": "page", "previousPage": 1, "nextPage": 3, "currentPage": 2, "reset": false }
  },
  "mergeProps": ["posts.data"]
}
```

`OffsetPaginator` provides page numbers. Other `yiisoft/data` paginators, such as `KeysetPaginator`, provide their
page tokens.

## On the client

```jsx
import { InfiniteScroll } from '@inertiajs/react'

export default function Index({ posts }) {
  return (
    <InfiniteScroll data="posts">
      {posts.data.map((post) => (
        <article key={post.id}>{post.title}</article>
      ))}
    </InfiniteScroll>
  )
}
```

The component loads more items as the user scrolls and keeps the `page` query parameter in sync. When the user
scrolls up, the client asks for items to be prepended, and the adapter sends prepend metadata instead.

## Options

```php
Inertia::scroll(
    $paginator,
    wrapper: 'items',     // key that holds the items, default 'data'
    pageName: 'posts',    // query parameter, default 'page'
)
```

Use a distinct `pageName` when a page has several infinite lists.

## Other data sources

For arrays or custom pagination, pass the prop value and describe the pagination with a callback or a
`ScrollMetadata` object:

```php
use Crenspire\Inertia\Prop\ScrollMetadata;

$page = (int) ($request->getQueryParams()['page'] ?? 1);
$items = $this->posts->page($page, 20);

return $this->inertia->render($request, 'Posts/Index', [
    'posts' => Inertia::scroll(
        ['data' => $items],
        metadata: new ScrollMetadata(
            pageName: 'page',
            previousPage: $page > 1 ? $page - 1 : null,
            nextPage: count($items) === 20 ? $page + 1 : null,
            currentPage: $page,
        ),
    ),
]);
```

The callback form receives the resolved value, which is useful with lazy values:

```php
'posts' => Inertia::scroll(
    fn () => $this->posts->paginate($page),
    metadata: fn (MyPagination $result) => new ScrollMetadata('page', $result->previous, $result->next, $result->current),
),
```

You can also implement `ProvidesScrollMetadata` on your own pagination class and pass it as `metadata`.

## Deferring the first page

```php
'posts' => Inertia::scroll($paginator)->defer(),
```

## Resetting

When filters change, reset the list so new results replace the old ones:

```js
router.reload({ data: { search }, only: ['posts'], reset: ['posts'] })
```
