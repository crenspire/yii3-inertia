# Basic PSR Example

This example demonstrates using `crenspire/yii3-inertia` with raw PSR-7/PSR-15 (without Yii3 framework).

**Note:** This example is for PSR-compatible frameworks. For Yii3 applications, use the Yii3-specific examples:
- [Yii3 Web Application Example](../yii3-web/README.md)
- [Yii3 Minimal Example](../yii3-minimal/README.md)

## Installation

```bash
cd examples/basic
composer install
```

## Running

```bash
# Install frontend dependencies
cd vite
npm install

# Build frontend assets
npm run build

# Or run dev server
npm run dev

# Start PHP server
cd ../public
php -S localhost:8000
```

Visit `http://localhost:8000` in your browser.

