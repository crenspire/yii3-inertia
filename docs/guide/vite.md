# Vite

The `Vite` helper outputs the script and stylesheet tags for your entry points. It reads the build manifest in
production and points to the Vite dev server during development.

## Tags

```php
<?= $vite->reactRefresh() ?>
<?= $vite->tags('resources/js/app.jsx') ?>
```

`tags()` accepts one entry or a list:

```php
<?= $vite->tags(['resources/js/app.jsx', 'resources/css/app.css']) ?>
```

In production, for each entry it outputs:

- stylesheets of the entry and of every chunk it imports
- `modulepreload` links for imported chunks
- the entry script, or a stylesheet link for CSS entries

```html
<link rel="stylesheet" href="/build/assets/app-C7d8e9.css">
<link rel="modulepreload" href="/build/assets/vendor-D4e5f6.js">
<script type="module" src="/build/assets/app-B1a2c3.js"></script>
```

With the dev server it outputs the Vite client and the source entries:

```html
<script type="module" src="http://localhost:5173/@vite/client"></script>
<script type="module" src="http://localhost:5173/resources/js/app.jsx"></script>
```

An entry that is not in the manifest throws a `RuntimeException` that names the manifest path, so a missing
`npm run build` is easy to spot.

## React Fast Refresh

`@vitejs/plugin-react` needs a preamble when the page is not served by Vite. `reactRefresh()` outputs it in dev mode
and an empty string in production. Place it before `tags()`. Vue and Svelte don't need it.

## Asset URLs

`asset()` returns the URL of a file processed by Vite, such as an image imported by your frontend:

```php
<img src="<?= $vite->asset('resources/images/logo.svg') ?>" alt="">
```

## Dev server mode

The helper uses the dev server when either:

- the `devServerUrl` option is set, or
- the hot file exists in the web root (`public/hot` by default) and contains the dev server URL.

### Configure the URL explicitly

```php
// config/web/params.php
'crenspire/yii3-inertia' => [
    'vite' => [
        'devServerUrl' => $_ENV['VITE_DEV_SERVER_URL'] ?? null,
    ],
],
```

### Detect the dev server automatically

Let Vite write the hot file while it runs, and remove it when it stops:

```js
// vite.config.js
import fs from 'node:fs'
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

const port = Number(process.env.VITE_PORT ?? 5173)
const devServerUrl = `http://localhost:${port}`

function hotFile(path) {
  return {
    name: 'hot-file',
    apply: 'serve',
    configureServer(server) {
      server.httpServer?.once('listening', () => fs.writeFileSync(path, devServerUrl))

      const remove = () => fs.rmSync(path, { force: true })
      process.on('exit', remove)
      for (const signal of ['SIGINT', 'SIGTERM', 'SIGHUP']) {
        process.on(signal, () => process.exit())
      }
    },
  }
}

export default defineConfig(({ command }) => ({
  base: command === 'build' ? '/build/' : '/',
  plugins: [react(), hotFile('public/hot')],
  publicDir: false,
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: { input: 'resources/js/app.jsx' },
  },
  server: {
    port,
    strictPort: true,
    origin: devServerUrl,
  },
}))
```

Add `public/hot` to `.gitignore`. Plugins that write the same file, such as `laravel-vite-plugin`, work too.

::: warning Don't deploy the hot file
If `public/hot` exists in production, pages load assets from a dev server that isn't there.
:::

## Options

| Option | Default | Description |
|---|---|---|
| `publicPath` | `@public` | Filesystem path of the web root |
| `buildDirectory` | `build` | Vite `build.outDir`, relative to the web root |
| `manifest` | `.vite/manifest.json` | Manifest path, relative to the build directory |
| `devServerUrl` | `null` | Dev server URL; `null` uses the hot file or the build |
| `hotFile` | `hot` | Hot file, relative to the web root; `''` disables it |
| `baseUrl` | `@baseUrl` | URL under which the web root is served |

The manifest is cached and re-read only when the file changes.
