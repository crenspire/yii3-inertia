# CSRF protection

The Inertia client reads a CSRF token from the `XSRF-TOKEN` cookie and sends it back in the `X-XSRF-TOKEN` header.
`yiisoft/csrf` stores the token in the session and checks the `X-CSRF-Token` header or the `_csrf` body parameter.

`XsrfTokenMiddleware` connects the two:

- On every response, it sets the `XSRF-TOKEN` cookie to the current token.
- On every request, it copies `X-XSRF-TOKEN` to `X-CSRF-Token`, unless that header is already present.

No frontend changes are needed.

## Setup

Add the middleware after `SessionMiddleware` and before `CsrfTokenMiddleware`:

```php{3}
ErrorCatcher::class,
SessionMiddleware::class,
\Crenspire\Inertia\Middleware\XsrfTokenMiddleware::class,
CsrfTokenMiddleware::class,
\Crenspire\Inertia\Middleware\InertiaMiddleware::class,
Router::class,
```

Requests without a valid token are rejected by `CsrfTokenMiddleware` with `422 Unprocessable Entity`.

## Options

In Yii3 the middleware is autowired with its defaults. To change them, define it in your DI configuration:

```php
use Crenspire\Inertia\Middleware\XsrfTokenMiddleware;

return [
    XsrfTokenMiddleware::class => [
        '__construct()' => [
            'sameSite' => 'Strict',
        ],
    ],
];
```

| Argument | Default | Description |
|---|---|---|
| `token` | from DI | The `Yiisoft\Csrf\CsrfTokenInterface` |
| `csrfHeaderName` | `X-CSRF-Token` | Header checked by `CsrfTokenMiddleware` |
| `cookieName` | `XSRF-TOKEN` | Cookie read by the client |
| `clientHeaderName` | `X-XSRF-TOKEN` | Header sent by the client |
| `cookiePath` | `/` | Cookie path |
| `sameSite` | `Lax` | Cookie `SameSite` attribute |

The cookie is marked `Secure` when the request uses HTTPS. It is not `HttpOnly`, because the client must read it.

## Handling expired tokens

When a session expires, the next form submission fails with `422`. By default Inertia shows the response in a
modal. To handle it more gracefully, for example by reloading the page, listen for the client's `httpException`
event:

```js
import { router } from '@inertiajs/react'

router.on('httpException', (event) => {
  if (event.detail.response.status === 422) {
    event.preventDefault()
    window.location.reload()
  }
})
```

::: tip
Validation errors don't use status `422`. They are [flashed and redirected](./forms), so a `422` from an Inertia
visit indicates a CSRF failure.
:::
