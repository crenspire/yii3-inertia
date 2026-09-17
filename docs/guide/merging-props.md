# Merging props

By default a partial reload replaces a prop's value. Merge props are combined with the value the client already
has, which is useful for "load more" lists and live updates.

```php
$page = (int) ($request->getQueryParams()['page'] ?? 1);

return $this->inertia->render($request, 'Notifications', [
    'notifications' => Inertia::merge(fn () => $this->notifications->page($page)),
]);
```

```js
router.reload({ only: ['notifications'], data: { page: nextPage } })
```

Merging only applies to partial reloads. A full visit always replaces the value.

## Appending and prepending

```php
// Append to the end (default)
'messages' => Inertia::merge($messages),

// Prepend to the start
'messages' => Inertia::merge($messages)->prepend(),
```

## Merging nested arrays

When the list is inside the prop, for example next to pagination data, merge at a path:

```php
'users' => Inertia::merge([
    'data' => $users,
    'meta' => ['page' => $page, 'total' => $total],
])->append('data'),
```

Only `users.data` is merged; `users.meta` is replaced. Use several paths at once, or prepend at one path and append
at another:

```php
Inertia::merge($value)->append('data')->prepend('pinned')
```

## Matching items

To update items that are already on the client instead of duplicating them, match them on a key:

```php
'users' => Inertia::merge($users)->matchOn('id'),

// For a nested path, pass the key as the second argument of append() or prepend():
'users' => Inertia::merge(['data' => $users])->append('data', 'id'),
```

## Deep merging

`deepMerge()` merges nested objects recursively instead of appending lists:

```php
'settings' => Inertia::deepMerge([
    'notifications' => ['email' => true],
]),
```

## Resetting

To replace a merge prop, for example when search filters change, ask the client to reset it:

```js
router.reload({ only: ['users'], data: { search }, reset: ['users'] })
```

The client sends `X-Inertia-Reset`, and the adapter omits the merge instructions for those props.
