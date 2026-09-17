# Once props

A once prop is resolved once and remembered by the client. On later visits to pages with the same prop, the
client tells the server it already has the value, and the server skips it.

```php
return $this->inertia->render($request, 'Billing', [
    'plans' => Inertia::once(fn () => $this->plans->all()),
]);
```

This suits data that rarely changes, such as plans, countries or feature flags.

## How it works

1. The first response includes the value and lists it in `onceProps`.
2. On later visits, the client sends the names of the props it remembers in `X-Inertia-Except-Once-Props`.
3. The adapter doesn't resolve those props, but keeps them in `onceProps` so the client keeps using its value.

A full page load always resolves the prop, since the client has no stored state yet.

## Expiration

```php
// Seconds
Inertia::once(fn () => $this->rates->current())->until(3600)

// A DateInterval or a point in time
Inertia::once(fn () => $this->rates->current())->until(new DateInterval('PT1H'))
Inertia::once(fn () => $this->rates->current())->until(new DateTimeImmutable('tomorrow'))
```

After it expires, the client requests the value again on the next visit.

## Sharing between pages

Props with different names on different pages can share a remembered value through a custom key:

```php
// Page A
'countries' => Inertia::once(fn () => $this->countries->all())->as('country-list'),

// Page B
'countryOptions' => Inertia::once(fn () => $this->countries->all())->as('country-list'),
```

`as()` also accepts a backed or unit enum.

## Forcing a refresh

```php
'plans' => Inertia::once(fn () => $this->plans->all())->fresh(),
```

`fresh()` sends the value even if the client has it, for example right after the data changed. On the client,
`router.reload({ only: ['plans'] })` always returns a new value too.

## Shared once props

Once props are especially useful as [shared data](./shared-data), since the same value would otherwise be sent with
every page:

```php
'sharedProps' => [
    'featureFlags' => Inertia::once(static fn (): array => App\Features::all()),
],
```

Other prop types can be remembered too: `Inertia::defer(...)->once()`, `Inertia::merge(...)->once()` and
`Inertia::optional(...)->once()`.
