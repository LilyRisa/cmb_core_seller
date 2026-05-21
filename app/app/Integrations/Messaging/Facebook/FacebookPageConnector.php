<?php

namespace CMBcoreSeller\Integrations\Messaging\Facebook;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;
use CMBcoreSeller\Integrations\Messaging\Contracts\MessagingConnector;
use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\OutboundWindowPolicyDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\Page;
use CMBcoreSeller\Integrations\Messaging\DTO\SendResultDTO;
use CMBcoreSeller\Integrations\Messaging\Exceptions\ConversationClosed;
use CMBcoreSeller\Integrations\Messaging\Exceptions\OutboundWindowClosed;
use CMBcoreSeller\Integrations\Messaging\Exceptions\UnsupportedOperation;
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
        $scope = 'pages_messaging,pages_manage_metadata,pages_read_engagement,pages_show_list';

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
            'subscribed_fields' => 'messages,messaging_postbacks,message_deliveries,message_reads',
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
            return new MessagingWebhookEventDTO(
                provider: $this->code(),
                type: MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED,
                externalShopId: $pageId,
                externalConversationId: $senderId,            // PSID buyer = conversation
                externalMessageId: (string) ($event['message']['mid'] ?? ''),
                buyerExternalId: $senderId,
                occurredAt: $occurredAt,
                raw: $event,
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
        throw UnsupportedOperation::for($this->code(), 'fetchConversations (Messenger dựa webhook, không polling)');
    }

    public function fetchMessages(MessagingAuthContext $auth, string $externalConversationId, array $query = []): Page
    {
        throw UnsupportedOperation::for($this->code(), 'fetchMessages');
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

    private function graphUrl(string $path): string
    {
        return 'https://graph.facebook.com/'.$this->graphVersion().'/'.ltrim($path, '/');
    }

    private function graphVersion(): string
    {
        return (string) ($this->config['graph_version'] ?? 'v19.0');
    }
}
