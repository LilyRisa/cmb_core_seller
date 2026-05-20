<?php

namespace CMBcoreSeller\Integrations\Messaging\Lazada;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaSigner;
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

    public function fetchConversations(MessagingAuthContext $auth, array $query = []): Page
    {
        throw UnsupportedOperation::for($this->code(), 'fetchConversations');
    }

    public function fetchMessages(MessagingAuthContext $auth, string $externalConversationId, array $query = []): Page
    {
        throw UnsupportedOperation::for($this->code(), 'fetchMessages');
    }

    public function sendText(MessagingAuthContext $auth, string $externalConversationId, string $body, array $opts = []): SendResultDTO
    {
        return $this->send($auth, $externalConversationId, ['txt' => $body], 'text');
    }

    public function sendMedia(MessagingAuthContext $auth, string $externalConversationId, MediaRefDTO $media, array $opts = []): SendResultDTO
    {
        if ($media->kind->value !== 'image') {
            throw UnsupportedOperation::for($this->code(), 'sendMedia ('.$media->kind->value.')');
        }

        return $this->send($auth, $externalConversationId, ['img' => $media->externalUrl], 'image');
    }

    public function sendTemplate(MessagingAuthContext $auth, string $externalConversationId, string $templateKey, array $vars = [], array $opts = []): SendResultDTO
    {
        throw UnsupportedOperation::for($this->code(), 'sendTemplate');
    }

    public function outboundWindow(): OutboundWindowPolicyDTO
    {
        return new OutboundWindowPolicyDTO(freeWindowHours: null, requiresTag: false);
    }

    /**
     * Gửi message qua Lazada IM (`/im/message/send`), ký bằng LazadaSigner.
     *
     * @param  array<string,mixed>  $message
     */
    private function send(MessagingAuthContext $auth, string $sessionId, array $message, string $type): SendResultDTO
    {
        $cfg = (array) config('integrations.lazada', []);
        $base = rtrim((string) ($cfg['base_url'] ?? 'https://api.lazada.vn/rest'), '/');
        $path = '/im/message/send';

        $params = [
            'app_key' => (string) ($cfg['app_key'] ?? ''),
            'access_token' => $auth->accessToken,
            'sign_method' => 'sha256',
            'timestamp' => (string) (int) (microtime(true) * 1000),
            'session_id' => $sessionId,
            'message_type' => $type,
            'message' => json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
        $params['sign'] = LazadaSigner::sign((string) ($cfg['app_secret'] ?? ''), $path, $params);

        $response = Http::asForm()->timeout(30)->retry(2, 500, throw: false)->post($base.$path, $params);

        if (! $response->successful() || ($response->json('code') !== null && (string) $response->json('code') !== '0')) {
            throw new \RuntimeException('Lazada IM send failed: '.$response->body());
        }

        return new SendResultDTO(
            externalMessageId: (string) ($response->json('data.message_id') ?? $response->json('data.messageId') ?? ''),
            sentAt: CarbonImmutable::now(),
            raw: (array) $response->json('data', []),
        );
    }
}
