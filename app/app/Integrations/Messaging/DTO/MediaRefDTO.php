<?php

namespace CMBcoreSeller\Integrations\Messaging\DTO;

/**
 * Reference đến 1 media — có thể là URL gốc từ sàn (inbound, TTL ngắn — phải
 * relay vào MinIO qua `DownloadInboundMedia`) hoặc local storage_path (outbound,
 * shop upload). Connector tự biết cách feed vào API sàn:
 *   - Facebook chấp nhận URL public.
 *   - TikTok/Shopee có thể yêu cầu upload trước rồi gửi media_id.
 *
 * ADR-0020 §"media".
 */
final readonly class MediaRefDTO
{
    public function __construct(
        public MessageKind $kind,           // image | video | file
        public string $mime,
        public ?int $sizeBytes = null,
        public ?string $externalUrl = null, // URL gốc từ sàn (inbound)
        public ?string $storagePath = null, // MinIO path (outbound đã upload)
        public ?string $filename = null,
        public ?int $width = null,
        public ?int $height = null,
        public ?int $durationMs = null,
    ) {}
}
