<?php

namespace CMBcoreSeller\Integrations\Channels\DTO;

/**
 * Cursor-paginated result page from a connector list call.
 *
 * @template T
 */
final readonly class Page
{
    /**
     * @param  list<T>  $items
     */
    public function __construct(
        public array $items,
        public ?string $nextCursor = null,
        public bool $hasMore = false,
    ) {}
}
