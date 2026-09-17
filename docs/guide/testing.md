# Testing

Inertia responses are PSR-7 responses, so you can test actions with any HTTP testing approach. The adapter adds two
conveniences.

## Asserting the page with createPage()

`createPage()` resolves the props without creating a response. Use it to test what an action would render:

```php
use Crenspire\Inertia\Inertia;
use Nyholm\Psr7\ServerRequest;

public function testUsersPage(): void
{
    $inertia = $this->container->get(Inertia::class);
    $request = new ServerRequest('GET', '/users');

    $page = $inertia->createPage($request, 'Users/Index', [
        'users' => fn () => [['id' => 1, 'name' => 'Ada']],
    ]);

    $this->assertSame('Users/Index', $page->component);
    $this->assertSame([['id' => 1, 'name' => 'Ada']], $page->props['users']);
    $this->assertSame('/users', $page->url);
}
```

## Testing actions through JSON responses

Send the `X-Inertia` header and the current version to get the page object as JSON:

```php
use Crenspire\Inertia\Header;

$request = (new ServerRequest('GET', '/users'))
    ->withHeader(Header::INERTIA, 'true')
    ->withHeader(Header::VERSION, $inertia->getVersion());

$response = $action($request);
$page = json_decode((string) $response->getBody(), true);

$this->assertSame('Users/Index', $page['component']);
$this->assertArrayHasKey('users', $page['props']);
```

Add `X-Inertia-Partial-Component` and `X-Inertia-Partial-Data` to test partial reloads, for example to check that a
deferred prop resolves.

## Flash data in tests

Use `ArrayFlashStore` instead of a session:

```php
use Crenspire\Inertia\Flash\ArrayFlashStore;
use Crenspire\Inertia\Flash\InertiaFlash;

$store = new ArrayFlashStore();
$flash = new InertiaFlash($store);

$response = (new StoreAction($inertia, $flash, $validator, $users))($request);

$this->assertSame(302, $response->getStatusCode());
$this->assertSame(['name' => 'Name cannot be blank.'], $store->get(InertiaFlash::ERRORS)['default']);
```

## A minimal Inertia instance

For unit tests without a container:

```php
use Crenspire\Inertia\Inertia;
use Crenspire\Inertia\View\InertiaView;
use Crenspire\Inertia\View\RootViewRendererInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;

$factory = new Psr17Factory();

$inertia = new Inertia(
    responseFactory: $factory,
    streamFactory: $factory,
    rootViewRenderer: new class () implements RootViewRendererInterface {
        public function render(InertiaView $inertia, ServerRequestInterface $request): string
        {
            return $inertia->body();
        }
    },
);
```
