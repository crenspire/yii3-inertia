<?php

declare(strict_types=1);

namespace Crenspire\Inertia;

use Crenspire\Inertia\Prop\AlwaysProp;
use Crenspire\Inertia\Prop\Deferrable;
use Crenspire\Inertia\Prop\IgnoreFirstLoad;
use Crenspire\Inertia\Prop\Mergeable;
use Crenspire\Inertia\Prop\Onceable;
use Crenspire\Inertia\Prop\PropertyContext;
use Crenspire\Inertia\Prop\ProvidesInertiaProperties;
use Crenspire\Inertia\Prop\ProvidesInertiaProperty;
use Crenspire\Inertia\Prop\RenderContext;
use Crenspire\Inertia\Prop\Rescuable;
use Crenspire\Inertia\Prop\ScrollProp;
use JsonSerializable;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Traversable;

/**
 * Resolves shared and page props for one render and collects the page metadata that goes with them.
 *
 * @internal
 */
final class PropsResolver
{
    private readonly bool $isPartial;
    private readonly bool $isInertia;
    /** @var list<string>|null */
    private readonly ?array $only;
    /** @var list<string>|null */
    private readonly ?array $except;
    /** @var list<string> */
    private readonly array $resetProps;
    /** @var list<string> */
    private readonly array $loadedOnceProps;

    /** @var array<string, list<string>> */
    private array $deferredProps = [];
    /** @var list<string> */
    private array $rescuedProps = [];
    /** @var list<string> */
    private array $mergeProps = [];
    /** @var list<string> */
    private array $prependProps = [];
    /** @var list<string> */
    private array $deepMergeProps = [];
    /** @var list<string> */
    private array $matchPropsOn = [];
    /** @var array<string, array<string, mixed>> */
    private array $scrollProps = [];
    /** @var array<string, array{prop: string, expiresAt: int|null}> */
    private array $onceProps = [];
    /** @var list<string> */
    private array $sharedPropKeys = [];

    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly string $component,
        private readonly ?LoggerInterface $logger = null,
    ) {
        $this->isPartial = $request->getHeaderLine(Header::PARTIAL_COMPONENT) === $component;
        $this->isInertia = $request->getHeaderLine(Header::INERTIA) !== '';
        $this->only = $this->parseHeader(Header::PARTIAL_ONLY);
        $this->except = $this->parseHeader(Header::PARTIAL_EXCEPT);
        $this->resetProps = $this->parseHeader(Header::RESET) ?? [];
        $this->loadedOnceProps = $this->parseHeader(Header::EXCEPT_ONCE_PROPS) ?? [];
    }

    /**
     * @param array<array-key, mixed> $shared
     * @param array<array-key, mixed> $props
     * @return array{array<string, mixed>, array<string, mixed>} Resolved props and page metadata.
     */
    public function resolve(array $shared, array $props): array
    {
        $shared = $this->resolvePropertyProviders($shared);

        foreach (array_keys($shared) as $key) {
            $this->sharedPropKeys[] = explode('.', (string) $key, 2)[0];
        }
        $this->sharedPropKeys = array_values(array_unique($this->sharedPropKeys));

        $all = array_merge($shared, $this->resolvePropertyProviders($props));

        return [$this->resolveProps($this->unpackDotProps($all)), $this->buildMetadata()];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMetadata(): array
    {
        return array_filter([
            'sharedProps' => $this->sharedPropKeys,
            'mergeProps' => $this->mergeProps,
            'prependProps' => $this->prependProps,
            'deepMergeProps' => $this->deepMergeProps,
            'matchPropsOn' => $this->matchPropsOn,
            'deferredProps' => $this->deferredProps,
            'rescuedProps' => $this->rescuedProps,
            'scrollProps' => $this->scrollProps,
            'onceProps' => $this->onceProps,
        ], static fn (array $value): bool => $value !== []);
    }

    /**
     * @param array<array-key, mixed> $props
     * @return array<array-key, mixed>
     */
    private function resolvePropertyProviders(array $props): array
    {
        $context = null;
        $result = [];

        foreach ($props as $key => $value) {
            if (is_int($key) && $value instanceof ProvidesInertiaProperties) {
                $context ??= new RenderContext($this->component, $this->request);
                foreach ($value->toInertiaProperties($context) as $providedKey => $providedValue) {
                    $result[$providedKey] = $providedValue;
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @param array<array-key, mixed> $props
     * @return array<array-key, mixed>
     */
    private function resolveProps(array $props, string $prefix = '', bool $parentWasResolved = false): array
    {
        $props = $this->resolvePropertyProviders($props);
        $result = [];

        foreach ($props as $key => $prop) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            // Partial reloads only include requested paths; AlwaysProp and children of resolved values bypass the filter.
            if (!$this->shouldIncludeInPartialResponse($prop, $path, $parentWasResolved)) {
                continue;
            }

            // Deferred, optional and already-loaded once props are skipped before their callbacks run.
            if (!$this->isPartial && $this->excludeFromInitialResponse($prop, $path)) {
                continue;
            }

            $value = $this->resolveValue($prop, $path, $props);

            if (in_array($path, $this->rescuedProps, true)) {
                continue;
            }

            // A callback may return a prop type; unwrap it so it takes part in filtering and metadata.
            if ($value !== $prop && $this->isPropType($value)) {
                $prop = $value;

                if (!$this->isPartial && $this->excludeFromInitialResponse($prop, $path)) {
                    continue;
                }

                $value = $this->resolveValue($prop, $path, $props);

                if (in_array($path, $this->rescuedProps, true)) {
                    continue;
                }
            }

            $this->collectMetadata($prop, $path);

            $result[$key] = is_array($value)
                ? $this->resolveProps($value, $path, $parentWasResolved || !is_array($prop))
                : $value;
        }

        return $result;
    }

    private function shouldIncludeInPartialResponse(mixed $prop, string $path, bool $parentWasResolved): bool
    {
        if (!$this->isPartial || $prop instanceof AlwaysProp || $parentWasResolved) {
            return true;
        }

        if ($this->only !== null && !$this->matchesOnly($path) && !$this->leadsToOnly($path)) {
            return false;
        }

        return $this->except === null || !$this->matchesExcept($path);
    }

    private function excludeFromInitialResponse(mixed $prop, string $path): bool
    {
        if ($prop instanceof IgnoreFirstLoad) {
            if ($prop instanceof Deferrable && $prop->shouldDefer() && !$this->wasAlreadyLoadedByClient($prop, $path)) {
                $this->deferredProps[$prop->group()][] = $path;
            }
            if ($prop instanceof Mergeable && $prop->shouldMerge()) {
                $this->collectMergeableMetadata($path, $prop);
            }
            $this->collectOnceMetadata($path, $prop);

            return true;
        }

        if ($prop instanceof Deferrable && $prop->shouldDefer()) {
            $this->deferredProps[$prop->group()][] = $path;
            if ($prop instanceof Mergeable && $prop->shouldMerge()) {
                $this->collectMergeableMetadata($path, $prop);
            }

            return true;
        }

        if ($this->isInertia && $this->wasAlreadyLoadedByClient($prop, $path)) {
            $this->collectOnceMetadata($path, $prop);

            return true;
        }

        return false;
    }

    private function wasAlreadyLoadedByClient(mixed $prop, string $path): bool
    {
        return $prop instanceof Onceable
            && $prop->shouldResolveOnce()
            && !$prop->shouldBeRefreshed()
            && in_array($prop->getKey() ?? $path, $this->loadedOnceProps, true);
    }

    /**
     * @param array<array-key, mixed> $siblings
     */
    private function resolveValue(mixed $value, string $path, array $siblings): mixed
    {
        if ($value instanceof ScrollProp) {
            $value->configureMergeIntent($this->request);
        }

        $shouldRescue = $value instanceof Rescuable && $value->shouldRescue();

        try {
            if (is_object($value) && is_callable($value)) {
                $value = $value();
            }

            if ($value instanceof ProvidesInertiaProperty) {
                $value = $value->toInertiaProperty(new PropertyContext($path, $siblings, $this->request));
            }

            if ($value instanceof JsonSerializable) {
                $value = $value->jsonSerialize();
            }

            if ($value instanceof Traversable) {
                $value = iterator_to_array($value);
            }

            return $value;
        } catch (Throwable $e) {
            if (!$shouldRescue) {
                throw $e;
            }

            $this->logger?->error('Failed to resolve deferred Inertia prop "{prop}": {message}', [
                'prop' => $path,
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            $this->rescuedProps[] = $path;

            return null;
        }
    }

    private function isPropType(mixed $value): bool
    {
        return $value instanceof AlwaysProp
            || $value instanceof Deferrable
            || $value instanceof IgnoreFirstLoad
            || $value instanceof Mergeable
            || $value instanceof Onceable;
    }

    private function collectMetadata(mixed $prop, string $path): void
    {
        if ($prop instanceof Mergeable && $prop->shouldMerge()) {
            $this->collectMergeableMetadata($path, $prop);
        }

        if ($prop instanceof ScrollProp) {
            $this->scrollProps[$path] = [
                ...$prop->metadata(),
                'reset' => in_array($path, $this->resetProps, true),
            ];
        }

        $this->collectOnceMetadata($path, $prop);
    }

    private function collectMergeableMetadata(string $path, Mergeable $prop): void
    {
        if (in_array($path, $this->resetProps, true)) {
            return;
        }

        if ($this->isPartial && !$this->isIncludedInPartialMetadata($path)) {
            return;
        }

        if ($prop->shouldDeepMerge()) {
            $this->deepMergeProps[] = $path;
        } elseif ($prop->appendsAtRoot()) {
            $this->mergeProps[] = $path;
        } elseif ($prop->prependsAtRoot()) {
            $this->prependProps[] = $path;
        } else {
            foreach ($prop->appendsAtPaths() as $appendPath) {
                $this->mergeProps[] = "{$path}.{$appendPath}";
            }
            foreach ($prop->prependsAtPaths() as $prependPath) {
                $this->prependProps[] = "{$path}.{$prependPath}";
            }
        }

        foreach ($prop->matchesOn() as $strategy) {
            $this->matchPropsOn[] = "{$path}.{$strategy}";
        }
    }

    private function collectOnceMetadata(string $path, mixed $prop): void
    {
        if (!$prop instanceof Onceable || !$prop->shouldResolveOnce()) {
            return;
        }

        if ($this->isPartial && !$this->isIncludedInPartialMetadata($path)) {
            return;
        }

        $this->onceProps[$prop->getKey() ?? $path] = [
            'prop' => $path,
            'expiresAt' => $prop->expiresAt(),
        ];
    }

    private function isIncludedInPartialMetadata(string $path): bool
    {
        if ($this->only !== null && !$this->matchesOnly($path)) {
            return false;
        }

        return $this->except === null || !$this->matchesExcept($path);
    }

    private function matchesOnly(string $path): bool
    {
        foreach ($this->only ?? [] as $onlyPath) {
            if ($path === $onlyPath || str_starts_with($path, "{$onlyPath}.")) {
                return true;
            }
        }

        return false;
    }

    private function leadsToOnly(string $path): bool
    {
        foreach ($this->only ?? [] as $onlyPath) {
            if (str_starts_with($onlyPath, "{$path}.")) {
                return true;
            }
        }

        return false;
    }

    private function matchesExcept(string $path): bool
    {
        foreach ($this->except ?? [] as $exceptPath) {
            if ($path === $exceptPath || str_starts_with($path, "{$exceptPath}.")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Turn top-level keys such as "user.name" into nested arrays.
     *
     * @param array<array-key, mixed> $props
     * @return array<array-key, mixed>
     */
    private function unpackDotProps(array $props): array
    {
        foreach ($props as $key => $value) {
            if (!is_string($key) || !str_contains($key, '.')) {
                continue;
            }

            unset($props[$key]);

            $segments = explode('.', $key);
            $last = array_pop($segments);
            $current = &$props;

            foreach ($segments as $segment) {
                if (isset($current[$segment]) && is_object($current[$segment]) && is_callable($current[$segment])) {
                    $current[$segment] = $current[$segment]();
                }
                if (!isset($current[$segment]) || !is_array($current[$segment])) {
                    $current[$segment] = [];
                }
                $current = &$current[$segment];
            }

            $current[$last] = $value;
            unset($current);
        }

        return $props;
    }

    /**
     * @return list<string>|null
     */
    private function parseHeader(string $name): ?array
    {
        $values = array_values(array_filter(
            array_map('trim', explode(',', $this->request->getHeaderLine($name))),
            static fn (string $value): bool => $value !== '',
        ));

        return $values === [] ? null : $values;
    }
}
