<?php

namespace CMBcoreSeller\Integrations\Messaging\Zalo;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;
use CMBcoreSeller\Integrations\Messaging\Contracts\InteractiveMessagingConnector;
use CMBcoreSeller\Integrations\Messaging\Contracts\MessagingConnector;
use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDirection;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\OutboundWindowPolicyDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\Page;
use CMBcoreSeller\Integrations\Messaging\DTO\SendResultDTO;
use CMBcoreSeller\Integrations\Messaging\Exceptions\UnsupportedOperation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Request;

class ZaloOaConnector implements InteractiveMessagingConnector, MessagingConnector
{
    /** @param array<string,mixed> $config */
    public function __construct(
        private array $config,
        private ZaloSignatureVerifier $verifier,
        private ZaloClient $client,
    ) {}

    public function code(): string
    {
        return 'zalo_oa';
    }

    public function displayName(): string
    {
        return 'Zalo OA';
    }

    /** @return array<string,bool> */
    public function capabilities(): array
    {
        return [
            'inbound.webhook' => true,
            'inbound.polling' => false,
            'inbound.postback' => true,
            'outbound.text' => true,
            'outbound.image' => true,
            'outbound.file' => true,
            'outbound.video' => false,            // Phase 1 tắt cho an toàn
            'outbound.template' => false,
            'outbound.interactive' => true,        // nút ≤5
            'outbound.utility_template' => false,  // bật ở Phase ZNS
            'read_receipt' => true,
            'typing' => false,
        ];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    public function outboundWindow(): OutboundWindowPolicyDTO
    {
        // CS window: Zalo enforce server-side, lộ qua error codes. Không free-window cứng ở client.
        return new OutboundWindowPolicyDTO(freeWindowHours: null, requiresTag: false);
    }

    // --- OAuth (Task 6) ---
    // NEEDS-VERIFY: Zalo OA cấp quyền ở mức app/OA, không có scope.
    public function buildAuthorizationUrl(string $state, array $opts = []): string
    {
        return 'https://oauth.zaloapp.com/v4/oa/permission?'.http_build_query([
            'app_id' => (string) $this->cfg('app_id'),
            'redirect_uri' => $opts['redirect_uri'] ?? $this->redirectUri(),
            'state' => $state,
        ]);
    }

    /**
     * Callback OAuth cố định ở /oauth/zalo_oa/callback (routes/web.php). Cho phép cấu hình
     * MESSAGING_ZALO_REDIRECT_URI để override; nếu trống thì tự dựng từ app.url → env này
     * KHÔNG bắt buộc. (Token exchange v4 không gửi redirect_uri nên chỉ dùng ở authorize.)
     */
    private function redirectUri(): string
    {
        $configured = (string) $this->cfg('redirect_uri');
        if ($configured !== '') {
            return $configured;
        }

        return rtrim((string) config('app.url'), '/').'/oauth/zalo_oa/callback';
    }

    public function exchangeCodeForToken(string $code): TokenDTO
    {
        $res = $this->client->oauthToken([
            'code' => $code,
            'app_id' => (string) $this->cfg('app_id'),
            'grant_type' => 'authorization_code',
        ], (string) $this->cfg('app_secret'));

        return $this->tokenFromOauth($res);
    }

    public function refreshToken(string $refreshToken): TokenDTO
    {
        $res = $this->client->oauthToken([
            'refresh_token' => $refreshToken,
            'app_id' => (string) $this->cfg('app_id'),
            'grant_type' => 'refresh_token',
        ], (string) $this->cfg('app_secret'));

        return $this->tokenFromOauth($res);
    }

    /** @param array<string,mixed> $res */
    private function tokenFromOauth(array $res): TokenDTO
    {
        $expiresIn = (int) ($res['expires_in'] ?? 0);

        return new TokenDTO(
            accessToken: (string) $res['access_token'],
            refreshToken: (string) ($res['refresh_token'] ?? ''),
            expiresAt: $expiresIn > 0 ? CarbonImmutable::now()->addSeconds($expiresIn) : null,
            raw: $res,
        );
    }

    public function registerWebhooks(MessagingAuthContext $auth): void
    {
        // Zalo OA webhook cấu hình trên Zalo Developer Console (URL cố định), không gọi API ở đây.
    }

    /** @return array{name: ?string, avatar_url: ?string} */
    public function fetchPageProfile(MessagingAuthContext $auth): array
    {
        $data = $this->client->get($auth->accessToken, 'v2.0/oa/getoa');

        return ['name' => $data['name'] ?? null, 'avatar_url' => $data['avatar'] ?? null];
    }

    /** OA id của tài khoản (đổi token → định danh shop). `v2.0/oa/getoa` → data.oa_id. */
    public function fetchOaId(MessagingAuthContext $auth): string
    {
        return (string) ($this->client->get($auth->accessToken, 'v2.0/oa/getoa')['oa_id'] ?? '');
    }

    /** @return array{name: ?string, avatar_url: ?string} */
    public function fetchUserProfile(MessagingAuthContext $auth, string $externalUserId): array
    {
        $data = $this->client->get($auth->accessToken, 'v3.0/oa/user/detail', [
            'data' => json_encode(['user_id' => $externalUserId], JSON_UNESCAPED_UNICODE),
        ]);

        return ['name' => $data['display_name'] ?? null, 'avatar_url' => $data['avatar'] ?? null];
    }

    // --- Inbound (Task 4-5) ---
    public function verifyWebhookSignature(Request $request): bool
    {
        // oa_secret (bí mật ký webhook) — thường TRÙNG App Secret nên fallback về app_secret
        // khi để trống ⇒ MESSAGING_ZALO_OA_SECRET KHÔNG bắt buộc (chỉ set khi Zalo dùng secret webhook riêng).
        $oaSecret = (string) ($this->cfg('oa_secret') ?: $this->cfg('app_secret'));

        return $this->verifier->verify($request, (string) $this->cfg('app_id'), $oaSecret);
    }

    public function parseWebhook(Request $request): MessagingWebhookEventDTO
    {
        $p = json_decode($request->getContent(), true) ?: [];
        $event = (string) ($p['event_name'] ?? '');
        $oaId = (string) ($p['recipient']['id'] ?? '');     // user_send*: OA = recipient
        $userId = (string) ($p['sender']['id'] ?? '');       // user_send*: user = sender
        $occurredAt = isset($p['timestamp']) ? CarbonImmutable::createFromTimestampMs((int) $p['timestamp']) : null;
        $msg = (array) ($p['message'] ?? []);
        $msgId = (string) ($msg['msg_id'] ?? '');

        $base = fn (string $type, ?MessageKind $kind = null, ?string $body = null, array $atts = []) => new MessagingWebhookEventDTO(
            provider: $this->code(),
            type: $type,
            externalShopId: $oaId,
            externalConversationId: $userId,
            externalMessageId: $msgId,
            buyerExternalId: $userId,
            occurredAt: $occurredAt,
            raw: $p,
            kind: $kind,
            body: $body,
            attachments: $atts,
            threadType: 'message',
            direction: MessageDirection::Inbound,
        );

        if ($event === 'user_seen_message') {
            return $base(MessagingWebhookEventDTO::TYPE_MESSAGE_READ);
        }

        // Nút Zalo (oa.query.hide) echo lại payload dạng tin user prefix `postback_`.
        $text = (string) ($msg['text'] ?? '');
        if ($event === 'user_send_text' && str_starts_with($text, 'postback_')) {
            return $base(MessagingWebhookEventDTO::TYPE_POSTBACK, body: $text);
        }

        return match ($event) {
            'user_send_text' => $base(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, MessageKind::Text, $text),
            'user_send_image' => $base(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, MessageKind::Image, null, $this->mediaAttachments($msg, MessageKind::Image)),
            'user_send_file' => $base(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, MessageKind::File, null, $this->mediaAttachments($msg, MessageKind::File)),
            'user_send_audio' => $base(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, MessageKind::Audio, null, $this->mediaAttachments($msg, MessageKind::Audio)),
            'user_send_sticker' => $base(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, MessageKind::Text, '[sticker]'),
            'user_send_location' => $base(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, MessageKind::Text, '[location]'),
            default => $base(MessagingWebhookEventDTO::TYPE_UNKNOWN),
        };
    }

    /** @return list<MessagingWebhookEventDTO> */
    public function parseWebhookEvents(Request $request): array
    {
        return [$this->parseWebhook($request)];
    }

    public function fetchConversations(MessagingAuthContext $auth, array $query = []): Page
    {
        throw UnsupportedOperation::for($this->code(), 'fetchConversations');
    }

    public function fetchMessages(MessagingAuthContext $auth, string $externalConversationId, array $query = []): Page
    {
        throw UnsupportedOperation::for($this->code(), 'fetchMessages');
    }

    // --- Outbound (Task 7-9) ---
    public function sendText(MessagingAuthContext $auth, string $externalConversationId, string $body, array $opts = []): SendResultDTO
    {
        return $this->sendCs($auth, $externalConversationId, ['text' => $body]);
    }

    /**
     * @param  array<string,mixed>  $message  message template Zalo (text / attachment)
     */
    private function sendCs(MessagingAuthContext $auth, string $userId, array $message): SendResultDTO
    {
        $data = $this->client->post($auth->accessToken, 'v3.0/oa/message/cs', [
            'recipient' => ['user_id' => $userId],
            'message' => $message,
        ]);

        return new SendResultDTO(
            externalMessageId: (string) ($data['message_id'] ?? ''),
            sentAt: CarbonImmutable::now(),
            raw: $data,
        );
    }

    public function sendMedia(MessagingAuthContext $auth, string $externalConversationId, MediaRefDTO $media, array $opts = []): SendResultDTO
    {
        [$contents, $filename, $mime] = $this->readMedia($media, $opts);

        if ($media->kind === MessageKind::Image) {
            $up = $this->client->uploadMultipart($auth->accessToken, 'v2.0/oa/upload/image', 'file', $contents, $filename, $mime);
            $attachmentId = (string) ($up['attachment_id'] ?? '');

            return $this->sendCs($auth, $externalConversationId, [
                'attachment' => [
                    'type' => 'template',
                    'payload' => [
                        'template_type' => 'media',
                        'elements' => [['media_type' => 'image', 'attachment_id' => $attachmentId]],
                    ],
                ],
            ]);
        }

        // File (và audio): upload/file → token
        $up = $this->client->uploadMultipart($auth->accessToken, 'v2.0/oa/upload/file', 'file', $contents, $filename, $mime);
        $token = (string) ($up['token'] ?? '');

        return $this->sendCs($auth, $externalConversationId, [
            'attachment' => ['type' => 'file', 'payload' => ['token' => $token]],
        ]);
    }

    /** @return array{0:string,1:string,2:string} [contents, filename, mime] */
    private function readMedia(MediaRefDTO $media, array $opts): array
    {
        $filename = $media->filename ?: 'upload';
        $mime = $media->mime ?: 'application/octet-stream';
        if ($media->storagePath) {
            $disk = (string) ($opts['disk'] ?? config('filesystems.default'));
            $contents = (string) Storage::disk($disk)->get($media->storagePath);
        } elseif ($media->externalUrl) {
            $contents = (string) Http::get($media->externalUrl)->body();
        } else {
            throw new \RuntimeException('Zalo sendMedia: media has neither storagePath nor externalUrl');
        }

        return [$contents, $filename, $mime];
    }

    public function sendTemplate(MessagingAuthContext $auth, string $externalConversationId, string $templateKey, array $vars = [], array $opts = []): SendResultDTO
    {
        throw UnsupportedOperation::for($this->code(), 'sendTemplate'); // ZNS — Phase 3
    }

    public function sendInteractive(MessagingAuthContext $auth, string $externalConversationId, array $structure, array $opts = []): SendResultDTO
    {
        $buttons = [];
        foreach (array_slice((array) ($structure['buttons'] ?? []), 0, 5) as $btn) {
            $title = mb_substr((string) ($btn['title'] ?? $btn['label'] ?? ''), 0, 20);
            if (! empty($btn['url'])) {
                $buttons[] = ['title' => $title, 'type' => 'oa.open.url', 'payload' => ['url' => (string) $btn['url']]];
            } else {
                $buttons[] = ['title' => $title, 'type' => 'oa.query.hide', 'payload' => 'postback_'.((string) ($btn['payload'] ?? ''))];
            }
        }

        return $this->sendCs($auth, $externalConversationId, [
            'text' => (string) ($structure['text'] ?? ''),
            'attachment' => ['type' => 'template', 'payload' => ['buttons' => $buttons]],
        ]);
        // NEEDS-VERIFY: cấu trúc template button của Zalo OA.
    }

    // --- Comment moderation: Zalo OA không có comment feed ---
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
     * @param  array<string,mixed>  $msg
     * @return list<MediaRefDTO>
     */
    private function mediaAttachments(array $msg, MessageKind $kind): array
    {
        $out = [];
        foreach ((array) ($msg['attachments'] ?? []) as $att) {
            $payload = (array) ($att['payload'] ?? []);
            $url = (string) ($payload['url'] ?? '');
            if ($url === '') {
                continue;
            }
            $out[] = new MediaRefDTO(
                kind: $kind,
                mime: match ($kind) {
                    MessageKind::Image => 'image/jpeg', MessageKind::Audio => 'audio/mpeg', default => 'application/octet-stream'
                },
                externalUrl: $url,
                filename: (string) ($payload['name'] ?? ''),
            );
        }

        return $out;
    }

    private function cfg(string $key): mixed
    {
        return $this->config[$key] ?? '';
    }
}
