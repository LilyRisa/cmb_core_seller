<?php

namespace CMBcoreSeller\Integrations\Messaging\DTO;

use Carbon\CarbonImmutable;

/**
 * Kết quả 1 lần gửi tin — trả về `external_message_id` để dedupe khi cùng
 * tin về qua webhook (echo-back) và `sent_at` từ sàn (chính xác hơn local now()).
 */
final readonly class SendResultDTO
{
    public function __construct(
        public string $externalMessageId,
        public ?CarbonImmutable $sentAt = null,
        /** @var array<string, mixed> */
        public array $raw = [],
    ) {}
}
