# Troubleshooting

## Services are not found in Yii3

`Crenspire\Inertia\Inertia` can't be resolved, or the params are missing.

- Rebuild the configuration merge plan: `composer yii-config-rebuild`.
- Make sure the `yiisoft/config` Composer plugin is allowed in your `composer.json`:
  `"allow-plugins": { "yiisoft/config": true }`.

## "Vite manifest ... does not exist"

The page was rendered without built assets and without a dev server.

- Run `npm run build`, or start `npm run dev` and set `vite.devServerUrl`.
- Check that `vite.publicPath`, `vite.buildDirectory` and `vite.manifest` match `build.outDir` and `build.manifest` in
  `vite.config.js`. Vite 5 and later write `.vite/manifest.json` when `build.manifest` is `true`.

## "Vite entry ... is not in the manifest"

The entry passed to `$vite->tags()` must match `build.rollupOptions.input` in `vite.config.js`, relative to the
project root, for example `resources/js/app.jsx`.

## The page is blank and the console shows an error about the page

- Inertia.js 3 reads the page from `<script data-page="app" type="application/json">`, which `$inertia->body()`
  outputs. Inertia.js 1 and 2 read a `data-page` attribute: use `$inertia->legacyBody()` for them.
- The root element ID in the template must match the `id` option of `createInertiaApp`, which defaults to `app`.

## Every visit does a full page reload

The asset version changes between requests.

- With the default manifest hash, check that the manifest isn't rewritten on every request, for example by a running
  `vite build --watch`.
- A custom `version` callback must return the same value until assets change.

## The page title disappears after loading

The root view's `<title>` has the `data-inertia` attribute but the page doesn't render `<Head>`. Remove the attribute,
or set the title with `<Head title="...">`. See [Head elements](./root-view#head-elements-and-data-inertia).

## Form submissions fail with 422

`CsrfTokenMiddleware` rejected the request.

- Add `XsrfTokenMiddleware` after `SessionMiddleware` and before `CsrfTokenMiddleware`. See [CSRF protection](./csrf).
- The session may have expired; see [Handling expired tokens](./csrf#handling-expired-tokens).

## Validation errors don't appear

- Flash errors with `InertiaFlash::errors()` before redirecting, and make sure `yiisoft/session` is installed and
  `SessionMiddleware` runs before your action.
- Messages must be indexed by field name: `['email' => 'Invalid email.']`.
- If the form uses an error bag, errors are nested under the bag name on the client.

## Assets load from the dev server in production

A `public/hot` file was deployed, or `vite.devServerUrl` is set. Remove the file and add it to `.gitignore`.

## Dev mode loads, but imports fail with 404

Vite generated module URLs relative to the PHP server. Set `server.origin` in `vite.config.js` to the dev server URL,
for example `http://localhost:5173`.

## Server-side rendering doesn't happen

- Check that `ssr.enabled` is `true` and the SSR server is running.
- Look for `Inertia server-side rendering failed` warnings in the application log, or enable `ssr.throwOnError`.
- `ClientInterface` and `RequestFactoryInterface` must be available in the container.
