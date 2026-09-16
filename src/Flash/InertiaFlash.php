<?php

declare(strict_types=1);

namespace Crenspire\Inertia\Flash;

/**
 * Stores data for the next Inertia page render, usually right before a redirect.
 *
 * ```php
 * if (!$result->isValid()) {
 *     $flash->errors($result->getErrorMessagesIndexedByProperty());
 *     return $inertia->back($request);
 * }
 * ```
 */
final class InertiaFlash
{
    public const ERRORS = 'inertia.errors';
    public const FLASH = 'inertia.flash';
    public const CLEAR_HISTORY = 'inertia.clearHistory';
    public const PRESERVE_FRAGMENT = 'inertia.preserveFragment';

    public function __construct(private readonly FlashStoreInterface $store)
    {
    }

    /**
     * Flash validation errors. They are shared as the "errors" prop on the next render.
     *
     * @param array<string, string|list<string>> $errors Messages indexed by field name.
     * @param string $bag Error bag name; use it when a page has several forms.
     */
    public function errors(array $errors, string $bag = 'default'): self
    {
        $bags = $this->store->get(self::ERRORS);
        $bags = is_array($bags) ? $bags : [];
        $bags[$bag] = $errors;
        $this->store->set(self::ERRORS, $bags);

        return $this;
    }

    /**
     * Flash data that the client exposes as `page.flash` on the next render.
     *
     * @param string|array<string, mixed> $key
     */
    public function flash(string|array $key, mixed $value = null): self
    {
        $data = $this->store->get(self::FLASH);
        $data = is_array($data) ? $data : [];
        $data = is_array($key) ? array_merge($data, $key) : array_merge($data, [$key => $value]);
        $this->store->set(self::FLASH, $data);

        return $this;
    }

    /**
     * Clear the client's encrypted history state on the next render, for example after logout.
     */
    public function clearHistory(): self
    {
        $this->store->set(self::CLEAR_HISTORY, true);

        return $this;
    }

    /**
     * Keep the URL fragment of the original request across the next redirect.
     */
    public function preserveFragment(): self
    {
        $this->store->set(self::PRESERVE_FRAGMENT, true);

        return $this;
    }
}
