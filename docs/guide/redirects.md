# Redirects

Inertia follows redirects in the background, so actions can use ordinary redirects after form submissions.

## Internal redirects

```php
return $this->inertia->redirect('/users');
```

After a `PUT`, `PATCH` or `DELETE` request, [`InertiaMiddleware`](./installation#2-add-the-middleware) turns a
`302` redirect into `303 See Other`. Without it, browsers would repeat the original method on the redirect target.

Pass another status when you need one:

```php
return $this->inertia->redirect('/users', 303);
```

Redirects created by other components, such as Yii's `ResponseFactory`, work the same way; the middleware
applies the rules to any redirect.

### Redirecting back

`back()` redirects to the `Referer` header, which is where a form was submitted from:

```php
return $this->inertia->back($request);

// With a fallback when the header is missing:
return $this->inertia->back($request, '/users/create');
```

This is the usual response after [validation fails](./forms).

## External redirects

A regular redirect to another site doesn't work for Inertia visits, because the browser would fetch it in the
background. Use `location()` instead:

```php
return $this->inertia->location($request, 'https://github.com/login/oauth/authorize?...');
```

- For Inertia visits it returns `409 Conflict` with `X-Inertia-Location`, and the client does a full
  `window.location` visit.
- For regular requests it returns a normal `302` redirect.

Use `location()` for any page that isn't rendered by Inertia, including pages of your own application.

## Redirects with URL fragments

When a redirect target contains a fragment, such as `/docs#installation`, the middleware responds with
`409 Conflict` and `X-Inertia-Redirect`. The client then visits the URL and keeps the fragment, which a
background redirect would lose.

To keep the fragment of the *original* request across a redirect, flag it before redirecting:

```php
$this->flash->preserveFragment();

return $this->inertia->redirect('/login');
```

## Empty responses

If an Inertia visit gets a `200` response with an empty body, the middleware redirects back to the previous page.
This lets actions that have nothing to render, such as a "mark as read" endpoint, return an empty response.
