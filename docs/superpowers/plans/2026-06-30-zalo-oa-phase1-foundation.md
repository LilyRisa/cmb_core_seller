# Zalo OA Messaging — Phase 1 (Nền tảng) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm Zalo OA thành một messaging provider đầy đủ (kết nối OA, nhận webhook, hộp thoại 1-1, gửi tin CS text/media/nút, cron refresh token) theo đúng Connector + Registry pattern — không sửa core.

**Architecture:** Một connector `ZaloOaConnector` (implements `MessagingConnector` + `InteractiveMessagingConnector`) trong `app/app/Integrations/Messaging/Zalo/`, cùng `ZaloSignatureVerifier` + `ZaloClient`. Wiring qua registry + config + webhook route (mirror Facebook). OAuth connect qua `ZaloOaOAuthController`. Token xoay vòng qua command + job riêng dùng `MessagingRegistry` (KHÔNG dùng `TokenRefresher` của Channels vì nó resolve qua ChannelRegistry). Flow/auto-reply reuse nguyên trạng với `provider='zalo_oa'`. FE thêm submenu "Zalo OA" + nút kết nối, lọc kênh theo provider.

**Tech Stack:** Laravel 11 (PHP 8.3), PHPUnit, `Illuminate\Support\Facades\Http` (+ `Http::fake`), Cache lock, React 18 + Ant Design + TanStack Query.

## Global Constraints

- Tất cả lệnh PHP/Node chạy từ `app/` (không phải repo root).
- PSR-4 `CMBcoreSeller\` → `app/app/`. Connector namespace: `CMBcoreSeller\Integrations\Messaging\Zalo`.
- Dùng `config()` không bao giờ `env()` ngoài file config.
- Money = integer VND; timestamp ISO-8601 UTC; chuỗi hiển thị tiếng Việt, code/identifier tiếng Anh.
- Core KHÔNG được biết tên provider; mọi gating qua `capabilities()`/`supports()` + interface, không `instanceof ZaloOaConnector`.
- Connector tự verify chữ ký webhook; thao tác không hỗ trợ → `throw UnsupportedOperation::for($this->code(), 'method')`.
- Token lưu ở `channel_accounts` (cast `encrypted`), KHÔNG ở config. Per-OA.
- Scheduler theo giờ HCM qua `app_display_tz()` khi pin wall-clock.
- Quality gate (chạy từ `app/`): `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test`, `npm run lint && npm run typecheck && npm run build`. Baseline FE/BE chưa xanh toàn cục — không phá thêm; 7 test GHN/fulfillment fail sẵn trên main.
- **Zalo protocol = best-effort theo ChatbotX; mọi endpoint/chữ ký đánh dấu `// NEEDS-VERIFY` phải đối chiếu Zalo Open Platform khi có credentials.** Test dùng `Http::fake` nên không phụ thuộc credentials.

**Zalo protocol hằng số (dùng xuyên suốt):**
- OAuth host: `https://oauth.zaloapp.com` — authorize `GET /v4/oa/permission?app_id=&redirect_uri=&state=` (KHÔNG có `scope`); token `POST /v4/oa/access_token` (form-urlencoded, header `secret_key: <app_secret>`).
- API host: `https://openapi.zalo.me` — header auth `access_token: <token>` (KHÔNG Bearer). Envelope `{ error:int, message:string, data?:... }`; `error !== 0` là lỗi dù HTTP 200.
- Endpoints: OA profile `GET /v2.0/oa/getoa`; user profile `GET /v3.0/oa/user/detail?data=<json>`; send CS `POST /v3.0/oa/message/cs`; upload `POST /v2.0/oa/upload/{image,file}`.
- Webhook: header `X-ZEvent-Signature: mac=<sha256hex>`, `mac = sha256(app_id + raw_body + timestamp + oa_secret)` (SHA256 thường, KHÔNG hmac); `timestamp` đọc từ body. Event `user_send_text|user_send_image|user_send_file|user_send_audio|user_send_sticker|user_send_location|user_seen_message`; OA = `recipient.id`, user = `sender.id`.

---

## File Structure

**Tạo mới (backend):**
- `app/app/Integrations/Messaging/Zalo/ZaloSignatureVerifier.php` — verify MAC webhook (standalone, unit-testable).
- `app/app/Integrations/Messaging/Zalo/ZaloClient.php` — HTTP client: host/header/error-envelope; trả mảng `data` hoặc ném `ZaloApiException`.
- `app/app/Integrations/Messaging/Zalo/ZaloApiException.php` — exception mang `error` code + message.
- `app/app/Integrations/Messaging/Zalo/ZaloOaConnector.php` — connector chính.
- `app/app/Modules/Messaging/Http/Controllers/ZaloOaOAuthController.php` — connect + callback.
- `app/app/Modules/Messaging/Services/ZaloTokenRefresher.php` — refresh + lock per-account qua MessagingRegistry.
- `app/app/Modules/Messaging/Jobs/RefreshZaloToken.php` — job unique per account.
- `app/app/Console/Commands/RefreshZaloTokens.php` — command quét OA sắp hết hạn.

**Tạo mới (test):**
- `app/tests/Unit/Messaging/Zalo/ZaloSignatureVerifierTest.php`
- `app/tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php`
- `app/tests/Unit/Messaging/Zalo/ZaloTokenRefresherTest.php`
- `app/tests/Feature/Messaging/ZaloOaWebhookTest.php`
- `app/tests/Feature/Messaging/ZaloOaOAuthTest.php`

**Sửa (backend wiring):**
- `app/config/integrations.php` — thêm block `messaging_zalo_oa`.
- `app/app/Integrations/IntegrationsServiceProvider.php` — import + `$messagingConnectors['zalo_oa']` + explicit `bind()`.
- `app/routes/webhook.php` — `zalo_oa` vào `whereIn` (+ tùy chọn GET verify).
- `app/app/Modules/Messaging/Jobs/ProcessMessagingWebhook.php` — `'zalo_oa' => 'zalo_oa'`.
- `app/app/Modules/Channels/Models/ChannelAccount.php` — `MESSAGING_ONLY_PROVIDERS` + `messagingConnectorCode()`.
- `app/routes/console.php` — schedule `messaging:zalo:refresh-tokens`.
- `app/app/Modules/Messaging/Http/routes.php` — route `messaging/zalo/connect` (start OAuth).
- `app/routes/web.php` — callback `oauth/zalo_oa/callback`.

**Sửa (frontend):**
- `app/app/Modules/Messaging/Http/Controllers/MessagingChannelController.php` — bỏ hardcode `facebook_page`, nhận `provider`.
- `app/resources/js/lib/messagingConfig.tsx` (hoặc `messaging.tsx`) — hook kết nối Zalo + lọc kênh theo provider.
- `app/resources/js/pages/MessagingChannelsPage.tsx` — nút "Kết nối Zalo OA".
- `app/resources/js/components/AppLayout.tsx` + `app/resources/js/lib/desktop/appCatalog.tsx` — submenu "Zalo OA".

---

## Task 1: ZaloSignatureVerifier

**Files:**
- Create: `app/app/Integrations/Messaging/Zalo/ZaloSignatureVerifier.php`
- Test: `app/tests/Unit/Messaging/Zalo/ZaloSignatureVerifierTest.php`

**Interfaces:**
- Produces: `ZaloSignatureVerifier::verify(Request $request, string $appId, string $oaSecret): bool`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Messaging\Zalo;

use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloSignatureVerifier;
use Symfony\Component\HttpFoundation\Request;
use Tests\TestCase;

class ZaloSignatureVerifierTest extends TestCase
{
    private const APP_ID = 'app_123';
    private const OA_SECRET = 'oa_secret_xyz';

    private function request(string $body, ?string $signature): Request
    {
        $server = $signature !== null ? ['HTTP_X_ZEVENT_SIGNATURE' => $signature] : [];

        return Request::create('/webhook/messaging/zalo_oa', 'POST', [], [], [], $server, $body);
    }

    public function test_verifies_valid_mac(): void
    {
        $body = '{"app_id":"app_123","event_name":"user_send_text","timestamp":"1700000000"}';
        $mac = 'mac='.hash('sha256', self::APP_ID.$body.'1700000000'.self::OA_SECRET);

        $this->assertTrue((new ZaloSignatureVerifier)->verify($this->request($body, $mac), self::APP_ID, self::OA_SECRET));
    }

    public function test_rejects_wrong_mac(): void
    {
        $body = '{"app_id":"app_123","timestamp":"1700000000"}';
        $this->assertFalse((new ZaloSignatureVerifier)->verify($this->request($body, 'mac=deadbeef'), self::APP_ID, self::OA_SECRET));
    }

    public function test_rejects_missing_header_or_secret(): void
    {
        $body = '{"timestamp":"1"}';
        $this->assertFalse((new ZaloSignatureVerifier)->verify($this->request($body, null), self::APP_ID, self::OA_SECRET));
        $this->assertFalse((new ZaloSignatureVerifier)->verify($this->request($body, 'mac=x'), self::APP_ID, ''));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/Messaging/Zalo/ZaloSignatureVerifierTest.php`
Expected: FAIL — class `ZaloSignatureVerifier` not found.

- [ ] **Step 3: Write the implementation**

```php
<?php

namespace CMBcoreSeller\Integrations\Messaging\Zalo;

use Symfony\Component\HttpFoundation\Request;

/**
 * Xác minh chữ ký webhook Zalo OA.
 * Header `X-ZEvent-Signature: mac=<hex>`, mac = sha256(app_id + raw_body + timestamp + oa_secret).
 * Lưu ý: SHA256 thường (KHÔNG hmac); timestamp đọc từ body. // NEEDS-VERIFY (Zalo Open Platform)
 */
class ZaloSignatureVerifier
{
    public function verify(Request $request, string $appId, string $oaSecret): bool
    {
        if ($oaSecret === '' || $appId === '') {
            return false;
        }

        $header = (string) $request->headers->get('X-ZEvent-Signature', '');
        if (! str_starts_with($header, 'mac=')) {
            return false;
        }
        $provided = substr($header, 4);

        $body = $request->getContent();
        $decoded = json_decode($body, true);
        $timestamp = is_array($decoded) ? (string) ($decoded['timestamp'] ?? '') : '';
        if ($timestamp === '') {
            return false;
        }

        $expected = hash('sha256', $appId.$body.$timestamp.$oaSecret);

        return hash_equals($expected, $provided);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/Messaging/Zalo/ZaloSignatureVerifierTest.php`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Messaging/Zalo/ZaloSignatureVerifier.php app/tests/Unit/Messaging/Zalo/ZaloSignatureVerifierTest.php
git commit -m "feat(messaging-zalo): ZaloSignatureVerifier (MAC webhook)"
```

---

## Task 2: ZaloApiException + ZaloClient

**Files:**
- Create: `app/app/Integrations/Messaging/Zalo/ZaloApiException.php`
- Create: `app/app/Integrations/Messaging/Zalo/ZaloClient.php`
- Test: `app/tests/Unit/Messaging/Zalo/ZaloClientTest.php`

**Interfaces:**
- Produces:
  - `ZaloApiException extends \RuntimeException` với `public int $zaloError`; static `from(int $error, string $message): self`.
  - `ZaloClient::get(string $accessToken, string $path, array $query = []): array` — trả `data` (mảng), ném `ZaloApiException` khi `error !== 0`.
  - `ZaloClient::post(string $accessToken, string $path, array $json): array`
  - `ZaloClient::uploadMultipart(string $accessToken, string $path, string $fieldName, string $contents, string $filename, string $mime): array`
  - `ZaloClient::oauthToken(array $form, string $appSecret): array` — POST form tới `oauth.zaloapp.com/v4/oa/access_token`, header `secret_key`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Messaging\Zalo;

use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloApiException;
use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZaloClientTest extends TestCase
{
    public function test_post_returns_data_and_sends_access_token_header(): void
    {
        Http::fake(['openapi.zalo.me/v3.0/oa/message/cs' => Http::response(['error' => 0, 'message' => 'Success', 'data' => ['message_id' => 'm1']], 200)]);

        $data = (new ZaloClient)->post('TKN', 'v3.0/oa/message/cs', ['recipient' => ['user_id' => 'u1']]);

        $this->assertSame('m1', $data['message_id']);
        Http::assertSent(fn ($r) => $r->hasHeader('access_token', 'TKN') && str_contains($r->url(), 'openapi.zalo.me/v3.0/oa/message/cs'));
    }

    public function test_throws_on_nonzero_error_even_with_http_200(): void
    {
        Http::fake(['openapi.zalo.me/*' => Http::response(['error' => -216, 'message' => 'User has blocked OA'], 200)]);

        $this->expectException(ZaloApiException::class);
        try {
            (new ZaloClient)->post('TKN', 'v3.0/oa/message/cs', []);
        } catch (ZaloApiException $e) {
            $this->assertSame(-216, $e->zaloError);
            throw $e;
        }
    }

    public function test_oauth_token_posts_form_with_secret_key_header(): void
    {
        Http::fake(['oauth.zaloapp.com/v4/oa/access_token' => Http::response(['access_token' => 'AT', 'refresh_token' => 'RT', 'expires_in' => '90000'], 200)]);

        $res = (new ZaloClient)->oauthToken(['code' => 'C', 'app_id' => 'A', 'grant_type' => 'authorization_code'], 'SECRET');

        $this->assertSame('AT', $res['access_token']);
        Http::assertSent(fn ($r) => $r->hasHeader('secret_key', 'SECRET')
            && $r['grant_type'] === 'authorization_code'
            && str_contains((string) $r->header('Content-Type')[0], 'application/x-www-form-urlencoded'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/Messaging/Zalo/ZaloClientTest.php`
Expected: FAIL — classes not found.

- [ ] **Step 3: Write the implementations**

`ZaloApiException.php`:
```php
<?php

namespace CMBcoreSeller\Integrations\Messaging\Zalo;

class ZaloApiException extends \RuntimeException
{
    public function __construct(public int $zaloError, string $message)
    {
        parent::__construct($message, 0);
    }

    public static function from(int $error, string $message): self
    {
        return new self($error, "Zalo API error {$error}: {$message}");
    }
}
```

`ZaloClient.php`:
```php
<?php

namespace CMBcoreSeller\Integrations\Messaging\Zalo;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * HTTP client cho Zalo Open API. Host openapi.zalo.me, auth bằng header `access_token` (KHÔNG Bearer).
 * Envelope {error,message,data}: error !== 0 là lỗi dù HTTP 200 → ném ZaloApiException.
 */
class ZaloClient
{
    private const API_BASE = 'https://openapi.zalo.me/';

    private const OAUTH_TOKEN_URL = 'https://oauth.zaloapp.com/v4/oa/access_token';

    private function base(string $accessToken): PendingRequest
    {
        return Http::baseUrl(self::API_BASE)
            ->withHeaders(['access_token' => $accessToken])
            ->timeout(30)
            ->retry(2, 500, throw: false);
    }

    /** @param array<string,mixed> $query @return array<string,mixed> */
    public function get(string $accessToken, string $path, array $query = []): array
    {
        return $this->unwrap($this->base($accessToken)->get($path, $query)->json() ?? []);
    }

    /** @param array<string,mixed> $json @return array<string,mixed> */
    public function post(string $accessToken, string $path, array $json): array
    {
        return $this->unwrap($this->base($accessToken)->asJson()->post($path, $json)->json() ?? []);
    }

    /** @return array<string,mixed> */
    public function uploadMultipart(string $accessToken, string $path, string $fieldName, string $contents, string $filename, string $mime): array
    {
        $res = $this->base($accessToken)
            ->attach($fieldName, $contents, $filename, ['Content-Type' => $mime])
            ->post($path)
            ->json() ?? [];

        return $this->unwrap($res);
    }

    /**
     * Đổi/refresh token: POST form-urlencoded tới oauth.zaloapp.com, secret ở header `secret_key`.
     * @param array<string,string> $form @return array<string,mixed>
     */
    public function oauthToken(array $form, string $appSecret): array
    {
        $res = Http::asForm()
            ->withHeaders(['secret_key' => $appSecret])
            ->timeout(30)
            ->post(self::OAUTH_TOKEN_URL, $form);

        $json = $res->json() ?? [];
        if (isset($json['error']) && (int) $json['error'] !== 0) {
            throw ZaloApiException::from((int) $json['error'], (string) ($json['message'] ?? 'oauth error'));
        }
        if (empty($json['access_token'])) {
            throw ZaloApiException::from(-1, 'Zalo oauth: missing access_token: '.json_encode($json));
        }

        return $json;
    }

    /** @param array<string,mixed> $json @return array<string,mixed> */
    private function unwrap(array $json): array
    {
        $error = (int) ($json['error'] ?? 0);
        if ($error !== 0) {
            throw ZaloApiException::from($error, (string) ($json['message'] ?? 'unknown'));
        }

        return (array) ($json['data'] ?? []);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/Messaging/Zalo/ZaloClientTest.php`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Messaging/Zalo/ZaloApiException.php app/app/Integrations/Messaging/Zalo/ZaloClient.php app/tests/Unit/Messaging/Zalo/ZaloClientTest.php
git commit -m "feat(messaging-zalo): ZaloClient + ZaloApiException (envelope-aware HTTP)"
```

---

## Task 3: ZaloOaConnector — identity, capabilities, unsupported stubs

**Files:**
- Create: `app/app/Integrations/Messaging/Zalo/ZaloOaConnector.php`
- Test: `app/tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php`

**Interfaces:**
- Consumes: `ZaloSignatureVerifier` (Task 1), `ZaloClient` (Task 2), DTOs (`MessagingAuthContext`, `SendResultDTO`, `OutboundWindowPolicyDTO`, `MessagingWebhookEventDTO`, `MessageKind`, `MediaRefDTO`, `MessageDirection`, `TokenDTO`).
- Produces: `new ZaloOaConnector(array $config, ZaloSignatureVerifier $verifier, ZaloClient $client)` implementing `MessagingConnector, InteractiveMessagingConnector`. `code()='zalo_oa'`. Capability map below.

Constructor config keys: `app_id, app_secret, oa_secret, redirect_uri`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Messaging\Zalo;

use CMBcoreSeller\Integrations\Messaging\Contracts\InteractiveMessagingConnector;
use CMBcoreSeller\Integrations\Messaging\Contracts\MessagingConnector;
use CMBcoreSeller\Integrations\Messaging\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloClient;
use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloOaConnector;
use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloSignatureVerifier;
use Tests\TestCase;

class ZaloOaConnectorTest extends TestCase
{
    private function connector(): ZaloOaConnector
    {
        return new ZaloOaConnector(
            ['app_id' => 'app_123', 'app_secret' => 'sec', 'oa_secret' => 'oa_secret_xyz', 'redirect_uri' => 'https://x.test/oauth/zalo_oa/callback'],
            new ZaloSignatureVerifier,
            new ZaloClient,
        );
    }

    public function test_identity_and_interfaces(): void
    {
        $c = $this->connector();
        $this->assertSame('zalo_oa', $c->code());
        $this->assertInstanceOf(MessagingConnector::class, $c);
        $this->assertInstanceOf(InteractiveMessagingConnector::class, $c);
    }

    public function test_capability_map(): void
    {
        $c = $this->connector();
        $this->assertTrue($c->supports('inbound.webhook'));
        $this->assertTrue($c->supports('inbound.postback'));
        $this->assertTrue($c->supports('outbound.text'));
        $this->assertTrue($c->supports('outbound.image'));
        $this->assertTrue($c->supports('outbound.file'));
        $this->assertTrue($c->supports('outbound.interactive'));
        $this->assertTrue($c->supports('read_receipt'));
        $this->assertFalse($c->supports('outbound.video'));
        $this->assertFalse($c->supports('outbound.utility_template'));
    }

    public function test_comment_ops_unsupported(): void
    {
        $this->expectException(UnsupportedOperation::class);
        $this->connector()->hideComment(
            new \CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext(1, 'zalo_oa', 'oa1', 'TKN'),
            'c1', true,
        );
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Write the implementation (skeleton — methods filled in Tasks 4-9)**

```php
<?php

namespace CMBcoreSeller\Integrations\Messaging\Zalo;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;
use CMBcoreSeller\Integrations\Messaging\Contracts\InteractiveMessagingConnector;
use CMBcoreSeller\Integrations\Messaging\Contracts\MessagingConnector;
use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\OutboundWindowPolicyDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\Page;
use CMBcoreSeller\Integrations\Messaging\DTO\SendResultDTO;
use CMBcoreSeller\Integrations\Messaging\Exceptions\UnsupportedOperation;
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
    public function buildAuthorizationUrl(string $state, array $opts = []): string
    {
        throw UnsupportedOperation::for($this->code(), 'buildAuthorizationUrl'); // replaced in Task 6
    }

    public function exchangeCodeForToken(string $code): TokenDTO
    {
        throw UnsupportedOperation::for($this->code(), 'exchangeCodeForToken'); // Task 6
    }

    public function refreshToken(string $refreshToken): TokenDTO
    {
        throw UnsupportedOperation::for($this->code(), 'refreshToken'); // Task 6
    }

    public function registerWebhooks(MessagingAuthContext $auth): void
    {
        // Zalo OA webhook cấu hình trên Zalo Developer Console (URL cố định), không gọi API ở đây.
    }

    /** @return array{name: ?string, avatar_url: ?string} */
    public function fetchPageProfile(MessagingAuthContext $auth): array
    {
        throw UnsupportedOperation::for($this->code(), 'fetchPageProfile'); // Task 6
    }

    /** @return array{name: ?string, avatar_url: ?string} */
    public function fetchUserProfile(MessagingAuthContext $auth, string $externalUserId): array
    {
        throw UnsupportedOperation::for($this->code(), 'fetchUserProfile'); // Task 6
    }

    // --- Inbound (Task 4-5) ---
    public function verifyWebhookSignature(Request $request): bool
    {
        throw UnsupportedOperation::for($this->code(), 'verifyWebhookSignature'); // Task 4
    }

    public function parseWebhook(Request $request): MessagingWebhookEventDTO
    {
        throw UnsupportedOperation::for($this->code(), 'parseWebhook'); // Task 5
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
        throw UnsupportedOperation::for($this->code(), 'sendText'); // Task 7
    }

    public function sendMedia(MessagingAuthContext $auth, string $externalConversationId, MediaRefDTO $media, array $opts = []): SendResultDTO
    {
        throw UnsupportedOperation::for($this->code(), 'sendMedia'); // Task 8
    }

    public function sendTemplate(MessagingAuthContext $auth, string $externalConversationId, string $templateKey, array $vars = [], array $opts = []): SendResultDTO
    {
        throw UnsupportedOperation::for($this->code(), 'sendTemplate'); // ZNS — Phase 3
    }

    public function sendInteractive(MessagingAuthContext $auth, string $externalConversationId, array $structure, array $opts = []): SendResultDTO
    {
        throw UnsupportedOperation::for($this->code(), 'sendInteractive'); // Task 9
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

    /** @return array<string,mixed> */
    private function cfg(string $key): mixed
    {
        return $this->config[$key] ?? '';
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Messaging/Zalo/ZaloOaConnector.php app/tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php
git commit -m "feat(messaging-zalo): ZaloOaConnector skeleton + capability map"
```

---

## Task 4: verifyWebhookSignature on connector

**Files:**
- Modify: `app/app/Integrations/Messaging/Zalo/ZaloOaConnector.php` (`verifyWebhookSignature`)
- Test: append to `app/tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php`

**Interfaces:**
- Consumes: `ZaloSignatureVerifier::verify` (Task 1), config `app_id`/`oa_secret`.

- [ ] **Step 1: Write the failing test (append to ZaloOaConnectorTest)**

```php
    public function test_verify_webhook_signature_delegates_to_verifier(): void
    {
        $body = '{"app_id":"app_123","event_name":"user_send_text","timestamp":"1700000000"}';
        $mac = 'mac='.hash('sha256', 'app_123'.$body.'1700000000'.'oa_secret_xyz');
        $req = \Symfony\Component\HttpFoundation\Request::create('/webhook/messaging/zalo_oa', 'POST', [], [], [], ['HTTP_X_ZEVENT_SIGNATURE' => $mac], $body);

        $this->assertTrue($this->connector()->verifyWebhookSignature($req));
    }

    public function test_verify_webhook_signature_rejects_bad(): void
    {
        $req = \Symfony\Component\HttpFoundation\Request::create('/webhook/messaging/zalo_oa', 'POST', [], [], [], ['HTTP_X_ZEVENT_SIGNATURE' => 'mac=bad'], '{"timestamp":"1"}');
        $this->assertFalse($this->connector()->verifyWebhookSignature($req));
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php --filter verify_webhook`
Expected: FAIL — throws `UnsupportedOperation`.

- [ ] **Step 3: Replace `verifyWebhookSignature` body**

```php
    public function verifyWebhookSignature(Request $request): bool
    {
        return $this->verifier->verify($request, (string) $this->cfg('app_id'), (string) $this->cfg('oa_secret'));
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php --filter verify_webhook`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Messaging/Zalo/ZaloOaConnector.php app/tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php
git commit -m "feat(messaging-zalo): wire verifyWebhookSignature"
```

---

## Task 5: parseWebhook — inbound events

**Files:**
- Modify: `app/app/Integrations/Messaging/Zalo/ZaloOaConnector.php` (`parseWebhook`)
- Test: append to `app/tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php`

**Interfaces:**
- Produces: `parseWebhook` maps Zalo events → `MessagingWebhookEventDTO`. Text → `TYPE_MESSAGE_RECEIVED` kind Text; image/file → media attachment; postback (`message.text` starting `postback_`) → `TYPE_POSTBACK`; `user_seen_message` → `TYPE_MESSAGE_READ`; unknown → `TYPE_UNKNOWN`. `externalShopId = recipient.id` (OA), `buyerExternalId = sender.id` (user), `externalConversationId = sender.id`.

- [ ] **Step 1: Write the failing tests (append)**

```php
    private function webhookRequest(array $payload): \Symfony\Component\HttpFoundation\Request
    {
        return \Symfony\Component\HttpFoundation\Request::create('/webhook/messaging/zalo_oa', 'POST', [], [], [], [], json_encode($payload));
    }

    public function test_parse_user_send_text(): void
    {
        $dto = $this->connector()->parseWebhook($this->webhookRequest([
            'app_id' => 'app_123', 'event_name' => 'user_send_text', 'timestamp' => '1700000000',
            'sender' => ['id' => 'USER_1'], 'recipient' => ['id' => 'OA_9'],
            'message' => ['msg_id' => 'MID_1', 'text' => 'Còn hàng không shop?'],
        ]));

        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, $dto->type);
        $this->assertSame('OA_9', $dto->externalShopId);
        $this->assertSame('USER_1', $dto->buyerExternalId);
        $this->assertSame('USER_1', $dto->externalConversationId);
        $this->assertSame('MID_1', $dto->externalMessageId);
        $this->assertSame('Còn hàng không shop?', $dto->body);
        $this->assertSame(\CMBcoreSeller\Integrations\Messaging\DTO\MessageKind::Text, $dto->kind);
    }

    public function test_parse_user_send_image_builds_attachment(): void
    {
        $dto = $this->connector()->parseWebhook($this->webhookRequest([
            'app_id' => 'app_123', 'event_name' => 'user_send_image', 'timestamp' => '1700000001',
            'sender' => ['id' => 'USER_1'], 'recipient' => ['id' => 'OA_9'],
            'message' => ['msg_id' => 'MID_2', 'attachments' => [['type' => 'image', 'payload' => ['url' => 'https://zalo.test/a.jpg']]]],
        ]));

        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED, $dto->type);
        $this->assertSame(\CMBcoreSeller\Integrations\Messaging\DTO\MessageKind::Image, $dto->kind);
        $this->assertCount(1, $dto->attachments);
        $this->assertSame('https://zalo.test/a.jpg', $dto->attachments[0]->externalUrl);
    }

    public function test_parse_postback(): void
    {
        $dto = $this->connector()->parseWebhook($this->webhookRequest([
            'app_id' => 'app_123', 'event_name' => 'user_send_text', 'timestamp' => '1700000002',
            'sender' => ['id' => 'USER_1'], 'recipient' => ['id' => 'OA_9'],
            'message' => ['msg_id' => 'MID_3', 'text' => 'postback_eyJub2RlX2lkIjoibjEifQ=='],
        ]));

        $this->assertSame(MessagingWebhookEventDTO::TYPE_POSTBACK, $dto->type);
        $this->assertSame('postback_eyJub2RlX2lkIjoibjEifQ==', $dto->body);
    }

    public function test_parse_seen_and_unknown(): void
    {
        $seen = $this->connector()->parseWebhook($this->webhookRequest([
            'app_id' => 'app_123', 'event_name' => 'user_seen_message', 'timestamp' => '1700000003',
            'sender' => ['id' => 'USER_1'], 'recipient' => ['id' => 'OA_9'],
        ]));
        $this->assertSame(MessagingWebhookEventDTO::TYPE_MESSAGE_READ, $seen->type);

        $unknown = $this->connector()->parseWebhook($this->webhookRequest([
            'app_id' => 'app_123', 'event_name' => 'oa_send_text', 'timestamp' => '1700000004',
            'sender' => ['id' => 'OA_9'], 'recipient' => ['id' => 'USER_1'],
        ]));
        $this->assertSame(MessagingWebhookEventDTO::TYPE_UNKNOWN, $unknown->type);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php --filter parse_`
Expected: FAIL — throws `UnsupportedOperation`.

- [ ] **Step 3: Replace `parseWebhook` body**

```php
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

    /** @param array<string,mixed> $msg @return list<MediaRefDTO> */
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
                mime: match ($kind) { MessageKind::Image => 'image/jpeg', MessageKind::Audio => 'audio/mpeg', default => 'application/octet-stream' },
                externalUrl: $url,
                filename: (string) ($payload['name'] ?? ''),
            );
        }

        return $out;
    }
```

Add to the `use` block (top of connector): `use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;` (already imported in Task 3 via `MediaRefDTO`? No — add `MessageKind` and `MessageDirection` imports.) Ensure these two imports exist:
```php
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDirection;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php --filter parse_`
Expected: PASS (4 tests).

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Messaging/Zalo/ZaloOaConnector.php app/tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php
git commit -m "feat(messaging-zalo): parseWebhook for inbound events"
```

---

## Task 6: OAuth — authorize URL, token exchange, refresh, profiles

**Files:**
- Modify: `app/app/Integrations/Messaging/Zalo/ZaloOaConnector.php` (5 methods)
- Test: append to `app/tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php`

**Interfaces:**
- Produces: `buildAuthorizationUrl(state)` → URL; `exchangeCodeForToken(code): TokenDTO`; `refreshToken(refreshToken): TokenDTO`; `fetchUserProfile(auth, userId): array{name,avatar_url}`; `fetchPageProfile(auth): array{name,avatar_url}`.
- TokenDTO ctor: `new TokenDTO(accessToken:, refreshToken:, expiresAt:, raw:)` — verify properties exist (`accessToken, refreshToken, expiresAt, refreshExpiresAt, scope, raw`).

- [ ] **Step 1: Write the failing tests (append)**

```php
    public function test_build_authorization_url(): void
    {
        $url = $this->connector()->buildAuthorizationUrl('STATE_X');
        $this->assertStringContainsString('oauth.zaloapp.com/v4/oa/permission', $url);
        $this->assertStringContainsString('app_id=app_123', $url);
        $this->assertStringContainsString('state=STATE_X', $url);
        $this->assertStringContainsString('redirect_uri=', $url);
        $this->assertStringNotContainsString('scope=', $url);
    }

    public function test_exchange_code_for_token(): void
    {
        \Illuminate\Support\Facades\Http::fake(['oauth.zaloapp.com/v4/oa/access_token' => \Illuminate\Support\Facades\Http::response(['access_token' => 'AT', 'refresh_token' => 'RT', 'expires_in' => '90000'], 200)]);

        $token = $this->connector()->exchangeCodeForToken('CODE_1');

        $this->assertSame('AT', $token->accessToken);
        $this->assertSame('RT', $token->refreshToken);
        $this->assertNotNull($token->expiresAt);
        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => $r['grant_type'] === 'authorization_code' && $r['code'] === 'CODE_1' && $r->hasHeader('secret_key', 'sec'));
    }

    public function test_refresh_token_rotates(): void
    {
        \Illuminate\Support\Facades\Http::fake(['oauth.zaloapp.com/v4/oa/access_token' => \Illuminate\Support\Facades\Http::response(['access_token' => 'AT2', 'refresh_token' => 'RT2', 'expires_in' => '90000'], 200)]);

        $token = $this->connector()->refreshToken('RT1');

        $this->assertSame('AT2', $token->accessToken);
        $this->assertSame('RT2', $token->refreshToken);
        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => $r['grant_type'] === 'refresh_token' && $r['refresh_token'] === 'RT1');
    }

    public function test_fetch_user_profile(): void
    {
        \Illuminate\Support\Facades\Http::fake(['openapi.zalo.me/v3.0/oa/user/detail*' => \Illuminate\Support\Facades\Http::response(['error' => 0, 'data' => ['user_id' => 'USER_1', 'display_name' => 'Nguyễn A', 'avatar' => 'https://zalo.test/av.jpg']], 200)]);

        $profile = $this->connector()->fetchUserProfile(new \CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext(1, 'zalo_oa', 'OA_9', 'TKN'), 'USER_1');

        $this->assertSame('Nguyễn A', $profile['name']);
        $this->assertSame('https://zalo.test/av.jpg', $profile['avatar_url']);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php --filter "authorization_url|exchange_code|refresh_token_rotates|user_profile"`
Expected: FAIL — throws `UnsupportedOperation`.

- [ ] **Step 3: Replace the 5 method bodies**

```php
    public function buildAuthorizationUrl(string $state, array $opts = []): string
    {
        return 'https://oauth.zaloapp.com/v4/oa/permission?'.http_build_query([
            'app_id' => (string) $this->cfg('app_id'),
            'redirect_uri' => $opts['redirect_uri'] ?? (string) $this->cfg('redirect_uri'),
            'state' => $state,
        ]);
        // NEEDS-VERIFY: Zalo OA cấp quyền ở mức app/OA, không có scope.
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

    /** @return array{name: ?string, avatar_url: ?string} */
    public function fetchPageProfile(MessagingAuthContext $auth): array
    {
        $data = $this->client->get($auth->accessToken, 'v2.0/oa/getoa');

        return ['name' => $data['name'] ?? null, 'avatar_url' => $data['avatar'] ?? null];
    }

    /** @return array{name: ?string, avatar_url: ?string} */
    public function fetchUserProfile(MessagingAuthContext $auth, string $externalUserId): array
    {
        $data = $this->client->get($auth->accessToken, 'v3.0/oa/user/detail', [
            'data' => json_encode(['user_id' => $externalUserId], JSON_UNESCAPED_UNICODE),
        ]);

        return ['name' => $data['display_name'] ?? null, 'avatar_url' => $data['avatar'] ?? null];
    }
```

Verify `TokenDTO` accepts named args `accessToken, refreshToken, expiresAt, raw`. If `refreshToken` or `raw` differs, run `php artisan tinker --execute="echo (new ReflectionClass(\CMBcoreSeller\Integrations\Channels\DTO\TokenDTO::class))->getConstructor();"` — adjust arg names to match.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php`
Expected: PASS (all).

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Messaging/Zalo/ZaloOaConnector.php app/tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php
git commit -m "feat(messaging-zalo): OAuth exchange/refresh + profiles"
```

---

## Task 7: sendText (CS message)

**Files:**
- Modify: `app/app/Integrations/Messaging/Zalo/ZaloOaConnector.php` (`sendText` + private `sendCs`)
- Test: append to test file.

**Interfaces:**
- Produces: `sendText` → POST `v3.0/oa/message/cs` body `{recipient:{user_id}, message:{text}}`; returns `SendResultDTO(externalMessageId from data.message_id)`.

- [ ] **Step 1: Write the failing test (append)**

```php
    public function test_send_text_posts_cs_shape(): void
    {
        \Illuminate\Support\Facades\Http::fake(['openapi.zalo.me/v3.0/oa/message/cs' => \Illuminate\Support\Facades\Http::response(['error' => 0, 'message' => 'Success', 'data' => ['message_id' => 'OUT_1', 'user_id' => 'USER_1']], 200)]);

        $auth = new \CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext(1, 'zalo_oa', 'OA_9', 'TKN');
        $result = $this->connector()->sendText($auth, 'USER_1', 'Dạ còn hàng ạ!');

        $this->assertSame('OUT_1', $result->externalMessageId);
        \Illuminate\Support\Facades\Http::assertSent(function ($r) {
            $d = $r->data();

            return str_contains($r->url(), '/v3.0/oa/message/cs')
                && $r->hasHeader('access_token', 'TKN')
                && ($d['recipient']['user_id'] ?? null) === 'USER_1'
                && ($d['message']['text'] ?? null) === 'Dạ còn hàng ạ!';
        });
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php --filter send_text`
Expected: FAIL — `UnsupportedOperation`.

- [ ] **Step 3: Replace `sendText`, add private `sendCs`**

```php
    public function sendText(MessagingAuthContext $auth, string $externalConversationId, string $body, array $opts = []): SendResultDTO
    {
        return $this->sendCs($auth, $externalConversationId, ['text' => $body]);
    }

    /**
     * @param array<string,mixed> $message  message template Zalo (text / attachment)
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
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php --filter send_text`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Messaging/Zalo/ZaloOaConnector.php app/tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php
git commit -m "feat(messaging-zalo): sendText (CS message)"
```

---

## Task 8: sendMedia (2-step upload)

**Files:**
- Modify: `app/app/Integrations/Messaging/Zalo/ZaloOaConnector.php` (`sendMedia`)
- Test: append to test file.

**Interfaces:**
- Consumes: `MediaRefDTO` (`externalUrl`/`storagePath`, `mime`, `filename`, `kind`).
- Produces: `sendMedia` — ảnh: upload `v2.0/oa/upload/image` → `attachment_id` → CS `attachment.type=template, payload.template_type=media`. File: upload `v2.0/oa/upload/file` → `token` → CS `attachment.type=file`.

For Phase 1, read media bytes from the `MediaRefDTO->storagePath` on the configured disk (outbound media is uploaded to storage first by `OutboundMessageService::queueMedia`). Fall back to `externalUrl` fetch when no `storagePath`.

- [ ] **Step 1: Write the failing test (append)**

```php
    public function test_send_media_image_uploads_then_sends(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Storage::disk('local')->put('media/x.jpg', 'BYTES');

        \Illuminate\Support\Facades\Http::fake([
            'openapi.zalo.me/v2.0/oa/upload/image' => \Illuminate\Support\Facades\Http::response(['error' => 0, 'data' => ['attachment_id' => 'ATT_1']], 200),
            'openapi.zalo.me/v3.0/oa/message/cs' => \Illuminate\Support\Facades\Http::response(['error' => 0, 'data' => ['message_id' => 'OUT_2']], 200),
        ]);

        $media = new \CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO(
            kind: \CMBcoreSeller\Integrations\Messaging\DTO\MessageKind::Image,
            mime: 'image/jpeg', storagePath: 'media/x.jpg', filename: 'x.jpg',
        );
        $auth = new \CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext(1, 'zalo_oa', 'OA_9', 'TKN');

        $result = $this->connector()->sendMedia($auth, 'USER_1', $media, ['disk' => 'local']);

        $this->assertSame('OUT_2', $result->externalMessageId);
        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => str_contains($r->url(), '/v2.0/oa/upload/image'));
        \Illuminate\Support\Facades\Http::assertSent(function ($r) {
            $d = $r->data();

            return str_contains($r->url(), '/v3.0/oa/message/cs')
                && (($d['message']['attachment']['payload']['template_type'] ?? null) === 'media');
        });
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php --filter send_media`
Expected: FAIL — `UnsupportedOperation`.

- [ ] **Step 3: Replace `sendMedia`**

```php
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
            $contents = (string) \Illuminate\Support\Facades\Storage::disk($disk)->get($media->storagePath);
        } elseif ($media->externalUrl) {
            $contents = (string) \Illuminate\Support\Facades\Http::get($media->externalUrl)->body();
        } else {
            throw new \RuntimeException('Zalo sendMedia: media has neither storagePath nor externalUrl');
        }

        return [$contents, $filename, $mime];
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php --filter send_media`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Messaging/Zalo/ZaloOaConnector.php app/tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php
git commit -m "feat(messaging-zalo): sendMedia 2-step upload"
```

---

## Task 9: sendInteractive (buttons ≤5)

**Files:**
- Modify: `app/app/Integrations/Messaging/Zalo/ZaloOaConnector.php` (`sendInteractive`)
- Test: append to test file.

**Interfaces:**
- Consumes: `$structure = ['text'=>?, 'buttons'=>list<array{title|label, url?, payload?}>]`.
- Produces: CS message `attachment.type=template, payload.buttons[]` (≤5). Nút có `url` → `oa.open.url`; còn lại → `oa.query.hide` với `payload = 'postback_'.<payload>`. Title cắt ≤20 ký tự.

- [ ] **Step 1: Write the failing test (append)**

```php
    public function test_send_interactive_maps_buttons_and_caps_at_5(): void
    {
        \Illuminate\Support\Facades\Http::fake(['openapi.zalo.me/v3.0/oa/message/cs' => \Illuminate\Support\Facades\Http::response(['error' => 0, 'data' => ['message_id' => 'OUT_3']], 200)]);

        $auth = new \CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext(1, 'zalo_oa', 'OA_9', 'TKN');
        $structure = [
            'text' => 'Chọn nhé',
            'buttons' => [
                ['title' => 'Website', 'url' => 'https://shop.test'],
                ['title' => 'Đặt hàng', 'payload' => 'ENC_1'],
                ['title' => 'B3', 'payload' => 'p3'], ['title' => 'B4', 'payload' => 'p4'],
                ['title' => 'B5', 'payload' => 'p5'], ['title' => 'B6_DROP', 'payload' => 'p6'],
            ],
        ];

        $result = $this->connector()->sendInteractive($auth, 'USER_1', $structure);
        $this->assertSame('OUT_3', $result->externalMessageId);

        \Illuminate\Support\Facades\Http::assertSent(function ($r) {
            $btns = $r->data()['message']['attachment']['payload']['buttons'] ?? [];

            return count($btns) === 5
                && $btns[0]['type'] === 'oa.open.url'
                && $btns[1]['type'] === 'oa.query.hide'
                && $btns[1]['payload'] === 'postback_ENC_1';
        });
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php --filter send_interactive`
Expected: FAIL — `UnsupportedOperation`.

- [ ] **Step 3: Replace `sendInteractive`**

```php
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
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php`
Expected: PASS (all connector tests).

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Messaging/Zalo/ZaloOaConnector.php app/tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php
git commit -m "feat(messaging-zalo): sendInteractive button mapping"
```

---

## Task 10: Wiring — config, registry, route, provider maps

**Files:**
- Modify: `app/config/integrations.php`
- Modify: `app/app/Integrations/IntegrationsServiceProvider.php`
- Modify: `app/routes/webhook.php`
- Modify: `app/app/Modules/Messaging/Jobs/ProcessMessagingWebhook.php`
- Modify: `app/app/Modules/Channels/Models/ChannelAccount.php`
- Test: `app/tests/Feature/Messaging/ZaloOaWebhookTest.php`

**Interfaces:**
- Consumes: `ZaloOaConnector` (Tasks 3-9).
- Produces: `zalo_oa` resolvable via `MessagingRegistry` when CSV includes it; webhook route accepts `zalo_oa`; inbound creates Conversation/Message.

- [ ] **Step 1: Write the failing feature test**

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZaloOaWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('integrations.messaging', ['zalo_oa']);
        config()->set('integrations.messaging_zalo_oa', [
            'app_id' => 'app_123', 'app_secret' => 'sec', 'oa_secret' => 'oa_secret_xyz', 'redirect_uri' => 'https://x.test/cb',
        ]);
    }

    public function test_zalo_webhook_ingests_text_message(): void
    {
        $tenant = \CMBcoreSeller\Modules\Tenancy\Models\Tenant::factory()->create();
        ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->id, 'provider' => 'zalo_oa', 'external_shop_id' => 'OA_9',
            'shop_name' => 'Shop Zalo', 'access_token' => 'TKN', 'status' => ChannelAccount::STATUS_ACTIVE,
            'messaging_enabled' => true,
        ]);

        $payload = ['app_id' => 'app_123', 'event_name' => 'user_send_text', 'timestamp' => (string) (now()->valueOf()),
            'sender' => ['id' => 'USER_1'], 'recipient' => ['id' => 'OA_9'], 'message' => ['msg_id' => 'MID_1', 'text' => 'Xin chào shop']];
        $body = json_encode($payload);
        $mac = 'mac='.hash('sha256', 'app_123'.$body.$payload['timestamp'].'oa_secret_xyz');

        $this->call('POST', '/webhook/messaging/zalo_oa', [], [], [], ['HTTP_X_ZEVENT_SIGNATURE' => $mac, 'CONTENT_TYPE' => 'application/json'], $body)
            ->assertOk();

        // Webhook ingest dispatches ProcessMessagingWebhook (sync queue in tests) → conversation created.
        $this->assertDatabaseHas('conversations', ['provider' => 'zalo_oa', 'buyer_external_id' => 'USER_1']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/Messaging/ZaloOaWebhookTest.php`
Expected: FAIL — route `zalo_oa` not allowed (404) / connector not registered.

- [ ] **Step 3: Apply the wiring edits**

`config/integrations.php` — add after the `messaging_facebook_page` block:
```php
'messaging_zalo_oa' => [
    'app_id' => env('MESSAGING_ZALO_APP_ID'),
    'app_secret' => env('MESSAGING_ZALO_APP_SECRET'),
    'oa_secret' => env('MESSAGING_ZALO_OA_SECRET'),
    'redirect_uri' => env('MESSAGING_ZALO_REDIRECT_URI'),
],
```

`IntegrationsServiceProvider.php` — add import at top:
```php
use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloClient;
use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloOaConnector;
use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloSignatureVerifier;
```
Add to `$messagingConnectors`:
```php
'zalo_oa' => ZaloOaConnector::class,
```
Add an explicit `bind()` next to the Facebook/Shopee binds:
```php
$this->app->bind(ZaloOaConnector::class, function ($app) {
    return new ZaloOaConnector(
        (array) config('integrations.messaging_zalo_oa', []),
        $app->make(ZaloSignatureVerifier::class),
        $app->make(ZaloClient::class),
    );
});
```

`routes/webhook.php` — add `'zalo_oa'` to the `whereIn`:
```php
->whereIn('provider', ['manual', 'facebook_page', 'facebook', 'tiktok_chat', 'lazada_chat', 'zalo_oa'])
```

`ProcessMessagingWebhook.php` — add the explicit mapping line in `channelProviderForMessaging()`:
```php
'zalo_oa' => 'zalo_oa',
```

`ChannelAccount.php` — extend the list + the code map:
```php
public const MESSAGING_ONLY_PROVIDERS = ['facebook_page', 'lazada_im', 'zalo_oa'];
```
```php
public function messagingConnectorCode(): ?string
{
    return match ($this->provider) {
        'lazada_im' => 'lazada_chat',
        'tiktok' => 'tiktok_chat',
        'shopee' => 'shopee_chat',
        'facebook_page' => 'facebook_page',
        'zalo_oa' => 'zalo_oa',
        default => null,
    };
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/Messaging/ZaloOaWebhookTest.php`
Expected: PASS. If the ingest pipeline runs jobs async, set `Queue::fake()` off (tests use sync) — the existing `MessagingWebhookIngestTest` confirms sync ingest; mirror its setup if conversation isn't created.

- [ ] **Step 5: Run pint + phpstan, then commit**

Run: `vendor/bin/pint app/Integrations/Messaging/Zalo app/Modules/Messaging/Jobs/ProcessMessagingWebhook.php && vendor/bin/phpstan analyse app/Integrations/Messaging/Zalo`
```bash
git add app/config/integrations.php app/app/Integrations/IntegrationsServiceProvider.php app/routes/webhook.php app/app/Modules/Messaging/Jobs/ProcessMessagingWebhook.php app/app/Modules/Channels/Models/ChannelAccount.php app/tests/Feature/Messaging/ZaloOaWebhookTest.php
git commit -m "feat(messaging-zalo): wire connector (config, registry, webhook route, provider maps)"
```

---

## Task 11: ZaloOaOAuthController — connect + callback

**Files:**
- Create: `app/app/Modules/Messaging/Http/Controllers/ZaloOaOAuthController.php`
- Modify: `app/app/Modules/Messaging/Http/routes.php` (start route, auth)
- Modify: `app/routes/web.php` (callback route, no-auth)
- Test: `app/tests/Feature/Messaging/ZaloOaOAuthTest.php`

**Interfaces:**
- Consumes: `MessagingRegistry`, `OAuthState::issue`, `ChannelAccount`, `MessagingAccountMeta`, `CurrentTenant`.
- Produces: `GET /api/v1/messaging/zalo/connect` → `{data:{authorize_url}}`; `GET /oauth/zalo_oa/callback?code=&state=` → upserts ChannelAccount (provider `zalo_oa`), renders `oauth-callback` view.

- [ ] **Step 1: Write the failing feature test**

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\OAuthState;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZaloOaOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('integrations.messaging', ['zalo_oa']);
        config()->set('integrations.messaging_zalo_oa', ['app_id' => 'app_123', 'app_secret' => 'sec', 'oa_secret' => 'oa', 'redirect_uri' => 'https://x.test/oauth/zalo_oa/callback']);
    }

    public function test_start_returns_authorize_url(): void
    {
        [$user, $tenant] = $this->actingAsTenantUserWithPermission('messaging.connect'); // helper in TestCase; else create + Gate

        $this->getJson('/api/v1/messaging/zalo/connect', ['X-Tenant-Id' => $tenant->id])
            ->assertOk()
            ->assertJsonPath('data.authorize_url', fn ($u) => str_contains($u, 'oauth.zaloapp.com/v4/oa/permission'));
    }

    public function test_callback_upserts_channel_account(): void
    {
        $tenant = \CMBcoreSeller\Modules\Tenancy\Models\Tenant::factory()->create();
        $state = OAuthState::issue('zalo_oa', (int) $tenant->id, null, '/messaging/channels?connected=zalo_oa');

        Http::fake([
            'oauth.zaloapp.com/v4/oa/access_token' => Http::response(['access_token' => 'AT', 'refresh_token' => 'RT', 'expires_in' => '90000'], 200),
            'openapi.zalo.me/v2.0/oa/getoa' => Http::response(['error' => 0, 'data' => ['oa_id' => 'OA_9', 'name' => 'Shop Zalo', 'avatar' => 'https://zalo.test/a.png']], 200),
        ]);

        $this->get('/oauth/zalo_oa/callback?code=CODE_1&state='.$state->state)->assertOk();

        $acc = ChannelAccount::withoutGlobalScope(TenantScope::class)->where('provider', 'zalo_oa')->where('external_shop_id', 'OA_9')->first();
        $this->assertNotNull($acc);
        $this->assertSame('Shop Zalo', $acc->shop_name);
        $this->assertSame('AT', $acc->access_token);
        $this->assertSame('RT', $acc->refresh_token);
    }
}
```

> If `actingAsTenantUserWithPermission` does not exist in `Tests\TestCase`, replace the first line of `test_start_returns_authorize_url` with the project's standard auth helper (search `tests/Feature/Messaging/MessagingFacebookWebhookTest.php` for how messaging tests authenticate + set tenant), and grant `messaging.connect` via the role seeded for the user.

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/Messaging/ZaloOaOAuthTest.php`
Expected: FAIL — routes/controller missing.

- [ ] **Step 3: Create controller + routes**

`ZaloOaOAuthController.php` (mirror FacebookOAuthController; single OA per connect):
```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Messaging\Models\OAuthState;
use CMBcoreSeller\Modules\Tenancy\Support\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class ZaloOaOAuthController
{
    private const PROVIDER = 'zalo_oa';

    public function __construct(private MessagingRegistry $registry) {}

    public function start(Request $request): JsonResponse
    {
        Gate::authorize('messaging.connect');

        $tenantId = app(CurrentTenant::class)->id();
        $state = OAuthState::issue(self::PROVIDER, (int) $tenantId, $request->user()?->id, '/messaging/channels?connected=zalo_oa');

        $url = $this->registry->for(self::PROVIDER)->buildAuthorizationUrl($state->state);

        return response()->json(['data' => ['authorize_url' => $url]]);
    }

    public function callback(Request $request)
    {
        $code = (string) $request->query('code', '');
        $stateToken = (string) $request->query('state', '');
        $state = OAuthState::where('state', $stateToken)->where('provider', self::PROVIDER)->first();
        if ($code === '' || ! $state || $state->isExpired()) {
            return redirect('/messaging/channels?error=zalo_state');
        }

        $connector = $this->registry->for(self::PROVIDER);

        try {
            $token = $connector->exchangeCodeForToken($code);
            $auth = new MessagingAuthContext(0, self::PROVIDER, '', $token->accessToken);
            $profile = $connector->fetchPageProfile($auth);   // {name, avatar_url}
            $oaId = $connector->fetchOaId($auth);             // data.oa_id
        } catch (\Throwable $e) {
            Log::warning('messaging.zalo.connect_failed', ['error' => $e->getMessage()]);

            return redirect('/messaging/channels?error=zalo_exchange');
        }

        if ($oaId === '') {
            return redirect('/messaging/channels?error=zalo_oa_id');
        }

        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->withTrashed()->firstOrNew([
            'tenant_id' => $state->tenant_id, 'provider' => self::PROVIDER, 'external_shop_id' => $oaId,
        ]);
        $account->forceFill([
            'tenant_id' => $state->tenant_id,
            'shop_name' => $profile['name'] ?? 'Zalo OA',
            'access_token' => $token->accessToken,
            'refresh_token' => $token->refreshToken,
            'token_expires_at' => $token->expiresAt,
            'status' => ChannelAccount::STATUS_ACTIVE,
            'messaging_enabled' => true,
            'created_by' => $account->created_by ?? $state->created_by,
            'deleted_at' => null,
        ])->save();

        MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->updateOrCreate(
            ['channel_account_id' => (int) $account->getKey()],
            ['tenant_id' => (int) $account->tenant_id, 'messaging_enabled' => true, 'sync_status' => MessagingAccountMeta::SYNC_QUEUED],
        );
        BackfillMessagingChannel::dispatch((int) $account->getKey());

        $state->delete();

        return response()->view('oauth-callback', ['redirect' => '/messaging/channels?connected=zalo_oa']);
    }
}
```

> **Required before this controller compiles:** the controller calls `$connector->fetchOaId(...)`, which does not exist yet (`fetchPageProfile` returns only `{name,avatar_url}`, not `oa_id`). Add this method to `ZaloOaConnector`:
> ```php
> public function fetchOaId(MessagingAuthContext $auth): string
> {
>     return (string) ($this->client->get($auth->accessToken, 'v2.0/oa/getoa')['oa_id'] ?? '');
> }
> ```
> And add a unit test to `ZaloOaConnectorTest` (mirror `test_fetch_user_profile`):
> ```php
> public function test_fetch_oa_id(): void
> {
>     \Illuminate\Support\Facades\Http::fake(['openapi.zalo.me/v2.0/oa/getoa' => \Illuminate\Support\Facades\Http::response(['error' => 0, 'data' => ['oa_id' => 'OA_9', 'name' => 'Shop Zalo']], 200)]);
>     $oaId = $this->connector()->fetchOaId(new \CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext(0, 'zalo_oa', '', 'TKN'));
>     $this->assertSame('OA_9', $oaId);
> }
> ```

`app/app/Modules/Messaging/Http/routes.php` — add inside the authed messaging group:
```php
Route::get('messaging/zalo/connect', [\CMBcoreSeller\Modules\Messaging\Http\Controllers\ZaloOaOAuthController::class, 'start']);
```

`app/routes/web.php` — add a no-auth callback near the Facebook OAuth callback:
```php
Route::get('oauth/zalo_oa/callback', [\CMBcoreSeller\Modules\Messaging\Http\Controllers\ZaloOaOAuthController::class, 'callback']);
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/Messaging/ZaloOaOAuthTest.php`
Expected: PASS.

- [ ] **Step 5: pint + commit**

Run: `vendor/bin/pint app/Modules/Messaging/Http/Controllers/ZaloOaOAuthController.php`
```bash
git add app/app/Modules/Messaging/Http/Controllers/ZaloOaOAuthController.php app/app/Modules/Messaging/Http/routes.php app/routes/web.php app/app/Integrations/Messaging/Zalo/ZaloOaConnector.php app/tests/Feature/Messaging/ZaloOaOAuthTest.php app/tests/Unit/Messaging/Zalo/ZaloOaConnectorTest.php
git commit -m "feat(messaging-zalo): OA connect + callback (OAuth)"
```

---

## Task 12: Token refresh — service + job + command + schedule

**Files:**
- Create: `app/app/Modules/Messaging/Services/ZaloTokenRefresher.php`
- Create: `app/app/Modules/Messaging/Jobs/RefreshZaloToken.php`
- Create: `app/app/Console/Commands/RefreshZaloTokens.php`
- Modify: `app/routes/console.php`
- Test: `app/tests/Unit/Messaging/Zalo/ZaloTokenRefresherTest.php`

**Interfaces:**
- `ZaloTokenRefresher::refresh(ChannelAccount $account): bool` — lock per-account, call `MessagingRegistry->for('zalo_oa')->refreshToken(...)`, store rotated tokens; transient failure keeps `active`, auth error sets `expired`.
- `RefreshZaloToken(int $channelAccountId)` job, `ShouldBeUnique`, queue `tokens`.
- `messaging:zalo:refresh-tokens {--within=21600}` command queues refresh for active `zalo_oa` accounts expiring within window.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Messaging\Zalo;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Services\ZaloTokenRefresher;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZaloTokenRefresherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('integrations.messaging', ['zalo_oa']);
        config()->set('integrations.messaging_zalo_oa', ['app_id' => 'app_123', 'app_secret' => 'sec', 'oa_secret' => 'oa', 'redirect_uri' => 'https://x.test/cb']);
    }

    public function test_refresh_rotates_and_persists(): void
    {
        Http::fake(['oauth.zaloapp.com/v4/oa/access_token' => Http::response(['access_token' => 'AT2', 'refresh_token' => 'RT2', 'expires_in' => '90000'], 200)]);

        $tenant = \CMBcoreSeller\Modules\Tenancy\Models\Tenant::factory()->create();
        $acc = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->id, 'provider' => 'zalo_oa', 'external_shop_id' => 'OA_9',
            'access_token' => 'AT1', 'refresh_token' => 'RT1', 'status' => ChannelAccount::STATUS_ACTIVE,
            'token_expires_at' => now()->addMinutes(10), 'messaging_enabled' => true,
        ]);

        $ok = app(ZaloTokenRefresher::class)->refresh($acc->fresh());

        $this->assertTrue($ok);
        $fresh = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($acc->id);
        $this->assertSame('AT2', $fresh->access_token);
        $this->assertSame('RT2', $fresh->refresh_token);
        $this->assertSame(ChannelAccount::STATUS_ACTIVE, $fresh->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/Messaging/Zalo/ZaloTokenRefresherTest.php`
Expected: FAIL — class missing.

- [ ] **Step 3: Create the three classes + schedule line**

`ZaloTokenRefresher.php`:
```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloApiException;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ZaloTokenRefresher
{
    public function __construct(private MessagingRegistry $registry) {}

    public function refresh(ChannelAccount $account): bool
    {
        if (! $account->refresh_token || ! $this->registry->has('zalo_oa')) {
            return false;
        }

        $lock = Cache::lock('channel-token-refresh:'.$account->getKey(), 30);
        if (! $lock->get()) {
            $account->refresh();

            return $account->status === ChannelAccount::STATUS_ACTIVE;
        }

        try {
            $account->refresh(); // re-read inside lock (sibling may have rotated)
            $token = $this->registry->for('zalo_oa')->refreshToken((string) $account->refresh_token);
        } catch (ZaloApiException $e) {
            // Token bị thu hồi dứt khoát (-124) → expired; lỗi tạm thời giữ active.
            if (in_array($e->zaloError, [-124, -1001], true)) {
                $account->forceFill(['status' => ChannelAccount::STATUS_EXPIRED])->save();
            } else {
                Log::warning('messaging.zalo.refresh_transient', ['account' => $account->getKey(), 'error' => $e->zaloError]);
            }

            return false;
        } catch (\Throwable $e) {
            Log::warning('messaging.zalo.refresh_failed', ['account' => $account->getKey(), 'error' => $e->getMessage()]);

            return false;
        } finally {
            $lock->release();
        }

        $account->forceFill([
            'access_token' => $token->accessToken,
            'refresh_token' => $token->refreshToken ?: $account->refresh_token,
            'token_expires_at' => $token->expiresAt,
            'status' => ChannelAccount::STATUS_ACTIVE,
        ])->save();

        return true;
    }
}
```

`RefreshZaloToken.php`:
```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Services\ZaloTokenRefresher;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshZaloToken implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $channelAccountId)
    {
        $this->onQueue('tokens');
    }

    public function uniqueId(): string
    {
        return "refresh-zalo-token:{$this->channelAccountId}";
    }

    public function uniqueFor(): int
    {
        return 300;
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(ZaloTokenRefresher $refresher): void
    {
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($this->channelAccountId);
        if ($account) {
            $refresher->refresh($account);
        }
    }
}
```

`RefreshZaloTokens.php`:
```php
<?php

namespace CMBcoreSeller\Console\Commands;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Jobs\RefreshZaloToken;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Console\Command;

class RefreshZaloTokens extends Command
{
    protected $signature = 'messaging:zalo:refresh-tokens {--within=21600 : Refresh Zalo OA tokens expiring within this many seconds}';

    protected $description = 'Queue Zalo OA access-token refreshes before expiry (rotating refresh token)';

    public function handle(): int
    {
        $threshold = now()->addSeconds((int) $this->option('within'));

        $count = ChannelAccount::withoutGlobalScope(TenantScope::class)
            ->where('provider', 'zalo_oa')
            ->where('status', ChannelAccount::STATUS_ACTIVE)
            ->whereNotNull('refresh_token')
            ->where(function ($q) use ($threshold) {
                $q->whereNull('token_expires_at')->orWhere('token_expires_at', '<=', $threshold);
            })
            ->get()
            ->each(fn (ChannelAccount $a) => RefreshZaloToken::dispatch((int) $a->getKey()))
            ->count();

        $this->info("Queued {$count} Zalo token refresh job(s).");

        return self::SUCCESS;
    }
}
```

`routes/console.php` — add in the messaging block:
```php
Schedule::command('messaging:zalo:refresh-tokens')->hourly()->onOneServer()->withoutOverlapping();
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/Messaging/Zalo/ZaloTokenRefresherTest.php`
Expected: PASS.

- [ ] **Step 5: pint + commit**

Run: `vendor/bin/pint app/Modules/Messaging/Services/ZaloTokenRefresher.php app/Modules/Messaging/Jobs/RefreshZaloToken.php app/Console/Commands/RefreshZaloTokens.php`
```bash
git add app/app/Modules/Messaging/Services/ZaloTokenRefresher.php app/app/Modules/Messaging/Jobs/RefreshZaloToken.php app/app/Console/Commands/RefreshZaloTokens.php app/routes/console.php app/tests/Unit/Messaging/Zalo/ZaloTokenRefresherTest.php
git commit -m "feat(messaging-zalo): token refresh service + job + scheduled command"
```

---

## Task 13: Generalize MessagingChannelController (provider param)

**Files:**
- Modify: `app/app/Modules/Messaging/Http/Controllers/MessagingChannelController.php`
- Test: `app/tests/Feature/Messaging/ZaloOaChannelsListTest.php`

**Interfaces:**
- Produces: `GET /api/v1/messaging/channels?provider=zalo_oa` returns only `zalo_oa` channels; without `provider`, returns all messaging-enabled channels (current behavior must not regress for Facebook).

- [ ] **Step 1: Read the controller to find the hardcoded filter**

Run: `grep -n "facebook_page" app/app/Modules/Messaging/Http/Controllers/MessagingChannelController.php`
Expected: a `where('provider', 'facebook_page')` (or similar) in `index()`.

- [ ] **Step 2: Write the failing test**

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZaloOaChannelsListTest extends TestCase
{
    use RefreshDatabase;

    public function test_channels_filtered_by_provider(): void
    {
        [$user, $tenant] = $this->actingAsTenantUserWithPermission('messaging.view'); // match Task 11 note if helper differs

        foreach (['facebook_page' => 'FB_1', 'zalo_oa' => 'OA_9'] as $provider => $ext) {
            ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
                'tenant_id' => $tenant->id, 'provider' => $provider, 'external_shop_id' => $ext,
                'shop_name' => $provider, 'status' => ChannelAccount::STATUS_ACTIVE, 'messaging_enabled' => true,
            ]);
        }

        $this->getJson('/api/v1/messaging/channels?provider=zalo_oa', ['X-Tenant-Id' => $tenant->id])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.provider', 'zalo_oa');
    }
}
```

- [ ] **Step 3: Edit `index()` — accept optional `provider`, default to all messaging providers**

Replace the hardcoded `->where('provider', 'facebook_page')` with:
```php
$query = ChannelAccount::query()->where('messaging_enabled', true);
if ($provider = $request->query('provider')) {
    $query->where('provider', (string) $provider);
}
```
(Keep the rest of the method — resource mapping, capabilities — unchanged. Ensure the API Resource already exposes `provider`; if not, add `'provider' => $this->provider` to the resource array.)

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/Messaging/ZaloOaChannelsListTest.php`
Expected: PASS. Also run the existing channels test to confirm no regression: `vendor/bin/phpunit tests/Feature/Messaging --filter Channel`.

- [ ] **Step 5: pint + commit**

```bash
git add app/app/Modules/Messaging/Http/Controllers/MessagingChannelController.php app/tests/Feature/Messaging/ZaloOaChannelsListTest.php
git commit -m "feat(messaging-zalo): channels endpoint filters by provider"
```

---

## Task 14: Frontend — Zalo connect button + Zalo submenu

**Files:**
- Modify: `app/resources/js/lib/messagingConfig.tsx` — add `useStartZaloConnect()` hook (GET `/messaging/zalo/connect`, open popup).
- Modify: `app/resources/js/pages/MessagingChannelsPage.tsx` — "Kết nối Zalo OA" button (provider-aware).
- Modify: `app/resources/js/components/AppLayout.tsx` — add "Zalo OA" submenu under the "Tin nhắn" group.
- Modify: `app/resources/js/lib/desktop/appCatalog.tsx` — add "Zalo OA" child group in messaging app menu.
- Verify: `npm run lint && npm run typecheck && npm run build`.

**Interfaces:**
- Zalo routes reuse the same page components, provider-scoped via the channels filter. Phase 1 wires the connect entry-point + menu; deeper per-platform inbox filtering is incremental.

- [ ] **Step 1: Add the connect hook**

In `app/resources/js/lib/messagingConfig.tsx` (mirror the existing Facebook connect hook — find it with `grep -n "connect" app/resources/js/lib/messagingConfig.tsx app/resources/js/lib/messaging.tsx`):
```tsx
export function useStartZaloConnect() {
    return useMutation({
        mutationFn: async () => {
            const { data } = await api.get('/messaging/zalo/connect');
            return data.data.authorize_url as string;
        },
        onSuccess: (url) => { window.open(url, 'zalo_oauth', 'width=720,height=820'); },
    });
}
```
(Match the existing import style for `api` and `useMutation`. If a Facebook equivalent exists, copy its exact shape and rename.)

- [ ] **Step 2: Add the connect button**

In `app/resources/js/pages/MessagingChannelsPage.tsx`, near the existing "Kết nối Facebook" button, add (icon font, not emoji):
```tsx
<Button icon={<MessageOutlined />} onClick={() => startZalo.mutate()} loading={startZalo.isPending}>
    Kết nối Zalo OA
</Button>
```
Wire `const startZalo = useStartZaloConnect();` and import `MessageOutlined` from `@ant-design/icons` and `useStartZaloConnect` from the lib.

- [ ] **Step 3: Add the Zalo submenu (both shells)**

`app/resources/js/components/AppLayout.tsx` — inside the `{ type: 'group', label: 'Tin nhắn', children: [...] }` group, add a sibling submenu after the Facebook one:
```tsx
{ key: 'messaging-zalo', icon: <MessageOutlined />, label: 'Zalo OA', children: [
    { key: '/messaging?platform=zalo_oa', label: <Link to="/messaging?platform=zalo_oa">Hộp thư</Link> },
    { key: '/messaging/channels?platform=zalo_oa', label: <Link to="/messaging/channels?platform=zalo_oa">Kết nối Zalo OA</Link> },
    { key: '/messaging/auto-rules?platform=zalo_oa', label: <Link to="/messaging/auto-rules?platform=zalo_oa">Tự động trả lời</Link> },
    { key: '/messaging/flows?platform=zalo_oa', label: <Link to="/messaging/flows?platform=zalo_oa">Kịch bản tự động</Link> },
] },
```
Re-add `MessageOutlined` to the icon import in `AppLayout.tsx` (it was removed earlier; it is now used by the Zalo submenu).

`app/resources/js/lib/desktop/appCatalog.tsx` — add a sibling child group in the messaging app menu after `messaging-facebook`:
```tsx
{ key: 'messaging-zalo', label: 'Zalo OA', children: [
    { key: '/messaging?platform=zalo_oa', label: 'Hộp thư' },
    { key: '/messaging/channels?platform=zalo_oa', label: 'Kết nối Zalo OA' },
    { key: '/messaging/auto-rules?platform=zalo_oa', label: 'Tự động trả lời' },
    { key: '/messaging/flows?platform=zalo_oa', label: 'Kịch bản tự động' },
] },
```

> The `platform` query param scopes each page to a provider. The pages must read it and pass `provider` to the channels/conversations queries. If the pages don't yet read `platform`, add a minimal `const platform = new URLSearchParams(useLocation().search).get('platform') ?? 'facebook_page';` and thread it into the channel/conversation hooks. Keep Facebook the default when absent (no regression).

- [ ] **Step 4: Verify frontend builds**

Run: `npm run lint && npm run typecheck && npm run build`
Expected: lint 0 errors, typecheck clean, build succeeds. Fix any unused-import or type errors before committing.

- [ ] **Step 5: Commit**

```bash
git add app/resources/js/lib/messagingConfig.tsx app/resources/js/pages/MessagingChannelsPage.tsx app/resources/js/components/AppLayout.tsx app/resources/js/lib/desktop/appCatalog.tsx
git commit -m "feat(messaging-zalo): FE connect button + Zalo OA submenu"
```

---

## Final verification (after all tasks)

- [ ] Run the full backend suite for the new code:
  `vendor/bin/phpunit tests/Unit/Messaging/Zalo tests/Feature/Messaging/ZaloOaWebhookTest.php tests/Feature/Messaging/ZaloOaOAuthTest.php tests/Feature/Messaging/ZaloOaChannelsListTest.php`
- [ ] `vendor/bin/pint --test` (format), `vendor/bin/phpstan analyse` (level 5 — fix new findings only).
- [ ] `npm run lint && npm run typecheck && npm run build`.
- [ ] Push to main (per project autocommit convention).
- [ ] Update `docs/05-api/endpoints.md` with the two new endpoints (`GET /api/v1/messaging/zalo/connect`, `GET /oauth/zalo_oa/callback`) and `docs/specs/0039-zalo-oa-messaging.md` status → "Phase 1 Implemented".
- [ ] Add `INTEGRATIONS_MESSAGING=...,zalo_oa` + `MESSAGING_ZALO_*` placeholders to `app/.env` (dev) and note prod `./.env` needs them at deploy.

## Notes for the executor

- **`// NEEDS-VERIFY`** markers are best-effort Zalo protocol from ChatbotX; all behavior is tested via `Http::fake` so tasks pass regardless. When real credentials arrive, run a live smoke test of webhook signature + one CS send and adjust endpoint paths/field names only.
- Do not add Zalo menu links to pages/routes that don't render — Task 14 reuses existing `/messaging*` routes with a `platform` query param, so no dead links.
- Horizon: the `tokens` queue (used by `RefreshZaloToken`) and `messaging-outbound`/`messaging-webhooks` queues already exist in the supervisor; no new supervisor needed. Verify with `grep -rn "messaging-outbound\|'tokens'" app/config/horizon.php`.
