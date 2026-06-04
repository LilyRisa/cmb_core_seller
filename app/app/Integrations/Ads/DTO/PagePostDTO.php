<?php

namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class PagePostDTO
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public string $id,
        public ?string $message,
        public string $createdTime,
        public string $mediaType,
        public ?string $imageUrl,
        public ?string $videoId,
        public int $likes,
        public int $comments,
        public int $shares,
        public array $raw = [],
    ) {}
}
