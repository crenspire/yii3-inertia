# Examples

The repository contains two examples with the same small application: a user list with a deferred prop, and a
form with validation errors and a flash message.

## PSR-15 example

[`examples/psr15`](https://github.com/crenspire/yii3-inertia/tree/develop/examples/psr15) runs on the PHP built-in
server and uses only PSR components, a hand-written router and a React 19 frontend.

```bash
git clone https://github.com/crenspire/yii3-inertia.git
cd yii3-inertia/examples/psr15
composer install
npm install
npm run build
php -S 127.0.0.1:8000 -t public public/index.php
```

Open `http://127.0.0.1:8000`.

For hot module replacement:

```bash
npm run dev
VITE_DEV_SERVER_URL=http://localhost:5173 php -S 127.0.0.1:8000 -t public public/index.php
```

| File | Shows |
|---|---|
| `public/index.php` | Creating `Inertia`, the middleware and the flash store |
| `src/Router.php` | Rendering pages, deferred props, validation, redirects |
| `src/NativeSessionFlashStore.php` | A flash store on PHP sessions |
| `resources/views/inertia.php` | The root view |
| `resources/js/Pages` | React pages with `useForm`, `<Deferred>` and flash data |

## Yii3 example

[`examples/yii3`](https://github.com/crenspire/yii3-inertia/tree/develop/examples/yii3) contains the files to add to
an application created from `yiisoft/app`:

```bash
composer create-project yiisoft/app my-app
cd my-app
composer require crenspire/yii3-inertia:^2.0 yiisoft/validator
```

Copy the example's `config`, `resources` and `src` directories into the application, and the frontend files from
the PSR-15 example. Then:

```bash
npm install
npm run build
APP_ENV=dev ./yii serve
```

| File | Shows |
|---|---|
| `config/web/di/application.php` | The middleware stack with `XsrfTokenMiddleware` and `InertiaMiddleware` |
| `config/web/params.php` | Package params |
| `src/Web/Shared/ShareAuthMiddleware.php` | Per-request shared props |
| `src/Web/Users/StoreAction.php` | Validation with `yiisoft/validator` and `InertiaFlash` |
