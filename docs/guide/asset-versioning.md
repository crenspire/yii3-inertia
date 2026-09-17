# Asset versioning

When you deploy new frontend assets, users with the app already open still run the old JavaScript. Inertia
detects this with an asset version and reloads the page.

## How it works

1. Every page object contains the current `version`.
2. The client sends it back in `X-Inertia-Version` on each visit.
3. If a `GET` visit carries a different version, `InertiaMiddleware` answers `409 Conflict` with
   `X-Inertia-Location`, without running the action.
4. The client does a full page load and gets the new assets.

Non-`GET` requests are not interrupted, so form submissions are never lost. The redirect after the submission
triggers the reload.

## Default: hash of the Vite manifest

By default, the version is a hash of the Vite manifest, so it changes whenever a build produces different files.
The hash is recomputed only when the manifest file changes, so it's cheap even in long-running workers.

If the manifest doesn't exist, for example while using the dev server, the version is an empty string.

## Setting the version

In Yii3, use the `version` or `manifestPath` params:

```php
'crenspire/yii3-inertia' => [
    // A fixed string, for example a release tag
    'version' => '2.4.1',

    // A callable
    'version' => static fn (): string => getenv('RELEASE_SHA') ?: '',

    // Another file to hash
    'manifestPath' => '@public/assets/manifest.json',
],
```

Without Yii3, pass a version provider to `Inertia`:

```php
use Crenspire\Inertia\Version\CallbackVersion;
use Crenspire\Inertia\Version\ManifestVersion;
use Crenspire\Inertia\Version\StaticVersion;

new ManifestVersion(__DIR__ . '/public/build/.vite/manifest.json');
new StaticVersion('2.4.1');
new CallbackVersion(static fn (): string => getenv('RELEASE_SHA') ?: '');
```

Or implement `VersionProviderInterface`:

```php
use Crenspire\Inertia\Version\VersionProviderInterface;

final readonly class ReleaseVersion implements VersionProviderInterface
{
    public function __construct(private string $release) {}

    public function getVersion(): string
    {
        return $this->release;
    }
}
```

## Keeping flash data across the reload

The version check runs before your action, so flash data isn't consumed by the `409` response and is shown after
the reload.
