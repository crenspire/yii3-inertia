<?php

declare(strict_types=1);

namespace Crenspire\Inertia;

/**
 * Optional Bootstrap helper for Yii3 Inertia.js
 * 
 * This class provides convenience methods for common bootstrap tasks.
 * It's completely optional - you can use Inertia::share() directly instead.
 * 
 * Zero overhead if not used - only runs if you explicitly call these methods.
 * 
 * Usage:
 * ```php
 * use Crenspire\Inertia\Bootstrap;
 * 
 * // In your application bootstrap
 * Bootstrap::setupSharedProps($userService, $flashService);
 * Bootstrap::setupVersion('/path/to/manifest.json');
 * ```
 */
final class Bootstrap
{
    /**
     * Setup common shared props for Yii3 applications
     * 
     * This is a convenience method. You can also call Inertia::share() directly:
     * ```php
     * Inertia::share('user', fn() => $userService->getCurrentUser());
     * ```
     * 
     * @param object|null $userService Service that provides getCurrentUser() method
     * @param object|null $flashService Service that provides getFlashMessages() method
     * @param array<string, mixed> $additional Additional shared props
     * @return void
     */
    public static function setupSharedProps(
        ?object $userService = null,
        ?object $flashService = null,
        array $additional = []
    ): void {
        // Share current user if user service provided
        if ($userService !== null && method_exists($userService, 'getCurrentUser')) {
            Inertia::share('user', function () use ($userService) {
                return $userService->getCurrentUser();
            });
        }

        // Share flash messages if flash service provided
        if ($flashService !== null && method_exists($flashService, 'getFlashMessages')) {
            Inertia::share('flash', function () use ($flashService) {
                return $flashService->getFlashMessages();
            });
        }

        // Share additional props
        if (!empty($additional)) {
            Inertia::share($additional);
        }
    }

    /**
     * Setup asset version from manifest file
     * 
     * Convenience method for setting version callback from manifest.json.
     * You can also call Inertia::version() directly:
     * ```php
     * Inertia::version(fn() => filemtime('/path/to/manifest.json'));
     * ```
     * 
     * @param string $manifestPath Path to manifest.json file
     * @return void
     */
    public static function setupVersion(string $manifestPath): void
    {
        if (!file_exists($manifestPath)) {
            throw new \InvalidArgumentException("Manifest file not found: {$manifestPath}");
        }

        Inertia::version(function () use ($manifestPath) {
            return (string) filemtime($manifestPath);
        });
    }

    /**
     * Setup root view template
     * 
     * Convenience method. You can also call Inertia::setRootView() directly.
     * 
     * @param string $viewName Root view template name/path
     * @return void
     */
    public static function setupRootView(string $viewName): void
    {
        Inertia::setRootView($viewName);
    }

    /**
     * Complete bootstrap setup
     * 
     * Convenience method that sets up all common configurations at once.
     * 
     * @param array<string, mixed> $options Bootstrap options
     * @return void
     */
    public static function setup(array $options): void
    {
        // Setup shared props
        if (isset($options['userService']) || isset($options['flashService']) || isset($options['shared'])) {
            self::setupSharedProps(
                $options['userService'] ?? null,
                $options['flashService'] ?? null,
                $options['shared'] ?? []
            );
        }

        // Setup version
        if (isset($options['manifestPath'])) {
            self::setupVersion($options['manifestPath']);
        } elseif (isset($options['version'])) {
            Inertia::version($options['version']);
        }

        // Setup root view
        if (isset($options['rootView'])) {
            self::setupRootView($options['rootView']);
        }
    }
}

