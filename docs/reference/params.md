# Configuration params

Params for the Yii3 configuration, under the `crenspire/yii3-inertia` key. Values set in your application are merged
over these defaults.

```php
return [
    'crenspire/yii3-inertia' => [
        'rootView' => '@root/resources/views/inertia.php',
        'viewParameters' => [],
        'version' => null,
        'manifestPath' => null,
        'sharedProps' => [],
        'encryptHistory' => false,
        'allErrors' => false,
        'vite' => [
            'publicPath' => '@public',
            'buildDirectory' => 'build',
            'manifest' => '.vite/manifest.json',
            'devServerUrl' => null,
            'hotFile' => 'hot',
            'baseUrl' => '@baseUrl',
        ],
        'ssr' => [
            'enabled' => false,
            'url' => 'http://127.0.0.1:13714/render',
            'except' => [],
            'throwOnError' => false,
        ],
    ],
];
```

## General

| Param | Type | Default | Description |
|---|---|---|---|
| `rootView` | `string` | `@root/resources/views/inertia.php` | PHP template for the first visit. Aliases are resolved. |
| `viewParameters` | `array` | `[]` | Extra root view variables. A `Closure` is called with the container. |
| `version` | `string\|callable\|null` | `null` | Asset version. `null` hashes the manifest. |
| `manifestPath` | `string\|null` | `null` | File hashed when `version` is `null`. `null` uses the Vite manifest. |
| `sharedProps` | `array` | `[]` | Props added to every page. |
| `encryptHistory` | `bool` | `false` | Encrypt history state for every page. |
| `allErrors` | `bool` | `false` | Send all validation messages per field. |

## vite

| Param | Type | Default | Description |
|---|---|---|---|
| `publicPath` | `string` | `@public` | Filesystem path of the web root. |
| `buildDirectory` | `string` | `build` | Build directory relative to the web root. |
| `manifest` | `string` | `.vite/manifest.json` | Manifest path relative to the build directory. |
| `devServerUrl` | `string\|null` | `null` | Dev server URL. |
| `hotFile` | `string` | `hot` | File in the web root that enables dev mode. `''` disables it. |
| `baseUrl` | `string` | `@baseUrl` | URL of the web root. |

## ssr

| Param | Type | Default | Description |
|---|---|---|---|
| `enabled` | `bool` | `false` | Render pages on the server. |
| `url` | `string` | `http://127.0.0.1:13714/render` | SSR server endpoint. |
| `except` | `list<string>` | `[]` | Path prefixes that are never rendered on the server. |
| `throwOnError` | `bool` | `false` | Throw SSR failures instead of logging them. |
