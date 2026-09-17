# Deferred props

Deferred props are left out of the first response so the page renders immediately. Right after rendering, the
client requests them in the background with a partial reload.

```php
return $this->inertia->render($request, 'Dashboard', [
    'user' => $user,
    'permissions' => Inertia::defer(fn () => $this->permissions->forUser($user)),
]);
```

The page lists the deferred props so the client knows what to load:

```json
{
  "props": { "errors": {}, "user": { "id": 1 } },
  "deferredProps": { "default": ["permissions"] }
}
```

## Showing a fallback

::: code-group

```jsx [React]
import { Deferred } from '@inertiajs/react'

export default function Dashboard({ permissions }) {
  return (
    <Deferred data="permissions" fallback={<div>Loading…</div>}>
      <PermissionList permissions={permissions} />
    </Deferred>
  )
}
```

```vue [Vue]
<script setup>
import { Deferred } from '@inertiajs/vue3'

defineProps({ permissions: Array })
</script>

<template>
  <Deferred data="permissions">
    <template #fallback>
      <div>Loading…</div>
    </template>
    <PermissionList :permissions="permissions" />
  </Deferred>
</template>
```

:::

`data` also accepts a list: `data={['teams', 'users']}`.

## Groups

Deferred props in the same group are loaded in one request. Props without a group use `default`:

```php
return $this->inertia->render($request, 'Dashboard', [
    'teams' => Inertia::defer(fn () => $this->teams->all(), 'sidebar'),
    'projects' => Inertia::defer(fn () => $this->projects->all(), 'sidebar'),
    'activity' => Inertia::defer(fn () => $this->activity->latest()),
]);
```

This page makes two background requests: one for `teams` and `projects`, one for `activity`.

## Handling failures

By default, an exception in a deferred prop fails its request. With `rescue: true`, the exception is logged
through the PSR-3 logger, the prop is omitted, and its name is reported in `rescuedProps`:

```php
'permissions' => Inertia::defer(fn () => $this->permissions->forUser($user), rescue: true),
```

On the client, render the `rescue` state of `<Deferred>`:

```jsx
<Deferred
  data="permissions"
  fallback={<div>Loading…</div>}
  rescue={({ reloading }) => (
    <button disabled={reloading} onClick={() => router.reload({ only: ['permissions'] })}>
      Retry
    </button>
  )}
>
  <PermissionList permissions={permissions} />
</Deferred>
```

In Yii3 the logger is taken from the DI container. Without Yii3, pass it as the `logger` argument of `Inertia`.

## Combining with other prop types

Deferred props can be merged and remembered:

```php
'feed' => Inertia::defer(fn () => $this->feed->page(1))->merge(),
'countries' => Inertia::defer(fn () => $this->countries->all())->once(),
```
