# Props

Props are the data passed to the page component. They are the third argument of `render()`.

```php
return $this->inertia->render($request, 'Posts/Show', [
    'post' => $post->toArray(),
    'comments' => fn () => $this->comments->forPost($post->id),
    'canEdit' => $this->access->can('edit', $post),
]);
```

## Value types

| Value | Sent as |
|---|---|
| Scalars, `null`, arrays | As is. Nested arrays are resolved recursively. |
| Closures and invokable objects | Their return value, computed only when the prop is sent |
| `JsonSerializable` | The result of `jsonSerialize()` |
| `Traversable` | Converted to an array |
| `ProvidesInertiaProperty` | The result of `toInertiaProperty()` |
| Prop types such as `Inertia::defer()` | See below |
| Other objects | Encoded with `json_encode()` |

An empty props array is sent as `{}`.

## Lazy evaluation

Wrap expensive values in a closure. A closure only runs when its prop is actually sent, so a
[partial reload](./partial-reloads) that asks for other props skips the work:

```php
return $this->inertia->render($request, 'Dashboard', [
    'user' => $user,                                   // always computed
    'stats' => fn () => $this->stats->heavyQuery(),    // computed only when included
]);
```

## Prop types

| Factory | Behavior | Guide |
|---|---|---|
| `Inertia::optional($callback)` | Only resolved when a partial reload asks for it | [Partial reloads](./partial-reloads#optional-props) |
| `Inertia::always($value)` | Included in every response, even partial reloads for other props | [Partial reloads](./partial-reloads#always-props) |
| `Inertia::defer($callback, $group)` | Loaded in a second request right after the page renders | [Deferred props](./deferred-props) |
| `Inertia::merge($value)` | Appended to the client's current value on partial reloads | [Merging props](./merging-props) |
| `Inertia::deepMerge($value)` | Deep-merged into the client's current value | [Merging props](./merging-props#deep-merging) |
| `Inertia::once($callback)` | Resolved once and remembered by the client | [Once props](./once-props) |
| `Inertia::scroll($paginator)` | Paginated data for infinite scroll | [Infinite scroll](./infinite-scroll) |

A closure may also return a prop type; it is unwrapped and treated like the prop type itself.

## Nested props with dot notation

Keys that contain dots are expanded into nested arrays, and merged into existing props with the same prefix:

```php
return $this->inertia->render($request, 'Settings', [
    'user' => fn () => ['name' => 'Ada'],
    'user.permissions' => ['edit', 'delete'],
    'ui.theme.color' => 'dark',
]);
```

```json
{
  "user": { "name": "Ada", "permissions": ["edit", "delete"] },
  "ui": { "theme": { "color": "dark" } }
}
```

Prop types also work at nested paths, and partial reloads can target them with dot notation, for example
`only: ['user.permissions']`.

## Prop providers

### ProvidesInertiaProperty

An object that resolves its own value. It receives the prop path, the sibling props and the request:

```php
use Crenspire\Inertia\Prop\PropertyContext;
use Crenspire\Inertia\Prop\ProvidesInertiaProperty;

final readonly class UserResource implements ProvidesInertiaProperty
{
    public function __construct(private User $user) {}

    public function toInertiaProperty(PropertyContext $context): mixed
    {
        return [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'avatar' => $this->user->avatarUrl($context->request->getUri()->getHost()),
        ];
    }
}
```

```php
return $this->inertia->render($request, 'Profile', [
    'user' => new UserResource($user),
]);
```

### ProvidesInertiaProperties

An object that contributes several props at once. Add it to the props array under a numeric key:

```php
use Crenspire\Inertia\Prop\ProvidesInertiaProperties;
use Crenspire\Inertia\Prop\RenderContext;

final readonly class NavigationProps implements ProvidesInertiaProperties
{
    public function __construct(private MenuRepository $menus) {}

    public function toInertiaProperties(RenderContext $context): iterable
    {
        yield 'menu' => $this->menus->main();
        yield 'currentComponent' => $context->component;
    }
}
```

```php
return $this->inertia->render($request, 'Home', [
    $this->navigationProps,
    'title' => 'Welcome',
]);
```

Providers work in [shared data](./shared-data) too:

```php
$request = Inertia::share($request, $this->navigationProps);
```

## The errors prop

Every page has an `errors` prop containing validation errors, or an empty object. See
[Forms and validation](./forms).
