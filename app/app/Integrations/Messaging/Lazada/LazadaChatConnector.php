<?php

namespace CMBcoreSeller\Integrations\Messaging\Lazada;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaSigner;
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
use CMBcoreSeller\Integrations\Messaging\Exceptions\UnsupportedOperation;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Request;

/**
 * Lazada IM chat connector — SPEC-0024 S8 (BEST-EFFORT / backlog, §11 Q3).
 *
 * CẢNH BÁO: Lazada Open Platform IM API có lịch sử thay đổi & giới hạn vùng —
 * SPEC §11 Q3 chưa chốt sàn còn hỗ trợ không. Connector này faithful theo tài
 * liệu nhưng PHẢI verify với sandbox thật trước khi bật production
 * (`INTEGRATIONS_MESSAGING` không gồm `lazada_chat` mặc định).
 *
 * Tái dùng hạ tầng Lazada của Channels: `config('integrations.lazada')`,
 * verify webhook (header HMAC / body-sign), {@see LazadaSigner} (UPPERCASE hex).
 * OAuth dùng chung token với orders (ADR-0019).
 */
class LazadaChatConnector implements MessagingConnector
{
    private const SIG_HEADERS = ['X-Lazop-Sign', 'Lazop-Sign', 'X-Lzd-Sign', 'X-Signature'];

    public function code(): string
    {
        return 'lazada_chat';
    }

    public function displayName(): string
    {
        return 'Lazada Chat';
    }

    public function capabilities(): array
    {
        return [
            'inbound.webhook' => false,   // Lazada IM has NO push webhook — polling only (SPEC-0024 §8)
            'inbound.polling' => true,
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
        throw UnsupportedOperation::for($this->code(), 'buildAuthorizationUrl (dùng chung OAuth Lazada orders — ADR-0019)');
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
        // Lazada App Push đăng ký ở Open Platform console.
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $secret = (string) (config('integrations.lazada.app_secret') ?? '');
        if ($secret === '') {
            return false;
        }
        $body = (string) $request->getContent();

        // (A) header HMAC-SHA256(rawBody) hex.
        foreach (self::SIG_HEADERS as $h) {
            $provided = strtolower(trim((string) $request->headers->get($h, '')));
            if ($provided !== '' && hash_equals(strtolower(hash_hmac('sha256', $body, $secret)), $provided)) {
                return true;
            }
        }

        // (B) body có `sign`: ký trên các key còn lại sort & concat {k}{v}.
        $json = json_decode($body, true);
        if (is_array($json) && isset($json['sign']) && is_string($json['sign'])) {
            $provided = strtolower(trim($json['sign']));
            unset($json['sign']);
            ksort($json, SORT_STRING);
            $str = '';
            foreach ($json as $k => $v) {
                $str .= $k.(is_scalar($v) ? (string) $v : (string) json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            }

            return hash_equals(strtolower(hash_hmac('sha256', $str, $secret)), $provided);
        }

        return false;
    }

    public function parseWebhook(Request $request): MessagingWebhookEventDTO
    {
        $body = json_decode($request->getContent() ?: '{}', true) ?: [];
        $data = (array) ($body['data'] ?? []);

        $sessionId = $data['session_id'] ?? ($data['conversation_id'] ?? null);
        $messageId = $data['message_id'] ?? null;
        $buyerId = $data['from_account_id'] ?? ($data['buyer_id'] ?? null);
        $hasMessage = $sessionId !== null && $messageId !== null;

        return new MessagingWebhookEventDTO(
            provider: $this->code(),
            type: $hasMessage ? MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED : MessagingWebhookEventDTO::TYPE_UNKNOWN,
            externalShopId: isset($data['seller_id']) ? (string) $data['seller_id'] : null,
            externalConversationId: $sessionId !== null ? (string) $sessionId : null,
            externalMessageId: $messageId !== null ? (string) $messageId : null,
            buyerExternalId: $buyerId !== null ? (string) $buyerId : null,
            occurredAt: isset($body['timestamp']) ? CarbonImmutable::createFromTimestampMs((int) $body['timestamp']) : null,
            raw: $body,
        );
    }

    public function parseWebhookEvents(Request $request): array
    {
        return [$this->parseWebhook($request)];
    }

    /**
     * Poll Lazada IM `/im/session/list` (GET) để lấy danh sách conversation.
     *
     * Lazada IM KHÔNG có webhook outbound polling — đây là cách DUY NHẤT nhận chat buyer.
     *
     * Request params (doc chính thức GetSessionList):
     *   - `start_time`      (ms) — bắt buộc; first-page = current time, next-page = next_start_time từ response.
     *   - `page_size`       — bắt buộc.
     *   - `last_session_id` — optional; từ $query['cursor'] (cursor = last_session_id của page trước).
     *
     * Pagination fields trong response (doc):
     *   - `has_more`         Boolean (string "true"/"false" hoặc bool) — còn trang sau.
     *   - `next_start_time`  Number — timestamp ms của trang sau (truyền làm start_time).
     *   - `last_session_id`  String — session_id cuối trang (truyền làm last_session_id + nextCursor).
     *
     * nextCursor = `last_session_id` (caller cần pass cả start_time lần sau = next_start_time;
     * ta encode cả hai vào cursor dạng "lastSessionId|nextStartTime").
     */
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
        $cfg = (array) config('integrations.lazada', []);
        $path = '/im/session/list';

        // start_time (doc /im/session/list): TRANG ĐẦU = current timestamp; TRANG SAU = next_start_time
        // của response trước. KHÔNG dùng `since` làm start_time — start_time là CẬN TRÊN (API lùi dần về
        // quá khứ); truyền mốc cũ sẽ chỉ trả session CŨ HƠN ⇒ bỏ sót tin mới. `since` chỉ dùng để DỪNG.
        $startTime = (string) (int) (microtime(true) * 1000);
        $lastSessionId = null;
        $sinceMs = (isset($query['since']) && $query['since'] instanceof CarbonImmutable)
            ? (int) $query['since']->valueOf()
            : null;

        // cursor = "lastSessionId|nextStartTime"
        if (! empty($query['cursor']) && str_contains((string) $query['cursor'], '|')) {
            [$lastSessionId, $startTime] = explode('|', (string) $query['cursor'], 2);
        }

        $pageSize = (string) ((int) ($query['pageSize'] ?? 50));

        $params = [
            'app_key' => (string) ($cfg['app_key'] ?? ''),
            'partner_id' => (string) ($cfg['partner_id'] ?? 'lazop-sdk-php-20180422'),
            'access_token' => $auth->accessToken,
            'sign_method' => 'sha256',
            'timestamp' => (string) (int) (microtime(true) * 1000),
            'start_time' => $startTime,
            'page_size' => $pageSize,
        ];
        if ($lastSessionId !== null && $lastSessionId !== '') {
            $params['last_session_id'] = $lastSessionId;
        }

        $params['sign'] = LazadaSigner::sign((string) ($cfg['app_secret'] ?? ''), $path, $params);

        $base = rtrim((string) ($cfg['api_base_url'] ?? 'https://api.lazada.vn/rest'), '/');
        $response = Http::timeout(30)->retry(2, 500, throw: false)->get($base.$path, $params);

        $data = (array) ($response->json('data') ?? []);
        $sessionList = (array) ($data['session_list'] ?? []);

        $items = array_values(array_map(function (array $s) {
            $lastMsgTime = isset($s['last_message_time'])
                ? CarbonImmutable::createFromTimestampMs((int) $s['last_message_time'])
                : null;

            return new ConversationDTO(
                externalConversationId: (string) ($s['session_id'] ?? ''),
                buyerExternalId: (string) ($s['buyer_id'] ?? ''),
                buyerName: isset($s['title']) ? (string) $s['title'] : null,
                buyerAvatarUrl: isset($s['head_url']) && $s['head_url'] !== '' ? (string) $s['head_url'] : null,
                lastMessageAt: $lastMsgTime,
                lastMessagePreview: isset($s['summary']) ? (string) $s['summary'] : null,
                unreadCount: isset($s['unread_count']) ? (int) $s['unread_count'] : null,
                raw: $s,
            );
        }, array_filter($sessionList, 'is_array')));

        // has_more: doc nói Boolean nhưng response example dùng string "true"/"false" — normalize.
        $hasMore = filter_var($data['has_more'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // Incremental: khi đã lùi tới session CŨ HƠN mốc `since` ⇒ phần còn lại đều cũ ⇒ lọc bỏ +
        // dừng phân trang (tránh quét lại toàn bộ lịch sử mỗi lần poll — vượt rate-limit Lazada).
        if ($sinceMs !== null) {
            $before = count($items);
            $items = array_values(array_filter(
                $items,
                fn (ConversationDTO $c) => $c->lastMessageAt === null || $c->lastMessageAt->valueOf() >= $sinceMs,
            ));
            if (count($items) < $before) {
                $hasMore = false;
            }
        }

        // nextCursor = "last_session_id|next_start_time" — encode cả hai để trang sau dùng đúng.
        $nextCursor = null;
        if ($hasMore) {
            $respLastSessionId = (string) ($data['last_session_id'] ?? '');
            $respNextStartTime = (string) ($data['next_start_time'] ?? '');
            $nextCursor = $respLastSessionId.'|'.$respNextStartTime;
        }

        return new Page(items: $items, nextCursor: $nextCursor, hasMore: $hasMore);
    }

    /**
     * Poll Lazada IM `/im/message/list` (GET) để lấy messages trong 1 conversation.
     *
     * Request params (doc chính thức GetMessages):
     *   - `session_id`      — bắt buộc.
     *   - `start_time`      (ms) — bắt buộc; first-page = current time, next-page = next_start_time.
     *   - `page_size`       — bắt buộc.
     *   - `last_message_id` — optional; cursor từ page trước.
     *
     * `content` field trong message/list là JSON string — decode defensively.
     *
     * template_id → kind/body/attachments mapping (doc):
     *   1  = text   → body = content.txt
     *   3  = image  → attachment(externalUrl=content.img_url, width, height)
     *   6  = video  → attachment(externalUrl=content.video_url ?? content.url, durationMs)
     *   10006 = item card  → body = '[Sản phẩm]'
     *   10007 = order card → body = '[Đơn hàng]'
     *   else → body = '[{template_id}]'
     *
     * from_account_type: 1=buyer (Inbound), 2=seller (Outbound).
     * Pagination: has_more + last_message_id (nextCursor).
     */
    public function fetchMessages(MessagingAuthContext $auth, string $externalConversationId, array $query = []): Page
    {
        $cfg = (array) config('integrations.lazada', []);
        $path = '/im/message/list';

        // start_time (doc /im/message/list): TRANG ĐẦU = current timestamp; KHÔNG dùng `since` làm
        // start_time (cận trên). `since` chỉ để DỪNG khi đã lùi quá mốc sync. cursor = "lastMessageId|nextStartTime".
        $startTime = (string) (int) (microtime(true) * 1000);
        $lastMessageId = null;
        $sinceMs = (isset($query['since']) && $query['since'] instanceof CarbonImmutable)
            ? (int) $query['since']->valueOf()
            : null;
        if (! empty($query['cursor']) && str_contains((string) $query['cursor'], '|')) {
            [$lastMessageId, $startTime] = explode('|', (string) $query['cursor'], 2);
        }

        $pageSize = (string) ((int) ($query['pageSize'] ?? 50));

        $params = [
            'app_key' => (string) ($cfg['app_key'] ?? ''),
            'partner_id' => (string) ($cfg['partner_id'] ?? 'lazop-sdk-php-20180422'),
            'access_token' => $auth->accessToken,
            'sign_method' => 'sha256',
            'timestamp' => (string) (int) (microtime(true) * 1000),
            'session_id' => $externalConversationId,
            'start_time' => $startTime,
            'page_size' => $pageSize,
        ];
        if ($lastMessageId !== null && $lastMessageId !== '') {
            $params['last_message_id'] = $lastMessageId;
        }

        $params['sign'] = LazadaSigner::sign((string) ($cfg['app_secret'] ?? ''), $path, $params);

        $base = rtrim((string) ($cfg['api_base_url'] ?? 'https://api.lazada.vn/rest'), '/');
        $response = Http::timeout(30)->retry(2, 500, throw: false)->get($base.$path, $params);

        $data = (array) ($response->json('data') ?? []);
        $messageList = (array) ($data['message_list'] ?? []);

        $items = array_values(array_map(function (array $m) use ($externalConversationId) {
            // content là JSON string per doc — decode defensively
            $content = json_decode($m['content'] ?? '{}', true) ?: [];

            $templateId = (int) ($m['template_id'] ?? 0);
            $fromAccountType = (int) ($m['from_account_type'] ?? 0);
            $direction = $fromAccountType === 1 ? MessageDirection::Inbound : MessageDirection::Outbound;

            $kind = MessageKind::Text;
            $body = null;
            $attachments = [];

            switch ($templateId) {
                case 1: // text
                    $kind = MessageKind::Text;
                    $body = (string) ($content['txt'] ?? '');
                    break;

                case 3: // image
                    $kind = MessageKind::Image;
                    $attachments = [new MediaRefDTO(
                        kind: MessageKind::Image,
                        mime: 'image/jpeg',
                        externalUrl: isset($content['img_url']) ? (string) $content['img_url'] : null,
                        width: isset($content['width']) ? (int) $content['width'] : null,
                        height: isset($content['height']) ? (int) $content['height'] : null,
                    )];
                    break;

                case 6: // video
                    $kind = MessageKind::Video;
                    $videoUrl = (string) ($content['video_url'] ?? $content['url'] ?? '');
                    $durationMs = isset($content['duration']) ? (int) ((float) $content['duration'] * 1000) : null;
                    $attachments = [new MediaRefDTO(
                        kind: MessageKind::Video,
                        mime: 'video/mp4',
                        externalUrl: $videoUrl !== '' ? $videoUrl : null,
                        durationMs: $durationMs,
                    )];
                    break;

                case 10006: // item card
                    $kind = MessageKind::Text;
                    $body = '[Sản phẩm]';
                    break;

                case 10007: // order card
                    $kind = MessageKind::Text;
                    $body = '[Đơn hàng]';
                    break;

                default:
                    $kind = MessageKind::Text;
                    $body = '['.$templateId.']';
                    break;
            }

            $sentAt = isset($m['send_time'])
                ? CarbonImmutable::createFromTimestampMs((int) $m['send_time'])
                : null;

            $buyerExternalId = (string) ($m['from_account_id'] ?? $m['buyer_id'] ?? '');

            return new MessageDTO(
                externalConversationId: $externalConversationId,
                externalMessageId: (string) ($m['message_id'] ?? ''),
                buyerExternalId: $buyerExternalId,
                direction: $direction,
                kind: $kind,
                body: $body,
                attachments: $attachments,
                sentAt: $sentAt,
                raw: $m,
            );
        }, array_filter($messageList, 'is_array')));

        // has_more: normalize string/bool per doc example pattern
        $hasMore = filter_var($data['has_more'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // Incremental: lọc bỏ + dừng khi gặp message CŨ HƠN mốc `since` (đã quét hết phần mới).
        if ($sinceMs !== null) {
            $before = count($items);
            $items = array_values(array_filter(
                $items,
                fn (MessageDTO $m) => $m->sentAt === null || $m->sentAt->valueOf() >= $sinceMs,
            ));
            if (count($items) < $before) {
                $hasMore = false;
            }
        }

        // nextCursor = "last_message_id|next_start_time" (doc: trang sau cần next_start_time, kèm last_message_id).
        $nextCursor = null;
        if ($hasMore) {
            $respLastMessageId = (string) ($data['last_message_id'] ?? '');
            $respNextStartTime = (string) ($data['next_start_time'] ?? '');
            if ($respLastMessageId !== '' || $respNextStartTime !== '') {
                $nextCursor = $respLastMessageId.'|'.$respNextStartTime;
            }
        }

        return new Page(items: $items, nextCursor: $nextCursor, hasMore: $hasMore);
    }

    /**
     * `template_id` của Lazada IM `/im/message/send` chọn loại nội dung; field
     * content đi kèm tương ứng (txt / img_url+width+height / item_id / order_id /
     * promotion_id). Giá trị int chuẩn theo doc Lazada IM Open API + SDK
     * `lazada-openapi`. (Xem docs/04-channels/lazada-chat-setup.md — verify lại
     * trên sandbox vì doc Lazada gated.)
     */
    private const TEMPLATE_TEXT = 1;

    // template_id 3 = image per official Lazada IM Open API doc (GetMessages / SendMessage reference).
    private const TEMPLATE_IMAGE = 3;

    public function sendText(MessagingAuthContext $auth, string $externalConversationId, string $body, array $opts = []): SendResultDTO
    {
        return $this->send($auth, $externalConversationId, self::TEMPLATE_TEXT, ['txt' => $body]);
    }

    public function sendMedia(MessagingAuthContext $auth, string $externalConversationId, MediaRefDTO $media, array $opts = []): SendResultDTO
    {
        if ($media->kind->value !== 'image') {
            throw UnsupportedOperation::for($this->code(), 'sendMedia ('.$media->kind->value.') — Lazada IM chỉ hỗ trợ ảnh');
        }

        // Phase D: upload-first — fetch bytes from our signed URL, upload to Lazada CDN,
        // then send the CDN URL. Per official doc /image/upload: param `image` = binary stream,
        // JPG/PNG ≤1MB; returns data.image.url. Binary is NOT included in the signed params.
        if (! $media->externalUrl) {
            throw new \RuntimeException('Lazada sendMedia cần externalUrl (signed) để fetch bytes ảnh');
        }

        $fetch = Http::timeout(30)->get($media->externalUrl);
        if (! $fetch->successful()) {
            throw new \RuntimeException('Không tải được media để upload: HTTP '.$fetch->status());
        }
        $bytes = $fetch->body();

        $cfg = (array) config('integrations.lazada', []);
        $base = rtrim((string) ($cfg['base_url'] ?? 'https://api.lazada.vn/rest'), '/');
        $uploadPath = '/image/upload';

        $uploadSysParams = [
            'app_key' => (string) ($cfg['app_key'] ?? ''),
            'partner_id' => (string) ($cfg['partner_id'] ?? 'lazop-sdk-php-20180422'),
            'access_token' => $auth->accessToken,
            'sign_method' => 'sha256',
            'timestamp' => (string) (int) (microtime(true) * 1000),
        ];
        // Sign over system params only — the binary field `image` is NOT signed (per SDK pattern).
        $uploadSysParams['sign'] = LazadaSigner::sign((string) ($cfg['app_secret'] ?? ''), $uploadPath, $uploadSysParams);

        $uploadResp = Http::attach('image', $bytes, $media->filename ?? 'image.jpg')
            ->timeout(30)
            ->post($base.$uploadPath.'?'.http_build_query($uploadSysParams));

        $uploadCode = $uploadResp->json('code');
        if (! $uploadResp->successful() || ($uploadCode !== null && (string) $uploadCode !== '0' && (string) $uploadCode !== '')) {
            throw new \RuntimeException('Lazada image/upload failed: '.$uploadResp->body());
        }
        $cdnUrl = $uploadResp->json('data.image.url');
        if (! $cdnUrl) {
            throw new \RuntimeException('Lazada image/upload missing data.image.url: '.$uploadResp->body());
        }

        return $this->send($auth, $externalConversationId, self::TEMPLATE_IMAGE, array_filter([
            'img_url' => $cdnUrl,
            'width' => $media->width,
            'height' => $media->height,
        ], fn ($v) => $v !== null));
    }

    public function sendTemplate(MessagingAuthContext $auth, string $externalConversationId, string $templateKey, array $vars = [], array $opts = []): SendResultDTO
    {
        throw UnsupportedOperation::for($this->code(), 'sendTemplate');
    }

    public function outboundWindow(): OutboundWindowPolicyDTO
    {
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

    public function replyToComment(MessagingAuthContext $auth, string $commentId, string $message): string
    {
        throw UnsupportedOperation::for($this->code(), 'replyToComment');
    }

    public function privateReplyToComment(MessagingAuthContext $auth, string $commentId, string $message): void
    {
        throw UnsupportedOperation::for($this->code(), 'privateReplyToComment');
    }

    /**
     * Gửi message qua Lazada IM `/im/message/send`.
     *
     * Tham số THẬT (Lazada Open Platform + SDK lazada-openapi): system params
     * (app_key/sign_method/timestamp/access_token/sign) + `session_id` +
     * `template_id` + field content phẳng (`txt` | `img_url`,`width`,`height` |
     * `item_id` | `order_id`). Ký bằng {@see LazadaSigner} (sha256, UPPERCASE).
     * Lazada nhận business params ở query/form nên đưa hết vào 1 map đã ký.
     *
     * @param  array<string,scalar>  $content  field nội dung theo template_id
     */
    private function send(MessagingAuthContext $auth, string $sessionId, int $templateId, array $content): SendResultDTO
    {
        $cfg = (array) config('integrations.lazada', []);
        $base = rtrim((string) ($cfg['base_url'] ?? 'https://api.lazada.vn/rest'), '/');
        $path = '/im/message/send';

        $params = array_merge([
            'app_key' => (string) ($cfg['app_key'] ?? ''),
            'partner_id' => (string) ($cfg['partner_id'] ?? 'lazop-sdk-php-20180422'),
            'access_token' => $auth->accessToken,
            'sign_method' => 'sha256',
            'timestamp' => (string) (int) (microtime(true) * 1000),
            'session_id' => $sessionId,
            'template_id' => (string) $templateId,
        ], array_map(fn ($v) => (string) $v, $content));

        $params['sign'] = LazadaSigner::sign((string) ($cfg['app_secret'] ?? ''), $path, $params);

        $response = Http::asForm()->timeout(30)->retry(2, 500, throw: false)->post($base.$path, $params);

        // Lazada: thành công khi `code` rỗng/'0'. Có `code` khác ⇒ lỗi (kèm message để debug).
        $code = $response->json('code');
        if (! $response->successful() || ($code !== null && (string) $code !== '0' && (string) $code !== '')) {
            throw new \RuntimeException('Lazada IM send failed: '.$response->body());
        }

        return new SendResultDTO(
            externalMessageId: (string) ($response->json('data.message_id') ?? $response->json('data.messageId') ?? ''),
            sentAt: CarbonImmutable::now(),
            raw: (array) $response->json('data', []),
        );
    }
}
