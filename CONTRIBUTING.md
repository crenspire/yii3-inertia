# Contributing

Thank you for considering contributing to Yii3 Inertia.

## Development setup

```bash
git clone https://github.com/crenspire/yii3-inertia.git
cd yii3-inertia
composer install
```

## Checks

Run both before opening a pull request:

```bash
composer test     # PHPUnit
composer analyse  # PHPStan
```

To try changes in a browser, run [`examples/psr15`](examples/psr15), which uses the package from the repository.

## Documentation

The documentation site is built with [VitePress](https://vitepress.dev) from the `docs` directory and published to
GitHub Pages when `develop` changes.

```bash
cd docs
npm install
npm run dev
```

Update the documentation together with the code, and run `npm run build` to check for dead links.

## Guidelines

- Follow PSR-12 and use `declare(strict_types=1);`.
- Keep services free of per-request state; pass request data through request attributes.
- Match the [Inertia.js protocol](https://inertiajs.com/docs/v3/core-concepts/the-protocol) and the reference Laravel
  adapter when adding protocol features.
- Add tests for new behavior, and update the documentation and CHANGELOG.

## Pull requests

1. Create a feature branch from `develop`.
2. Make your changes with tests.
3. Make sure `composer test` and `composer analyse` pass.
4. Open a pull request with a clear description.

## Versioning

The project follows [Semantic Versioning](https://semver.org/).
