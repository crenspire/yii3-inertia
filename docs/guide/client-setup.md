# Client-side setup

The adapter works with the official Inertia.js clients for React, Vue and Svelte. This page uses Vite, which the
[`Vite` helper](./vite) supports out of the box.

## Install the client

::: code-group

```bash [React]
npm install @inertiajs/react react react-dom
npm install --save-dev vite @vitejs/plugin-react
```

```bash [Vue]
npm install @inertiajs/vue3 vue
npm install --save-dev vite @vitejs/plugin-vue
```

```bash [Svelte]
npm install @inertiajs/svelte svelte
npm install --save-dev vite @sveltejs/vite-plugin-svelte
```

:::

Inertia.js 3 requires React 19 or Svelte 5.

## Configure Vite

Create `vite.config.js` in the project root:

::: code-group

```js [React]
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig(({ command }) => ({
  // Built files are served from /build; the dev server serves from its own root.
  base: command === 'build' ? '/build/' : '/',
  plugins: [react()],
  publicDir: false,
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: 'resources/js/app.jsx',
    },
  },
  server: {
    port: 5173,
    strictPort: true,
    origin: 'http://localhost:5173',
  },
}))
```

```js [Vue]
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig(({ command }) => ({
  base: command === 'build' ? '/build/' : '/',
  plugins: [vue()],
  publicDir: false,
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: 'resources/js/app.js',
    },
  },
  server: {
    port: 5173,
    strictPort: true,
    origin: 'http://localhost:5173',
  },
}))
```

```js [Svelte]
import { defineConfig } from 'vite'
import { svelte } from '@sveltejs/vite-plugin-svelte'

export default defineConfig(({ command }) => ({
  base: command === 'build' ? '/build/' : '/',
  plugins: [svelte()],
  publicDir: false,
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: 'resources/js/app.js',
    },
  },
  server: {
    port: 5173,
    strictPort: true,
    origin: 'http://localhost:5173',
  },
}))
```

:::

Key settings:

- `publicDir: false` stops Vite from copying `public/` into the build directory.
- `base` must match the URL the build directory is served from.
- `manifest: true` writes `public/build/.vite/manifest.json`, which the `Vite` helper reads and which is hashed for
  [asset versioning](./asset-versioning).
- `origin` makes URLs in dev mode point to the dev server instead of the PHP server.

## Create the entry point

::: code-group

```jsx [React: resources/js/app.jsx]
import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'

createInertiaApp({
  title: (title) => (title ? `${title} - My App` : 'My App'),
  resolve: (name) => {
    const pages = import.meta.glob('./Pages/**/*.jsx')
    return pages[`./Pages/${name}.jsx`]()
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />)
  },
})
```

```js [Vue: resources/js/app.js]
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'

createInertiaApp({
  title: (title) => (title ? `${title} - My App` : 'My App'),
  resolve: (name) => {
    const pages = import.meta.glob('./Pages/**/*.vue')
    return pages[`./Pages/${name}.vue`]()
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el)
  },
})
```

```js [Svelte: resources/js/app.js]
import { createInertiaApp } from '@inertiajs/svelte'
import { mount } from 'svelte'

createInertiaApp({
  resolve: (name) => {
    const pages = import.meta.glob('./Pages/**/*.svelte')
    return pages[`./Pages/${name}.svelte`]()
  },
  setup({ el, App, props }) {
    mount(App, { target: el, props })
  },
})
```

:::

Update the entry point passed to `$vite->tags()` in the root view to match, for example
`resources/js/app.js` for Vue.

::: tip The @inertiajs/vite plugin
The optional [`@inertiajs/vite`](https://inertiajs.com/docs/v3/installation/client-side-setup) plugin can
generate `resolve` for you with a `pages` option, and simplifies [server-side rendering](./ssr).
:::

## Create a page

::: code-group

```jsx [React: resources/js/Pages/Home.jsx]
import { Head, Link } from '@inertiajs/react'

export default function Home({ message }) {
  return (
    <>
      <Head title="Home" />
      <h1>{message}</h1>
      <Link href="/about">About</Link>
    </>
  )
}
```

```vue [Vue: resources/js/Pages/Home.vue]
<script setup>
import { Head, Link } from '@inertiajs/vue3'

defineProps({ message: String })
</script>

<template>
  <Head title="Home" />
  <h1>{{ message }}</h1>
  <Link href="/about">About</Link>
</template>
```

```svelte [Svelte: resources/js/Pages/Home.svelte]
<script>
  import { inertia } from '@inertiajs/svelte'

  let { message } = $props()
</script>

<svelte:head>
  <title>Home</title>
</svelte:head>

<h1>{message}</h1>
<a href="/about" use:inertia>About</a>
```

:::

## Add scripts

```json
{
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "vite build"
  }
}
```

## Develop and build

For production, build the assets once:

```bash
npm run build
```

During development, run the Vite dev server for hot module replacement, and tell PHP where it is:

```bash
npm run dev
```

```php
// config/web/params.php
return [
    'crenspire/yii3-inertia' => [
        'vite' => [
            'devServerUrl' => $_ENV['VITE_DEV_SERVER_URL'] ?? null,
        ],
    ],
];
```

```bash
VITE_DEV_SERVER_URL=http://localhost:5173 APP_ENV=dev ./yii serve
```

To switch automatically without an environment variable, let Vite write a
[hot file](./vite#detect-the-dev-server-automatically).
