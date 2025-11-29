<?php

declare(strict_types=1);

namespace Crenspire\Inertia;

/**
 * Helper class to bridge ResponseFactory with Yii3 View system
 * 
 * This class provides integration with Yii3's view renderer for rendering
 * the Inertia root template. It gracefully degrades if yiisoft/view is not installed.
 */
final class ViewRenderer
{
    /**
     * Render Inertia root template using Yii3 View
     * 
     * @param object $view Yii3 ViewInterface instance
     * @param string $viewName View template name/path
     * @param array<string, mixed> $payload Inertia page payload
     * @return string Rendered HTML
     */
    public static function render(object $view, string $viewName, array $payload): string
    {
        // Check if view has render method (Yii3 ViewInterface)
        if (!method_exists($view, 'render')) {
            throw new \RuntimeException('View object must have a render() method');
        }

        try {
            // Encode payload for data-page attribute
            $page = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $pageEscaped = htmlspecialchars($page, ENT_QUOTES, 'UTF-8');
            
            // Render view with page data
            // The view template should have access to $page variable
            return $view->render($viewName, [
                'page' => $pageEscaped,
                'payload' => $payload,
            ]);
        } catch (\JsonException $e) {
            throw new \RuntimeException("Failed to encode payload for view: {$e->getMessage()}", 0, $e);
        } catch (\Throwable $e) {
            throw new \RuntimeException("View rendering failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Check if Yii3 View is available
     * 
     * @param object $view View object to check
     * @return bool
     */
    public static function isAvailable(object $view): bool
    {
        return method_exists($view, 'render');
    }
}

