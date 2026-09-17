<?php

declare(strict_types=1);

namespace Crenspire\Inertia;

use Closure;
use Crenspire\Inertia\Flash\FlashStoreInterface;
use Crenspire\Inertia\Flash\InertiaFlash;
use Crenspire\Inertia\Prop\AlwaysProp;
use Crenspire\Inertia\Prop\DeferProp;
use Crenspire\Inertia\Prop\MergeProp;
use Crenspire\Inertia\Prop\OnceProp;
use Crenspire\Inertia\Prop\OptionalProp;
use Crenspire\Inertia\Prop\ProvidesInertiaProperties;
use Crenspire\Inertia\Prop\ProvidesScrollMetadata;
use Crenspire\Inertia\Prop\ScrollProp;
use Crenspire\Inertia\Ssr\GatewayInterface;
use Crenspire\Inertia\Version\StaticVersion;
use Crenspire\Inertia\Version\VersionProviderInterface;
use Crenspire\Inertia\View\InertiaView;
use Crenspire\Inertia\View\RootViewRendererInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use stdClass;

/**
 * Creates Inertia responses.
 *
 * The service holds no per-request state, so a single instance can serve every request, including in
 * long-running workers. Request-specific data is passed through request attributes, see {@see share()}.
 *
 * ```php
 * public function __invoke(ServerRequestInterface $request, Inertia $inertia): ResponseInterface
 * {
 *     return $inertia->render($request, 'Users/Index', [
 *         'users' => fn () => $this->users->findAll(),
 *     ]);
 * }
 * ```
 */
final class Inertia
{
    public const SHARED_ATTRIBUTE = 'inertia.shared';
    public const ERRORS_ATTRIBUTE = 'inertia.errors';
    public const ENCRYPT_HISTORY_ATTRIBUTE = 'inertia.encryptHistory';
    public const CLEAR_HISTORY_ATTRIBUTE = 'inertia.clearHistory';

    private ?Closure $urlResolver = null;

    /**
     * @param array<array-key, mixed> $sharedProps Props added to every page. Values may be callables and prop types.
     * @param bool $encryptHistory Encrypt page data stored in the browser history.
     * @param bool $allErrors Send every validation message per field instead of only the first one.
     */
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly RootViewRendererInterface $rootViewRenderer,
        private readonly VersionProviderInterface $version = new StaticVersion(),
        private readonly ?FlashStoreInterface $flashStore = null,
        private readonly ?GatewayInterface $ssrGateway = null,
        private readonly ?LoggerInterface $logger = null,
        private array $sharedProps = [],
        private bool $encryptHistory = false,
        private bool $allErrors = false,
    ) {
    }

    /**
     * Render a page component: JSON for Inertia visits, the root view for the first visit.
     *
     * @param array<array-key, mixed> $props
     */
    public function render(ServerRequestInterface $request, string $component, array $props = []): ResponseInterface
    {
        $page = $this->createPage($request, $component, $props);

        return self::isInertiaRequest($request)
            ? $this->jsonResponse($page)
            : $this->htmlResponse($page, $request);
    }

    /**
     * Resolve props and build the page object without creating a response.
     *
     * @param array<array-key, mixed> $props
     */
    public function createPage(ServerRequestInterface $request, string $component, array $props = []): Page
    {
        if ($component === '') {
            throw new InvalidArgumentException('Inertia component name must not be empty.');
        }

        $requestShared = $request->getAttribute(self::SHARED_ATTRIBUTE, []);
        $shared = array_merge(
            ['errors' => new AlwaysProp(fn (): object => $this->resolveErrors($request))],
            $this->sharedProps,
            is_array($requestShared) ? $requestShared : [],
        );

        [$resolvedProps, $metadata] = (new PropsResolver($request, $component, $this->logger))->resolve($shared, $props);

        $encryptHistory = $request->getAttribute(self::ENCRYPT_HISTORY_ATTRIBUTE);
        if (is_bool($encryptHistory) ? $encryptHistory : $this->encryptHistory) {
            $metadata['encryptHistory'] = true;
        }

        $clearHistory = $this->flashStore?->pull(InertiaFlash::CLEAR_HISTORY) === true;
        if ($clearHistory || $request->getAttribute(self::CLEAR_HISTORY_ATTRIBUTE) === true) {
            $metadata['clearHistory'] = true;
        }

        $flash = $this->flashStore?->pull(InertiaFlash::FLASH);
        if (is_array($flash) && $flash !== []) {
            $metadata['flash'] = $flash;
        }

        if ($this->flashStore?->pull(InertiaFlash::PRESERVE_FRAGMENT) === true) {
            $metadata['preserveFragment'] = true;
        }

        /** @var array<string, mixed> $resolvedProps */
        return new Page($component, $resolvedProps, $this->resolveUrl($request), $this->getVersion(), $metadata);
    }

    /**
     * Redirect to a URL outside of Inertia, such as another application or a non-Inertia page.
     *
     * Inertia visits receive 409 with X-Inertia-Location, which makes the client do a full page visit.
     */
    public function location(ServerRequestInterface $request, string|UriInterface $url): ResponseInterface
    {
        if (self::isInertiaRequest($request)) {
            return $this->responseFactory->createResponse(409)->withHeader(Header::LOCATION, (string) $url);
        }

        return $this->redirect($url);
    }

    /**
     * Regular redirect. InertiaMiddleware turns 302 into 303 after PUT, PATCH and DELETE.
     */
    public function redirect(string|UriInterface $url, int $status = 302): ResponseInterface
    {
        return $this->responseFactory->createResponse($status)->withHeader('Location', (string) $url);
    }

    /**
     * Redirect to the page the request came from.
     */
    public function back(ServerRequestInterface $request, string $fallback = '/', int $status = 302): ResponseInterface
    {
        $referer = $request->getHeaderLine('Referer');

        return $this->redirect($referer !== '' ? $referer : $fallback, $status);
    }

    public function getVersion(): string
    {
        return $this->version->getVersion();
    }

    /**
     * @param array<array-key, mixed> $props
     */
    public function withSharedProps(array $props): self
    {
        $new = clone $this;
        $new->sharedProps = array_merge($this->sharedProps, $props);

        return $new;
    }

    public function withEncryptHistory(bool $encrypt = true): self
    {
        $new = clone $this;
        $new->encryptHistory = $encrypt;

        return $new;
    }

    public function withAllErrors(bool $allErrors = true): self
    {
        $new = clone $this;
        $new->allErrors = $allErrors;

        return $new;
    }

    /**
     * Customize the page URL, for example when the application is served from a sub-path behind a proxy.
     *
     * @param callable(ServerRequestInterface): string $resolver
     */
    public function withUrlResolver(callable $resolver): self
    {
        $new = clone $this;
        $new->urlResolver = $resolver(...);

        return $new;
    }

    public static function isInertiaRequest(ServerRequestInterface $request): bool
    {
        return $request->getHeaderLine(Header::INERTIA) !== '';
    }

    /**
     * Share props for the current request only, typically from a middleware.
     *
     * ```php
     * $request = Inertia::share($request, 'auth', ['user' => $identity]);
     * return $handler->handle($request);
     * ```
     *
     * @param string|array<array-key, mixed>|ProvidesInertiaProperties $key
     */
    public static function share(
        ServerRequestInterface $request,
        string|array|ProvidesInertiaProperties $key,
        mixed $value = null,
    ): ServerRequestInterface {
        $shared = $request->getAttribute(self::SHARED_ATTRIBUTE, []);
        $shared = is_array($shared) ? $shared : [];

        if ($key instanceof ProvidesInertiaProperties) {
            $shared[] = $key;
        } elseif (is_array($key)) {
            $shared = array_merge($shared, $key);
        } else {
            $shared[$key] = $value;
        }

        return $request->withAttribute(self::SHARED_ATTRIBUTE, $shared);
    }

    /**
     * Add validation errors for a page rendered in the same request.
     *
     * To show errors after a redirect, use {@see InertiaFlash::errors()}.
     *
     * @param array<string, string|list<string>> $errors Messages indexed by field name.
     */
    public static function withErrors(ServerRequestInterface $request, array $errors, string $bag = 'default'): ServerRequestInterface
    {
        $bags = $request->getAttribute(self::ERRORS_ATTRIBUTE, []);
        $bags = is_array($bags) ? $bags : [];
        $bags[$bag] = array_merge(is_array($bags[$bag] ?? null) ? $bags[$bag] : [], $errors);

        return $request->withAttribute(self::ERRORS_ATTRIBUTE, $bags);
    }

    public static function encryptHistory(ServerRequestInterface $request, bool $encrypt = true): ServerRequestInterface
    {
        return $request->withAttribute(self::ENCRYPT_HISTORY_ATTRIBUTE, $encrypt);
    }

    public static function clearHistory(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request->withAttribute(self::CLEAR_HISTORY_ATTRIBUTE, true);
    }

    /**
     * A prop that is only resolved when a partial reload asks for it.
     */
    public static function optional(callable $callback): OptionalProp
    {
        return new OptionalProp($callback);
    }

    /**
     * A prop loaded by the client in a separate request right after the page renders.
     *
     * @param bool $rescue Log a failure and report it to the client instead of failing the request.
     */
    public static function defer(callable $callback, string $group = 'default', bool $rescue = false): DeferProp
    {
        return new DeferProp($callback, $group, $rescue);
    }

    /**
     * A prop merged into the client's current value during partial reloads.
     */
    public static function merge(mixed $value): MergeProp
    {
        return new MergeProp($value);
    }

    /**
     * A prop deep-merged into the client's current value during partial reloads.
     */
    public static function deepMerge(mixed $value): MergeProp
    {
        return (new MergeProp($value))->deepMerge();
    }

    /**
     * A prop included in every response, even partial reloads that did not request it.
     */
    public static function always(mixed $value): AlwaysProp
    {
        return new AlwaysProp($value);
    }

    /**
     * A prop resolved once and remembered by the client across visits.
     */
    public static function once(callable $callback): OnceProp
    {
        return new OnceProp($callback);
    }

    /**
     * A paginated prop for the client's infinite scroll component.
     *
     * @param ProvidesScrollMetadata|callable(mixed): ProvidesScrollMetadata|null $metadata
     */
    public static function scroll(
        mixed $value,
        string $wrapper = 'data',
        ProvidesScrollMetadata|callable|null $metadata = null,
        string $pageName = 'page',
    ): ScrollProp {
        return new ScrollProp($value, $wrapper, $metadata, $pageName);
    }

    private function jsonResponse(Page $page): ResponseInterface
    {
        $json = json_encode($page, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json')
            ->withHeader(Header::INERTIA, 'true')
            ->withHeader('Vary', Header::INERTIA)
            ->withBody($this->streamFactory->createStream($json));
    }

    private function htmlResponse(Page $page, ServerRequestInterface $request): ResponseInterface
    {
        $view = new InertiaView($page, $this->ssrGateway?->dispatch($page, $request));
        $html = $this->rootViewRenderer->render($view, $request);

        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/html; charset=UTF-8')
            ->withHeader('Vary', Header::INERTIA)
            ->withBody($this->streamFactory->createStream($html));
    }

    private function resolveUrl(ServerRequestInterface $request): string
    {
        if ($this->urlResolver !== null) {
            return (string) ($this->urlResolver)($request);
        }

        $uri = $request->getUri();
        $url = '/' . ltrim($uri->getPath(), '/');
        $query = $uri->getQuery();

        return $query === '' ? $url : "{$url}?{$query}";
    }

    private function resolveErrors(ServerRequestInterface $request): object
    {
        $bags = [];

        $flashed = $this->flashStore?->pull(InertiaFlash::ERRORS);
        if (is_array($flashed)) {
            $bags = $flashed;
        }

        $attribute = $request->getAttribute(self::ERRORS_ATTRIBUTE);
        if (is_array($attribute)) {
            foreach ($attribute as $bag => $errors) {
                $bags[$bag] = array_merge(is_array($bags[$bag] ?? null) ? $bags[$bag] : [], (array) $errors);
            }
        }

        $normalized = [];
        foreach ($bags as $bag => $errors) {
            if (!is_array($errors) || $errors === []) {
                continue;
            }
            foreach ($errors as $field => $messages) {
                if (is_array($messages)) {
                    $messages = array_values(array_map('strval', $messages));
                    if ($messages === []) {
                        continue;
                    }
                    $normalized[$bag][$field] = $this->allErrors ? $messages : $messages[0];
                } else {
                    $normalized[$bag][$field] = $this->allErrors ? [(string) $messages] : (string) $messages;
                }
            }
        }

        if ($normalized === []) {
            return new stdClass();
        }

        if (isset($normalized['default'])) {
            $errorBag = $request->getHeaderLine(Header::ERROR_BAG);

            return $errorBag !== ''
                ? (object) [$errorBag => (object) $normalized['default']]
                : (object) $normalized['default'];
        }

        return (object) array_map(static fn (array $errors): object => (object) $errors, $normalized);
    }
}
