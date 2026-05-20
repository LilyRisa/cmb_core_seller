<?php

namespace CMBcoreSeller\Integrations\Messaging\Manual;

use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;
use CMBcoreSeller\Integrations\Messaging\Contracts\MessagingConnector;
use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\OutboundWindowPolicyDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\Page;
use CMBcoreSeller\Integrations\Messaging\DTO\SendResultDTO;
use CMBcoreSeller\Integrations\Messaging\Exceptions\UnsupportedOperation;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Request;

/**
 * "manual" pseudo-connector cho messaging — KHÔNG có sàn ngoài. Dùng để:
 *   1. Test pipeline E2E (webhook → ingest → conversation/message → outbound)
 *      mà chưa cần connector thật.
 *   2. Mở rộng tương lai cho "ghi chú nội bộ" trên 1 conversation (Phase sau).
 *
 * Webhook giả lập: POST /webhook/messaging/manual với JSON `{event_type,
 * external_shop_id, external_conversation_id, external_message_id?,
 * buyer_external_id, body?}`. Không có chữ ký — chỉ enabled khi
 * `APP_ENV != 'production'` (xem `verifyWebhookSignature`).
 *
 * Outbound: `sendText`/`sendMedia` ghi DTO trả về với `external_message_id`
 * random — KHÔNG gọi API ngoài, KHÔNG có sàn để nhận. Hữu ích cho test gửi tin
 * mà không gọi out-of-process.
 *
 * Mirror `Channels\Manual\ManualConnector` pattern.
 */
class ManualMessagingConnector implements MessagingConnector
{
    public function code(): string
    {
        return 'manual';
    }

    public function displayName(): string
    {
        return 'Nội bộ (test/manual)';
    }

    public function capabilities(): array
    {
        return [
            'inbound.webhook' => true,
            'inbound.polling' => false,
            'outbound.text' => true,
            'outbound.image' => true,
            'outbound.video' => true,
            'outbound.file' => true,
            'outbound.template' => true,
            'read_receipt' => false,
            'typing' => false,
        ];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    public function buildAuthorizationUrl(string $state, array $opts = []): string
    {
        throw UnsupportedOperation::for($this->code(), 'buildAuthorizationUrl');
    }

    public function exchangeCodeForToken(string $code): TokenDTO
    {
        throw UnsupportedOperation::for($this->code(), 'exchangeCodeForToken');
    }

    public function refreshToken(string $refreshToken): TokenDTO
    {
        throw UnsupportedOperation::for($this->code(), 'refreshToken');
    }

    public function registerWebhooks(MessagingAuthContext $auth): void
    {
        // no-op
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        // Chỉ cho phép trong môi trường non-production để test pipeline.
        // Production cần chữ ký rõ rằng — phải dùng connector thật.
        return app()->environment() !== 'production';
    }

    public function parseWebhook(Request $request): MessagingWebhookEventDTO
    {
        $payload = (array) $request->json()->all();

        return new MessagingWebhookEventDTO(
            provider: $this->code(),
            type: (string) ($payload['event_type'] ?? MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED),
            externalShopId: isset($payload['external_shop_id']) ? (string) $payload['external_shop_id'] : null,
            externalConversationId: isset($payload['external_conversation_id']) ? (string) $payload['external_conversation_id'] : null,
            externalMessageId: isset($payload['external_message_id']) ? (string) $payload['external_message_id'] : (string) Str::uuid(),
            buyerExternalId: isset($payload['buyer_external_id']) ? (string) $payload['buyer_external_id'] : null,
            raw: $payload,
        );
    }

    public function fetchConversations(MessagingAuthContext $auth, array $query = []): Page
    {
        return new Page(items: [], nextCursor: null, hasMore: false);
    }

    public function fetchMessages(MessagingAuthContext $auth, string $externalConversationId, array $query = []): Page
    {
        return new Page(items: [], nextCursor: null, hasMore: false);
    }

    public function sendText(MessagingAuthContext $auth, string $externalConversationId, string $body, array $opts = []): SendResultDTO
    {
        return new SendResultDTO(
            externalMessageId: 'manual_'.Str::uuid(),
            sentAt: \Carbon\CarbonImmutable::now(),
            raw: ['echo' => true, 'body' => $body, 'opts' => $opts],
        );
    }

    public function sendMedia(MessagingAuthContext $auth, string $externalConversationId, MediaRefDTO $media, array $opts = []): SendResultDTO
    {
        return new SendResultDTO(
            externalMessageId: 'manual_'.Str::uuid(),
            sentAt: \Carbon\CarbonImmutable::now(),
            raw: ['echo' => true, 'media_kind' => $media->kind->value],
        );
    }

    public function sendTemplate(MessagingAuthContext $auth, string $externalConversationId, string $templateKey, array $vars = [], array $opts = []): SendResultDTO
    {
        return new SendResultDTO(
            externalMessageId: 'manual_'.Str::uuid(),
            sentAt: \Carbon\CarbonImmutable::now(),
            raw: ['echo' => true, 'template_key' => $templateKey, 'vars' => $vars],
        );
    }

    public function outboundWindow(): OutboundWindowPolicyDTO
    {
        return new OutboundWindowPolicyDTO(
            freeWindowHours: null,
            requiresTag: false,
        );
    }
}
