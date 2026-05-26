<?php

namespace CMBcoreSeller\Integrations\Messaging\Shopee;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeClient;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeWebhookVerifier;
use CMBcoreSeller\Integrations\Messaging\Contracts\MessagingConnector;
use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\OutboundWindowPolicyDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\Page;
use CMBcoreSeller\Integrations\Messaging\DTO\SendResultDTO;
use CMBcoreSeller\Integrations\Messaging\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Modules\Channels\Http\Controllers\ShopeeWebhookController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Shopee Seller Chat connector — SPEC-0024 / ADR-0017, ADR-0019.
 *
 * Inbound: Shopee 1 push URL/app ⇒ tin chat (push code 10 "Webchat") về
 * /webhook/shopee; {@see ShopeeWebhookController}
 * demux code 10 vào pipeline messaging. Chữ ký push tái dùng
 * {@see ShopeeWebhookVerifier} (HMAC-SHA256(push_key, push_url|raw_body)).
 *
 * Outbound: send_message ký bằng ShopeeSigner qua {@see ShopeeClient::shopPost}
 * (lo ký + throttle + envelope `error`). OAuth/token dùng chung Channels Shopee
 * ⇒ buildAuthorizationUrl/exchange/refresh ném UnsupportedOperation.
 *
 * MỨC ĐỘ XÁC MINH: verify + parse + send shape test bằng Http::fake. Tên field
 * payload code-10 + schema send_message theo tài liệu/SDK Shopee — PHẢI verify
 * sandbox thật trước production (như LazadaChatConnector).
 */
class ShopeeChatConnector implements MessagingConnector
{
    /** @param array<string,mixed> $config config('integrations.shopee') */
    public function __construct(
        private array $config,
        private ShopeeWebhookVerifier $verifier,
        private ShopeeClient $client,
    ) {}

    public function code(): string
    {
        return 'shopee_chat';
    }

    public function displayName(): string
    {
        return 'Shopee Chat';
    }

    public function capabilities(): array
    {
        return [
            'inbound.webhook' => true,
            'inbound.polling' => false,
            'outbound.text' => true,
            'outbound.image' => true,
            'outbound.video' => false,
            'outbound.file' => false,
            'outbound.template' => false,
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
        throw UnsupportedOperation::for($this->code(), 'buildAuthorizationUrl (dùng chung OAuth Shopee orders — ADR-0019)');
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
        // Shopee push cấu hình ở Console → Push Mechanism (không subscribe per-shop qua API).
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        return $this->verifier->verify($request);
    }

    public function parseWebhook(Request $request): MessagingWebhookEventDTO
    {
        return $this->parseWebhookEvents($request)[0]
            ?? new MessagingWebhookEventDTO($this->code(), MessagingWebhookEventDTO::TYPE_UNKNOWN);
    }

    public function parseWebhookEvents(Request $request): array
    {
        $body = json_decode($request->getContent() ?: '{}', true) ?: [];
        $code = (int) ($body['code'] ?? -1);
        $shopId = isset($body['shop_id']) ? (string) $body['shop_id'] : null;

        $chatCodes = array_map('intval', (array) ($this->config['chat_push_codes'] ?? [10]));
        if (! in_array($code, $chatCodes, true)) {
            return [new MessagingWebhookEventDTO($this->code(), MessagingWebhookEventDTO::TYPE_UNKNOWN, $shopId, raw: $body)];
        }

        $data = $body['data'] ?? [];
        if (is_string($data)) {
            $data = json_decode($data, true) ?: [];
        }
        // Shopee webchat: chi tiết tin nằm ở data.content (fallback data nếu sàn phẳng).
        $content = (array) ($data['content'] ?? $data);

        $conversationId = isset($content['conversation_id']) ? (string) $content['conversation_id'] : null;
        $messageId = isset($content['message_id']) ? (string) $content['message_id'] : null;
        $fromId = isset($content['from_id']) ? (string) $content['from_id'] : null;
        $hasMessage = $conversationId !== null && $messageId !== null;

        $occurredAt = isset($content['created_timestamp'])
            ? CarbonImmutable::createFromTimestamp((int) $content['created_timestamp'])
            : (isset($body['timestamp']) ? CarbonImmutable::createFromTimestamp((int) $body['timestamp']) : null);

        // Parse message kind/body/attachments from data.content.message_type + data.content.content.
        // Per Shopee webchat_push docs (push-025-webchat-push.md):
        //   text  → content.text (string)
        //   image → content.url, content.thumb_width, content.thumb_height
        //   video → content.video_url, content.duration_seconds, content.thumb_width, content.thumb_height
        //   item  → content.item_id
        //   other → fallback text label
        $kind = MessageKind::Text;
        $parsedBody = null;
        $attachments = [];

        if ($hasMessage) {
            $messageType = (string) ($content['message_type'] ?? 'text');
            $msgContent = (array) ($content['content'] ?? []);

            switch ($messageType) {
                case 'text':
                case 'faq_liveagent':
                    $kind = MessageKind::Text;
                    $parsedBody = isset($msgContent['text']) ? (string) $msgContent['text'] : null;
                    break;

                case 'image':
                    $kind = MessageKind::Image;
                    $attachments[] = new MediaRefDTO(
                        kind: MessageKind::Image,
                        mime: 'image/jpeg',
                        externalUrl: isset($msgContent['url']) ? (string) $msgContent['url'] : null,
                        width: isset($msgContent['thumb_width']) ? (int) $msgContent['thumb_width'] : null,
                        height: isset($msgContent['thumb_height']) ? (int) $msgContent['thumb_height'] : null,
                    );
                    break;

                case 'video':
                    $kind = MessageKind::Video;
                    $attachments[] = new MediaRefDTO(
                        kind: MessageKind::Video,
                        mime: 'video/mp4',
                        externalUrl: isset($msgContent['video_url']) ? (string) $msgContent['video_url'] : null,
                        durationMs: isset($msgContent['duration_seconds'])
                            ? (int) ((float) $msgContent['duration_seconds'] * 1000)
                            : null,
                        width: isset($msgContent['thumb_width']) ? (int) $msgContent['thumb_width'] : null,
                        height: isset($msgContent['thumb_height']) ? (int) $msgContent['thumb_height'] : null,
                    );
                    break;

                case 'item':
                    $kind = MessageKind::Text;
                    $parsedBody = '[Sản phẩm] item_id='.($msgContent['item_id'] ?? '');
                    break;

                default:
                    $kind = MessageKind::Text;
                    $parsedBody = '['.$messageType.']';
                    break;
            }
        }

        return [new MessagingWebhookEventDTO(
            provider: $this->code(),
            type: $hasMessage ? MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED : MessagingWebhookEventDTO::TYPE_UNKNOWN,
            externalShopId: $shopId,
            externalConversationId: $conversationId,
            externalMessageId: $messageId,
            buyerExternalId: $fromId,
            occurredAt: $occurredAt,
            raw: $body,
            kind: $hasMessage ? $kind : null,
            body: $hasMessage ? $parsedBody : null,
            attachments: $hasMessage ? $attachments : [],
        )];
    }

    public function fetchPageProfile(MessagingAuthContext $auth): array
    {
        return ['name' => null, 'avatar_url' => null];
    }

    public function fetchUserProfile(MessagingAuthContext $auth, string $externalUserId): array
    {
        return ['name' => null, 'avatar_url' => null];
    }

    public function fetchConversations(MessagingAuthContext $auth, array $query = []): Page
    {
        throw UnsupportedOperation::for($this->code(), 'fetchConversations (Shopee dựa webhook; polling follow-up)');
    }

    public function fetchMessages(MessagingAuthContext $auth, string $externalConversationId, array $query = []): Page
    {
        throw UnsupportedOperation::for($this->code(), 'fetchMessages');
    }

    public function sendText(MessagingAuthContext $auth, string $externalConversationId, string $body, array $opts = []): SendResultDTO
    {
        return $this->send($auth, $externalConversationId, 'text', ['text' => $body]);
    }

    public function sendMedia(MessagingAuthContext $auth, string $externalConversationId, MediaRefDTO $media, array $opts = []): SendResultDTO
    {
        if ($media->kind->value !== 'image') {
            throw UnsupportedOperation::for($this->code(), 'sendMedia ('.$media->kind->value.') — Shopee chat bản đầu chỉ hỗ trợ ảnh');
        }
        $url = $media->externalUrl;
        if (! $url) {
            throw new \RuntimeException('Shopee sendMedia cần externalUrl (signed) — storage_path không gửi trực tiếp được.');
        }

        // NOTE (Phase D): Shopee sendMedia dùng community/SDK endpoint image_url trực tiếp (không
        // có upload-first API riêng trong tài liệu chính thức hiện tại). PHẢI verify sandbox thật.
        return $this->send($auth, $externalConversationId, 'image', ['image_url' => $url]);
    }

    public function sendTemplate(MessagingAuthContext $auth, string $externalConversationId, string $templateKey, array $vars = [], array $opts = []): SendResultDTO
    {
        throw UnsupportedOperation::for($this->code(), 'sendTemplate');
    }

    public function outboundWindow(): OutboundWindowPolicyDTO
    {
        // Shopee không có hard-window 24h như Facebook (rate-limit per shop ở MessageSendService).
        return new OutboundWindowPolicyDTO(freeWindowHours: null, requiresTag: false);
    }

    public function hideComment(MessagingAuthContext $auth, string $commentId, bool $hidden): void
    {
        throw UnsupportedOperation::for($this->code(), 'hideComment');
    }

    public function deleteComment(MessagingAuthContext $auth, string $commentId): void
    {
        throw UnsupportedOperation::for($this->code(), 'deleteComment');
    }

    public function replyToComment(MessagingAuthContext $auth, string $commentId, string $message, array $attachments = []): string
    {
        throw UnsupportedOperation::for($this->code(), 'replyToComment');
    }

    public function privateReplyToComment(MessagingAuthContext $auth, string $commentId, string $message, array $attachments = []): void
    {
        throw UnsupportedOperation::for($this->code(), 'privateReplyToComment');
    }

    /**
     * Gửi 1 tin qua Shopee Seller Chat send_message. `to_id` = id buyer
     * (external_conversation_id). Ký + envelope lỗi do ShopeeClient lo.
     *
     * @param  array<string,scalar>  $content
     */
    private function send(MessagingAuthContext $auth, string $toId, string $messageType, array $content): SendResultDTO
    {
        $path = (string) (($this->config['endpoints'] ?? [])['send_message'] ?? '/api/v2/sellerchat/send_message');

        $resp = $this->client->shopPost($this->authContext($auth), $path, [], [
            'to_id' => $toId,
            'message_type' => $messageType,
            'content' => $content,
        ]);

        $messageId = (string) ($resp['message_id'] ?? '');

        return new SendResultDTO(
            externalMessageId: $messageId,
            sentAt: CarbonImmutable::now(),
            raw: $resp,
        );
    }

    /** MessagingAuthContext → Channels AuthContext (provider 'shopee' cho ký shop). */
    private function authContext(MessagingAuthContext $auth): AuthContext
    {
        return new AuthContext(
            channelAccountId: $auth->channelAccountId,
            provider: 'shopee',
            externalShopId: $auth->externalShopId,
            accessToken: $auth->accessToken,
            region: $auth->region,
            extra: $auth->extra,
        );
    }
}
