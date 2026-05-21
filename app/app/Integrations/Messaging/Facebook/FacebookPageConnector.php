<?php

namespace CMBcoreSeller\Integrations\Messaging\Facebook;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;
use CMBcoreSeller\Integrations\Messaging\Contracts\MessagingConnector;
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
use CMBcoreSeller\Integrations\Messaging\Exceptions\ConversationClosed;
use CMBcoreSeller\Integrations\Messaging\Exceptions\OutboundWindowClosed;
use CMBcoreSeller\Integrations\Messaging\Exceptions\UnsupportedOperation;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
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
class FacebookPageConnector implements MessagingConnector
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
            'outbound.file' => true,
            'outbound.template' => true,   // qua MESSAGE_TAG
            'read_receipt' => true,        // sender_action=mark_seen
            'typing' => true,              // sender_action=typing_on
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
        $scope = 'pages_messaging,pages_manage_metadata,pages_read_engagement,pages_show_list,pages_read_user_content,pages_manage_engagement';

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
        // Subscribe page vào app cho field `messages`, `messaging_postbacks`.
        Http::post($this->graphUrl($auth->externalShopId.'/subscribed_apps'), [
            'subscribed_fields' => 'messages,messaging_postbacks,message_deliveries,message_reads,feed',
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
        $res = Http::timeout(20)->get($this->graphUrl($psid), [
            'fields' => 'name,profile_pic',
            'access_token' => $auth->accessToken,
        ]);

        if (! $res->successful()) {
            return ['name' => null, 'avatar_url' => null];
        }

        return [
            'name' => $res->json('name'),
            'avatar_url' => $res->json('profile_pic'),
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
        }

        return $events;
    }

    /**
     * Map 1 Messenger messaging-event → DTO chuẩn. PSID người gửi = conversation
     * (Send API địa chỉ theo PSID). Bỏ echo (tin do page tự gửi) — type unknown.
     *
     * @param  array<string,mixed>  $event
     */
    private function mapEvent(array $event, ?string $pageId): MessagingWebhookEventDTO
    {
        $senderId = isset($event['sender']['id']) ? (string) $event['sender']['id'] : null;
        $occurredAt = isset($event['timestamp']) ? CarbonImmutable::createFromTimestampMs((int) $event['timestamp']) : null;

        if (isset($event['message']) && empty($event['message']['is_echo'])) {
            $msg = (array) $event['message'];

            // Normalize body: plain text first.
            $body = isset($msg['text']) && (string) $msg['text'] !== '' ? (string) $msg['text'] : null;
            $attachments = [];
            $kind = MessageKind::Text;

            // Sticker: Facebook sends `sticker_id` + an attachment with type=image and
            // payload.sticker_id + payload.url. Treat the attachment url as the sticker image.
            foreach ((array) ($msg['attachments'] ?? []) as $watt) {
                $wtype = (string) ($watt['type'] ?? '');
                $payload = (array) ($watt['payload'] ?? []);
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
                if ($wtype === 'fallback' || $wtype === 'share') {
                    // Shared link: populate body if no text yet.
                    $attUrl = (string) ($payload['url'] ?? '');
                    if ($attUrl !== '' && $body === null) {
                        $attTitle = isset($payload['title']) && (string) $payload['title'] !== ''
                            ? (string) $payload['title']
                            : null;
                        $body = $attTitle !== null ? $attTitle.' '.$attUrl : $attUrl;
                    }

                    continue;
                }
                // Other attachment types (image/video/audio/file without sticker).
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

            return new MessagingWebhookEventDTO(
                provider: $this->code(),
                type: MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED,
                externalShopId: $pageId,
                externalConversationId: $senderId,            // PSID buyer = conversation
                externalMessageId: (string) ($msg['mid'] ?? ''),
                buyerExternalId: $senderId,
                occurredAt: $occurredAt,
                raw: $event,
                kind: $kind,
                body: $body,
                attachments: $attachments,
            );
        }
        if (isset($event['delivery'])) {
            return new MessagingWebhookEventDTO($this->code(), MessagingWebhookEventDTO::TYPE_MESSAGE_DELIVERED, $pageId, $senderId, null, $senderId, $occurredAt, $event);
        }
        if (isset($event['read'])) {
            return new MessagingWebhookEventDTO($this->code(), MessagingWebhookEventDTO::TYPE_MESSAGE_READ, $pageId, $senderId, null, $senderId, $occurredAt, $event);
        }

        return new MessagingWebhookEventDTO($this->code(), MessagingWebhookEventDTO::TYPE_UNKNOWN, $pageId, $senderId, null, $senderId, $occurredAt, $event);
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

        $res = Http::timeout(30)->get($this->graphUrl($threadId), [
            'fields' => "messages.limit({$limit}){id,message,created_time,from,sticker,attachments{mime_type,name,image_data,video_data,file_url,type,title,url}}",
            'access_token' => $auth->accessToken,
        ]);
        if (! $res->successful()) {
            $this->throwGraphError($res, 'fetchMessages');
        }

        $items = [];
        foreach ((array) $res->json('messages.data', []) as $row) {
            $fromId = (string) ($row['from']['id'] ?? '');
            $direction = $fromId === $auth->externalShopId ? MessageDirection::Outbound : MessageDirection::Inbound;

            $body = ($row['message'] ?? '') !== '' ? (string) $row['message'] : null;
            $attachments = [];

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
            foreach ((array) ($row['attachments']['data'] ?? []) as $att) {
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
                $attachments[] = $this->mapBackfillAttachment((array) $att);
            }

            // When the message text is empty, fall back to the share URL as the body
            // so the FE can linkify it.
            if ($body === null && $shareUrl !== null) {
                $body = $shareUrl;
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
            );
        }

        return new Page($items, null, false);
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
            'fields' => "id,message,permalink_url,created_time,comments.limit({$commentLimit}){id,message,created_time,from{id,name},parent}",
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
                    'message' => isset($comment['message']) && (string) $comment['message'] !== '' ? (string) $comment['message'] : null,
                    'created_time' => $comment['created_time'] ?? null,
                    'post_id' => $postId,
                    'post_message' => $postMessage,
                    'post_permalink' => $postPermalink,
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

    public function outboundWindow(): OutboundWindowPolicyDTO
    {
        return new OutboundWindowPolicyDTO(
            freeWindowHours: 24,
            requiresTag: true,
            allowedTags: ['CONFIRMED_EVENT_UPDATE', 'POST_PURCHASE_UPDATE', 'ACCOUNT_UPDATE'],
        );
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

    public function replyToComment(MessagingAuthContext $auth, string $commentId, string $message): string
    {
        $res = Http::post($this->graphUrl($commentId.'/comments'), [
            'message' => $message,
            'access_token' => $auth->accessToken,
        ]);

        if (! $res->successful()) {
            $this->throwGraphError($res, 'replyToComment');
        }

        return (string) $res->json('id');
    }

    public function privateReplyToComment(MessagingAuthContext $auth, string $commentId, string $message): void
    {
        $res = Http::post($this->graphUrl('me/messages'), [
            'recipient' => ['comment_id' => $commentId],
            'message' => ['text' => $message],
            'access_token' => $auth->accessToken,
        ]);

        if (! $res->successful()) {
            $this->throwGraphError($res, 'privateReplyToComment');
        }
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
