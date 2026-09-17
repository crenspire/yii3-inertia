# History encryption

Inertia stores page data in the browser history so that back navigation is instant. On shared computers, that data
could be read after the user logs out. History encryption encrypts it with a key kept in session storage, and
clearing the history rotates the key.

History encryption requires a secure context (HTTPS or `localhost`), because it uses `window.crypto.subtle`.

## Encrypting all pages

```php
// config/web/params.php
'crenspire/yii3-inertia' => [
    'encryptHistory' => true,
],
```

Without Yii3, pass `encryptHistory: true` to `Inertia`, or use `$inertia->withEncryptHistory()`.

## Encrypting some pages

Enable it per request, for example in a middleware for an account area:

```php
$request = Inertia::encryptHistory($request);

return $handler->handle($request);
```

`Inertia::encryptHistory($request, false)` disables it for a request when it's enabled globally.

## Clearing history

After logout, clear the history so previous pages can't be decrypted. Since logout usually redirects, flash the flag:

```php
public function __invoke(ServerRequestInterface $request): ResponseInterface
{
    $this->currentUser->logout();
    $this->flash->clearHistory();

    return $this->inertia->redirect('/login');
}
```

To clear it for a page rendered in the same request:

```php
$request = Inertia::clearHistory($request);
```

On the client, `router.clearHistory()` does the same.
