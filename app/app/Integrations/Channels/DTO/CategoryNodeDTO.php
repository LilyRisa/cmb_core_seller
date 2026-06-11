<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\DTO;

/**
 * A single node in a marketplace category tree.
 */
final readonly class CategoryNodeDTO
{
    /**
     * @param  array<string,mixed>  $raw
     */
    public function __construct(
        public string $id,
        public ?string $parentId,
        public string $name,
        public bool $isLeaf,
        public array $raw = [],
    ) {}
}
