<?php

namespace CMBcoreSeller\Integrations\Messaging\TikTok;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;
use CMBcoreSeller\Integrations\Channels\TikTok\TikTokSigner;
use CMBcoreSeller\Integrations\Messaging\Contracts\MessagingConnector;
use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
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
 * TikTok Shop chat (Customer Service IM) connector — SPEC-0024 S4 / ADR-0019.
 *
 * Tái dùng hạ tầng TikTok của Channels: cùng app_key/app_secret
 * (`config('integrations.tiktok')`), cùng scheme verify webhook
 * (Authorization = HMAC-SHA256(app_secret, app_key + rawBody), hex), cùng
 * {@see TikTokSigner} cho request ký. OAuth dùng chung token với orders (ADR-0019)
 * ⇒ buildAuthorizationUrl/exchange/refresh ném UnsupportedOperation (đi qua
 * Channels OAuth).
 *
 * MỨC ĐỘ XÁC MINH: verify signature + parseWebhook + outboundWindow + shape
 * sendText (Http::fake) test được. Live cần TikTok Shop API approval + region
 * (TikTok IM giới hạn vùng) — endpoint/version `202309` theo tài liệu, xác nhận
 * khi có sandbox.
 */
class TikTokChatConnector implements MessagingConnector
{
    public function code(): string
    {
        return 'tiktok_chat';
    }

    public function displayName(): string
    {
        return 'TikTok Shop Chat';
    }

    public function capabilities(): array
    {
        return [
            'inbound.webhook' => true,
            'inbound.polling' => false,   // có list API; bật ở follow-up
            'outbound.text' => true,
            'outbound.image' => true,
            'outbound.video' => false,
            'outbound.file' => false,
            'outbound.template' => false,
            'read_receipt' => true,
            'typing' => false,
        ];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    public function buildAuthorizationUrl(string $state, array $opts = []): string
    {
        throw UnsupportedOperation::for($this->code(), 'buildAuthorizationUrl (dùng chung OAuth với TikTok orders — ADR-0019)');
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
        // TikTok webhook đăng ký ở Partner console (không per-shop API).
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $cfg = (array) config('integrations.tiktok', []);
        $secret = (string) ($cfg['app_secret'] ?? '');
        $appKey = (string) ($cfg['app_key'] ?? '');
        if ($secret === '' || $appKey === '') {
            return false;
        }
        $provided = strtolower(trim((string) $request->headers->get('Authorization', '')));
        if ($provided === '') {
            return false;
        }
        $expected = hash_hmac('sha256', $appKey.$request->getContent(), $secret);

        return hash_equals($expected, $provided);
    }

    public function parseWebhook(Request $request): MessagingWebhookEventDTO
    {
        $body = json_decode($request->getContent() ?: '{}', true) ?: [];
        $data = (array) ($body['data'] ?? []);

        // TikTok message webhook (type code IM) — data mang conversation/message.
        // Per docv2_page_14-new-message.md: data.conversation_id, data.message_id,
        // data.sender.im_user_id, data.sender.role, data.type (TEXT/IMAGE/VIDEO/...),
        // data.content (JSON string).
        $conversationId = $data['conversation_id'] ?? null;
        $messageId = $data['message_id'] ?? null;
        $senderId = $data['sender']['im_user_id'] ?? ($data['from_user_id'] ?? null);
        $hasMessage = $conversationId !== null && $messageId !== null;

        // Echo guard: bỏ tin do shop/agent/system tự gửi (không phải buyer) — mirror Facebook is_echo.
        // Per docv2_page_customer-service-api-overview.md: sender.role = BUYER | SHOP |
        // CUSTOMER_SERVICE | SYSTEM | ROBOT. Nếu role có mặt và != BUYER thì là tin shop gửi/echo,
        // ack nhưng không ingest vào hộp thư (tránh auto-reply nhầm).
        $senderRole = isset($data['sender']['role']) ? strtoupper((string) $data['sender']['role']) : null;
        if ($senderRole !== null && $senderRole !== 'BUYER') {
            return new MessagingWebhookEventDTO(
                $this->code(),
                MessagingWebhookEventDTO::TYPE_UNKNOWN,
                isset($body['shop_id']) ? (string) $body['shop_id'] : null,
                raw: $body,
            );
        }

        // Parse normalized kind/body/attachments from TikTok's type + content (JSON string).
        // doc: data.type = "TEXT"|"IMAGE"|"VIDEO"|...; data.content = JSON string.
        $kind = MessageKind::Text;
        $parsedBody = null;
        $attachments = [];

        if ($hasMessage) {
            $messageType = strtoupper((string) ($data['type'] ?? 'TEXT'));
            // data.content is a JSON-encoded string per docs: {"content":"text"} for TEXT,
            // {"url":"...","width":"304","height":"290"} for IMAGE,
            // {"url":"...","width":640,"height":360,"duration":"20.504",...} for VIDEO.
            $rawContent = $data['content'] ?? '';
            $msgContent = is_string($rawContent)
                ? (json_decode($rawContent, true) ?: [])
                : (array) $rawContent;

            switch ($messageType) {
                case 'TEXT':
                    $kind = MessageKind::Text;
                    // TEXT content: {"content": "simple text"} — key is "content" per docs.
                    $parsedBody = isset($msgContent['content']) ? (string) $msgContent['content'] : null;
                    break;

                case 'IMAGE':
                    $kind = MessageKind::Image;
                    // IMAGE content: {"url":"...","width":"304","height":"290"} — verify sandbox.
                    $attachments[] = new MediaRefDTO(
                        kind: MessageKind::Image,
                        mime: 'image/jpeg',
                        externalUrl: isset($msgContent['url']) ? (string) $msgContent['url'] : null,
                        width: isset($msgContent['width']) ? (int) $msgContent['width'] : null,
                        height: isset($msgContent['height']) ? (int) $msgContent['height'] : null,
                    );
                    break;

                case 'VIDEO':
                    $kind = MessageKind::Video;
                    // VIDEO content: {"url":"...","width":640,"height":360,"duration":"20.504",...}
                    // duration field is a string in seconds — verify sandbox.
                    $attachments[] = new MediaRefDTO(
                        kind: MessageKind::Video,
                        mime: 'video/mp4',
                        externalUrl: isset($msgContent['url']) ? (string) $msgContent['url'] : null,
                        durationMs: isset($msgContent['duration'])
                            ? (int) ((float) $msgContent['duration'] * 1000)
                            : null,
                        width: isset($msgContent['width']) ? (int) $msgContent['width'] : null,
                        height: isset($msgContent['height']) ? (int) $msgContent['height'] : null,
                    );
                    break;

                default:
                    // PRODUCT_CARD, ORDER_CARD, EMOTICONS, COUPON_CARD, LOGISTICS_CARD,
                    // ALLOCATED_SERVICE, NOTIFICATION, BUYER_ENTER_FROM_*, OTHER → text label.
                    $kind = MessageKind::Text;
                    $parsedBody = '['.$messageType.']';
                    break;
            }
        }

        return new MessagingWebhookEventDTO(
            provider: $this->code(),
            type: $hasMessage ? MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED : MessagingWebhookEventDTO::TYPE_UNKNOWN,
            externalShopId: isset($body['shop_id']) ? (string) $body['shop_id'] : null,
            externalConversationId: $conversationId !== null ? (string) $conversationId : null,
            externalMessageId: $messageId !== null ? (string) $messageId : null,
            buyerExternalId: $senderId !== null ? (string) $senderId : null,
            occurredAt: isset($body['timestamp']) ? CarbonImmutable::createFromTimestamp((int) $body['timestamp']) : null,
            raw: $body,
            kind: $hasMessage ? $kind : null,
            body: $hasMessage ? $parsedBody : null,
            attachments: $hasMessage ? $attachments : [],
        );
    }

    public function parseWebhookEvents(Request $request): array
    {
        // TikTok IM push 1 event / POST — wrap. Nâng lên loop nếu sàn batch.
        return [$this->parseWebhook($request)];
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
        throw UnsupportedOperation::for($this->code(), 'fetchConversations (polling — follow-up)');
    }

    public function fetchMessages(MessagingAuthContext $auth, string $externalConversationId, array $query = []): Page
    {
        throw UnsupportedOperation::for($this->code(), 'fetchMessages');
    }

    public function sendText(MessagingAuthContext $auth, string $externalConversationId, string $body, array $opts = []): SendResultDTO
    {
        return $this->send($auth, $externalConversationId, ['type' => 'TEXT', 'content' => json_encode(['content' => $body], JSON_UNESCAPED_UNICODE)]);
    }

    public function sendMedia(MessagingAuthContext $auth, string $externalConversationId, MediaRefDTO $media, array $opts = []): SendResultDTO
    {
        if ($media->kind->value !== 'image') {
            throw UnsupportedOperation::for($this->code(), 'sendMedia ('.$media->kind->value.')');
        }

        // Phase D: upload-first — fetch bytes from our signed URL, upload to TikTok CDN via
        // POST /customer_service/202309/images/upload (multipart field `data`), then send.
        // Per official doc: returns data.url, data.width, data.height.
        if (! $media->externalUrl) {
            throw new \RuntimeException('TikTok sendMedia cần externalUrl (signed) để fetch bytes ảnh');
        }

        $fetch = Http::timeout(30)->get($media->externalUrl);
        if (! $fetch->successful()) {
            throw new \RuntimeException('Không tải được media để upload: HTTP '.$fetch->status());
        }
        $bytes = $fetch->body();

        $cfg = (array) config('integrations.tiktok', []);
        $appKey = (string) ($cfg['app_key'] ?? '');
        $appSecret = (string) ($cfg['app_secret'] ?? '');
        $base = rtrim((string) ($cfg['base_url'] ?? 'https://open-api.tiktokglobalshop.com'), '/');
        $uploadPath = '/customer_service/202309/images/upload';

        $uploadQuery = [
            'app_key' => $appKey,
            'shop_cipher' => (string) ($auth->extra['shop_cipher'] ?? ''),
            'timestamp' => (string) time(),
        ];
        // Multipart: body NOT included in sign (multipart=true).
        $uploadQuery['sign'] = TikTokSigner::sign($appSecret, $uploadPath, $uploadQuery, '', true);

        $uploadResp = Http::attach('data', $bytes, $media->filename ?? 'image.jpg')
            ->withHeaders(['x-tts-access-token' => $auth->accessToken])
            ->timeout(30)
            ->post($base.$uploadPath.'?'.http_build_query($uploadQuery));

        if (! $uploadResp->successful() || (int) $uploadResp->json('code', -1) !== 0) {
            throw new \RuntimeException('TikTok images/upload failed: '.$uploadResp->body());
        }
        $cdnUrl = $uploadResp->json('data.url');
        if (! $cdnUrl) {
            throw new \RuntimeException('TikTok images/upload missing data.url: '.$uploadResp->body());
        }
        $cdnWidth = $uploadResp->json('data.width');
        $cdnHeight = $uploadResp->json('data.height');

        $imageContent = array_filter([
            'url' => $cdnUrl,
            'width' => $cdnWidth,
            'height' => $cdnHeight,
        ], fn ($v) => $v !== null);

        return $this->send($auth, $externalConversationId, [
            'type' => 'IMAGE',
            'content' => json_encode($imageContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function sendTemplate(MessagingAuthContext $auth, string $externalConversationId, string $templateKey, array $vars = [], array $opts = []): SendResultDTO
    {
        throw UnsupportedOperation::for($this->code(), 'sendTemplate');
    }

    public function outboundWindow(): OutboundWindowPolicyDTO
    {
        // TikTok không có hard 24h như Facebook (có chính sách spam) — không chặn cứng.
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
     * Gửi message qua TikTok IM (Customer Service 202309), ký bằng TikTokSigner.
     *
     * @param  array<string,mixed>  $payload
     */
    private function send(MessagingAuthContext $auth, string $conversationId, array $payload): SendResultDTO
    {
        $cfg = (array) config('integrations.tiktok', []);
        $appKey = (string) ($cfg['app_key'] ?? '');
        $appSecret = (string) ($cfg['app_secret'] ?? '');
        $base = rtrim((string) ($cfg['base_url'] ?? 'https://open-api.tiktokglobalshop.com'), '/');
        $path = "/customer_service/202309/conversations/{$conversationId}/messages";

        $query = [
            'app_key' => $appKey,
            'shop_cipher' => (string) ($auth->extra['shop_cipher'] ?? ''),
            'timestamp' => (string) time(),
        ];
        $bodyJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $query['sign'] = TikTokSigner::sign($appSecret, $path, $query, $bodyJson);

        $response = Http::withHeaders([
            'x-tts-access-token' => $auth->accessToken,
            'content-type' => 'application/json',
        ])->timeout(30)->retry(2, 500, throw: false)
            ->withBody($bodyJson, 'application/json')
            ->post($base.$path.'?'.http_build_query($query));

        if (! $response->successful() || (int) $response->json('code', 0) !== 0) {
            throw new \RuntimeException('TikTok IM send failed: '.$response->body());
        }

        return new SendResultDTO(
            externalMessageId: (string) ($response->json('data.message_id') ?? ''),
            sentAt: CarbonImmutable::now(),
            raw: (array) $response->json('data', []),
        );
    }
}
