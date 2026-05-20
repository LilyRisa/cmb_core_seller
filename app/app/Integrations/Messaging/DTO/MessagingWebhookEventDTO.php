<?php

namespace CMBcoreSeller\Integrations\Messaging\DTO;

use Carbon\CarbonImmutable;

/**
 * Normalized messaging webhook event — `MessagingWebhookController` lưu vào
 * `webhook_events` row (provider=`messaging.<code>`) trước khi dispatch
 * `ProcessMessagingWebhook` để xử lý async.
 *
 * Dedupe key: `(provider, type, externalMessageId|externalShopId+externalConversationId)`.
 */
final readonly class MessagingWebhookEventDTO
{
    public const TYPE_MESSAGE_RECEIVED = 'message_received';
    public const TYPE_MESSAGE_DELIVERED = 'message_delivered';
    public const TYPE_MESSAGE_READ = 'message_read';
    public const TYPE_CONVERSATION_OPENED = 'conversation_opened';
    public const TYPE_CONVERSATION_CLOSED = 'conversation_closed';
    public const TYPE_TYPING = 'typing';
    public const TYPE_UNKNOWN = 'unknown';

    public function __construct(
        public string $provider,
        public string $type,
        public ?string $externalShopId = null,
        public ?string $externalConversationId = null,
        public ?string $externalMessageId = null,
        public ?string $buyerExternalId = null,
        public ?CarbonImmutable $occurredAt = null,
        /** @var array<string, mixed> */
        public array $raw = [],
    ) {}
}
