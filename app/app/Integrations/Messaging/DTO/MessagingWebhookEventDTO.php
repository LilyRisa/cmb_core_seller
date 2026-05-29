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

    public const TYPE_MESSAGE_REACTION = 'message_reaction';

    public const TYPE_POSTBACK = 'postback';

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
        // Normalized content fields — set by connector parsers (Phase B).
        // When set, MessagingWebhookIngestService stores them as _kind/_body/_attachments
        // so ProcessMessagingWebhook can rebuild them without re-parsing the raw payload.
        public ?MessageKind $kind = null,
        public ?string $body = null,
        /** @var list<MediaRefDTO> */
        public array $attachments = [],
        // Thread context — set by connectors that produce non-DM threads (e.g. Facebook feed
        // comment events). MessagingWebhookIngestService persists these as _thread_type /
        // _thread_meta so ProcessMessagingWebhook can upsert the correct conversation type.
        public ?string $threadType = null,
        /** @var array<string, mixed> */
        public array $threadMeta = [],
        // Direction — mặc định inbound (buyer→shop). Connector set Outbound cho echo
        // (tin page tự gửi qua công cụ Facebook) để ingest đúng chiều.
        public MessageDirection $direction = MessageDirection::Inbound,
        // Structured meta — vd `['buttons' => [...]]` cho template/quick-reply có nút bấm.
        // MessagingWebhookIngestService lưu thành `_meta`; ProcessMessagingWebhook rebuild.
        /** @var array<string, mixed> */
        public array $meta = [],
    ) {}
}
