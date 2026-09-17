# Yii3 example

Files to add to an application created from the [`yiisoft/app`](https://github.com/yiisoft/app) template. The
example has the same pages as [`examples/psr15`](../psr15), using Yii actions, `yiisoft/validator` and
`yiisoft/session`.

## Set up

1. Create the application and install the packages:

   ```bash
   composer create-project yiisoft/app my-app
   cd my-app
   composer require crenspire/yii3-inertia yiisoft/validator
   ```

2. Copy the files from this directory into the application, replacing existing ones:

   ```
   config/common/routes.php           routes for the pages
   config/web/di/application.php      middleware stack with XsrfTokenMiddleware and InertiaMiddleware
   config/web/params.php              package params
   resources/views/inertia.php        root view
   src/Web/HomePage/Action.php        replaces the template home page
   src/Web/Shared/ShareAuthMiddleware.php
   src/Web/Users/*.php
   ```

3. Copy the frontend from `examples/psr15`: `resources/js`, `package.json` and `vite.config.js`.

4. Build the assets and start the server:

   ```bash
   npm install
   npm run build
   APP_ENV=dev ./yii serve
   ```

For hot reload, run `npm run dev` and start the server with `VITE_DEV_SERVER_URL=http://localhost:5173`.

## How it fits together

- The package registers `Inertia`, `InertiaFlash` and the Vite helper through `yiisoft/config`; actions receive
  them by constructor injection.
- `XsrfTokenMiddleware` sits between `SessionMiddleware` and `CsrfTokenMiddleware`, so form submissions from the
  Inertia client pass CSRF validation.
- `InertiaMiddleware` runs before the router and handles asset versions and redirects.
- `ShareAuthMiddleware` shows how to share per-request props.
- `StoreAction` validates input, flashes errors through the session and redirects back.
