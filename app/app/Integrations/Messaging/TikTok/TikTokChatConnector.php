<?php

namespace CMBcoreSeller\Integrations\Messaging\TikTok;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;
use CMBcoreSeller\Integrations\Channels\TikTok\TikTokSigner;
use CMBcoreSeller\Integrations\Messaging\Contracts\MessagingConnector;
use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
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
        $conversationId = $data['conversation_id'] ?? null;
        $messageId = $data['message_id'] ?? null;
        $senderId = $data['sender']['im_user_id'] ?? ($data['from_user_id'] ?? null);
        $hasMessage = $conversationId !== null && $messageId !== null;

        return new MessagingWebhookEventDTO(
            provider: $this->code(),
            type: $hasMessage ? MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED : MessagingWebhookEventDTO::TYPE_UNKNOWN,
            externalShopId: isset($body['shop_id']) ? (string) $body['shop_id'] : null,
            externalConversationId: $conversationId !== null ? (string) $conversationId : null,
            externalMessageId: $messageId !== null ? (string) $messageId : null,
            buyerExternalId: $senderId !== null ? (string) $senderId : null,
            occurredAt: isset($body['timestamp']) ? CarbonImmutable::createFromTimestamp((int) $body['timestamp']) : null,
            raw: $body,
        );
    }

    public function parseWebhookEvents(Request $request): array
    {
        // TikTok IM push 1 event / POST — wrap. Nâng lên loop nếu sàn batch.
        return [$this->parseWebhook($request)];
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

        return $this->send($auth, $externalConversationId, ['type' => 'IMAGE', 'content' => json_encode(['image_url' => $media->externalUrl], JSON_UNESCAPED_UNICODE)]);
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
