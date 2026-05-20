<?php

namespace CMBcoreSeller\Integrations\Messaging\DTO;

use Carbon\CarbonImmutable;

/**
 * Standard conversation shape — what every connector must produce.
 * Provider-specific fields go in $raw (kept for debug, purged from
 * `messages.raw_payload` after 30d per ADR-0020).
 */
final readonly class ConversationDTO
{
    public function __construct(
        public string $externalConversationId,
        public string $buyerExternalId,
        public ?string $buyerName = null,
        public ?string $buyerAvatarUrl = null,
        public ?CarbonImmutable $lastMessageAt = null,
        public ?string $lastMessagePreview = null,
        public ?int $unreadCount = null,
        /** @var array<string, mixed> */
        public array $raw = [],
    ) {}
}
