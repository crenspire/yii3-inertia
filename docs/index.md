---
layout: home

hero:
  name: Yii3 Inertia
  text: Modern single-page apps with Yii3
  tagline: The Inertia.js server-side adapter for Yii3 and any PSR-15 application. Use React, Vue or Svelte while routing, controllers and validation stay in PHP.
  image:
    src: /logo.svg
    alt: Yii3 Inertia
  actions:
    - theme: brand
      text: Get started
      link: /guide/introduction
    - theme: alt
      text: Installation
      link: /guide/installation
    - theme: alt
      text: View on GitHub
      link: https://github.com/crenspire/yii3-inertia

features:
  - icon: 🚀
    title: Inertia.js 3 protocol
    details: Partial reloads, deferred, optional, merge, once and infinite scroll props, with support for Inertia.js 1 and 2 clients.
  - icon: ⚙️
    title: Zero-config Yii3 setup
    details: Services register themselves through yiisoft/config. Add the middleware, create a root view, and render pages.
  - icon: 📝
    title: Forms and validation
    details: Validation errors with error bags, flash data across redirects, and CSRF protection that works with yiisoft/csrf.
  - icon: ⚡
    title: Vite built in
    details: Script and stylesheet tags from the dev server or the build manifest, React Fast Refresh, and automatic asset versioning.
  - icon: 🖥️
    title: Server-side rendering
    details: Render pages in Node.js through a PSR-18 HTTP client, with automatic fallback to client-side rendering.
  - icon: 🔁
    title: Worker safe
    details: Stateless services and request-scoped data, ready for RoadRunner, Swoole and FrankenPHP.
---

## Quick look

Return a page component and its props from a Yii3 action. Expensive data can load after the page renders:

```php
use Crenspire\Inertia\Inertia;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\CurrentRoute;

final readonly class ShowAction
{
    public function __construct(
        private Inertia $inertia,
        private UserRepository $users,
    ) {}

    public function __invoke(ServerRequestInterface $request, CurrentRoute $route): ResponseInterface
    {
        $id = (int) $route->getArgument('id');

        return $this->inertia->render($request, 'Users/Show', [
            'user' => $this->users->get($id),
            'activity' => Inertia::defer(fn () => $this->users->activity($id)),
        ]);
    }
}
```

The React page receives the props. `<Deferred>` shows a fallback until `activity` arrives:

```jsx
import { Deferred, Head } from '@inertiajs/react'

export default function Show({ user, activity }) {
  return (
    <>
      <Head title={user.name} />
      <h1>{user.name}</h1>
      <Deferred data="activity" fallback={<p>Loading activity…</p>}>
        <ActivityList items={activity} />
      </Deferred>
    </>
  )
}
```

Ready to start? Install the package and follow the [installation guide](/guide/installation):

```bash
composer require crenspire/yii3-inertia:^2.0
```
