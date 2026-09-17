# Partial reloads

A partial reload requests the current page again but only asks for some props. The client keeps the rest of its
props, and the server skips resolving the ones that weren't requested.

## Requesting props

::: code-group

```js [React]
import { router } from '@inertiajs/react'

router.reload({ only: ['users'] })
router.reload({ except: ['stats'] })
```

```js [Vue]
import { router } from '@inertiajs/vue3'

router.reload({ only: ['users'] })
```

:::

The client sends:

| Header | Value |
|---|---|
| `X-Inertia-Partial-Component` | The current component name |
| `X-Inertia-Partial-Data` | Comma-separated props for `only` |
| `X-Inertia-Partial-Except` | Comma-separated props for `except` |

The adapter applies the filter only when `X-Inertia-Partial-Component` matches the component being rendered. When
an action renders a different component, for example after a redirect, all props are sent.

Paths can use dot notation: `only: ['filters.status']` sends only that key of the `filters` prop.

## Skipping work with closures

A partial reload only helps if unrequested props are cheap. Wrap expensive props in closures:

```php
return $this->inertia->render($request, 'Users/Index', [
    'users' => fn () => $this->users->search($filters),
    'companies' => fn () => $this->companies->all(),
]);
```

With `router.reload({ only: ['users'] })`, the companies query doesn't run.

## Optional props

An optional prop is never included in a full visit. It is resolved only when a partial reload asks for it by name:

```php
return $this->inertia->render($request, 'Reports/Show', [
    'summary' => $summary,
    'export' => Inertia::optional(fn () => $this->reports->buildExport($id)),
]);
```

```js
router.reload({ only: ['export'] })
```

## Always props

An always prop is included in every response, including partial reloads that asked for other props:

```php
return $this->inertia->render($request, 'Dashboard', [
    'notificationsCount' => Inertia::always(fn () => $this->notifications->unreadCount()),
    'stats' => fn () => $this->stats->all(),
]);
```

The `errors` prop is an always prop.

::: info Shared props in partial reloads
Shared props are filtered like any other prop. To include a shared prop in every partial reload, wrap it in
`Inertia::always()`.
:::
