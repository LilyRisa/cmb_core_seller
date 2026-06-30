<?php

namespace CMBcoreSeller\Integrations\Messaging\Facebook;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;
use CMBcoreSeller\Integrations\Messaging\Contracts\CommentEngagementConnector;
use CMBcoreSeller\Integrations\Messaging\Contracts\InteractiveMessagingConnector;
use CMBcoreSeller\Integrations\Messaging\Contracts\ListsPostsConnector;
use CMBcoreSeller\Integrations\Messaging\Contracts\MessagingConnector;
use CMBcoreSeller\Integrations\Messaging\Contracts\UtilityTemplateConnector;
use CMBcoreSeller\Integrations\Messaging\DTO\ConversationDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDirection;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\OutboundWindowPolicyDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\Page;
use CMBcoreSeller\Integrations\Messaging\DTO\SendResultDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\UtilityTemplateDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\UtilityTemplateRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\UtilityTemplateStatusDTO;
use CMBcoreSeller\Integrations\Messaging\Exceptions\ConversationClosed;
use CMBcoreSeller\Integrations\Messaging\Exceptions\OutboundWindowClosed;
use CMBcoreSeller\Integrations\Messaging\Exceptions\UnsupportedOperation;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Request;

/**
 * Facebook Page Messenger connector (SPEC-0024 S2, ADR-0017/0019).
 *
 * Conversation = cặp (page, PSID buyer). `external_conversation_id` = PSID buyer
 * (Messenger Send API địa chỉ theo PSID). `external_shop_id` = page id (lưu ở
 * `channel_accounts.external_shop_id`, provider `facebook_page`).
 *
 * Webhook verify: HMAC-SHA256 `X-Hub-Signature-256` (app_secret) — xem
 * {@see FacebookSignatureVerifier}. Outbound: 24h window; quá hạn chỉ gửi được
 * với MESSAGE_TAG hợp lệ ({@see outboundWindow}).
 *
 * MỨC ĐỘ XÁC MINH: signature verify + parseWebhook + outboundWindow + SHAPE
 * request Send API test được (Http::fake). LIVE call cần Page access token thật
 * + app review (pages_messaging) — credentials lưu `channel_accounts` per page.
 *
 * Token: Page access token dài hạn ⇒ `refreshToken` ném UnsupportedOperation
 * (re-OAuth khi hết hạn). Polling: Messenger dựa webhook ⇒ `inbound.polling`=false.
 */
class FacebookPageConnector implements CommentEngagementConnector, InteractiveMessagingConnector, ListsPostsConnector, MessagingConnector, UtilityTemplateConnector
{
    /** @param array{verify_token?:?string, app_id?:?string, app_secret?:?string, graph_version?:string} $config */
    public function __construct(
        private array $config,
        private FacebookSignatureVerifier $verifier,
    ) {}

    public function code(): string
    {
        return 'facebook_page';
    }

    public function displayName(): string
    {
        return 'Facebook Page';
    }

    public function capabilities(): array
    {
        return [
            'inbound.webhook' => true,
            'inbound.polling' => false,
            'inbound.backfill' => true,
            'inbound.comments' => true,
            'outbound.text' => true,
            'outbound.image' => true,
            'outbound.video' => true,
            'outbound.audio' => true,
            'outbound.file' => true,
            'outbound.template' => true,   // qua MESSAGE_TAG
            'outbound.interactive' => true, // button/generic template (nút bấm / carousel)
            'outbound.utility_template' => true, // gửi utility message qua template đã Meta duyệt (ngoài 24h)
            'inbound.postback' => true,    // nhận sự kiện bấm nút (messaging_postbacks)
            'read_receipt' => true,        // sender_action=mark_seen
            'typing' => true,              // sender_action=typing_on
            'comment.list' => true,        // đọc comment bài viết (backfill)
            'post.list' => true,           // liệt kê bài đăng (post picker cho comment_on_post)
            'comment.reply_public' => true, // trả lời công khai dưới comment
            'comment.reply_private' => true, // Private Reply (nhắn riêng cho người comment)
            'comment.like' => true,        // Page thích / bỏ thích comment
            'comment.media' => true,       // đính ảnh vào reply công khai / nhắn riêng
            'comment.webhook' => true,     // nhận comment qua webhook feed
        ];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    // --- OAuth ------------------------------------------------------------

    public function buildAuthorizationUrl(string $state, array $opts = []): string
    {
        $appId = (string) ($this->config['app_id'] ?? '');
        // `business_management` để liệt kê page thuộc Business Manager (business asset):
        // `/me/accounts` chỉ trả page user là admin classic; page giao qua Business Manager
        // phải duyệt `/me/businesses` → owned_pages/client_pages (xem FacebookOAuthController::fetchPages).
        // `pages_utility_messaging`: gửi utility message qua template đã duyệt (thay
        // message tag đã bị Meta khai tử) — cần App Review để dùng ngoài test user.
        $scope = 'pages_messaging,pages_utility_messaging,pages_manage_metadata,pages_read_engagement,pages_show_list,pages_read_user_content,pages_manage_engagement,business_management';

        return 'https://www.facebook.com/'.$this->graphVersion().'/dialog/oauth?'.http_build_query([
            'client_id' => $appId,
            // Dùng redirect_uri canonical (config/APP_URL) — KHÔNG lấy từ caller — để
            // KHỚP TUYỆT ĐỐI với bước exchangeCodeForToken (Meta bắt buộc giống nhau).
            'redirect_uri' => $opts['redirect_uri'] ?? $this->redirectUri(),
            'state' => $state,
            'scope' => $scope,
            'response_type' => 'code',
        ]);
    }

    /** Redirect URI OAuth canonical — phải đăng ký y hệt trong Meta App. */
    private function redirectUri(): string
    {
        $configured = (string) ($this->config['redirect_uri'] ?? '');
        if ($configured !== '') {
            return $configured;
        }

        return rtrim((string) config('app.url'), '/').'/oauth/facebook_page/callback';
    }

    public function exchangeCodeForToken(string $code): TokenDTO
    {
        $res = Http::get('https://graph.facebook.com/'.$this->graphVersion().'/oauth/access_token', [
            'client_id' => $this->config['app_id'] ?? '',
            'client_secret' => $this->config['app_secret'] ?? '',
            'redirect_uri' => $this->redirectUri(),   // PHẢI giống dialog login — nếu không Meta trả lỗi mismatch.
            'code' => $code,
        ]);

        if (! $res->successful()) {
            throw new \RuntimeException('Facebook token exchange failed: '.$res->body());
        }

        return new TokenDTO(
            accessToken: (string) $res->json('access_token'),
            expiresAt: $res->json('expires_in') ? CarbonImmutable::now()->addSeconds((int) $res->json('expires_in')) : null,
            raw: (array) $res->json(),
        );
    }

    public function refreshToken(string $refreshToken): TokenDTO
    {
        // Page access token dài hạn — không refresh; re-OAuth khi hết hạn.
        throw UnsupportedOperation::for($this->code(), 'refreshToken');
    }

    public function registerWebhooks(MessagingAuthContext $auth): void
    {
        // Subscribe page vào app. `message_echoes` để nhận tin page tự gửi (qua Page
        // Inbox / Meta Business Suite / trả lời tự động có nút bấm) → hiển thị trong hộp thư.
        Http::post($this->graphUrl($auth->externalShopId.'/subscribed_apps'), [
            'subscribed_fields' => 'messages,message_echoes,messaging_postbacks,messaging_referrals,message_deliveries,message_reads,feed,message_reactions',
            'access_token' => $auth->accessToken,
        ]);
    }

    /**
     * Lấy tên + avatar của page. URL avatar (picture.data.url) là CDN sẽ hết hạn —
     * caller relay vào object storage. Lỗi ⇒ trả null (best-effort).
     *
     * @return array{name: ?string, avatar_url: ?string}
     */
    public function fetchPageProfile(MessagingAuthContext $auth): array
    {
        $res = Http::timeout(20)->get($this->graphUrl($auth->externalShopId), [
            'fields' => 'name,picture{url}',
            'access_token' => $auth->accessToken,
        ]);

        if (! $res->successful()) {
            return ['name' => null, 'avatar_url' => null];
        }

        return [
            'name' => $res->json('name'),
            'avatar_url' => $res->json('picture.data.url'),
        ];
    }

    /**
     * Lấy tên + profile_pic của buyer theo PSID. profile_pic URL hết hạn ⇒ relay.
     * Cần app review "Business Asset User Profile Access" với page người khác (dev mode
     * chạy với tester). Lỗi ⇒ null (không chặn backfill).
     *
     * @return array{name: ?string, avatar_url: ?string}
     */
    public function fetchUserProfile(MessagingAuthContext $auth, string $psid): array
    {
        // Messenger User Profile API — field HỢP LỆ cho node PSID theo tài liệu chính thức
        // CHỈ gồm: first_name, last_name, profile_pic (+ locale/timezone/gender cần duyệt).
        // KHÔNG xin `name` — không phải field của node này ⇒ Graph trả lỗi (#100) khiến CẢ
        // request fail → mất luôn avatar. Tên ghép từ first+last (tên đầy đủ vẫn lấy từ
        // participants ở fetchConversations). profile_pic là URL CDN HẾT HẠN ⇒ relay.
        // `profile_pic` CHỈ trả về khi app có quyền (Advanced Access `pages_messaging` qua
        // App Review; Dev Mode chỉ trả cho người có vai trò trong app).
        // docs: developers.facebook.com/docs/messenger-platform/identity/user-profile
        $res = Http::timeout(20)->get($this->graphUrl($psid), [
            'fields' => 'first_name,last_name,profile_pic',
            'access_token' => $auth->accessToken,
        ]);

        if (! $res->successful()) {
            // Best-effort: PSID không lấy được profile (thường #100/#10/#200 — app chưa có
            // Advanced Access User Profile cho PSID này). Trả null, KHÔNG log (nguyên nhân đã biết).
            return ['name' => null, 'avatar_url' => null];
        }

        $name = $res->json('name');
        if (! $name) {
            $name = trim(((string) $res->json('first_name')).' '.((string) $res->json('last_name'))) ?: null;
        }

        $avatarUrl = $res->json('profile_pic');
        if (blank($avatarUrl)) {
            // Graph trả 200 nhưng KHÔNG có profile_pic ⇒ thiếu quyền: app chưa Live + chưa có
            // Advanced Access `pages_messaging` (App Review "Business Asset User Profile Access"),
            // HOẶC Dev Mode mà người nhắn KHÔNG phải vai trò app (Admin/Developer/Tester).
            // KHÔNG phải lỗi OAuth scope ⇒ hủy/cấp lại quyền page KHÔNG khắc phục được.
            Log::warning('facebook.fetchUserProfile: không có profile_pic (thiếu quyền User Profile — xem ghi chú)', [
                'page' => $auth->externalShopId,
                'psid' => $psid,
            ]);
        }

        return [
            'name' => $name,
            'avatar_url' => $avatarUrl,
        ];
    }

    // --- Inbound ----------------------------------------------------------

    public function verifyWebhookSignature(Request $request): bool
    {
        return $this->verifier->verify($request, $this->config['app_secret'] ?? null);
    }

    public function parseWebhook(Request $request): MessagingWebhookEventDTO
    {
        $events = $this->parseWebhookEvents($request);

        // Event đầu tiên (contract test / fallback). Rỗng ⇒ unknown.
        return $events[0] ?? new MessagingWebhookEventDTO($this->code(), MessagingWebhookEventDTO::TYPE_UNKNOWN);
    }

    public function parseWebhookEvents(Request $request): array
    {
        $payload = json_decode($request->getContent(), true) ?: [];

        // Messenger gộp nhiều page (entry) × nhiều messaging event / POST.
        // Map TỪNG event → core ingest riêng (không mất tin khi batch).
        $events = [];
        foreach ((array) ($payload['entry'] ?? []) as $entry) {
            $pageId = isset($entry['id']) ? (string) $entry['id'] : null;
            foreach ((array) ($entry['messaging'] ?? []) as $messaging) {
                $events[] = $this->mapEvent((array) $messaging, $pageId);
            }
            // Real-time feed comment events (field='feed', item='comment', verb='add'|'edited').
            // Each change becomes an inbound comment on the top-level comment thread.
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $dto = $this->mapFeedChange((array) $change, $pageId);
                if ($dto !== null) {
                    $events[] = $dto;
                }
            }
        }

        return $events;
    }

    /**
     * Map 1 feed-change entry → DTO cho comment inbound, hoặc null nếu bỏ qua.
     * Chỉ xử lý field=feed + item=comment + verb=add|edited.
     * Bỏ qua comment của page (from.id === page id) để không ingest reply của page.
     *
     * @param  array<string,mixed>  $change
     */
    private function mapFeedChange(array $change, ?string $pageId): ?MessagingWebhookEventDTO
    {
        if (($change['field'] ?? '') !== 'feed') {
            return null;
        }

        $value = (array) ($change['value'] ?? []);

        if (($value['item'] ?? '') !== 'comment') {
            return null;
        }

        if (! in_array($value['verb'] ?? '', ['add', 'edited'], true)) {
            return null;
        }

        $fromId = isset($value['from']['id']) ? (string) $value['from']['id'] : null;

        // Bỏ comment của chính page — reply của page không phải inbound customer message.
        if ($fromId !== null && $pageId !== null && $fromId === $pageId) {
            return null;
        }

        $commentId = isset($value['comment_id']) ? (string) $value['comment_id'] : null;
        if ($commentId === null || $commentId === '') {
            return null;
        }

        // Group replies under the top-level comment thread.
        // When parent_id is present this is a reply; use parent_id as the thread id.
        $topLevelCommentId = isset($value['parent_id']) && (string) $value['parent_id'] !== ''
            ? (string) $value['parent_id']
            : $commentId;

        $postId = isset($value['post_id']) ? (string) $value['post_id'] : null;
        $body = isset($value['message']) && (string) $value['message'] !== ''
            ? (string) $value['message']
            : null;
        $occurredAt = isset($value['created_time']) && is_numeric($value['created_time'])
            ? CarbonImmutable::createFromTimestamp((int) $value['created_time'])
            : null;

        return new MessagingWebhookEventDTO(
            provider: $this->code(),
            type: MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED,
            externalShopId: $pageId,
            externalConversationId: $topLevelCommentId,
            externalMessageId: $commentId,
            buyerExternalId: $fromId,
            occurredAt: $occurredAt,
            raw: $change,
            kind: MessageKind::Text,
            body: $body,
            attachments: [],
            threadType: 'comment',
            threadMeta: array_filter([
                'fb_post_id' => $postId,
                'fb_comment_id' => $topLevelCommentId,
                // Tên người comment/reply (nếu webhook kèm) — để gộp danh sách người tham gia.
                'commenter_name' => isset($value['from']['name']) && (string) $value['from']['name'] !== ''
                    ? (string) $value['from']['name']
                    : null,
            ], fn ($v) => $v !== null),
        );
    }

    /**
     * Map 1 Messenger messaging-event → DTO chuẩn. PSID người gửi = conversation
     * (Send API địa chỉ theo PSID). Echo do CHÍNH app gửi ⇒ bỏ (type unknown); echo
     * từ nguồn khác (Page Inbox / trả lời tự động có nút bấm) ⇒ nhận thành outbound.
     *
     * @param  array<string,mixed>  $event
     */
    private function mapEvent(array $event, ?string $pageId): MessagingWebhookEventDTO
    {
        $senderId = isset($event['sender']['id']) ? (string) $event['sender']['id'] : null;
        $occurredAt = isset($event['timestamp']) ? CarbonImmutable::createFromTimestampMs((int) $event['timestamp']) : null;

        if (isset($event['message'])) {
            $msg = (array) $event['message'];
            $isEcho = ! empty($msg['is_echo']);

            // Bỏ echo do CHÍNH app này gửi — đã được SendMessage ghi DB (tránh trùng).
            // Echo từ nguồn khác (page tự gửi qua Page Inbox / Meta Business Suite /
            // trả lời tự động CÓ NÚT BẤM của Facebook) ⇒ vẫn nhận để hiển thị.
            if ($isEcho) {
                $appId = isset($msg['app_id']) ? (string) $msg['app_id'] : null;
                $ourAppId = isset($this->config['app_id']) && (string) $this->config['app_id'] !== ''
                    ? (string) $this->config['app_id'] : null;
                if ($appId !== null && $ourAppId !== null && $appId === $ourAppId) {
                    return new MessagingWebhookEventDTO($this->code(), MessagingWebhookEventDTO::TYPE_UNKNOWN, $pageId, null, null, null, $occurredAt, $event);
                }
            }

            // Inbound: PSID người GỬI = conversation. Echo (page→buyer): PSID người NHẬN.
            $recipientId = isset($event['recipient']['id']) ? (string) $event['recipient']['id'] : null;
            $convKey = $isEcho ? $recipientId : $senderId;
            $direction = $isEcho ? MessageDirection::Outbound : MessageDirection::Inbound;

            $parsed = $this->parseMessageContent($msg);

            return new MessagingWebhookEventDTO(
                provider: $this->code(),
                type: MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED,
                externalShopId: $pageId,
                externalConversationId: $convKey,
                externalMessageId: (string) ($msg['mid'] ?? ''),
                buyerExternalId: $convKey,
                occurredAt: $occurredAt,
                raw: $event,
                kind: $parsed['kind'],
                body: $parsed['body'],
                attachments: $parsed['attachments'],
                direction: $direction,
                meta: array_merge(
                    $parsed['buttons'] !== [] ? ['buttons' => $parsed['buttons']] : [],
                    $this->adReferralMeta($event),
                ),
            );
        }
        if (isset($event['reaction'])) {
            $reaction = (array) $event['reaction'];
            $mid = isset($reaction['mid']) ? (string) $reaction['mid'] : '';

            return new MessagingWebhookEventDTO(
                provider: $this->code(),
                type: MessagingWebhookEventDTO::TYPE_MESSAGE_REACTION,
                externalShopId: $pageId,
                externalConversationId: $senderId,   // PSID of the reacting user = conversation key
                externalMessageId: $mid,             // TARGET message mid
                buyerExternalId: $senderId,
                occurredAt: $occurredAt,
                raw: $event,                         // raw['reaction']['emoji'] + raw['reaction']['action']
            );
        }
        if (isset($event['delivery'])) {
            return new MessagingWebhookEventDTO($this->code(), MessagingWebhookEventDTO::TYPE_MESSAGE_DELIVERED, $pageId, $senderId, null, $senderId, $occurredAt, $event);
        }
        if (isset($event['read'])) {
            return new MessagingWebhookEventDTO($this->code(), MessagingWebhookEventDTO::TYPE_MESSAGE_READ, $pageId, $senderId, null, $senderId, $occurredAt, $event);
        }
        if (isset($event['postback'])) {
            // Buyer bấm nút (button template / persistent menu / get_started). PSID người
            // gửi = conversation. `payload` do builder sinh (opaque); listener giải mã.
            $pb = (array) $event['postback'];

            return new MessagingWebhookEventDTO(
                provider: $this->code(),
                type: MessagingWebhookEventDTO::TYPE_POSTBACK,
                externalShopId: $pageId,
                externalConversationId: $senderId,
                externalMessageId: isset($pb['mid']) ? (string) $pb['mid'] : null,
                buyerExternalId: $senderId,
                occurredAt: $occurredAt,
                raw: $event,
                meta: array_merge(array_filter([
                    'postback_payload' => isset($pb['payload']) ? (string) $pb['payload'] : null,
                    'postback_title' => isset($pb['title']) ? (string) $pb['title'] : null,
                ], fn ($v) => $v !== null && $v !== ''), $this->adReferralMeta($event)),
            );
        }

        return new MessagingWebhookEventDTO($this->code(), MessagingWebhookEventDTO::TYPE_UNKNOWN, $pageId, $senderId, null, $senderId, $occurredAt, $event);
    }

    /**
     * Trích NGỮ CẢNH QUẢNG CÁO (Click-to-Messenger / m.me) từ event Messenger để gắn vào meta tin nhắn,
     * dùng làm dữ liệu cho AI prompt + rẽ nhánh flow theo bài. Theo tài liệu chính chủ, khi khách nhắn từ
     * quảng cáo CTM, event mang `referral` (kèm `message` cho thread mới, hoặc trong `postback`, hoặc event
     * `messaging_referrals` cho thread cũ) với `source=ADS`, `ad_id` + `ads_context_data{ad_title,photo_url,
     * video_url,post_id,product_id}`. Cần subscribe field `messaging_referrals` (đã thêm). m.me link cho
     * `source=SHORTLINK` (không có ads_context_data). Rỗng ⇒ [] (không phải từ quảng cáo).
     *
     * @param  array<string,mixed>  $event
     * @return array<string,mixed> ['ad_referral' => [...]] hoặc []
     */
    private function adReferralMeta(array $event): array
    {
        $ref = $event['referral'] ?? ($event['postback']['referral'] ?? null);
        if (! is_array($ref) || empty($ref['source'])) {
            return [];
        }
        $ctx = is_array($ref['ads_context_data'] ?? null) ? $ref['ads_context_data'] : [];
        $out = array_filter([
            'source' => (string) $ref['source'],
            'ref' => isset($ref['ref']) ? (string) $ref['ref'] : null,
            'ad_id' => isset($ref['ad_id']) ? (string) $ref['ad_id'] : null,
            'post_id' => isset($ctx['post_id']) ? (string) $ctx['post_id'] : null,
            'ad_title' => isset($ctx['ad_title']) ? (string) $ctx['ad_title'] : null,
            'photo_url' => isset($ctx['photo_url']) ? (string) $ctx['photo_url'] : null,
            'video_url' => isset($ctx['video_url']) ? (string) $ctx['video_url'] : null,
            'product_id' => isset($ctx['product_id']) ? (string) $ctx['product_id'] : null,
        ], fn ($v) => $v !== null && $v !== '');

        return ['ad_referral' => $out];
    }

    /**
     * Parse 1 Messenger `message` object → nội dung chuẩn hoá: kind/body/attachments + buttons.
     * Dùng chung cho tin INBOUND (buyer→page) lẫn ECHO (page→buyer, gồm template/quick-reply
     * có NÚT BẤM của trả lời tự động Facebook). Giữ nguyên hành vi parse cũ cho inbound.
     *
     * @param  array<string,mixed>  $msg
     * @return array{kind: MessageKind, body: ?string, attachments: list<MediaRefDTO>, buttons: list<array<string,string>>}
     */
    private function parseMessageContent(array $msg): array
    {
        $body = isset($msg['text']) && (string) $msg['text'] !== '' ? (string) $msg['text'] : null;
        $attachments = [];
        $kind = MessageKind::Text;
        $buttons = [];

        // Quick replies (kèm tin text) → nút bấm.
        foreach ((array) ($msg['quick_replies'] ?? []) as $qr) {
            $title = (string) (((array) $qr)['title'] ?? '');
            if ($title !== '') {
                $buttons[] = ['title' => $title];
            }
        }

        // Sticker message? FB gắn `sticker_id` ở message-level và/hoặc trong payload
        // attachment. Khi là sticker, KHÔNG linkify URL fallback (URL đó chính là ảnh
        // sticker) ⇒ tránh hiển thị cả sticker lẫn link text trùng lặp.
        $isSticker = ! empty($msg['sticker_id']);
        if (! $isSticker) {
            foreach ((array) ($msg['attachments'] ?? []) as $watt) {
                if (! empty(((array) ($watt['payload'] ?? []))['sticker_id'])) {
                    $isSticker = true;
                    break;
                }
            }
        }

        foreach ((array) ($msg['attachments'] ?? []) as $watt) {
            $wtype = (string) ($watt['type'] ?? '');
            $payload = (array) ($watt['payload'] ?? []);

            // Sticker: type=image + payload.sticker_id (+ url) ⇒ coi url là ảnh sticker.
            if ($wtype === 'image' && ! empty($payload['sticker_id'])) {
                $stickerUrl = isset($payload['url']) && (string) $payload['url'] !== '' ? (string) $payload['url'] : null;
                $attachments[] = new MediaRefDTO(
                    kind: MessageKind::Image,
                    mime: 'image/png',
                    externalUrl: $stickerUrl,
                    filename: 'sticker',
                );
                $kind = MessageKind::Image;

                continue;
            }

            // Template (button/generic): lấy text + nhãn nút bấm — KHÔNG tạo file attachment.
            if ($wtype === 'template') {
                if ($body === null && isset($payload['text']) && (string) $payload['text'] !== '') {
                    $body = (string) $payload['text'];
                }
                foreach ((array) ($payload['buttons'] ?? []) as $b) {
                    $btn = $this->mapTemplateButton((array) $b);
                    if ($btn !== []) {
                        $buttons[] = $btn;
                    }
                }
                foreach ((array) ($payload['elements'] ?? []) as $el) {
                    $el = (array) $el;
                    if ($body === null && isset($el['title']) && (string) $el['title'] !== '') {
                        $body = (string) $el['title'];
                    }
                    foreach ((array) ($el['buttons'] ?? []) as $b) {
                        $btn = $this->mapTemplateButton((array) $b);
                        if ($btn !== []) {
                            $buttons[] = $btn;
                        }
                    }
                }

                continue;
            }

            if ($wtype === 'fallback' || $wtype === 'share') {
                // Shared link: populate body if no text yet. Bỏ qua khi là sticker —
                // fallback URL của sticker chính là ảnh, không phải link cần hiện.
                $attUrl = (string) ($payload['url'] ?? '');
                if ($attUrl !== '' && $body === null && ! $isSticker) {
                    $attTitle = isset($payload['title']) && (string) $payload['title'] !== ''
                        ? (string) $payload['title']
                        : null;
                    $body = $attTitle !== null ? $attTitle.' '.$attUrl : $attUrl;
                }

                continue;
            }

            // Media: image / video / audio / file.
            $mime = match ($wtype) {
                'image' => 'image/jpeg',
                'video' => 'video/mp4',
                'audio' => 'audio/mpeg',
                default => 'application/octet-stream',
            };
            $attKind = match ($wtype) {
                'image' => MessageKind::Image,
                'video' => MessageKind::Video,
                default => MessageKind::File,
            };
            $attUrl = isset($payload['url']) && (string) $payload['url'] !== '' ? (string) $payload['url'] : null;
            $attachments[] = new MediaRefDTO(
                kind: $attKind,
                mime: $mime,
                externalUrl: $attUrl,
                filename: null,
            );
            if ($kind === MessageKind::Text) {
                $kind = $attKind;
            }
        }

        return ['kind' => $kind, 'body' => $body, 'attachments' => $attachments, 'buttons' => $buttons];
    }

    /**
     * Chuẩn hoá 1 nút bấm template Messenger → ['title', 'url'?]. Nút rỗng tên ⇒ [].
     *
     * @param  array<string,mixed>  $b
     * @return array<string,string>
     */
    private function mapTemplateButton(array $b): array
    {
        $title = (string) ($b['title'] ?? '');
        if ($title === '') {
            return [];
        }
        $out = ['title' => $title];
        if (! empty($b['url'])) {
            $out['url'] = (string) $b['url'];
        }

        return $out;
    }

    public function fetchConversations(MessagingAuthContext $auth, array $query = []): Page
    {
        $params = [
            'platform' => 'MESSENGER',
            'fields' => 'id,updated_time,message_count,snippet,participants{id,name}',
            'limit' => (int) ($query['pageSize'] ?? 25),
            'access_token' => $auth->accessToken,
        ];
        if (! empty($query['cursor'])) {
            $params['after'] = (string) $query['cursor'];
        }

        $res = Http::timeout(30)->get($this->graphUrl($auth->externalShopId.'/conversations'), $params);
        if (! $res->successful()) {
            $this->throwGraphError($res, 'fetchConversations');
        }

        $items = [];
        foreach ((array) $res->json('data', []) as $row) {
            $threadId = (string) ($row['id'] ?? '');
            $psid = null;
            $buyerName = null;
            foreach ((array) ($row['participants']['data'] ?? []) as $p) {
                if ((string) ($p['id'] ?? '') !== $auth->externalShopId) {
                    $psid = (string) ($p['id'] ?? '');
                    $buyerName = $p['name'] ?? null;
                    break;
                }
            }
            if ($psid === null || $psid === '') {
                continue; // không xác định được buyer ⇒ bỏ qua hội thoại
            }

            $items[] = new ConversationDTO(
                externalConversationId: $psid,
                buyerExternalId: $psid,
                buyerName: $buyerName,
                buyerAvatarUrl: null,            // lấy riêng qua fetchUserProfile (relay)
                lastMessageAt: isset($row['updated_time']) ? CarbonImmutable::parse($row['updated_time']) : null,
                lastMessagePreview: $row['snippet'] ?? null,
                unreadCount: null,
                raw: [
                    'fb_thread_id' => $threadId,
                    'message_count' => (int) ($row['message_count'] ?? 0),
                    'updated_time' => $row['updated_time'] ?? null,
                ],
            );
        }

        $after = $res->json('paging.cursors.after');
        $hasMore = $res->json('paging.next') !== null;

        return new Page($items, $after ? (string) $after : null, (bool) $hasMore);
    }

    public function fetchMessages(MessagingAuthContext $auth, string $externalConversationId, array $query = []): Page
    {
        // Backfill địa chỉ tin theo THREAD id (Graph) truyền qua $query['thread_id'];
        // mỗi MessageDTO mang externalConversationId = PSID để ingest khớp hội thoại
        // (Send API/webhook đều dùng PSID).
        $threadId = (string) ($query['thread_id'] ?? $externalConversationId);
        $limit = (int) ($query['pageSize'] ?? 50);

        // Xin `attachments` DẠNG TRẦN (không liệt kê sub-field) ⇒ Graph trả representation
        // mặc định ĐẦY ĐỦ gồm cả `generic_template{title,cta,image_url}` (tin tự động của
        // page) lẫn image_data/video_data/file_url. Nếu liệt kê sub-field cụ thể thì
        // generic_template BỊ LOẠI ⇒ tin tự động thành rỗng ("không hỗ trợ hiển thị").
        $res = Http::timeout(30)->get($this->graphUrl($threadId), [
            'fields' => "messages.limit({$limit}){id,message,created_time,from,sticker,shares{link,name,description},attachments}",
            'access_token' => $auth->accessToken,
        ]);
        if (! $res->successful()) {
            $this->throwGraphError($res, 'fetchMessages');
        }

        $items = [];
        foreach ((array) $res->json('messages.data', []) as $row) {
            $fromId = (string) ($row['from']['id'] ?? '');
            $direction = $fromId === $auth->externalShopId ? MessageDirection::Outbound : MessageDirection::Inbound;
            // Tên buyer = `from.name` của tin INBOUND (tin OUTBOUND from.name là tên page).
            $fromName = $direction === MessageDirection::Inbound && isset($row['from']['name']) && (string) $row['from']['name'] !== ''
                ? (string) $row['from']['name']
                : null;

            $body = ($row['message'] ?? '') !== '' ? (string) $row['message'] : null;
            $attachments = [];
            // Sticker message ⇒ không linkify URL share/fallback (đó là ảnh sticker).
            $isSticker = ! empty($row['sticker']);

            // Sticker: `sticker` field is a direct CDN URL string.
            if (! empty($row['sticker'])) {
                $attachments[] = new MediaRefDTO(
                    kind: MessageKind::Image,
                    mime: 'image/png',
                    externalUrl: (string) $row['sticker'],
                    filename: 'sticker',
                );
            }

            // Shared links / fallback attachments from the `attachments` sub-field.
            $shareUrl = null;
            $buttons = [];
            foreach ((array) ($row['attachments']['data'] ?? []) as $att) {
                // Tin tự động của page: generic_template{title, cta} — title→body, cta→nút bấm.
                // KHÔNG tạo media attachment (đó là tin text có nút, không phải file).
                if (! empty($att['generic_template'])) {
                    $gt = (array) $att['generic_template'];
                    if ($body === null) {
                        $title = (string) ($gt['title'] ?? '');
                        $subtitle = (string) ($gt['subtitle'] ?? '');
                        $body = trim($title.($subtitle !== '' ? "\n".$subtitle : '')) ?: null;
                    }
                    // Ảnh sản phẩm trong template (nếu có) → attachment ảnh.
                    if (! empty($gt['image_url'])) {
                        $attachments[] = new MediaRefDTO(
                            kind: MessageKind::Image,
                            mime: 'image/jpeg',
                            externalUrl: (string) $gt['image_url'],
                            filename: null,
                        );
                    }
                    foreach ((array) ($gt['cta'] ?? []) as $c) {
                        $btn = $this->mapTemplateButton((array) $c);
                        if ($btn !== []) {
                            $buttons[] = $btn;
                        }
                    }

                    continue;
                }
                $type = (string) ($att['type'] ?? '');
                if ($type === 'fallback' || $type === 'share') {
                    // Shared link: carry title + url — linkify body instead of a media attachment.
                    $attUrl = (string) ($att['url'] ?? '');
                    if ($attUrl !== '' && $shareUrl === null) {
                        $attTitle = isset($att['title']) && (string) $att['title'] !== ''
                            ? (string) $att['title']
                            : null;
                        $shareUrl = $attTitle !== null ? $attTitle.' '.$attUrl : $attUrl;
                    }

                    continue;
                }
                // Sticker đã lấy ảnh từ field `sticker` ⇒ bỏ qua attachment ảnh trùng trong loop.
                if ($isSticker) {
                    continue;
                }
                // CHỈ giữ attachment THẬT (có URL media). Bỏ "rác" không URL (vd object rỗng)
                // để không tạo "file" giả + để recoverMessageContent() chạy khi cần.
                $mapped = $this->mapBackfillAttachment((array) $att);
                if ($mapped->externalUrl !== null) {
                    $attachments[] = $mapped;
                }
            }

            // Shared post/link nằm ở edge `shares` (KHÁC `attachments`) — nguồn gây
            // tin rỗng khi `message` trống. Lấy name/description + link làm body.
            if ($shareUrl === null) {
                foreach ((array) ($row['shares']['data'] ?? []) as $share) {
                    $link = (string) ($share['link'] ?? '');
                    if ($link === '') {
                        continue;
                    }
                    $label = (string) ($share['name'] ?? $share['description'] ?? '');
                    $shareUrl = $label !== '' ? $label.' '.$link : $link;
                    break;
                }
            }

            // When the message text is empty, fall back to the share URL as the body
            // so the FE can linkify it. Trừ sticker — URL share là chính ảnh sticker.
            if ($body === null && $shareUrl !== null && ! $isSticker) {
                $body = $shareUrl;
            }

            // Tin KHÔNG còn nội dung hiển thị (vd tin tự động/template của page): messages
            // edge KHÔNG trả generic_template. Fetch riêng /{mid}?fields=message,attachments
            // (endpoint trả generic_template{title,cta} đầy đủ) để lấy text + nút bấm.
            if ($body === null && $attachments === [] && $buttons === []) {
                $recovered = $this->recoverMessageContent((string) ($row['id'] ?? ''), $auth->accessToken);
                $body = $recovered['body'];
                $buttons = $recovered['buttons'];
            }

            $kind = $attachments !== [] ? $attachments[0]->kind : MessageKind::Text;

            $items[] = new MessageDTO(
                externalConversationId: $externalConversationId,
                externalMessageId: (string) ($row['id'] ?? ''),
                buyerExternalId: $externalConversationId,
                direction: $direction,
                kind: $kind,
                body: $body,
                attachments: $attachments,
                sentAt: isset($row['created_time']) ? CarbonImmutable::parse($row['created_time']) : null,
                raw: $row,
                meta: $buttons !== [] ? ['buttons' => $buttons] : [],
                buyerName: $fromName,
            );
        }

        return new Page($items, null, false);
    }

    /**
     * Lấy nội dung tin qua endpoint /{message-id} — dùng khi messages edge trả tin RỖNG
     * (tin tự động/template: edge bỏ qua, nhưng /{mid}?fields=message,attachments trả
     * `generic_template{title,cta}` đầy đủ). Best-effort: lỗi ⇒ rỗng (không chặn backfill).
     *
     * @return array{body: ?string, buttons: list<array<string,string>>}
     */
    private function recoverMessageContent(string $messageId, string $accessToken): array
    {
        if ($messageId === '') {
            return ['body' => null, 'buttons' => []];
        }

        try {
            $res = Http::timeout(20)->get($this->graphUrl($messageId), [
                'fields' => 'message,attachments',
                'access_token' => $accessToken,
            ]);
            if (! $res->successful()) {
                return ['body' => null, 'buttons' => []];
            }

            $body = ($res->json('message') ?? '') !== '' ? (string) $res->json('message') : null;
            $buttons = [];
            foreach ((array) $res->json('attachments.data', []) as $att) {
                $gt = (array) ($att['generic_template'] ?? []);
                if ($gt === []) {
                    continue;
                }
                if ($body === null) {
                    $title = (string) ($gt['title'] ?? '');
                    $subtitle = (string) ($gt['subtitle'] ?? '');
                    $body = trim($title.($subtitle !== '' ? "\n".$subtitle : '')) ?: null;
                }
                foreach ((array) ($gt['cta'] ?? []) as $c) {
                    $btn = $this->mapTemplateButton((array) $c);
                    if ($btn !== []) {
                        $buttons[] = $btn;
                    }
                }
            }

            return ['body' => $body, 'buttons' => $buttons];
        } catch (\Throwable) {
            return ['body' => null, 'buttons' => []];
        }
    }

    /**
     * Liệt kê bài đăng đã xuất bản của trang (post picker cho trigger comment_on_post).
     * `id` dạng `{pageId}_{postId}` — KHỚP với `post_id` của feed webhook ⇒ matcher
     * so trực tiếp. `full_picture` là CDN hết hạn (chỉ để xem trước trong picker).
     *
     * @param  array{pageSize?:int, cursor?:string}  $query
     * @return array{items: list<array{id:string, message:?string, permalink_url:?string, image_url:?string, created_time:?string, likes:int, comments:int, shares:int}>, nextCursor:?string, hasMore:bool}
     */
    public function listPosts(MessagingAuthContext $auth, array $query = []): array
    {
        $params = [
            // reactions ≈ "like" tổng (mọi cảm xúc); comments + shares cho lưới chọn bài.
            'fields' => 'id,message,created_time,permalink_url,full_picture'
                .',reactions.summary(true).limit(0)'
                .',comments.summary(true).limit(0)'
                .',shares',
            'limit' => (int) ($query['pageSize'] ?? 25),
            'access_token' => $auth->accessToken,
        ];
        if (! empty($query['cursor'])) {
            $params['after'] = (string) $query['cursor'];
        }

        $res = Http::timeout(30)->get($this->graphUrl($auth->externalShopId.'/published_posts'), $params);
        if (! $res->successful()) {
            $this->throwGraphError($res, 'listPosts');
        }

        $items = [];
        foreach ((array) $res->json('data', []) as $post) {
            $id = (string) ($post['id'] ?? '');
            if ($id === '') {
                continue;
            }
            $items[] = [
                'id' => $id,
                'message' => isset($post['message']) && (string) $post['message'] !== '' ? (string) $post['message'] : null,
                'permalink_url' => isset($post['permalink_url']) && (string) $post['permalink_url'] !== '' ? (string) $post['permalink_url'] : null,
                'image_url' => isset($post['full_picture']) && (string) $post['full_picture'] !== '' ? (string) $post['full_picture'] : null,
                'created_time' => isset($post['created_time']) ? (string) $post['created_time'] : null,
                'likes' => (int) ($post['reactions']['summary']['total_count'] ?? 0),
                'comments' => (int) ($post['comments']['summary']['total_count'] ?? 0),
                'shares' => (int) ($post['shares']['count'] ?? 0),
            ];
        }

        $after = $res->json('paging.cursors.after');
        $hasMore = $res->json('paging.next') !== null;

        return ['items' => $items, 'nextCursor' => $after !== null ? (string) $after : null, 'hasMore' => (bool) $hasMore];
    }

    /**
     * Lấy danh sách top-level comment threads từ page feed.
     *
     * Mỗi item = 1 comment khách hàng (cha — không có `parent`) trên 1 bài viết.
     * Comment của chính page (from.id == page id) bị bỏ qua (không phải customer).
     * Reply (comment con — có `parent`) được gom vào `replies[]` của comment cha.
     *
     * Return shape (plain array):
     * ```
     * [
     *   'items'      => [ [...comment_thread], ... ],
     *   'nextCursor' => string|null,
     *   'hasMore'    => bool,
     * ]
     * ```
     *
     * @param  array{pageSize?: int, commentLimit?: int, cursor?: string}  $query
     * @return array{items: list<array<string,mixed>>, nextCursor: string|null, hasMore: bool}
     */
    public function fetchCommentThreads(MessagingAuthContext $auth, array $query = []): array
    {
        $postLimit = (int) ($query['pageSize'] ?? 10);
        $commentLimit = (int) ($query['commentLimit'] ?? 50);

        $params = [
            'fields' => "id,message,permalink_url,created_time,full_picture,attachments{media_type},comments.limit({$commentLimit}){id,message,created_time,from{id,name,picture},parent}",
            'limit' => $postLimit,
            'access_token' => $auth->accessToken,
        ];
        if (! empty($query['cursor'])) {
            $params['after'] = (string) $query['cursor'];
        }

        $res = Http::timeout(30)->get($this->graphUrl($auth->externalShopId.'/feed'), $params);
        if (! $res->successful()) {
            $this->throwGraphError($res, 'fetchCommentThreads');
        }

        $pageId = $auth->externalShopId;
        $items = [];

        foreach ((array) $res->json('data', []) as $post) {
            $postId = (string) ($post['id'] ?? '');
            $postMessage = isset($post['message']) && (string) $post['message'] !== '' ? (string) $post['message'] : null;
            $postPermalink = isset($post['permalink_url']) && (string) $post['permalink_url'] !== '' ? (string) $post['permalink_url'] : null;
            // `full_picture` là CDN hết hạn (chỉ để xem trước post card — refresh mỗi lần sync).
            // Với bài video, full_picture chính là ảnh thumbnail của video.
            $postPicture = isset($post['full_picture']) && (string) $post['full_picture'] !== '' ? (string) $post['full_picture'] : null;
            $postCreated = isset($post['created_time']) ? (string) $post['created_time'] : null;
            // Loại media bài viết — để FE phủ icon ▶ lên ảnh preview khi là video.
            $postIsVideo = false;
            foreach ((array) ($post['attachments']['data'] ?? []) as $att) {
                if (in_array((string) ($att['media_type'] ?? ''), ['video', 'video_inline', 'video_autoplay'], true)) {
                    $postIsVideo = true;
                    break;
                }
            }

            // index top-level comments keyed by id for reply grouping
            /** @var array<string, array<string,mixed>> $topLevel */
            $topLevel = [];
            /** @var array<string, list<array<string,mixed>>> $replies */
            $replies = [];

            foreach ((array) ($post['comments']['data'] ?? []) as $comment) {
                $commentId = (string) ($comment['id'] ?? '');
                $fromId = (string) ($comment['from']['id'] ?? '');

                if (isset($comment['parent'])) {
                    // This is a reply — attach to parent thread
                    $parentId = (string) ($comment['parent']['id'] ?? '');
                    if ($parentId !== '') {
                        $replies[$parentId][] = [
                            'id' => $commentId,
                            'from_id' => $fromId,
                            'from_name' => $comment['from']['name'] ?? null,
                            // Avatar người reply (CDN FB hết hạn ⇒ caller relay). Stack avatar comment thread.
                            'from_avatar' => $comment['from']['picture']['data']['url'] ?? null,
                            'message' => isset($comment['message']) && (string) $comment['message'] !== '' ? (string) $comment['message'] : null,
                            'created_time' => $comment['created_time'] ?? null,
                        ];
                    }

                    continue;
                }

                // Top-level comment — skip page's own comments (not a customer)
                if ($fromId === $pageId) {
                    continue;
                }

                $topLevel[$commentId] = [
                    'comment_id' => $commentId,
                    'commenter_id' => $fromId,
                    'commenter_name' => $comment['from']['name'] ?? null,
                    // Avatar người comment (CDN FB hết hạn ⇒ caller relay). Stack avatar comment thread.
                    'commenter_avatar' => $comment['from']['picture']['data']['url'] ?? null,
                    'message' => isset($comment['message']) && (string) $comment['message'] !== '' ? (string) $comment['message'] : null,
                    'created_time' => $comment['created_time'] ?? null,
                    'post_id' => $postId,
                    'post_message' => $postMessage,
                    'post_permalink' => $postPermalink,
                    'post_picture' => $postPicture,
                    'post_is_video' => $postIsVideo,
                    'post_created_time' => $postCreated,
                    'replies' => [],
                ];
            }

            // Attach gathered replies to their top-level parent
            foreach ($topLevel as $commentId => $thread) {
                $topLevel[$commentId]['replies'] = $replies[$commentId] ?? [];
                $items[] = $topLevel[$commentId];
            }
        }

        $nextCursor = $res->json('paging.cursors.after');
        $hasMore = $res->json('paging.next') !== null;

        return [
            'items' => $items,
            'nextCursor' => $nextCursor !== null ? (string) $nextCursor : null,
            'hasMore' => (bool) $hasMore,
        ];
    }

    /**
     * Lấy tên + avatar tác giả của 1 comment (path WEBHOOK realtime — feed webhook
     * chỉ có from{id,name}, không kèm ảnh). `picture` là URL CDN hết hạn ⇒ caller relay.
     * Best-effort: lỗi/thiếu quyền ⇒ trả null (FE fallback chữ cái đầu).
     *
     * @return array{name: ?string, avatar_url: ?string}
     */
    public function fetchCommentAuthorAvatar(MessagingAuthContext $auth, string $commentId): array
    {
        $res = Http::timeout(20)->get($this->graphUrl($commentId), [
            'fields' => 'from{id,name,picture}',
            'access_token' => $auth->accessToken,
        ]);

        if (! $res->successful()) {
            return ['name' => null, 'avatar_url' => null];
        }

        return [
            'name' => $res->json('from.name'),
            'avatar_url' => $res->json('from.picture.data.url'),
        ];
    }

    /** @param array<string,mixed> $att */
    private function mapBackfillAttachment(array $att): MediaRefDTO
    {
        $mime = (string) ($att['mime_type'] ?? 'application/octet-stream');
        $url = $att['image_data']['url'] ?? $att['video_data']['url'] ?? $att['file_url'] ?? null;
        $kind = match (true) {
            isset($att['image_data']) || str_starts_with($mime, 'image/') => MessageKind::Image,
            isset($att['video_data']) || str_starts_with($mime, 'video/') => MessageKind::Video,
            default => MessageKind::File,
        };

        return new MediaRefDTO(
            kind: $kind,
            mime: $mime,
            externalUrl: $url !== null ? (string) $url : null,
            filename: $att['name'] ?? null,
        );
    }

    // --- Outbound ---------------------------------------------------------

    public function sendText(MessagingAuthContext $auth, string $externalConversationId, string $body, array $opts = []): SendResultDTO
    {
        $tag = is_string($opts['message_tag'] ?? null) ? $opts['message_tag'] : null;

        return $this->send($auth, [
            'recipient' => ['id' => $externalConversationId],
            'message' => ['text' => $body],
            'messaging_type' => $tag ? 'MESSAGE_TAG' : 'RESPONSE',
        ] + ($tag ? ['tag' => $tag] : []));
    }

    public function sendMedia(MessagingAuthContext $auth, string $externalConversationId, MediaRefDTO $media, array $opts = []): SendResultDTO
    {
        // Messenger cần URL public. Module (SendMessage) populate `externalUrl`
        // bằng signed URL trước khi gọi (storage_path không gửi thẳng được).
        $url = $media->externalUrl;
        if (! $url) {
            throw new \RuntimeException('Facebook sendMedia cần externalUrl (signed) — storage_path không gửi trực tiếp được.');
        }

        $type = match ($media->kind->value) {
            'image' => 'image',
            'video' => 'video',
            'audio' => 'audio',
            default => 'file',
        };
        $tag = is_string($opts['message_tag'] ?? null) ? $opts['message_tag'] : null;

        return $this->send($auth, [
            'recipient' => ['id' => $externalConversationId],
            'message' => ['attachment' => ['type' => $type, 'payload' => ['url' => $url, 'is_reusable' => false]]],
            'messaging_type' => $tag ? 'MESSAGE_TAG' : 'RESPONSE',
        ] + ($tag ? ['tag' => $tag] : []));
    }

    public function sendTemplate(MessagingAuthContext $auth, string $externalConversationId, string $templateKey, array $vars = [], array $opts = []): SendResultDTO
    {
        // Core đã resolve body; gửi như text kèm MESSAGE_TAG (giữ ngoài 24h window).
        $body = (string) ($vars['_resolved_body'] ?? $opts['body'] ?? '');

        return $this->sendText($auth, $externalConversationId, $body, $opts);
    }

    /**
     * Gửi button template (Send API): text + tối đa 3 nút. Nút `postback` mang
     * `payload` (engine bắt qua webhook messaging_postbacks); nút `url` → web_url.
     * Không có nút hợp lệ ⇒ gửi như text (button template bắt buộc có ≥1 nút).
     * Tôn trọng 24h window qua {@see send} (FB trả lỗi 10/200 ⇒ OutboundWindowClosed).
     *
     * @param  array{text?:string, buttons?:list<array<string,mixed>>}  $structure
     * @param  array<string, mixed>  $opts
     */
    public function sendInteractive(MessagingAuthContext $auth, string $externalConversationId, array $structure, array $opts = []): SendResultDTO
    {
        $text = trim((string) ($structure['text'] ?? ''));

        $fbButtons = [];
        foreach (array_slice((array) ($structure['buttons'] ?? []), 0, 3) as $b) {
            $b = (array) $b;
            $title = (string) ($b['title'] ?? $b['label'] ?? '');
            if ($title === '') {
                continue;
            }
            if (((string) ($b['type'] ?? 'postback')) === 'url' && ! empty($b['url'])) {
                $fbButtons[] = ['type' => 'web_url', 'title' => $title, 'url' => (string) $b['url']];
            } else {
                $fbButtons[] = ['type' => 'postback', 'title' => $title, 'payload' => (string) ($b['payload'] ?? $title)];
            }
        }

        if ($fbButtons === []) {
            return $this->sendText($auth, $externalConversationId, $text, $opts);
        }

        $tag = is_string($opts['message_tag'] ?? null) ? $opts['message_tag'] : null;

        return $this->send($auth, [
            'recipient' => ['id' => $externalConversationId],
            'message' => ['attachment' => ['type' => 'template', 'payload' => [
                'template_type' => 'button',
                'text' => $text !== '' ? $text : '…',   // FB yêu cầu text non-empty
                'buttons' => $fbButtons,
            ]]],
            'messaging_type' => $tag ? 'MESSAGE_TAG' : 'RESPONSE',
        ] + ($tag ? ['tag' => $tag] : []));
    }

    public function outboundWindow(): OutboundWindowPolicyDTO
    {
        // Meta đã KHAI TỬ message tag (POST_PURCHASE_UPDATE/CONFIRMED_EVENT_UPDATE/
        // ACCOUNT_UPDATE → error_subcode 1893061). Chỉ còn `HUMAN_AGENT` (tin nhân
        // viên người thật, 7 ngày). Ngoài cửa sổ ⇒ chỉ utility template đã duyệt.
        return new OutboundWindowPolicyDTO(
            freeWindowHours: 24,
            requiresTag: true,
            allowedTags: ['HUMAN_AGENT'],
            humanAgentWindowHours: 168,
            templateOnlyOutsideWindow: true,
        );
    }

    // --- Utility Messages (template đã duyệt) -----------------------------

    /**
     * Tạo + submit utility template lên Meta để duyệt: `POST /{page_id}/message_templates`
     * (category UTILITY). Body dùng `{{1}}…`; `examples` cung cấp giá trị mẫu (Meta bắt buộc).
     *
     * ⚠️ MỨC ĐỘ XÁC MINH: shape request map theo tài liệu Meta + test Http::fake. LIVE
     * cần Page token thật + quyền `pages_utility_messaging` (App Review). Payload gửi/đăng ký
     * cô lập tại đây — nếu Meta đổi field chỉ sửa 1 chỗ.
     */
    public function createUtilityTemplate(MessagingAuthContext $auth, UtilityTemplateDTO $template): UtilityTemplateRefDTO
    {
        $components = [[
            'type' => 'BODY',
            'text' => $template->body,
        ]];
        if ($template->examples !== []) {
            $components[0]['example'] = ['body_text' => [$template->examples]];
        }
        if ($template->buttons !== []) {
            $components[] = ['type' => 'BUTTONS', 'buttons' => $this->mapTemplateButtons($template->buttons)];
        }

        $res = Http::post($this->graphUrl($auth->externalShopId.'/message_templates'), [
            'name' => $template->name,
            'language' => $template->language,
            'category' => 'UTILITY',
            'components' => $components,
            'access_token' => $auth->accessToken,
        ]);

        if (! $res->successful()) {
            $this->throwGraphError($res, 'createUtilityTemplate');
        }

        return new UtilityTemplateRefDTO(
            externalTemplateId: (string) ($res->json('id') ?? ''),
            name: $template->name,
            language: $template->language,
            status: $this->mapTemplateStatus((string) ($res->json('status') ?? 'PENDING'))->status,
        );
    }

    public function syncUtilityTemplateStatus(MessagingAuthContext $auth, string $externalTemplateId): UtilityTemplateStatusDTO
    {
        $res = Http::get($this->graphUrl($externalTemplateId), [
            'fields' => 'status,quality_score,rejected_reason',
            'access_token' => $auth->accessToken,
        ]);

        if (! $res->successful()) {
            $this->throwGraphError($res, 'syncUtilityTemplateStatus');
        }

        return $this->mapTemplateStatus(
            (string) ($res->json('status') ?? 'PENDING'),
            $res->json('rejected_reason'),
            (array) $res->json(),
        );
    }

    /**
     * Gửi utility template đã duyệt tới PSID. Tham chiếu template theo tên + ngôn ngữ;
     * `$vars` thay `{{1}},{{2}}…` đúng thứ tự. `messaging_type=MESSAGE_TAG` không còn
     * dùng — utility message tự được phép ngoài 24h khi template đã duyệt.
     *
     * @param  list<string>  $vars
     * @param  array<string, mixed>  $opts
     */
    public function sendUtilityTemplate(MessagingAuthContext $auth, string $externalConversationId, UtilityTemplateRefDTO $template, array $vars = [], array $opts = []): SendResultDTO
    {
        $params = array_map(
            fn (string $v): array => ['type' => 'text', 'text' => $v],
            $vars,
        );

        return $this->send($auth, [
            'recipient' => ['id' => $externalConversationId],
            'messaging_type' => 'UPDATE',
            'message' => ['attachment' => ['type' => 'template', 'payload' => [
                'template_type' => 'utility_message',
                'template_name' => $template->name,
                'language' => $template->language,
                'parameters' => $params,
            ]]],
        ]);
    }

    /**
     * Map nút chuẩn hoá → component BUTTONS của Meta. URL → URL button; còn lại →
     * QUICK_REPLY (postback). Tối đa Meta cho phép; cắt ở 3 cho an toàn.
     *
     * @param  list<array<string, mixed>>  $buttons  mỗi nút: {type, title, url?, payload?}
     * @return list<array<string, mixed>>
     */
    private function mapTemplateButtons(array $buttons): array
    {
        $out = [];
        foreach (array_slice($buttons, 0, 3) as $b) {
            $title = (string) ($b['title'] ?? '');
            if ($title === '') {
                continue;
            }
            if (($b['type'] ?? 'postback') === 'url' && ! empty($b['url'])) {
                $out[] = ['type' => 'URL', 'text' => $title, 'url' => (string) $b['url']];
            } else {
                $out[] = ['type' => 'QUICK_REPLY', 'text' => $title];
            }
        }

        return $out;
    }

    /** Chuẩn hoá status Meta (APPROVED/PENDING/REJECTED/PAUSED/DISABLED…) → 3 giá trị. */
    private function mapTemplateStatus(string $raw, ?string $reason = null, array $rawData = []): UtilityTemplateStatusDTO
    {
        $status = match (strtoupper($raw)) {
            'APPROVED', 'ACTIVE' => UtilityTemplateStatusDTO::APPROVED,
            'REJECTED', 'DISABLED', 'PAUSED', 'DELETED' => UtilityTemplateStatusDTO::REJECTED,
            default => UtilityTemplateStatusDTO::PENDING,
        };

        return new UtilityTemplateStatusDTO($status, $reason, $rawData);
    }

    // --- Comment moderation -----------------------------------------------

    public function hideComment(MessagingAuthContext $auth, string $commentId, bool $hidden): void
    {
        $res = Http::post($this->graphUrl($commentId), [
            'is_hidden' => $hidden,
            'access_token' => $auth->accessToken,
        ]);

        if (! $res->successful()) {
            $this->throwGraphError($res, 'hideComment');
        }
    }

    public function deleteComment(MessagingAuthContext $auth, string $commentId): void
    {
        $res = Http::delete($this->graphUrl($commentId), [
            'access_token' => $auth->accessToken,
        ]);

        if (! $res->successful()) {
            $this->throwGraphError($res, 'deleteComment');
        }
    }

    public function replyToComment(MessagingAuthContext $auth, string $commentId, string $message, array $attachments = []): string
    {
        $params = [
            'message' => $message,
            'access_token' => $auth->accessToken,
        ];
        // Reply công khai kèm ảnh: Graph chấp nhận `attachment_url` (1 ảnh, URL public).
        if (($imageUrl = $this->firstImageUrl($attachments)) !== null) {
            $params['attachment_url'] = $imageUrl;
        }

        $res = Http::post($this->graphUrl($commentId.'/comments'), $params);

        if (! $res->successful()) {
            $this->throwGraphError($res, 'replyToComment');
        }

        return (string) $res->json('id');
    }

    public function privateReplyToComment(MessagingAuthContext $auth, string $commentId, string $message, array $attachments = []): void
    {
        // Delegate sang sendCommentPrivateMessage (idempotent + lấy PSID) — giữ chữ ký
        // void cho caller cũ (auto-reply job + endpoint private-reply). Bỏ qua kết quả.
        $this->sendCommentPrivateMessage($auth, $commentId, null, $message, $attachments);
    }

    public function likeComment(MessagingAuthContext $auth, string $commentId, bool $like): void
    {
        $res = $like
            ? Http::post($this->graphUrl($commentId.'/likes'), ['access_token' => $auth->accessToken])
            : Http::delete($this->graphUrl($commentId.'/likes'), ['access_token' => $auth->accessToken]);

        if ($res->successful()) {
            return;
        }

        // Idempotent: thích cái đã thích / bỏ thích cái chưa thích ⇒ coi như xong.
        $code = (int) ($res->json('error.code') ?? 0);
        if (in_array($code, [3, 100], true) && str_contains((string) $res->json('error.message'), 'already')) {
            return;
        }

        $this->throwGraphError($res, 'likeComment');
    }

    public function sendCommentPrivateMessage(MessagingAuthContext $auth, string $commentId, ?string $psid, string $message, array $attachments = []): array
    {
        // Facebook: 1 message = text HOẶC 1 attachment. Ghép thành danh sách phần,
        // gửi TUẦN TỰ. Phần ĐẦU qua comment_id (Private Reply — chỉ 1 lần/comment, lấy
        // PSID từ recipient_id, KHÔNG message tag). Các phần SAU qua PSID + MESSAGE_TAG
        // (HUMAN_AGENT) vì private reply không tự mở cửa sổ 24h. Cửa sổ đóng / bị chặn /
        // đã nhắn riêng ⇒ dừng êm (best-effort), KHÔNG ném — báo số phần đã gửi.
        $parts = [];
        if (trim($message) !== '') {
            $parts[] = ['text' => $message];
        }
        foreach ($attachments as $media) {
            if ($media->externalUrl === null || $media->externalUrl === '') {
                continue;
            }
            $parts[] = ['attachment' => [
                'type' => $this->sendAttachmentType($media->kind),
                'payload' => ['url' => $media->externalUrl, 'is_reusable' => false],
            ]];
        }

        $total = count($parts);
        $delivered = 0;
        $firstMessageId = null;

        foreach ($parts as $part) {
            $result = $this->sendPrivatePart($auth, $commentId, $psid, $part);
            if ($result['psid'] !== null) {
                $psid = $result['psid'];
            }
            if ($result['ok']) {
                $delivered++;
                // Giữ mid phần ĐẦU để caller ghi tin outbound vào hộp thoại DM với đúng id
                // Facebook ⇒ webhook echo về sau dedupe (conversation_id + external_message_id).
                if ($firstMessageId === null && ($result['message_id'] ?? null) !== null) {
                    $firstMessageId = (string) $result['message_id'];
                }

                continue;
            }
            // Cửa sổ đóng / bị chặn / đã nhắn riêng mà chưa có PSID ⇒ các phần sau cũng
            // hỏng tương tự — dừng để báo cáo phần đã gửi thay vì spam lỗi.
            break;
        }

        return ['psid' => (string) $psid, 'message_id' => $firstMessageId, 'delivered' => $delivered, 'total' => $total];
    }

    /**
     * Gửi 1 phần tin riêng. Chưa có PSID ⇒ recipient {comment_id} (Private Reply, không
     * tag); có PSID ⇒ recipient {id} + MESSAGE_TAG (HUMAN_AGENT). Trả
     * `['ok'=>bool, 'psid'=>?string]`: bắt 10900 (đã nhắn riêng) + cửa sổ đóng
     * (10/200/2018278) + bị chặn (551) ⇒ ok=false, KHÔNG ném. Lỗi khác ⇒ ném.
     *
     * @param  array<string,mixed>  $message
     * @return array{ok: bool, psid: ?string, message_id: ?string}
     */
    private function sendPrivatePart(MessagingAuthContext $auth, string $commentId, ?string $psid, array $message): array
    {
        $hasPsid = $psid !== null && $psid !== '';
        $body = [
            'recipient' => $hasPsid ? ['id' => $psid] : ['comment_id' => $commentId],
            'message' => $message,
            'access_token' => $auth->accessToken,
        ];
        // Tin tiếp theo (đã có PSID) nằm ngoài cửa sổ chuẩn ⇒ phải gắn MESSAGE_TAG.
        if ($hasPsid) {
            $body['messaging_type'] = 'MESSAGE_TAG';
            $body['tag'] = 'HUMAN_AGENT';
        }

        $res = Http::post($this->graphUrl('me/messages'), $body);

        if ($res->successful()) {
            $recipientId = $res->json('recipient_id');
            $messageId = $res->json('message_id');

            return [
                'ok' => true,
                'psid' => $recipientId !== null && (string) $recipientId !== '' ? (string) $recipientId : null,
                'message_id' => $messageId !== null && (string) $messageId !== '' ? (string) $messageId : null,
            ];
        }

        $code = (int) ($res->json('error.code') ?? 0);
        $subcode = (int) ($res->json('error.error_subcode') ?? 0);

        // Best-effort: đã nhắn riêng (10900) / cửa sổ đóng (10,200 / 2018278) / bị chặn (551)
        // ⇒ không ném (caller dừng & báo cáo). Lỗi khác (token, rate-limit…) ⇒ ném.
        if (in_array($code, [10900, 10, 200, 551], true) || $subcode === 2018278) {
            return ['ok' => false, 'psid' => null, 'message_id' => null];
        }

        $this->throwGraphError($res, 'privateReplyToComment');
    }

    /** Map kind nội bộ → loại attachment Send API. */
    private function sendAttachmentType(MessageKind $kind): string
    {
        return match ($kind) {
            MessageKind::Image => 'image',
            MessageKind::Video => 'video',
            MessageKind::Audio => 'audio',
            default => 'file',
        };
    }

    /**
     * URL ảnh đầu tiên (đã có externalUrl signed) trong danh sách attachment — dùng
     * đính vào reply công khai / nhắn riêng. Null nếu không có ảnh.
     *
     * @param  list<MediaRefDTO>  $attachments
     */
    private function firstImageUrl(array $attachments): ?string
    {
        foreach ($attachments as $media) {
            if ($media->kind === MessageKind::Image && $media->externalUrl !== null && $media->externalUrl !== '') {
                return $media->externalUrl;
            }
        }

        return null;
    }

    // --- Internals --------------------------------------------------------

    /** @param array<string,mixed> $body */
    private function send(MessagingAuthContext $auth, array $body): SendResultDTO
    {
        $res = Http::post($this->graphUrl('me/messages').'?access_token='.urlencode($auth->accessToken), $body);

        if ($res->successful()) {
            return new SendResultDTO(
                externalMessageId: (string) ($res->json('message_id') ?? ''),
                sentAt: CarbonImmutable::now(),
                raw: (array) $res->json(),
            );
        }

        $error = (array) $res->json('error');
        $code = (int) ($error['code'] ?? 0);

        // 24h window đóng / không gửi được tới user này.
        if (in_array($code, [10, 200], true) || (int) ($error['error_subcode'] ?? 0) === 2018278) {
            throw OutboundWindowClosed::for('facebook_page', 24);
        }
        // User không cho nhắn / đã chặn.
        if ($code === 551) {
            throw new ConversationClosed('Buyer không nhận tin (đã chặn / xoá hội thoại).');
        }

        throw new \RuntimeException('Facebook send failed: '.$res->body());
    }

    /** Ném lỗi Graph; map rate-limit (code 80006) sang RuntimeException nhận diện được để job backoff. */
    private function throwGraphError(Response $res, string $op): never
    {
        $error = (array) $res->json('error');
        $code = (int) ($error['code'] ?? 0);
        if ($code === 80006) {
            throw new \RuntimeException('FACEBOOK_RATE_LIMIT: '.$op);
        }
        throw new \RuntimeException("Facebook {$op} failed: ".$res->body());
    }

    private function graphUrl(string $path): string
    {
        return 'https://graph.facebook.com/'.$this->graphVersion().'/'.ltrim($path, '/');
    }

    private function graphVersion(): string
    {
        return (string) ($this->config['graph_version'] ?? 'v19.0');
    }
}
