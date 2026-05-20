<?php

namespace CMBcoreSeller\Integrations\Messaging\DTO;

use Carbon\CarbonImmutable;

/**
 * Standard inbound message shape. Outbound messages produced by Messaging
 * core when sending — connectors return SendResultDTO with new
 * externalMessageId after send().
 *
 * Idempotency: `(externalConversationId, externalMessageId)` UNIQUE — SPEC-0024 §4.1.
 */
final readonly class MessageDTO
{
    public function __construct(
        public string $externalConversationId,
        public string $externalMessageId,
        public string $buyerExternalId,
        public MessageDirection $direction,
        public MessageKind $kind,
        public ?string $body = null,
        /** @var list<MediaRefDTO> */
        public array $attachments = [],
        public ?CarbonImmutable $sentAt = null,
        public ?CarbonImmutable $deliveredAt = null,
        public ?CarbonImmutable $readAt = null,
        public ?string $replyToExternalMessageId = null,
        /** @var array<string, mixed> */
        public array $raw = [],
    ) {}
}
