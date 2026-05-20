<?php

namespace CMBcoreSeller\Integrations\Messaging\DTO;

/**
 * Cursor-paginated result page for messaging list calls (conversations / messages).
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
