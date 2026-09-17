# Server-side rendering

With server-side rendering (SSR), the first visit returns HTML that is already rendered, which improves the first
paint and makes content visible to crawlers. The client then hydrates it.

The adapter sends the page object to a Node.js SSR server over HTTP and places the returned HTML in the root view.
If the SSR server is unavailable, the page falls back to client-side rendering.

## 1. Install an HTTP client

SSR needs a PSR-18 client and a PSR-17 request factory. For example:

```bash
composer require symfony/http-client psr/http-client
```

In Yii3, register the client in the DI container if it isn't already:

```php
// config/web/di/http-client.php
use Psr\Http\Client\ClientInterface;
use Symfony\Component\HttpClient\Psr18Client;

return [
    ClientInterface::class => Psr18Client::class,
];
```

`RequestFactoryInterface` and `StreamFactoryInterface` are registered by the `yiisoft/app` template.

## 2. Create the SSR entry

::: code-group

```jsx [React: resources/js/ssr.jsx]
import { createInertiaApp } from '@inertiajs/react'
import createServer from '@inertiajs/react/server'
import ReactDOMServer from 'react-dom/server'

createServer((page) =>
  createInertiaApp({
    page,
    render: ReactDOMServer.renderToString,
    resolve: (name) => {
      const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true })
      return pages[`./Pages/${name}.jsx`]
    },
    setup: ({ App, props }) => <App {...props} />,
  }),
)
```

```js [Vue: resources/js/ssr.js]
import { createInertiaApp } from '@inertiajs/vue3'
import createServer from '@inertiajs/vue3/server'
import { renderToString } from 'vue/server-renderer'
import { createSSRApp, h } from 'vue'

createServer((page) =>
  createInertiaApp({
    page,
    render: renderToString,
    resolve: (name) => {
      const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
      return pages[`./Pages/${name}.vue`]
    },
    setup({ App, props, plugin }) {
      return createSSRApp({ render: () => h(App, props) }).use(plugin)
    },
  }),
)
```

:::

## 3. Hydrate on the client

Hydrate the server-rendered markup instead of replacing it:

::: code-group

```jsx [React: resources/js/app.jsx]
import { createInertiaApp } from '@inertiajs/react'
import { hydrateRoot } from 'react-dom/client'

createInertiaApp({
  resolve: (name) => {
    const pages = import.meta.glob('./Pages/**/*.jsx')
    return pages[`./Pages/${name}.jsx`]()
  },
  setup({ el, App, props }) {
    hydrateRoot(el, <App {...props} />)
  },
})
```

```js [Vue: resources/js/app.js]
import { createSSRApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'

createInertiaApp({
  resolve: (name) => {
    const pages = import.meta.glob('./Pages/**/*.vue')
    return pages[`./Pages/${name}.vue`]()
  },
  setup({ el, App, props, plugin }) {
    createSSRApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el)
  },
})
```

:::

## 4. Build the SSR bundle

Add the `@inertiajs/vite` plugin and an SSR build to `vite.config.js`:

```bash
npm install --save-dev @inertiajs/vite
```

```js
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import inertia from '@inertiajs/vite'

export default defineConfig(({ command, isSsrBuild }) => ({
  base: command === 'build' ? '/build/' : '/',
  plugins: [react(), inertia({ ssr: 'resources/js/ssr.jsx' })],
  publicDir: false,
  build: isSsrBuild
    ? { outDir: 'bootstrap/ssr' }
    : {
        outDir: 'public/build',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: { input: 'resources/js/app.jsx' },
      },
}))
```

```json
{
  "scripts": {
    "build": "vite build && vite build --ssr"
  }
}
```

`npm run build` now writes the client assets to `public/build` and the SSR server to `bootstrap/ssr/ssr.js`.

## 5. Run the SSR server

```bash
node bootstrap/ssr/ssr.js
```

It listens on `http://127.0.0.1:13714`. Keep it running with your process manager, next to PHP.

## 6. Enable SSR

```php
// config/web/params.php
'crenspire/yii3-inertia' => [
    'ssr' => [
        'enabled' => true,
        'url' => 'http://127.0.0.1:13714/render',
    ],
],
```

The root view already contains everything needed: `$inertia->head()` outputs the rendered head tags, and
`$inertia->body()` outputs the rendered HTML instead of the empty root element.

## SSR during development

With `@inertiajs/vite`, the Vite dev server can render pages without a separate Node process. Point the SSR URL
to its endpoint:

```php
'ssr' => [
    'enabled' => true,
    'url' => 'http://localhost:5173/__inertia_ssr',
],
```

## Options

| Param | Default | Description |
|---|---|---|
| `ssr.enabled` | `false` | Register the SSR gateway |
| `ssr.url` | `http://127.0.0.1:13714/render` | SSR server endpoint |
| `ssr.except` | `[]` | Path prefixes rendered on the client only, for example `['/admin']` |
| `ssr.throwOnError` | `false` | Throw instead of falling back to client-side rendering |

SSR failures are logged at `warning` level through the PSR-3 logger. Enable `throwOnError` in development to see
them immediately.

## Without Yii3

```php
use Crenspire\Inertia\Ssr\HttpGateway;
use Symfony\Component\HttpClient\Psr18Client;

$inertia = new Inertia(
    responseFactory: $factory,
    streamFactory: $factory,
    rootViewRenderer: $renderer,
    ssrGateway: new HttpGateway(
        client: new Psr18Client(),
        requestFactory: $factory,
        streamFactory: $factory,
        url: 'http://127.0.0.1:13714/render',
    ),
);
```

To render another way, implement `Ssr\GatewayInterface`. It receives the `Page` and the request, and returns an
`SsrResponse` with `head` and `body`, or `null` to render on the client.
