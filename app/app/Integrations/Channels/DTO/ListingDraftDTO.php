<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\DTO;

/**
 * Normalized product listing draft passed to ChannelConnector::publishListing().
 */
final readonly class ListingDraftDTO
{
    /**
     * @param  array<string,mixed>  $attributes
     * @param  MediaRefDTO[]  $media
     * @param  array<mixed>  $skus
     * @param  array<string,mixed>  $logistics
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $categoryId,
        public ?string $brandId,
        public array $attributes,
        public array $media,
        public array $skus,
        public array $logistics,
        public ?string $shortDescription = null,
        /** Nguồn video (URL/đường dẫn) để upload lên sàn. */
        public ?string $videoRef = null,
        /** Id video ĐÃ sẵn sàng trên sàn (do job chuẩn bị trước qua start/status) — gắn thẳng vào payload. */
        public ?string $videoExternalId = null,
        /** Khóa idempotency ổn định theo nháp — chống tạo sản phẩm trùng khi retry (sàn dedupe). */
        public ?string $idempotencyKey = null,
    ) {}
}
