# PSR-15 example

A small application built only from PSR components: `nyholm/psr7`, a hand-written router, and
`crenspire/yii3-inertia`. It uses React 19 with Inertia.js 3 and shows:

- A page with a deferred prop (`/users`)
- A form with validation errors flashed across a redirect (`/users/create`)
- Flash messages and shared props

Users are stored in the PHP session.

## Run

```bash
cd examples/psr15
composer install
npm install
npm run build
php -S 127.0.0.1:8000 -t public public/index.php
```

Open http://127.0.0.1:8000.

## Develop with hot reload

```bash
npm run dev
VITE_DEV_SERVER_URL=http://localhost:5173 php -S 127.0.0.1:8000 -t public public/index.php
```

## Files

- `public/index.php`: creates `Inertia`, `InertiaMiddleware` and the router.
- `src/Router.php`: routes and actions.
- `src/NativeSessionFlashStore.php`: flash store on PHP sessions.
- `resources/views/inertia.php`: root view.
- `resources/js`: React pages.
