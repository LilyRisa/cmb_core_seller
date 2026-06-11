<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\DTO;

/**
 * Reference to a media asset used in a product listing.
 * kind: cdn_url | image_id | uri
 */
final readonly class MediaRefDTO
{
    /**
     * @param  array<string,mixed>  $raw
     */
    public function __construct(
        public string $ref,
        public string $kind,
        public array $raw = [],
    ) {}
}
