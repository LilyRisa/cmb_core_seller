# Shopee Chat Connector (`shopee_chat`) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm connector `shopee_chat` (Shopee Seller Chat) vào hộp thư hợp nhất: nhận tin (push code 10) + gửi text/ảnh, dùng chung OAuth/token với Gian hàng Shopee.

**Architecture:** `ShopeeChatConnector` implement `MessagingConnector` (mirror TikTok/Lazada chat). Shopee chỉ có 1 push URL/app nên tin chat (code 10) về `/webhook/shopee`; thêm `ShopeeWebhookController` demux theo `code`: chat → `MessagingWebhookIngestService`, còn lại → `WebhookIngestService` (đơn hàng). Gửi tin tái dùng `ShopeeClient::shopPost`. Phần pipeline còn lại (`ProcessMessagingWebhook` map `shopee_chat→shopee`, route whitelist) đã có sẵn.

**Tech Stack:** Laravel 11, PHP 8.2+, PHPUnit (`Http::fake`, `Queue::fake`, `RefreshDatabase`). HMAC-SHA256 (chữ ký push + ký request Shopee v2).

**Spec:** `docs/superpowers/specs/2026-05-21-shopee-chat-connector-design.md`

---

## File Structure

- **Create** `app/app/Integrations/Messaging/Shopee/ShopeeChatConnector.php` — connector (verify/parse/send), 1 trách nhiệm: adapt Shopee Seller Chat ↔ contract messaging.
- **Create** `app/app/Modules/Channels/Http/Controllers/ShopeeWebhookController.php` — demux push Shopee theo `code`.
- **Modify** `app/config/integrations.php` — thêm `shopee.endpoints.send_message` + `shopee.chat_push_codes`.
- **Modify** `app/app/Integrations/IntegrationsServiceProvider.php` — bỏ comment register `shopee_chat` + bind tường minh.
- **Modify** `app/routes/webhook.php` — tách `shopee` khỏi vòng lặp chung → `ShopeeWebhookController`.
- **Create** `app/tests/Feature/Messaging/ShopeeChatConnectorTest.php` — contract test connector.
- **Create** `app/tests/Feature/Messaging/ShopeeChatWebhookRoutingTest.php` — test demux.
- **Create** `docs/04-channels/shopee-chat-setup.md` — hướng dẫn cấu hình.

Tất cả lệnh chạy trong thư mục `app/` (Laravel root). Test runner: `php artisan test`.

---

## Task 1: `ShopeeChatConnector`

**Files:**
- Create: `app/app/Integrations/Messaging/Shopee/ShopeeChatConnector.php`
- Test: `app/tests/Feature/Messaging/ShopeeChatConnectorTest.php`

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/Messaging/ShopeeChatConnectorTest.php`:

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeClient;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeWebhookVerifier;
use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Integrations\Messaging\Shopee\ShopeeChatConnector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\Channels\Shopee\ShopeeFixtures;
use Tests\TestCase;

/**
 * Contract test ShopeeChatConnector (SPEC-0024 / spec 2026-05-21). Shape-tested:
 * verify chữ ký push, parse code-10 webchat, send_message shape (Http::fake).
 * Live cần Shopee Seller Chat approval + sandbox (ngoài unit test).
 */
class ShopeeChatConnectorTest extends TestCase
{
    private function connector(): ShopeeChatConnector
    {
        ShopeeFixtures::configure();
        config([
            'integrations.shopee.push_url' => 'https://app.cmbcore.com/webhook/shopee',
            'integrations.shopee.endpoints.send_message' => '/api/v2/sellerchat/send_message',
            'integrations.shopee.chat_push_codes' => [10],
        ]);

        return new ShopeeChatConnector(
            (array) config('integrations.shopee'),
            new ShopeeWebhookVerifier,
            new ShopeeClient,
        );
    }

    private function signedPush(array $body): Request
    {
        $raw = json_encode($body, JSON_UNESCAPED_SLASHES);
        $pushUrl = 'https://app.cmbcore.com/webhook/shopee';
        $sign = hash_hmac('sha256', $pushUrl.'|'.$raw, 'PARTNER_KEY');
        $req = Request::create($pushUrl, 'POST', content: $raw);
        $req->headers->set('Authorization', $sign);

        return $req;
    }

    public function test_identity_and_capabilities(): void
    {
        $c = $this->connector();
        $this->assertSame('shopee_chat', $c->code());
        $this->assertTrue($c->supports('inbound.webhook'));
        $this->assertTrue($c->supports('outbound.text'));
        $this->assertTrue($c->supports('outbound.image'));
        $this->assertFalse($c->supports('outbound.video'));
    }

    public function test_oauth_methods_unsupported(): void
    {
        $this->expectException(UnsupportedOperation::class);
        $this->connector()->buildAuthorizationUrl('state');
    }

    public function test_verifies_valid_push_signature_and_rejects_bad(): void
    {
        $req = $this->signedPush(['code' => 10, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode(['content' => ['conversation_id' => 'c1', 'message_id' => 'm1', 'from_id' => 'b1']])]);
        $this->assertTrue($this->connector()->verifyWebhookSignature($req));

        $bad = Request::create('https://app.cmbcore.com/webhook/shopee', 'POST', content: '{}');
        $bad->headers->set('Authorization', 'deadbeef');
        $this->assertFalse($this->connector()->verifyWebhookSignature($bad));
    }

    public function test_parses_code_10_webchat_message(): void
    {
        $req = $this->signedPush(['code' => 10, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode([
            'type' => 'message',
            'content' => ['conversation_id' => 'CONV_1', 'message_id' => 'MSG_1', 'from_id' => 'BUYER_1', 'message_type' => 'text', 'content' => ['text' => 'Còn hàng không shop?'], 'created_timestamp' => 1700000001],
        ])]);

        $event = $this->connector()->parseWebhook($req);

        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, $event->type);
        $this->assertSame('shopee_chat', $event->provider);
        $this->assertSame('55', $event->externalShopId);
        $this->assertSame('CONV_1', $event->externalConversationId);
        $this->assertSame('MSG_1', $event->externalMessageId);
        $this->assertSame('BUYER_1', $event->buyerExternalId);
    }

    public function test_non_chat_code_is_unknown(): void
    {
        $req = $this->signedPush(['code' => 3, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode(['ordersn' => 'SN_9'])]);
        $this->assertSame(MessagingWebhookEventDTO::TYPE_UNKNOWN, $this->connector()->parseWebhook($req)->type);
    }

    public function test_send_text_posts_correct_shape(): void
    {
        Http::fake(['*/api/v2/sellerchat/send_message*' => Http::response(['error' => '', 'response' => ['message_id' => 'OUT_1']], 200)]);

        $auth = new MessagingAuthContext(channelAccountId: 1, provider: 'shopee', externalShopId: '55', accessToken: 'ACCESS_1');
        $result = $this->connector()->sendText($auth, 'BUYER_1', 'Còn hàng nhé!');

        $this->assertSame('OUT_1', $result->externalMessageId);
        Http::assertSent(function ($request) {
            $data = $request->data();

            return str_contains($request->url(), '/api/v2/sellerchat/send_message')
                && str_contains($request->url(), 'sign=')
                && ($data['to_id'] ?? null) === 'BUYER_1'
                && ($data['message_type'] ?? null) === 'text'
                && ($data['content']['text'] ?? null) === 'Còn hàng nhé!';
        });
    }

    public function test_send_image_posts_image_type(): void
    {
        Http::fake(['*/api/v2/sellerchat/send_message*' => Http::response(['error' => '', 'response' => ['message_id' => 'OUT_2']], 200)]);

        $auth = new MessagingAuthContext(channelAccountId: 1, provider: 'shopee', externalShopId: '55', accessToken: 'ACCESS_1');
        $media = new MediaRefDTO(kind: MessageKind::Image, mime: 'image/jpeg', externalUrl: 'https://cdn/x.jpg');
        $result = $this->connector()->sendMedia($auth, 'BUYER_1', $media);

        $this->assertSame('OUT_2', $result->externalMessageId);
        Http::assertSent(fn ($r) => ($r->data()['message_type'] ?? null) === 'image'
            && ($r->data()['content']['image_url'] ?? null) === 'https://cdn/x.jpg');
    }

    public function test_send_non_image_media_unsupported(): void
    {
        $auth = new MessagingAuthContext(channelAccountId: 1, provider: 'shopee', externalShopId: '55', accessToken: 'ACCESS_1');
        $media = new MediaRefDTO(kind: MessageKind::Video, mime: 'video/mp4', externalUrl: 'https://cdn/v.mp4');

        $this->expectException(UnsupportedOperation::class);
        $this->connector()->sendMedia($auth, 'BUYER_1', $media);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=ShopeeChatConnectorTest`
Expected: FAIL — `Class "CMBcoreSeller\Integrations\Messaging\Shopee\ShopeeChatConnector" not found`.

- [ ] **Step 3: Write the connector**

Create `app/app/Integrations/Messaging/Shopee/ShopeeChatConnector.php`:

```php
<?php

namespace CMBcoreSeller\Integrations\Messaging\Shopee;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeClient;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeWebhookVerifier;
use CMBcoreSeller\Integrations\Messaging\Contracts\MessagingConnector;
use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\OutboundWindowPolicyDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\Page;
use CMBcoreSeller\Integrations\Messaging\DTO\SendResultDTO;
use CMBcoreSeller\Integrations\Messaging\Exceptions\UnsupportedOperation;
use Symfony\Component\HttpFoundation\Request;

/**
 * Shopee Seller Chat connector — SPEC-0024 / ADR-0017, ADR-0019.
 *
 * Inbound: Shopee 1 push URL/app ⇒ tin chat (push code 10 "Webchat") về
 * /webhook/shopee; {@see \CMBcoreSeller\Modules\Channels\Http\Controllers\ShopeeWebhookController}
 * demux code 10 vào pipeline messaging. Chữ ký push tái dùng
 * {@see ShopeeWebhookVerifier} (HMAC-SHA256(push_key, push_url|raw_body)).
 *
 * Outbound: send_message ký bằng ShopeeSigner qua {@see ShopeeClient::shopPost}
 * (lo ký + throttle + envelope `error`). OAuth/token dùng chung Channels Shopee
 * ⇒ buildAuthorizationUrl/exchange/refresh ném UnsupportedOperation.
 *
 * MỨC ĐỘ XÁC MINH: verify + parse + send shape test bằng Http::fake. Tên field
 * payload code-10 + schema send_message theo tài liệu/SDK Shopee — PHẢI verify
 * sandbox thật trước production (như LazadaChatConnector).
 */
class ShopeeChatConnector implements MessagingConnector
{
    /** @param array<string,mixed> $config config('integrations.shopee') */
    public function __construct(
        private array $config,
        private ShopeeWebhookVerifier $verifier,
        private ShopeeClient $client,
    ) {}

    public function code(): string
    {
        return 'shopee_chat';
    }

    public function displayName(): string
    {
        return 'Shopee Chat';
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
        throw UnsupportedOperation::for($this->code(), 'buildAuthorizationUrl (dùng chung OAuth Shopee orders — ADR-0019)');
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
        // Shopee push cấu hình ở Console → Push Mechanism (không subscribe per-shop qua API).
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        return $this->verifier->verify($request);
    }

    public function parseWebhook(Request $request): MessagingWebhookEventDTO
    {
        return $this->parseWebhookEvents($request)[0]
            ?? new MessagingWebhookEventDTO($this->code(), MessagingWebhookEventDTO::TYPE_UNKNOWN);
    }

    public function parseWebhookEvents(Request $request): array
    {
        $body = json_decode($request->getContent() ?: '{}', true) ?: [];
        $code = (int) ($body['code'] ?? -1);
        $shopId = isset($body['shop_id']) ? (string) $body['shop_id'] : null;

        $chatCodes = array_map('intval', (array) ($this->config['chat_push_codes'] ?? [10]));
        if (! in_array($code, $chatCodes, true)) {
            return [new MessagingWebhookEventDTO($this->code(), MessagingWebhookEventDTO::TYPE_UNKNOWN, $shopId, raw: $body)];
        }

        $data = $body['data'] ?? [];
        if (is_string($data)) {
            $data = json_decode($data, true) ?: [];
        }
        // Shopee webchat: chi tiết tin nằm ở data.content (fallback data nếu sàn phẳng).
        $content = (array) ($data['content'] ?? $data);

        $conversationId = isset($content['conversation_id']) ? (string) $content['conversation_id'] : null;
        $messageId = isset($content['message_id']) ? (string) $content['message_id'] : null;
        $fromId = isset($content['from_id']) ? (string) $content['from_id'] : null;
        $hasMessage = $conversationId !== null && $messageId !== null;

        $occurredAt = isset($content['created_timestamp'])
            ? CarbonImmutable::createFromTimestamp((int) $content['created_timestamp'])
            : (isset($body['timestamp']) ? CarbonImmutable::createFromTimestamp((int) $body['timestamp']) : null);

        return [new MessagingWebhookEventDTO(
            provider: $this->code(),
            type: $hasMessage ? MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED : MessagingWebhookEventDTO::TYPE_UNKNOWN,
            externalShopId: $shopId,
            externalConversationId: $conversationId,
            externalMessageId: $messageId,
            buyerExternalId: $fromId,
            occurredAt: $occurredAt,
            raw: $body,
        )];
    }

    public function fetchConversations(MessagingAuthContext $auth, array $query = []): Page
    {
        throw UnsupportedOperation::for($this->code(), 'fetchConversations (Shopee dựa webhook; polling follow-up)');
    }

    public function fetchMessages(MessagingAuthContext $auth, string $externalConversationId, array $query = []): Page
    {
        throw UnsupportedOperation::for($this->code(), 'fetchMessages');
    }

    public function sendText(MessagingAuthContext $auth, string $externalConversationId, string $body, array $opts = []): SendResultDTO
    {
        return $this->send($auth, $externalConversationId, 'text', ['text' => $body]);
    }

    public function sendMedia(MessagingAuthContext $auth, string $externalConversationId, MediaRefDTO $media, array $opts = []): SendResultDTO
    {
        if ($media->kind->value !== 'image') {
            throw UnsupportedOperation::for($this->code(), 'sendMedia ('.$media->kind->value.') — Shopee chat bản đầu chỉ hỗ trợ ảnh');
        }
        $url = $media->externalUrl;
        if (! $url) {
            throw new \RuntimeException('Shopee sendMedia cần externalUrl (signed) — storage_path không gửi trực tiếp được.');
        }

        return $this->send($auth, $externalConversationId, 'image', ['image_url' => $url]);
    }

    public function sendTemplate(MessagingAuthContext $auth, string $externalConversationId, string $templateKey, array $vars = [], array $opts = []): SendResultDTO
    {
        $body = (string) ($vars['_resolved_body'] ?? $opts['body'] ?? '');

        return $this->sendText($auth, $externalConversationId, $body, $opts);
    }

    public function outboundWindow(): OutboundWindowPolicyDTO
    {
        // Shopee không có hard-window 24h như Facebook (rate-limit per shop ở MessageSendService).
        return new OutboundWindowPolicyDTO(freeWindowHours: null, requiresTag: false);
    }

    /**
     * Gửi 1 tin qua Shopee Seller Chat send_message. `to_id` = id buyer
     * (external_conversation_id). Ký + envelope lỗi do ShopeeClient lo.
     *
     * @param  array<string,scalar>  $content
     */
    private function send(MessagingAuthContext $auth, string $toId, string $messageType, array $content): SendResultDTO
    {
        $path = (string) (($this->config['endpoints'] ?? [])['send_message'] ?? '/api/v2/sellerchat/send_message');

        $resp = $this->client->shopPost($this->authContext($auth), $path, [], [
            'to_id' => $toId,
            'message_type' => $messageType,
            'content' => $content,
        ]);

        $messageId = $resp['message_id'] ?? ($resp['data']['message_id'] ?? '');

        return new SendResultDTO(
            externalMessageId: (string) $messageId,
            sentAt: CarbonImmutable::now(),
            raw: $resp,
        );
    }

    /** MessagingAuthContext → Channels AuthContext (provider 'shopee' cho ký shop). */
    private function authContext(MessagingAuthContext $auth): AuthContext
    {
        return new AuthContext(
            channelAccountId: $auth->channelAccountId,
            provider: 'shopee',
            externalShopId: $auth->externalShopId,
            accessToken: $auth->accessToken,
            region: $auth->region,
            extra: $auth->extra,
        );
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --filter=ShopeeChatConnectorTest`
Expected: PASS (8 tests).

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Messaging/Shopee/ShopeeChatConnector.php app/tests/Feature/Messaging/ShopeeChatConnectorTest.php
git commit -m "feat(messaging): ShopeeChatConnector (verify/parse code-10 + send text/image)"
```

---

## Task 2: Config + registry wiring

**Files:**
- Modify: `app/config/integrations.php` (block `shopee` — `endpoints` + new `chat_push_codes`)
- Modify: `app/app/Integrations/IntegrationsServiceProvider.php:84` (uncomment register) + `register()` (explicit bind)
- Test: `app/tests/Feature/Messaging/ShopeeChatConnectorTest.php` (add registry-resolves test)

- [ ] **Step 1: Write the failing test** — append this method to `ShopeeChatConnectorTest`:

```php
    public function test_registry_resolves_shopee_chat_when_enabled(): void
    {
        ShopeeFixtures::configure();
        config(['integrations.messaging' => ['shopee_chat']]);
        $this->app->forgetInstance(\CMBcoreSeller\Integrations\Messaging\MessagingRegistry::class);

        $registry = $this->app->make(\CMBcoreSeller\Integrations\Messaging\MessagingRegistry::class);
        $this->assertTrue($registry->has('shopee_chat'));
        $this->assertInstanceOf(ShopeeChatConnector::class, $registry->for('shopee_chat'));
    }
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --filter=test_registry_resolves_shopee_chat_when_enabled`
Expected: FAIL — registry `has('shopee_chat')` is false (connector commented out).

- [ ] **Step 3a: Add config** — in `app/config/integrations.php`, inside the `'shopee' => [ ... 'endpoints' => [ ... ] ]` array, add the `send_message` line after `'escrow_list'`:

```php
            'escrow_list'               => '/api/v2/payment/get_escrow_list',
            'send_message'              => '/api/v2/sellerchat/send_message',
```

Then, inside the `'shopee' => [ ... ]` block (after the `'webhook_event_types' => [...]` array), add:

```php
        // Push code coi là tin chat (Webchat). Demux ở ShopeeWebhookController: code này → pipeline messaging.
        'chat_push_codes' => [10],
```

- [ ] **Step 3b: Uncomment register** — in `app/app/Integrations/IntegrationsServiceProvider.php`, replace the commented line (`:85`):

```php
        // 'shopee_chat' => ...: chờ Channels Shopee infra (Phase 4 — chưa có signer/config Shopee).
```

with:

```php
        'shopee_chat' => \CMBcoreSeller\Integrations\Messaging\Shopee\ShopeeChatConnector::class,        // SPEC-0024 (spec 2026-05-21)
```

- [ ] **Step 3c: Explicit bind** — in the same file's `register()` method, right after the `FacebookPageConnector` bind block (the `$this->app->bind(\CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector::class, ...)` closure), add:

```php
        // ShopeeChatConnector cần config block + ShopeeClient/Verifier (Channels) — bind tường minh.
        $this->app->bind(\CMBcoreSeller\Integrations\Messaging\Shopee\ShopeeChatConnector::class, function ($app) {
            return new \CMBcoreSeller\Integrations\Messaging\Shopee\ShopeeChatConnector(
                (array) config('integrations.shopee', []),
                $app->make(\CMBcoreSeller\Integrations\Channels\Shopee\ShopeeWebhookVerifier::class),
                $app->make(\CMBcoreSeller\Integrations\Channels\Shopee\ShopeeClient::class),
            );
        });
```

- [ ] **Step 4: Run to verify it passes**

Run: `php artisan test --filter=ShopeeChatConnectorTest`
Expected: PASS (9 tests).

- [ ] **Step 5: Commit**

```bash
git add app/config/integrations.php app/app/Integrations/IntegrationsServiceProvider.php app/tests/Feature/Messaging/ShopeeChatConnectorTest.php
git commit -m "feat(messaging): register shopee_chat connector + send_message/chat_push_codes config"
```

---

## Task 3: `ShopeeWebhookController` demux + route

**Files:**
- Create: `app/app/Modules/Channels/Http/Controllers/ShopeeWebhookController.php`
- Modify: `app/routes/webhook.php` (tách `shopee` route)
- Test: `app/tests/Feature/Messaging/ShopeeChatWebhookRoutingTest.php`

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/Messaging/ShopeeChatWebhookRoutingTest.php`:

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Jobs\ProcessWebhookEvent;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Messaging\Jobs\ProcessMessagingWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Fixtures\Channels\Shopee\ShopeeFixtures;
use Tests\TestCase;

/**
 * Demux push Shopee tại /webhook/shopee: chat (code 10) → pipeline messaging
 * (shopee_chat); đơn hàng (code 3) → pipeline Channels — không hồi quy.
 */
class ShopeeChatWebhookRoutingTest extends TestCase
{
    use RefreshDatabase;

    private const PUSH_URL = 'https://app.cmbcore.com/webhook/shopee';

    protected function setUp(): void
    {
        parent::setUp();
        ShopeeFixtures::configure();
        config([
            'integrations.shopee.push_url' => self::PUSH_URL,
            'integrations.shopee.chat_push_codes' => [10],
            'integrations.channels' => ['manual', 'shopee'],
            'integrations.messaging' => ['shopee_chat'],
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);
        $this->app->forgetInstance(\CMBcoreSeller\Integrations\Channels\ChannelRegistry::class);
    }

    private function postPush(array $body): \Illuminate\Testing\TestResponse
    {
        $raw = json_encode($body, JSON_UNESCAPED_SLASHES);
        $sign = hash_hmac('sha256', self::PUSH_URL.'|'.$raw, 'PARTNER_KEY');

        return $this->call('POST', '/webhook/shopee', [], [], [],
            ['HTTP_AUTHORIZATION' => $sign, 'CONTENT_TYPE' => 'application/json'], $raw);
    }

    public function test_chat_push_routes_to_messaging(): void
    {
        Queue::fake();

        $this->postPush(['code' => 10, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode([
            'content' => ['conversation_id' => 'CONV_1', 'message_id' => 'MSG_1', 'from_id' => 'BUYER_1', 'message_type' => 'text', 'content' => ['text' => 'hi']],
        ])])->assertOk();

        $this->assertSame(1, WebhookEvent::query()->where('provider', 'messaging.shopee_chat')->count());
        $this->assertSame(0, WebhookEvent::query()->where('provider', 'shopee')->count());
        Queue::assertPushed(ProcessMessagingWebhook::class, 1);
    }

    public function test_order_push_routes_to_channels(): void
    {
        Queue::fake();

        $this->postPush(['code' => 3, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode(['ordersn' => 'SN_9', 'status' => 'READY_TO_SHIP'])])
            ->assertOk();

        $this->assertSame(1, WebhookEvent::query()->where('provider', 'shopee')->count());
        $this->assertSame(0, WebhookEvent::query()->where('provider', 'messaging.shopee_chat')->count());
        Queue::assertPushed(ProcessWebhookEvent::class, 1);
    }

    public function test_bad_signature_rejected(): void
    {
        $raw = json_encode(['code' => 10, 'shop_id' => 55], JSON_UNESCAPED_SLASHES);

        $this->call('POST', '/webhook/shopee', [], [], [],
            ['HTTP_AUTHORIZATION' => 'deadbeef', 'CONTENT_TYPE' => 'application/json'], $raw)
            ->assertStatus(401);
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --filter=ShopeeChatWebhookRoutingTest`
Expected: FAIL — chat push lands in Channels pipeline (`messaging.shopee_chat` count = 0) because no demux yet.

- [ ] **Step 3a: Create the demux controller**

Create `app/app/Modules/Channels/Http/Controllers/ShopeeWebhookController.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Channels\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Services\WebhookIngestService;
use CMBcoreSeller\Modules\Messaging\Services\MessagingWebhookIngestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Shopee dùng MỘT push URL/app cho cả đơn hàng lẫn chat (push code 10 = Webchat).
 * Controller demux theo `code`: chat → pipeline messaging (shopee_chat); còn lại
 * → pipeline đơn hàng (Channels). Mỗi ingest service tự verify chữ ký push.
 *
 * Shopee là ngoại lệ của "1 webhook controller chung" (ADR-0017) vì 1 URL gánh 2
 * domain — tiktok/lazada vẫn dùng WebhookController chung.
 */
class ShopeeWebhookController extends Controller
{
    public function handle(
        Request $request,
        WebhookIngestService $orders,
        MessagingWebhookIngestService $messaging,
        MessagingRegistry $registry,
    ): JsonResponse {
        $code = (int) ($request->json('code') ?? -1);
        $chatCodes = array_map('intval', (array) config('integrations.shopee.chat_push_codes', [10]));

        $result = (in_array($code, $chatCodes, true) && $registry->has('shopee_chat'))
            ? $messaging->ingest('shopee_chat', $request)
            : $orders->ingest('shopee', $request);

        return response()->json($result['body'], $result['status']);
    }
}
```

- [ ] **Step 3b: Update the route**

In `app/routes/webhook.php`, add the import near the top (after the existing `use ... WebhookController;`):

```php
use CMBcoreSeller\Modules\Channels\Http\Controllers\ShopeeWebhookController;
```

Then replace the generic provider loop:

```php
foreach (['tiktok', 'shopee', 'lazada'] as $provider) {
    Route::post($provider, [WebhookController::class, 'handle'])
        ->defaults('provider', $provider)
        ->name($provider);
}
```

with (drop `shopee` from the loop; add a dedicated shopee route — keeps the `webhook.shopee` route name):

```php
foreach (['tiktok', 'lazada'] as $provider) {
    Route::post($provider, [WebhookController::class, 'handle'])
        ->defaults('provider', $provider)
        ->name($provider);
}

// Shopee: 1 push URL gánh cả đơn hàng lẫn chat (code 10) → controller riêng demux.
Route::post('shopee', [ShopeeWebhookController::class, 'handle'])->name('shopee');
```

- [ ] **Step 4: Run to verify it passes**

Run: `php artisan test --filter=ShopeeChatWebhookRoutingTest`
Expected: PASS (3 tests).

Then run the broader Shopee + messaging suites to confirm no regression:

Run: `php artisan test --filter=Shopee` and `php artisan test --filter=Messaging`
Expected: PASS (existing Shopee order webhook tests still green — they post to `/webhook/shopee` with order codes, now via `ShopeeWebhookController`).

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Channels/Http/Controllers/ShopeeWebhookController.php app/routes/webhook.php app/tests/Feature/Messaging/ShopeeChatWebhookRoutingTest.php
git commit -m "feat(messaging): demux Shopee push code 10 (chat) into messaging pipeline"
```

---

## Task 4: Docs

**Files:**
- Create: `docs/04-channels/shopee-chat-setup.md`
- Modify: `docs/superpowers/specs/2026-05-21-shopee-chat-connector-design.md` (status → Implemented)

- [ ] **Step 1: Write the setup doc**

Create `docs/04-channels/shopee-chat-setup.md`:

````markdown
# Cấu hình Shopee Chat (Seller Chat) — SPEC-0024 / ADR-0017, ADR-0019

Bật nhận & trả lời tin nhắn Shopee Chat ngay trong hộp thư hợp nhất. Shopee Chat
**dùng chung kết nối với Gian hàng Shopee** (ADR-0019) — KHÔNG đăng nhập riêng.

> ⚠️ Connector ở mức **shape-tested** (đúng tài liệu + unit-test), **chưa verify
> sandbox thật**. Test kỹ trên 1 shop trước khi dùng rộng.

## 0. Điều kiện tiên quyết
- Shop Shopee đã kết nối cho **đơn hàng**: `shopee` có trong `INTEGRATIONS_CHANNELS`
  + đã OAuth (`/oauth/shopee/callback`) → có `channel_accounts` (provider `shopee`).
- Push key đã cấu hình: `SHOPEE_PUSH_PARTNER_KEY` (hoặc fallback `SHOPEE_PARTNER_KEY`).

## 1. Bật connector
`INTEGRATIONS_MESSAGING` (CSV) thêm `shopee_chat`. Ví dụ:

```dotenv
INTEGRATIONS_MESSAGING=facebook_page,shopee_chat
```

## 2. Push Mechanism (Shopee Console)
1. Console → **Push Mechanism** → chọn App → **Set Push**.
2. **Callback URL**: `https://app.cmbcore.com/webhook/shopee` (DÙNG CHUNG với đơn hàng —
   Shopee chỉ 1 URL/app; app tự demux code 10 sang hộp thư).
3. Subscribe thêm **Code 10 — Webchat Push** (ngoài các code đơn hàng 1/2/3/4/15…).

## 3. Bật nhắn tin cho shop
App → trang **Gian hàng** → shop Shopee → bật **"nhắn tin"**
(`PATCH /api/v1/channel-accounts/{id}/messaging`).

## 4. Luồng (đã code)
- Buyer nhắn → Shopee POST code 10 → `/webhook/shopee` → `ShopeeWebhookController`
  demux → pipeline messaging → hộp thư (≤ vài giây).
- NV trả lời → `send_message` (`/api/v2/sellerchat/send_message`, ký HMAC shop).

## 5. Xử lý lỗi thường gặp
| Triệu chứng | Nguyên nhân & cách sửa |
|---|---|
| Không nhận được tin chat | Chưa subscribe **Code 10**; hoặc callback URL ≠ `/webhook/shopee`; hoặc `shopee_chat` chưa trong `INTEGRATIONS_MESSAGING`. |
| Webhook chat 401 | `SHOPEE_PUSH_PARTNER_KEY`/`SHOPEE_PARTNER_KEY` sai (chữ ký push HMAC mismatch). |
| Gửi tin lỗi | Token shop hết hạn → kết nối lại Gian hàng; hoặc Seller Chat API chưa được duyệt cho app. |
| Tin về nhưng không thấy shop | `channel_accounts` provider `shopee` với `external_shop_id` = shop_id chưa tồn tại (chưa OAuth đơn hàng). |

## 6. Giới hạn bản đầu (YAGNI)
Gửi **text + ảnh**. Chưa có: item/order/sticker, read-receipt/typing, polling backup.
Thêm sau theo cùng pattern (sửa connector, không đụng controller/pipeline — ADR-0017).
````

- [ ] **Step 2: Update the spec status**

In `docs/superpowers/specs/2026-05-21-shopee-chat-connector-design.md`, change the first status line:

```markdown
> Trạng thái: **Đã duyệt thiết kế (2026-05-21)** — sẵn sàng viết plan.
```

to:

```markdown
> Trạng thái: **Đã implement (2026-05-21)** — xem plan `docs/superpowers/plans/2026-05-21-shopee-chat-connector.md` + doc `docs/04-channels/shopee-chat-setup.md`.
```

- [ ] **Step 3: Commit**

```bash
git add docs/04-channels/shopee-chat-setup.md docs/superpowers/specs/2026-05-21-shopee-chat-connector-design.md
git commit -m "docs(messaging): hướng dẫn cấu hình Shopee Chat + cập nhật spec status"
```

---

## Task 5 (độc lập — KHÔNG thuộc shopee_chat): bật `tiktok_chat`, `lazada_chat`

> Đây là yêu cầu riêng của user (bật 2 connector chat đã có sẵn). Không phụ thuộc code shopee_chat.
> Chỉ là config + docs. Giữ trong plan để không quên.

**Files:**
- Modify: `docker-compose.prod.yml:143`
- Modify: `docs/04-channels/lazada-chat-setup.md` / tạo `docs/04-channels/tiktok-chat-setup.md` (nếu chưa có)

- [ ] **Step 1:** Đổi default messaging trong `docker-compose.prod.yml` (dòng 143) — chỉ thêm
  2 connector chat đã verified-shape, KHÔNG auto-bật `facebook_page` (cần App Review):

```yaml
  INTEGRATIONS_MESSAGING: ${INTEGRATIONS_MESSAGING:-tiktok_chat,lazada_chat}
```

> ⚠️ Cân nhắc: `lazada_chat` là best-effort/chưa verify (cảnh báo trong connector). Nếu muốn
> thận trọng, để default rỗng và set `INTEGRATIONS_MESSAGING` ở Portainer thay vì hardcode.
> **Xác nhận với user trước khi đổi default** (xem phần handoff).

- [ ] **Step 2:** Đảm bảo điều kiện: `tiktok` đã trong `INTEGRATIONS_CHANNELS` (mặc định `manual,tiktok` — OK);
  `lazada` CHƯA → nếu muốn lazada_chat phải thêm `lazada` vào `INTEGRATIONS_CHANNELS` + OAuth shop Lazada.

- [ ] **Step 3:** Webhook console: TikTok Partner Center / Lazada Open Platform trỏ
  `/webhook/messaging/tiktok_chat` và `/webhook/messaging/lazada_chat`.

- [ ] **Step 4: Commit**

```bash
git add docker-compose.prod.yml docs/04-channels/
git commit -m "chore(messaging): bật tiktok_chat,lazada_chat mặc định prod + docs"
```

---

## Self-Review (đã chạy)

- **Spec coverage:** §3.A → Task 1; §3.C wiring → Task 2; §3.B demux + route → Task 3;
  §6 testing → test ở Task 1/3; §8 + §9 docs → Task 4. Yêu cầu user "bật tiktok/lazada" → Task 5. ✅
- **Placeholders:** không có TODO/TBD; mọi step có code/lệnh cụ thể. ✅
- **Type consistency:** `MessagingAuthContext`, `AuthContext`, `MessagingWebhookEventDTO`,
  `SendResultDTO`, `MediaRefDTO`/`MessageKind`, `OutboundWindowPolicyDTO`, `ShopeeClient::shopPost`,
  `ShopeeWebhookVerifier::verify` — đều khớp signature thực trong codebase. `chat_push_codes`,
  `endpoints.send_message` đặt ở Task 2 và dùng ở Task 1/3 nhất quán. ✅
- **Lưu ý verify-sandbox:** field payload code-10 (`data.content.{conversation_id,message_id,from_id}`)
  và schema `send_message` theo tài liệu — đánh dấu rõ cần đối chiếu sandbox (đúng tinh thần shape-tested). ✅
