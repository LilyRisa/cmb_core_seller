<?php

namespace CMBcoreSeller\Integrations\Messaging\Contracts;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\ConversationDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDTO;
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
 *   - 'outbound.interactive' — gửi tin có nút bấm / carousel (button/generic template)
 *   - 'inbound.postback'     — nhận sự kiện bấm nút (postback)
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

    /**
     * Lấy thông tin page/shop (tên, avatar). Dùng trong backfill.
     * Connector không hỗ trợ ⇒ trả `['name' => null, 'avatar_url' => null]`.
     *
     * @return array{name: ?string, avatar_url: ?string}
     */
    public function fetchPageProfile(MessagingAuthContext $auth): array;

    /**
     * Lấy thông tin người dùng (buyer) theo external id (vd PSID Facebook).
     * Connector không hỗ trợ ⇒ trả `['name' => null, 'avatar_url' => null]`.
     *
     * @return array{name: ?string, avatar_url: ?string}
     */
    public function fetchUserProfile(MessagingAuthContext $auth, string $externalUserId): array;

    // --- Inbound (webhook + polling) -------------------------------------

    /** Verify chữ ký webhook (HMAC SHA256 / SHA512 / hub.verify tuỳ provider). Sai ⇒ false. */
    public function verifyWebhookSignature(Request $request): bool;

    /**
     * Parse 1 webhook request thành event chuẩn (event ĐẦU TIÊN). Provider không
     * xác định được type ⇒ trả `type=TYPE_UNKNOWN` (kèm raw để debug, KHÔNG ném —
     * vì `webhook_events` đã ghi rồi). Dùng cho contract test + làm block cho
     * {@see parseWebhookEvents}.
     */
    public function parseWebhook(Request $request): MessagingWebhookEventDTO;

    /**
     * Parse TẤT CẢ event trong 1 webhook request. Một HTTP POST có thể chứa NHIỀU
     * tin nhắn (vd Facebook `entry[].messaging[]`, mỗi entry nhiều messaging) —
     * core ingest TỪNG event riêng (dedupe theo external_message_id) nên không
     * mất tin khi sàn gộp batch.
     *
     * Connector single-event chỉ cần `return [$this->parseWebhook($request)];`.
     *
     * @return list<MessagingWebhookEventDTO>
     */
    public function parseWebhookEvents(Request $request): array;

    /**
     * Polling backup: list conversations đã có activity gần đây.
     *
     * @param  array{since?:CarbonImmutable,cursor?:string,pageSize?:int}  $query
     * @return Page<ConversationDTO>
     */
    public function fetchConversations(MessagingAuthContext $auth, array $query = []): Page;

    /**
     * Polling backup: list messages của 1 conversation.
     *
     * @param  array{since?:CarbonImmutable,cursor?:string,pageSize?:int}  $query
     * @return Page<MessageDTO>
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

    // Gửi tin tương tác (nút bấm / carousel) KHÔNG ở interface chung — đây là năng
    // lực riêng (v1 chỉ Facebook). Connector hỗ trợ implement {@see InteractiveMessagingConnector};
    // core kiểm `supports('outbound.interactive')` + `instanceof InteractiveMessagingConnector`
    // (tên năng lực, KHÔNG phải tên sàn) trước khi gọi. Sàn khác chừa sẵn (capability=false).

    // --- Policy ---------------------------------------------------------

    /** Outbound window rule — `OutboundWindowGuard` đọc trước khi cho phép gửi. */
    public function outboundWindow(): OutboundWindowPolicyDTO;

    // --- Comment moderation (Facebook) -----------------------------------

    /**
     * Ẩn hoặc hiện 1 comment trên bài viết Facebook.
     * Connector không hỗ trợ ⇒ ném {@see UnsupportedOperation}.
     */
    public function hideComment(MessagingAuthContext $auth, string $commentId, bool $hidden): void;

    /**
     * Xoá vĩnh viễn 1 comment trên bài viết Facebook.
     * Connector không hỗ trợ ⇒ ném {@see UnsupportedOperation}.
     */
    public function deleteComment(MessagingAuthContext $auth, string $commentId): void;

    /**
     * Trả lời công khai 1 comment (tạo sub-comment). Trả về comment id mới.
     * `$attachments` (tùy chọn) đính media — connector hỗ trợ `comment.media` mới
     * dùng (mỗi item là {@see MediaRefDTO} có `externalUrl` signed). Connector
     * không hỗ trợ ⇒ ném {@see UnsupportedOperation}.
     *
     * @param  list<MediaRefDTO>  $attachments
     */
    public function replyToComment(MessagingAuthContext $auth, string $commentId, string $message, array $attachments = []): string;

    /**
     * Nhắn tin riêng tư cho người bình luận (Facebook Private Reply).
     * `$attachments` như {@see replyToComment}. Connector không hỗ trợ ⇒ ném
     * {@see UnsupportedOperation}.
     *
     * @param  list<MediaRefDTO>  $attachments
     */
    public function privateReplyToComment(MessagingAuthContext $auth, string $commentId, string $message, array $attachments = []): void;
}
