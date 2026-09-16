<?php

declare(strict_types=1);

namespace Crenspire\Inertia;

use JsonSerializable;
use stdClass;

/**
 * The Inertia page object sent to the client.
 */
final class Page implements JsonSerializable
{
    /**
     * @param array<string, mixed> $props Resolved props.
     * @param array<string, mixed> $metadata Optional page keys such as "deferredProps", "mergeProps" or "encryptHistory".
     */
    public function __construct(
        public readonly string $component,
        public readonly array $props,
        public readonly string $url,
        public readonly string $version,
        public readonly array $metadata = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'component' => $this->component,
            'props' => $this->props,
            'url' => $this->url,
            'version' => $this->version,
            ...$this->metadata,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $page = $this->toArray();
        // An empty PHP array would be encoded as a JSON list; the client expects an object.
        $page['props'] = $this->props === [] ? new stdClass() : $this->props;

        return $page;
    }
}
