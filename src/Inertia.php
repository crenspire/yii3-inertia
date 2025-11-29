<?php

declare(strict_types=1);

namespace Crenspire\Inertia;

use Closure;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Inertia service facade for Yii3
 * 
 * Provides a static interface to Inertia functionality, matching the
 * developer experience of inertia-laravel.
 */
class Inertia
{
    /**
     * @var array<string, mixed> Shared props available to all Inertia responses
     */
    private static array $sharedProps = [];

    /**
     * @var string|callable|null Asset version callback or string
     */
    private static $version = null;

    /**
     * @var string Root view template path
     */
    private static string $rootView = 'inertia';

    /**
     * @var ServerRequestInterface|null Current request
     */
    private static ?ServerRequestInterface $request = null;

    /**
     * Set the current request
     * 
     * @param ServerRequestInterface $request
     * @return void
     */
    public static function setRequest(ServerRequestInterface $request): void
    {
        self::$request = $request;
    }

    /**
     * Get the current request
     * 
     * @return ServerRequestInterface|null
     */
    public static function getRequest(): ?ServerRequestInterface
    {
        return self::$request;
    }

    /**
     * Render an Inertia page
     * 
     * @param string $component The Inertia component name (e.g., 'Dashboard/Index')
     * @param array<string, mixed> $props Props to pass to the component
     * @return array<string, mixed> Payload array for response factory
     */
    public static function render(string $component, array $props = []): array
    {
        // Validate component name
        if (empty($component)) {
            throw new \InvalidArgumentException('Component name cannot be empty');
        }

        // Validate props
        if (!is_array($props)) {
            throw new \InvalidArgumentException('Props must be an array');
        }

        $request = self::$request;
        if ($request === null) {
            throw new \RuntimeException('Request not set. Ensure InertiaMiddleware is registered or call Inertia::setRequest().');
        }

        // Note: Version mismatch is handled by middleware, not here
        // This allows actions to handle it themselves if needed

        // Merge shared props
        $allProps = array_merge(self::getSharedProps(), $props);

        // Handle partial reloads
        if (self::isInertiaRequest($request) && self::isPartialReload($request)) {
            $allProps = self::filterPartialProps($allProps, $request);
        }

        // Build URL with query string
        $uri = $request->getUri();
        $url = $uri->getPath();
        $query = $uri->getQuery();
        if (!empty($query)) {
            $url .= '?' . $query;
        }

        return [
            'component' => $component,
            'props' => $allProps,
            'url' => $url,
            'version' => self::version(),
        ];
    }

    /**
     * Share data with all Inertia responses
     * 
     * @param string|array<string, mixed> $key Key or array of key-value pairs
     * @param mixed $value Value or closure (if key is string)
     * @return void
     */
    public static function share($key, $value = null): void
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                self::$sharedProps[$k] = $v;
            }
        } else {
            self::$sharedProps[$key] = $value;
        }
    }

    /**
     * Get all shared props (evaluating closures)
     * 
     * @return array<string, mixed>
     */
    private static function getSharedProps(): array
    {
        $props = [];
        foreach (self::$sharedProps as $key => $value) {
            $props[$key] = $value instanceof Closure ? $value() : $value;
        }
        return $props;
    }

    /**
     * Set or get the asset version
     * 
     * @param string|callable|null $version Version string or callback
     * @return string|callable|null
     */
    public static function version($version = null)
    {
        if ($version !== null) {
            self::$version = $version;
        }

        if (self::$version === null) {
            // Default version - users should configure their own version callback
            // For Yii3, there's no standard webroot alias, so we return a default
            // Users can set their own version via Inertia::version() or a callback
            return '1';
        }

        if (is_callable(self::$version)) {
            try {
                return call_user_func(self::$version);
            } catch (\Exception $e) {
                // Fallback to default version if callback fails
                return '1';
            }
        }

        return self::$version;
    }

    /**
     * Create an Inertia location redirect response data
     * 
     * @param string $url The URL to redirect to
     * @return array<string, mixed> Response data for location redirect
     */
    public static function location(string $url): array
    {
        $request = self::$request;
        $isInertiaRequest = $request !== null && self::isInertiaRequest($request);
        
        return [
            'location' => $url,
            'status' => $isInertiaRequest ? 409 : 302, // 409 for Inertia, 302 for regular requests
        ];
    }

    /**
     * Set the root view template
     * 
     * @param string $view View path
     * @return void
     */
    public static function setRootView(string $view): void
    {
        self::$rootView = $view;
    }

    /**
     * Get the root view template
     * 
     * @return string
     */
    public static function getRootView(): string
    {
        return self::$rootView;
    }

    /**
     * Flush shared props (useful for tests)
     * 
     * @return void
     */
    public static function flushShared(): void
    {
        self::$sharedProps = [];
    }

    /**
     * Check if the request is an Inertia request
     * 
     * @param ServerRequestInterface $request
     * @return bool
     */
    public static function isInertiaRequest(ServerRequestInterface $request): bool
    {
        return $request->hasHeader('X-Inertia');
    }

    /**
     * Check if this is a partial reload request
     * 
     * @param ServerRequestInterface $request
     * @return bool
     */
    private static function isPartialReload(ServerRequestInterface $request): bool
    {
        return $request->hasHeader('X-Inertia-Partial-Component') 
            && $request->hasHeader('X-Inertia-Partial-Data');
    }

    /**
     * Filter props based on partial reload headers
     * 
     * @param array<string, mixed> $props
     * @param ServerRequestInterface $request
     * @return array<string, mixed>
     */
    private static function filterPartialProps(array $props, ServerRequestInterface $request): array
    {
        $partialData = $request->getHeaderLine('X-Inertia-Partial-Data');
        
        // If partial data header is empty, return all props
        if (empty(trim($partialData))) {
            return $props;
        }
        
        $partialKeys = array_filter(array_map('trim', explode(',', $partialData)));
        
        // Always include shared props
        $sharedKeys = array_keys(self::$sharedProps);
        $allowedKeys = array_merge($sharedKeys, $partialKeys);
        
        return array_intersect_key($props, array_flip($allowedKeys));
    }

    /**
     * Check if there's a version mismatch between request and current version
     * 
     * @param ServerRequestInterface $request
     * @return bool
     */
    private static function hasVersionMismatch(ServerRequestInterface $request): bool
    {
        if (!$request->hasHeader('X-Inertia-Version')) {
            return false;
        }

        $requestVersion = $request->getHeaderLine('X-Inertia-Version');
        $currentVersion = self::version();

        return $requestVersion !== (string) $currentVersion;
    }
}

