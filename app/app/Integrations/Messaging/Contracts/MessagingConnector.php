<?php

namespace CMBcoreSeller\Integrations\Messaging\Contracts;

use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\OutboundWindowPolicyDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\Page;
use CMBcoreSeller\Integrations\Messaging\DTO\SendResultDTO;
use CMBcoreSeller\Integrations\Messaging\Exceptions\UnsupportedOperation;
use Symfony\Component\HttpFoundation\Request;

/**
 * Contract mọi messaging integration phải implement.
 *
 * GOLDEN RULE (xem docs/01-architecture/extensibility-rules.md + ADR-0017):
 * core của module `Messaging` không bao giờ biết tên một provider cụ thể.
 * Thêm provider mới = 1 class implement interface này + 1 dòng register
 * vào `MessagingRegistry` + 1 dòng config + 1 doc channel. KHÔNG
 * `if ($provider === 'facebook_page')` ở core — khác biệt nằm trong
 * capability map / DTO field nullable / connector-internal mapping.
 *
 * Connector không hỗ trợ 1 operation phải ném {@see UnsupportedOperation};
 * caller kiểm `capabilities()`/`supports()` trước. Capabilities chuẩn:
 *   - 'inbound.webhook'      — sàn có webhook push
 *   - 'inbound.polling'      — sàn có API list để polling backup
 *   - 'outbound.text'        — gửi text
 *   - 'outbound.image'       — gửi ảnh
 *   - 'outbound.video'       — gửi video
 *   - 'outbound.file'        — gửi file
 *   - 'outbound.template'    — gửi template tag (vd Facebook MESSAGE_TAG)
 *   - 'read_receipt'         — mark read API
 *   - 'typing'               — typing indicator
 */
interface MessagingConnector
{
    // --- Identity ---------------------------------------------------------

    /** Stable provider code, e.g. 'facebook_page' | 'tiktok_chat' | 'shopee_chat' | 'lazada_chat' | 'manual'. */
    public function code(): string;

    /** Human-readable name, e.g. 'Facebook Page'. */
    public function displayName(): string;

    /**
     * Capability flags. Core check supports() trước khi gọi optional methods.
     *
     * @return array<string, bool>
     */
    public function capabilities(): array;

    public function supports(string $capability): bool;

    // --- OAuth / connection ----------------------------------------------

    /**
     * Build URL OAuth để user redirect tới. Provider không cần OAuth (vd `manual`)
     * ⇒ ném {@see UnsupportedOperation}.
     *
     * @param  array<string, mixed>  $opts
     */
    public function buildAuthorizationUrl(string $state, array $opts = []): string;

    public function exchangeCodeForToken(string $code): TokenDTO;

    /**
     * Refresh access token. Provider không hỗ trợ (vd Facebook page access token
     * dài hạn không refresh — re-OAuth) ⇒ ném {@see UnsupportedOperation}.
     */
    public function refreshToken(string $refreshToken): TokenDTO;

    /**
     * Subscribe webhook cho shop này. Provider không có API subscribe per-shop
     * (vd Lazada) ⇒ no-op (đăng ký 1 lần ở console).
     */
    public function registerWebhooks(MessagingAuthContext $auth): void;

    // --- Inbound (webhook + polling) -------------------------------------

    /** Verify chữ ký webhook (HMAC SHA256 / SHA512 / hub.verify tuỳ provider). Sai ⇒ false. */
    public function verifyWebhookSignature(Request $request): bool;

    /**
     * Parse 1 webhook request thành event chuẩn. Provider không xác định được
     * type ⇒ trả `type=TYPE_UNKNOWN` (kèm raw để debug, KHÔNG ném — vì
     * `webhook_events` đã ghi rồi).
     */
    public function parseWebhook(Request $request): MessagingWebhookEventDTO;

    /**
     * Polling backup: list conversations đã có activity gần đây.
     *
     * @param  array{since?:\Carbon\CarbonImmutable,cursor?:string,pageSize?:int}  $query
     * @return Page<\CMBcoreSeller\Integrations\Messaging\DTO\ConversationDTO>
     */
    public function fetchConversations(MessagingAuthContext $auth, array $query = []): Page;

    /**
     * Polling backup: list messages của 1 conversation.
     *
     * @param  array{since?:\Carbon\CarbonImmutable,cursor?:string,pageSize?:int}  $query
     * @return Page<\CMBcoreSeller\Integrations\Messaging\DTO\MessageDTO>
     */
    public function fetchMessages(MessagingAuthContext $auth, string $externalConversationId, array $query = []): Page;

    // --- Outbound --------------------------------------------------------

    /**
     * Gửi text. `$opts` cho phép truyền `message_tag` (Facebook), `quick_reply` …
     *
     * @param  array<string, mixed>  $opts
     */
    public function sendText(MessagingAuthContext $auth, string $externalConversationId, string $body, array $opts = []): SendResultDTO;

    /**
     * Gửi media (image/video/file). Connector tự biết cách feed vào sàn —
     * upload trước rồi gửi media_id, hay đính URL public. Core chỉ gọi.
     *
     * @param  array<string, mixed>  $opts
     */
    public function sendMedia(MessagingAuthContext $auth, string $externalConversationId, MediaRefDTO $media, array $opts = []): SendResultDTO;

    /**
     * Gửi template (Facebook MESSAGE_TAG, structured template …). `$templateKey`
     * = mã template trong app (`message_templates.code`); core đã resolve body
     * + vars trước, connector chỉ care về wire format.
     *
     * @param  array<string, mixed>  $vars  vars đã resolve sẵn (vd `customer_name`)
     * @param  array<string, mixed>  $opts
     */
    public function sendTemplate(MessagingAuthContext $auth, string $externalConversationId, string $templateKey, array $vars = [], array $opts = []): SendResultDTO;

    // --- Policy ---------------------------------------------------------

    /** Outbound window rule — `OutboundWindowGuard` đọc trước khi cho phép gửi. */
    public function outboundWindow(): OutboundWindowPolicyDTO;
}
