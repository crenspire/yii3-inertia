# Flash data

Flash data is stored for the next page render, typically right before a redirect. Inertia exposes it as
`page.flash` on the client, separate from the props.

```php
use Crenspire\Inertia\Flash\InertiaFlash;

$this->flash->flash('message', 'Profile updated.');

// Several values at once:
$this->flash->flash(['message' => 'Saved.', 'level' => 'success']);

return $this->inertia->redirect('/profile');
```

`flash` is only included in the page when there is data, and the data is removed once a page has been rendered.

## Reading flash data

::: code-group

```jsx [React]
import { usePage } from '@inertiajs/react'

export default function FlashMessage() {
  const { flash } = usePage()

  return flash?.message ? <p className="alert">{flash.message}</p> : null
}
```

```vue [Vue]
<script setup>
import { usePage } from '@inertiajs/vue3'

const page = usePage()
</script>

<template>
  <p v-if="page.flash?.message" class="alert">{{ page.flash.message }}</p>
</template>
```

:::

## What InertiaFlash stores

| Method | Effect on the next render |
|---|---|
| `errors(array $errors, string $bag = 'default')` | Merged into the `errors` prop, see [Forms](./forms) |
| `flash(string\|array $key, mixed $value = null)` | Added to `page.flash` |
| `clearHistory()` | Sets `clearHistory`, see [History encryption](./history-encryption) |
| `preserveFragment()` | Keeps the URL fragment across the redirect, see [Redirects](./redirects#redirects-with-url-fragments) |

All methods return the same instance, so calls can be chained.

## Storage

`InertiaFlash` writes to a `FlashStoreInterface`:

- In Yii3 with `yiisoft/session`, the package registers `SessionFlashStore`.
- Without a session package, implement the interface, as shown in [Without Yii3](./psr15#flash-data-without-yiisoft-session).
- In tests, use `ArrayFlashStore`.

Values stay in the store until a page is rendered. If a redirect leads to a non-Inertia page, the values appear on
the next Inertia page instead.
