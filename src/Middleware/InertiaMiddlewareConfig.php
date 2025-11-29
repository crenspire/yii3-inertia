<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Middleware;

/**
 * Helper class for easy Inertia middleware registration in Yii3
 * 
 * Provides static methods and recommendations for registering
 * InertiaMiddleware in Yii3's middleware pipeline.
 */
final class InertiaMiddlewareConfig
{
    /**
     * Get the middleware class name
     * 
     * @return string
     */
    public static function getMiddlewareClass(): string
    {
        return InertiaMiddleware::class;
    }

    /**
     * Get recommended middleware stack position
     * 
     * InertiaMiddleware should be registered:
     * - After authentication/authorization middleware (to access user context)
     * - Before routing middleware (to set up request early)
     * 
     * @return array<string, mixed> Position information
     */
    public static function getRecommendedPosition(): array
    {
        return [
            'after' => [
                'Error handling middleware',
                'Authentication middleware',
            ],
            'before' => [
                'Routing middleware',
                'Controller/Action execution',
            ],
            'reason' => 'InertiaMiddleware needs to set up the request early and check version before processing',
        ];
    }

    /**
     * Create middleware instance (for manual registration)
     * 
     * Note: In most cases, you should use DI container to get the middleware
     * instead of creating it manually.
     * 
     * @param \Psr\Container\ContainerInterface $container DI container
     * @return InertiaMiddleware
     */
    public static function create(\Psr\Container\ContainerInterface $container): InertiaMiddleware
    {
        return $container->get(InertiaMiddleware::class);
    }

    /**
     * Check if middleware should be registered conditionally
     * 
     * You can use this to conditionally register middleware based on
     * environment, route patterns, etc.
     * 
     * @param callable $condition Condition callback
     * @return array<string, mixed> Conditional middleware config
     */
    public static function conditional(callable $condition): array
    {
        return [
            'middleware' => InertiaMiddleware::class,
            'condition' => $condition,
        ];
    }
}

