# Connector Shopee (Channels) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm connector Shopee (Channels) ngang Lazada/TikTok — OAuth + đồng bộ đơn + webhook + fulfillment + listings/tồn kho + tài chính — bằng cách mirror đúng pattern per-provider, không đụng core.

**Architecture:** 7 file mới trong `app/Integrations/Channels/Shopee/` (Connector/Client/Signer/Mappers/StatusMap/WebhookVerifier/ApiException) implement `ChannelConnector` trả DTO chuẩn; 1 khối config `shopee`; uncomment 1 dòng đăng ký. Seam dùng chung được mở rộng bằng tham số optional `array $context = []` trên `exchangeCodeForToken`/`refreshToken` để thread `shop_id` (Lazada/TikTok/Manual bỏ qua → no regression).

**Tech Stack:** Laravel 11 (PHP 8.3, PHPUnit). HMAC-SHA256 signing. Shopee Open Platform API v2. Tests: `Http::fake` + fixtures (không cần credentials thật).

> **Lệnh:** chạy từ `D:\cmb_core_seller\app`. BE test: `php artisan test --filter=<ClassName>` (docker: `docker compose exec -T app php artisan test --filter=<ClassName>`). Đường dẫn PHP gốc `D:\cmb_core_seller\app\` (vd `app/Integrations/...`).
>
> **Tài liệu nguồn:** spec `docs/superpowers/specs/2026-05-20-shopee-channel-connector-design.md`. Mẫu mirror: `app/app/Integrations/Channels/Lazada/*` + `app/app/Integrations/Channels/TikTok/*`. **Trước khi viết mỗi file connector, ĐỌC file Lazada tương ứng** để theo đúng cấu trúc (retry/throttle/fail, helper mappers).

---

## File Structure

**Tạo mới (`app/app/Integrations/Channels/Shopee/`):**
- `ShopeeSigner.php` — `signPublic()` / `signShop()` HMAC-SHA256.
- `ShopeeApiException.php` — RuntimeException + `$shopeeError` + `$httpStatus`.
- `ShopeeClient.php` — HTTP client ký sẵn (public + shop calls), OAuth helpers, retry/throttle.
- `ShopeeMappers.php` — Shopee JSON → DTO chuẩn.
- `ShopeeStatusMap.php` — raw status → `StandardOrderStatus`.
- `ShopeeWebhookVerifier.php` — verify (Authorization=HMAC(url|body)) + parse push.
- `ShopeeConnector.php` — implement `ChannelConnector`.

**Tạo mới (tests):**
- `tests/Unit/ShopeeSignerTest.php`
- `tests/Feature/Channels/ShopeeConnectorContractTest.php`
- `tests/Feature/Channels/ShopeeSyncTest.php`
- `tests/fixtures/Channels/shopee/ShopeeFixtures.php`

**Sửa (seam + đăng ký + config + docs):**
- `app/app/Integrations/Channels/Contracts/ChannelConnector.php` — 2 chữ ký optional `$context`.
- `app/app/Integrations/Channels/Lazada/LazadaConnector.php`, `TikTok/TikTokConnector.php`, `Manual/ManualConnector.php` — thêm `array $context = []` (bỏ qua).
- `app/app/Modules/Channels/Services/ChannelConnectionService.php` — `completeConnect` nhận `$callbackParams`.
- `app/app/Modules/Channels/Http/Controllers/OAuthCallbackController.php` — truyền query + nhánh lỗi `shopee_api_error`.
- `app/app/Modules/Channels/Support/TokenRefresher.php` — truyền `shop_id`.
- `app/app/Integrations/IntegrationsServiceProvider.php` — uncomment đăng ký shopee.
- `app/config/integrations.php` — khối `shopee`.
- `app/.env.example` — `SHOPEE_*`.
- `docs/04-channels/shopee.md`, `docs/04-channels/README.md`, `docs/03-domain/order-status-state-machine.md`.

---

## Task 1: Seam — optional `$context` trên exchangeCodeForToken/refreshToken (no regression)

**Files:**
- Modify: `app/app/Integrations/Channels/Contracts/ChannelConnector.php:58,60`
- Modify: `app/app/Integrations/Channels/Lazada/LazadaConnector.php:83,88`
- Modify: `app/app/Integrations/Channels/TikTok/TikTokConnector.php:85,90`
- Modify: `app/app/Integrations/Channels/Manual/ManualConnector.php:50,55`
- Modify: `app/app/Modules/Channels/Services/ChannelConnectionService.php:58,71`
- Modify: `app/app/Modules/Channels/Http/Controllers/OAuthCallbackController.php:63`
- Modify: `app/app/Modules/Channels/Support/TokenRefresher.php:30`

- [ ] **Step 1: Verify baseline green (regression guard)**

Run: `php artisan test --filter='LazadaConnectorContractTest|TikTokConnectorContractTest|TikTokSyncTest|ChannelConnectFlowTest'`
Expected: PASS (note any pre-existing failures; there should be none in these classes).

- [ ] **Step 2: Widen the interface (backward compatible)**

In `app/app/Integrations/Channels/Contracts/ChannelConnector.php` replace the two lines:
```php
    public function exchangeCodeForToken(string $code): TokenDTO;

    public function refreshToken(string $refreshToken): TokenDTO;
```
with:
```php
    /** @param array<string,mixed> $context callback/account fields some APIs need (e.g. Shopee shop_id). */
    public function exchangeCodeForToken(string $code, array $context = []): TokenDTO;

    /** @param array<string,mixed> $context account fields some APIs need (e.g. Shopee shop_id). */
    public function refreshToken(string $refreshToken, array $context = []): TokenDTO;
```

- [ ] **Step 3: Update the 3 existing implementors (params ignored)**

`LazadaConnector.php`:
```php
    public function exchangeCodeForToken(string $code, array $context = []): TokenDTO
    {
        return LazadaMappers::token($this->client->getAccessToken($code));
    }

    public function refreshToken(string $refreshToken, array $context = []): TokenDTO
    {
        return LazadaMappers::token($this->client->refreshAccessToken($refreshToken));
    }
```
`TikTokConnector.php`:
```php
    public function exchangeCodeForToken(string $code, array $context = []): TokenDTO
    {
        return TikTokMappers::token($this->client->getAccessToken($code));
    }

    public function refreshToken(string $refreshToken, array $context = []): TokenDTO
    {
        return TikTokMappers::token($this->client->refreshAccessToken($refreshToken));
    }
```
`ManualConnector.php`:
```php
    public function exchangeCodeForToken(string $code, array $context = []): TokenDTO
    {
        throw UnsupportedOperation::for($this->code(), 'exchangeCodeForToken');
    }

    public function refreshToken(string $refreshToken, array $context = []): TokenDTO
    {
        throw UnsupportedOperation::for($this->code(), 'refreshToken');
    }
```

- [ ] **Step 4: Thread callback params through completeConnect**

In `ChannelConnectionService.php`, change the `completeConnect` signature (line 58) and the exchange call (line 71):
```php
    public function completeConnect(string $provider, string $code, string $stateValue, array $callbackParams = []): array
    {
```
```php
        $token = $connector->exchangeCodeForToken($code, $callbackParams);
```

- [ ] **Step 5: Pass the callback query from the controller**

In `OAuthCallbackController.php`, change the call (line 63) to pass the query bag:
```php
            $result = $service->completeConnect($provider, $code, $state, $request->query());
```

- [ ] **Step 6: Thread shop_id on refresh**

In `TokenRefresher.php` line 30:
```php
            $token = $this->registry->for($account->provider)->refreshToken((string) $account->refresh_token, ['shop_id' => $account->external_shop_id]);
```

- [ ] **Step 7: Re-run regression suite — expect PASS (no behaviour change)**

Run: `php artisan test --filter='LazadaConnectorContractTest|TikTokConnectorContractTest|TikTokSyncTest|ChannelConnectFlowTest'`
Expected: PASS (identical to Step 1).

- [ ] **Step 8: Commit**
```bash
git add app/app/Integrations/Channels/Contracts/ChannelConnector.php app/app/Integrations/Channels/Lazada/LazadaConnector.php app/app/Integrations/Channels/TikTok/TikTokConnector.php app/app/Integrations/Channels/Manual/ManualConnector.php app/app/Modules/Channels/Services/ChannelConnectionService.php app/app/Modules/Channels/Http/Controllers/OAuthCallbackController.php app/app/Modules/Channels/Support/TokenRefresher.php
git commit -m "refactor(channels): optional \$context on exchangeCodeForToken/refreshToken (thread shop_id) — no behaviour change"
```

---

## Task 2: ShopeeSigner + ShopeeApiException + unit test

**Files:**
- Create: `app/app/Integrations/Channels/Shopee/ShopeeSigner.php`
- Create: `app/app/Integrations/Channels/Shopee/ShopeeApiException.php`
- Create: `app/tests/Unit/ShopeeSignerTest.php`

- [ ] **Step 1: Write the failing unit test** (mirror `tests/Unit/TikTokSignerTest.php`)

`app/tests/Unit/ShopeeSignerTest.php`:
```php
<?php

namespace Tests\Unit;

use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeSigner;
use PHPUnit\Framework\TestCase;

/** Pins the Shopee Open Platform v2 sign algorithm (HMAC-SHA256 hex of concatenated base). */
class ShopeeSignerTest extends TestCase
{
    public function test_public_sign_is_partner_path_timestamp(): void
    {
        $key = 'PARTNER_KEY';
        $base = '1001'.'/api/v2/auth/token/get'.'1700000000';
        $expected = hash_hmac('sha256', $base, $key);

        $this->assertSame($expected, ShopeeSigner::signPublic($key, 1001, '/api/v2/auth/token/get', 1700000000));
    }

    public function test_shop_sign_appends_token_and_shop(): void
    {
        $key = 'PARTNER_KEY';
        $base = '1001'.'/api/v2/order/get_order_list'.'1700000000'.'ACCESS'.'55';
        $expected = hash_hmac('sha256', $base, $key);

        $this->assertSame($expected, ShopeeSigner::signShop($key, 1001, '/api/v2/order/get_order_list', 1700000000, 'ACCESS', '55'));
    }

    public function test_deterministic_and_key_sensitive(): void
    {
        $this->assertSame(ShopeeSigner::signPublic('k1', 1, '/p', 1), ShopeeSigner::signPublic('k1', 1, '/p', 1));
        $this->assertNotSame(ShopeeSigner::signPublic('k1', 1, '/p', 1), ShopeeSigner::signPublic('k2', 1, '/p', 1));
        $this->assertSame(64, strlen(ShopeeSigner::signPublic('k1', 1, '/p', 1)));
    }
}
```

- [ ] **Step 2: Run — expect FAIL (class not found)**

Run: `php artisan test --filter=ShopeeSignerTest`
Expected: FAIL (ShopeeSigner missing).

- [ ] **Step 3: Implement ShopeeSigner**

`app/app/Integrations/Channels/Shopee/ShopeeSigner.php`:
```php
<?php

namespace CMBcoreSeller\Integrations\Channels\Shopee;

/**
 * Shopee Open Platform API v2 request signing.
 *
 * sign = HMAC-SHA256(partner_key, base_string) hex lowercase, where:
 *   - Public API (token get/refresh, auth_partner): base = partner_id . api_path . timestamp
 *   - Shop API:                                       base = partner_id . api_path . timestamp . access_token . shop_id
 * Pure concatenation, no separators. Pure & deterministic. See docs/04-channels/shopee.md §2.
 */
final class ShopeeSigner
{
    public static function signPublic(string $partnerKey, int $partnerId, string $apiPath, int $timestamp): string
    {
        return hash_hmac('sha256', $partnerId.$apiPath.$timestamp, $partnerKey);
    }

    public static function signShop(string $partnerKey, int $partnerId, string $apiPath, int $timestamp, string $accessToken, string $shopId): string
    {
        return hash_hmac('sha256', $partnerId.$apiPath.$timestamp.$accessToken.$shopId, $partnerKey);
    }
}
```

- [ ] **Step 4: Implement ShopeeApiException** (mirror `Lazada/LazadaApiException.php`)

`app/app/Integrations/Channels/Shopee/ShopeeApiException.php`:
```php
<?php

namespace CMBcoreSeller\Integrations\Channels\Shopee;

use RuntimeException;

/**
 * Raised when Shopee returns a non-empty `error` field or a non-2xx HTTP status.
 * Carries the Shopee error string (e.g. error_auth/error_sign/error_param) + HTTP status.
 */
class ShopeeApiException extends RuntimeException
{
    public function __construct(string $message, public readonly string $shopeeError = '', public readonly int $httpStatus = 0)
    {
        parent::__construct($message);
    }

    public function isAuthError(): bool
    {
        return $this->httpStatus === 401
            || in_array($this->shopeeError, ['error_auth', 'error_token', 'invalid_access_token'], true)
            || str_contains(strtolower($this->getMessage()), 'access_token')
            || str_contains(strtolower($this->getMessage()), 'invalid token');
    }

    public function isRateLimited(): bool
    {
        return $this->httpStatus === 429 || in_array($this->shopeeError, ['error_busy', 'error_rate_limit'], true);
    }
}
```

- [ ] **Step 5: Run — expect PASS**

Run: `php artisan test --filter=ShopeeSignerTest`
Expected: PASS (3 tests).

- [ ] **Step 6: Commit**
```bash
git add app/app/Integrations/Channels/Shopee/ShopeeSigner.php app/app/Integrations/Channels/Shopee/ShopeeApiException.php app/tests/Unit/ShopeeSignerTest.php
git commit -m "feat(channels): ShopeeSigner (v2 public/shop sign) + ShopeeApiException"
```

---

## Task 3: ShopeeClient + config block + registration

**Files:**
- Create: `app/app/Integrations/Channels/Shopee/ShopeeClient.php`
- Modify: `app/config/integrations.php` (add `shopee` block — see spec §4, copy verbatim)
- Modify: `app/app/Integrations/IntegrationsServiceProvider.php` (uncomment shopee)
- Modify: `app/.env.example`

> No standalone test in this task — `ShopeeClient` is exercised by the connector contract tests (Tasks 4-9). This task wires config + the signed-call plumbing.

- [ ] **Step 1: Add the `shopee` config block**

In `app/config/integrations.php`, add the full `shopee` array from the spec §4 (verbatim) as a new top-level key (place after the `lazada` block).

- [ ] **Step 2: Register the connector**

In `app/app/Integrations/IntegrationsServiceProvider.php`, in `$channelConnectors` replace the commented Shopee line with:
```php
        'shopee' => \CMBcoreSeller\Integrations\Channels\Shopee\ShopeeConnector::class,
```

- [ ] **Step 3: Add env keys to `.env.example`** (commented/empty — secrets not committed)

Append to `app/.env.example`:
```dotenv
# Shopee Open Platform (Phase 4) — build faithful theo docs; verify sandbox trước khi thêm vào INTEGRATIONS_CHANNELS
SHOPEE_PARTNER_ID=
SHOPEE_PARTNER_KEY=
SHOPEE_SANDBOX=true
# SHOPEE_API_BASE_URL=https://partner.shopeemobile.com   # sandbox: https://partner.test-stable.shopeemobile.com
# SHOPEE_REDIRECT_URI=    # default url('/oauth/shopee/callback')
# SHOPEE_PUSH_URL=        # default url('/webhook/shopee') — dùng để verify chữ ký push
# INTEGRATIONS_SHOPEE_FULFILLMENT=true
# INTEGRATIONS_SHOPEE_FINANCE=false
```

- [ ] **Step 4: Implement ShopeeClient** (READ `app/app/Integrations/Channels/Lazada/LazadaClient.php` first; mirror its retry/throttle/`fail()`/`http()` + `system_setting` override structure)

`app/app/Integrations/Channels/Shopee/ShopeeClient.php`:
```php
<?php

namespace CMBcoreSeller\Integrations\Channels\Shopee;

use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

/**
 * Thin signed HTTP client for the Shopee Open Platform API v2.
 *
 * Common params live in the QUERY string: partner_id, timestamp, sign (+ access_token, shop_id for
 * shop calls). POST bodies are JSON. Envelope carries `error` (string) — non-empty => ShopeeApiException.
 * Sandbox vs prod = config (`integrations.shopee.base_url`). Never logs secrets. See docs/04-channels/shopee.md.
 */
class ShopeeClient
{
    /** @var array<string, mixed> */
    protected array $cfg;

    public function __construct()
    {
        $this->cfg = (array) config('integrations.shopee', []);
        // Super-admin hot-override (mirror Lazada): /admin/system-settings → marketplace.shopee.*
        $this->cfg['partner_id'] = system_setting('marketplace.shopee.partner_id', $this->cfg['partner_id'] ?? null);
        $this->cfg['partner_key'] = system_setting('marketplace.shopee.partner_key', $this->cfg['partner_key'] ?? null);
        $this->cfg['sandbox'] = (bool) system_setting('marketplace.shopee.sandbox', $this->cfg['sandbox'] ?? false);
    }

    public function partnerId(): int
    {
        $v = $this->cfg['partner_id'] ?? throw new RuntimeException('Shopee partner_id is not configured (SHOPEE_PARTNER_ID).');

        return (int) $v;
    }

    protected function partnerKey(): string
    {
        return (string) ($this->cfg['partner_key'] ?? throw new RuntimeException('Shopee partner_key is not configured (SHOPEE_PARTNER_KEY).'));
    }

    public function endpoint(string $key): string
    {
        return (string) (($this->cfg['endpoints'] ?? [])[$key] ?? throw new RuntimeException("Shopee endpoint [{$key}] not configured."));
    }

    public function redirectUri(): string
    {
        return (string) ($this->cfg['redirect_uri'] ?? url('/oauth/shopee/callback'));
    }

    public function pushUrl(): string
    {
        return (string) ($this->cfg['push_url'] ?? url('/webhook/shopee'));
    }

    /** @return array<string,mixed> */
    public function cfg(): array
    {
        return $this->cfg;
    }

    // --- Authorization URL --------------------------------------------------

    /** auth_partner: redirect already carries our ?state=. */
    public function authorizeUrl(string $redirect): string
    {
        $path = $this->endpoint('auth_partner');
        $ts = time();
        $sign = ShopeeSigner::signPublic($this->partnerKey(), $this->partnerId(), $path, $ts);

        return rtrim($this->baseUrl(), '/').$path.'?'.http_build_query([
            'partner_id' => $this->partnerId(),
            'timestamp' => $ts,
            'sign' => $sign,
            'redirect' => $redirect,
        ]);
    }

    // --- OAuth (public sign, no shop context yet) ---------------------------

    /** @return array<string,mixed> */
    public function getAccessToken(string $code, string $shopId): array
    {
        return $this->publicPost($this->endpoint('token_get'), [
            'code' => $code, 'partner_id' => $this->partnerId(), 'shop_id' => (int) $shopId,
        ]);
    }

    /** @return array<string,mixed> */
    public function refreshAccessToken(string $refreshToken, string $shopId): array
    {
        return $this->publicPost($this->endpoint('token_refresh'), [
            'refresh_token' => $refreshToken, 'partner_id' => $this->partnerId(), 'shop_id' => (int) $shopId,
        ]);
    }

    // --- Public + shop calls ------------------------------------------------

    /** @param array<string,mixed> $body @return array<string,mixed> */
    public function publicPost(string $path, array $body): array
    {
        $ts = time();
        $query = ['partner_id' => $this->partnerId(), 'timestamp' => $ts,
            'sign' => ShopeeSigner::signPublic($this->partnerKey(), $this->partnerId(), $path, $ts)];

        return $this->send('POST', $path, $query, $body, null);
    }

    /** @param array<string,scalar|null> $query @return array<string,mixed> */
    public function shopGet(AuthContext $auth, string $path, array $query = []): array
    {
        return $this->send('GET', $path, $this->shopParams($auth, $path) + $this->stringify($query), null, $auth);
    }

    /** @param array<string,scalar|null> $query @param array<string,mixed> $body @return array<string,mixed> */
    public function shopPost(AuthContext $auth, string $path, array $query = [], array $body = []): array
    {
        return $this->send('POST', $path, $this->shopParams($auth, $path) + $this->stringify($query), $body, $auth);
    }

    /** Raw bytes (e.g. download_shipping_document) — returns the response body string. */
    public function shopPostRaw(AuthContext $auth, string $path, array $body = []): string
    {
        $this->throttle($auth);
        $url = rtrim($this->baseUrl(), '/').$path.'?'.http_build_query($this->shopParams($auth, $path));
        $resp = $this->http()->post($url, $body);
        if (! $resp->successful()) {
            $this->fail($path, $resp->json() ?? [], $resp->status());
        }

        return (string) $resp->body();
    }

    /** @return array<string,int|string> */
    protected function shopParams(AuthContext $auth, string $path): array
    {
        $ts = time();

        return [
            'partner_id' => $this->partnerId(),
            'timestamp' => $ts,
            'access_token' => $auth->accessToken,
            'shop_id' => (int) $auth->externalShopId,
            'sign' => ShopeeSigner::signShop($this->partnerKey(), $this->partnerId(), $path, $ts, $auth->accessToken, (string) $auth->externalShopId),
        ];
    }

    /** @param array<string,scalar|null> $params @return array<string,scalar> */
    protected function stringify(array $params): array
    {
        $out = [];
        foreach ($params as $k => $v) {
            if ($v !== null) {
                $out[$k] = is_bool($v) ? ($v ? 'true' : 'false') : $v;
            }
        }

        return $out;
    }

    /**
     * @param  array<string,scalar>  $query
     * @param  array<string,mixed>|null  $body
     * @return array<string,mixed>
     */
    protected function send(string $method, string $path, array $query, ?array $body, ?AuthContext $auth): array
    {
        if ($auth) {
            $this->throttle($auth);
        }
        $url = rtrim($this->baseUrl(), '/').$path.'?'.http_build_query($query);
        $resp = strtoupper($method) === 'GET'
            ? $this->http()->get($url)
            : $this->http()->post($url, $body ?? []);

        $json = $resp->json() ?? [];
        $error = (string) ($json['error'] ?? '');
        if (! $resp->successful() || $error !== '') {
            $this->fail($path, $json, $resp->status());
        }

        return (array) ($json['response'] ?? $json);
    }

    protected function baseUrl(): string
    {
        return (string) ($this->cfg['base_url'] ?? 'https://partner.shopeemobile.com');
    }

    protected function http(): PendingRequest
    {
        $http = (array) ($this->cfg['http'] ?? []);

        return Http::timeout((int) ($http['timeout'] ?? 20))
            ->connectTimeout(10)
            ->retry((int) ($http['retries'] ?? 2), (int) ($http['retry_sleep_ms'] ?? 500), throw: false)
            ->acceptJson();
    }

    protected function throttle(AuthContext $auth): void
    {
        $perMin = (int) config('integrations.throttle.shopee', 600);
        if ($perMin <= 0) {
            return;
        }
        $key = "shopee:rate:{$auth->channelAccountId}";
        for ($i = 0; $i < 50; $i++) {
            if (! RateLimiter::tooManyAttempts($key, $perMin)) {
                RateLimiter::hit($key, 60);

                return;
            }
            usleep(200_000);
        }
        throw new RuntimeException("Shopee rate limit: no slot after 10s for shop {$auth->channelAccountId}. Job will retry.");
    }

    /** @param array<string,mixed> $json @return never */
    protected function fail(string $path, array $json, int $httpStatus): never
    {
        $error = (string) ($json['error'] ?? '');
        $message = (string) ($json['message'] ?? 'unknown error');
        $requestId = (string) ($json['request_id'] ?? '');
        Log::warning('shopee.api.error', ['path' => $path, 'http' => $httpStatus, 'error' => $error, 'message_excerpt' => substr($message, 0, 200), 'request_id' => $requestId]);

        throw new ShopeeApiException("Shopee API error on {$path}: [{$error}] {$message}".($requestId ? " (request_id={$requestId})" : ''), $error, $httpStatus);
    }
}
```

- [ ] **Step 5: Sanity — config + registry resolve**

Run: `php artisan test --filter=ShopeeSignerTest` (still PASS) and `php -r "require 'vendor/autoload.php';"` is not needed; instead verify no syntax error by running any test:
Run: `php artisan test --filter=LazadaConnectorContractTest`
Expected: PASS (autoload of new files OK; registry binding gated by `INTEGRATIONS_CHANNELS` so shopee not loaded unless configured — no effect).

- [ ] **Step 6: Commit**
```bash
git add app/app/Integrations/Channels/Shopee/ShopeeClient.php app/config/integrations.php app/app/Integrations/IntegrationsServiceProvider.php app/.env.example
git commit -m "feat(channels): ShopeeClient (signed v2 public/shop calls) + config block + register connector"
```

---

## Task 4: OAuth — connector OAuth methods + Mappers.token/shopInfo + StatusMap + Connector skeleton

**Files:**
- Create: `app/app/Integrations/Channels/Shopee/ShopeeStatusMap.php`
- Create: `app/app/Integrations/Channels/Shopee/ShopeeMappers.php` (token + shopInfo now; order/listings/settlement added in later tasks)
- Create: `app/app/Integrations/Channels/Shopee/ShopeeConnector.php` (skeleton + OAuth; other methods throw UnsupportedOperation for now, filled in later tasks)
- Create: `app/tests/fixtures/Channels/shopee/ShopeeFixtures.php` (start; extended each task)
- Create: `app/tests/Feature/Channels/ShopeeConnectorContractTest.php` (OAuth tests)

- [ ] **Step 1: Write ShopeeStatusMap** (no test of its own; covered in Task 5)

`app/app/Integrations/Channels/Shopee/ShopeeStatusMap.php`:
```php
<?php

namespace CMBcoreSeller\Integrations\Channels\Shopee;

use CMBcoreSeller\Support\Enums\StandardOrderStatus;

/** Shopee raw order status -> canonical. Source of truth = config('integrations.shopee.status_map'). */
final class ShopeeStatusMap
{
    public static function toStandard(string $raw): StandardOrderStatus
    {
        $map = (array) config('integrations.shopee.status_map', []);
        $val = $map[$raw] ?? $map[strtoupper($raw)] ?? 'processing';

        return StandardOrderStatus::from((string) $val);
    }
}
```

- [ ] **Step 2: Write the fixtures helper (start)**

`app/tests/fixtures/Channels/shopee/ShopeeFixtures.php`:
```php
<?php

namespace Tests\Fixtures\Channels\Shopee;

/** Static factories returning Shopee Open Platform v2 response shapes for Http::fake. */
final class ShopeeFixtures
{
    public static function configure(): void
    {
        config([
            'integrations.shopee.partner_id' => 1001,
            'integrations.shopee.partner_key' => 'PARTNER_KEY',
            'integrations.shopee.base_url' => 'https://partner.test-stable.shopeemobile.com',
            'integrations.shopee.finance_enabled' => false,
            'integrations.shopee.fulfillment_enabled' => true,
            'integrations.shopee.status_map' => [
                'UNPAID' => 'unpaid', 'READY_TO_SHIP' => 'pending', 'PROCESSED' => 'processing',
                'RETRY_SHIP' => 'processing', 'SHIPPED' => 'shipped', 'TO_CONFIRM_RECEIVE' => 'delivered',
                'COMPLETED' => 'completed', 'IN_CANCEL' => 'processing', 'CANCELLED' => 'cancelled', 'TO_RETURN' => 'returning',
            ],
            'integrations.shopee.webhook_event_types' => [1 => 'shop_deauthorized', 3 => 'order_status_update', 6 => 'order_status_update'],
            'integrations.shopee.endpoints' => [
                'auth_partner' => '/api/v2/shop/auth_partner', 'token_get' => '/api/v2/auth/token/get',
                'token_refresh' => '/api/v2/auth/access_token/get', 'shop_info' => '/api/v2/shop/get_shop_info',
                'order_list' => '/api/v2/order/get_order_list', 'order_detail' => '/api/v2/order/get_order_detail',
                'shipping_parameter' => '/api/v2/logistics/get_shipping_parameter', 'ship_order' => '/api/v2/logistics/ship_order',
                'tracking_number' => '/api/v2/logistics/get_tracking_number', 'create_document' => '/api/v2/logistics/create_shipping_document',
                'get_document_result' => '/api/v2/logistics/get_shipping_document_result', 'download_document' => '/api/v2/logistics/download_shipping_document',
                'item_list' => '/api/v2/product/get_item_list', 'item_base_info' => '/api/v2/product/get_item_base_info',
                'model_list' => '/api/v2/product/get_model_list', 'update_stock' => '/api/v2/product/update_stock',
                'escrow_detail' => '/api/v2/payment/get_escrow_detail', 'escrow_list' => '/api/v2/payment/get_escrow_list',
            ],
        ]);
    }

    /** @return array<string,mixed> */
    public static function tokenGet(): array
    {
        return ['error' => '', 'request_id' => 'r1', 'access_token' => 'ACCESS_1', 'refresh_token' => 'REFRESH_1', 'expire_in' => 14400, 'shop_id' => 55];
    }

    /** @return array<string,mixed> */
    public static function shopInfo(): array
    {
        return ['error' => '', 'request_id' => 'r2', 'response' => ['shop_name' => 'Shop Shopee VN', 'region' => 'VN', 'status' => 'NORMAL']];
    }
}
```

- [ ] **Step 3: Write failing OAuth contract test**

`app/tests/Feature/Channels/ShopeeConnectorContractTest.php`:
```php
<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeConnector;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\Channels\Shopee\ShopeeFixtures;
use Tests\TestCase;

class ShopeeConnectorContractTest extends TestCase
{
    private function connector(): ShopeeConnector
    {
        ShopeeFixtures::configure();
        $registry = app(ChannelRegistry::class);
        $registry->register('shopee', ShopeeConnector::class);

        return $registry->for('shopee');
    }

    public function test_build_authorization_url_signs_and_carries_state_in_redirect(): void
    {
        $url = $this->connector()->buildAuthorizationUrl('STATE123', ['redirect_uri' => 'https://app.test/oauth/shopee/callback']);

        $this->assertStringContainsString('/api/v2/shop/auth_partner', $url);
        $this->assertStringContainsString('partner_id=1001', $url);
        $this->assertStringContainsString('sign=', $url);
        $this->assertStringContainsString('state%3DSTATE123', $url); // state nested in url-encoded redirect
    }

    public function test_exchange_code_for_token_uses_shop_id_from_context(): void
    {
        Http::fake(['*/api/v2/auth/token/get*' => Http::response(ShopeeFixtures::tokenGet(), 200)]);

        $token = $this->connector()->exchangeCodeForToken('CODE', ['shop_id' => '55']);

        $this->assertSame('ACCESS_1', $token->accessToken);
        $this->assertSame('REFRESH_1', $token->refreshToken);
        $this->assertSame('55', (string) $token->raw['shop_id']);
        $this->assertNotNull($token->expiresAt);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/api/v2/auth/token/get') && $r['shop_id'] === 55 && $r['code'] === 'CODE');
    }

    public function test_refresh_token_uses_shop_id_from_context(): void
    {
        Http::fake(['*/api/v2/auth/access_token/get*' => Http::response(ShopeeFixtures::tokenGet(), 200)]);

        $token = $this->connector()->refreshToken('REFRESH_1', ['shop_id' => '55']);

        $this->assertSame('ACCESS_1', $token->accessToken);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/api/v2/auth/access_token/get') && $r['refresh_token'] === 'REFRESH_1' && $r['shop_id'] === 55);
    }

    public function test_fetch_shop_info_reads_shop_id_from_token_raw(): void
    {
        Http::fake(['*/api/v2/shop/get_shop_info*' => Http::response(ShopeeFixtures::shopInfo(), 200)]);

        $shop = $this->connector()->fetchShopInfo(new AuthContext(0, 'shopee', '', 'ACCESS_1', extra: ['token_raw' => ['shop_id' => 55]]));

        $this->assertSame('55', $shop->externalShopId);
        $this->assertSame('Shop Shopee VN', $shop->name);
        $this->assertSame('VN', $shop->region);
    }
}
```

- [ ] **Step 4: Run — expect FAIL (ShopeeConnector missing)**

Run: `php artisan test --filter=ShopeeConnectorContractTest`
Expected: FAIL.

- [ ] **Step 5: Add token + shopInfo mappers**

`app/app/Integrations/Channels/Shopee/ShopeeMappers.php`:
```php
<?php

namespace CMBcoreSeller\Integrations\Channels\Shopee;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\ShopInfoDTO;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;

/** Shopee v2 JSON -> standard DTOs. The ONLY place Shopee field names live (besides StatusMap/Verifier). */
final class ShopeeMappers
{
    /** @param array<string,mixed> $res token/get|refresh response @param string $shopId from context */
    public static function token(array $res, string $shopId): TokenDTO
    {
        $expireIn = (int) ($res['expire_in'] ?? 14400);
        $raw = $res;
        $raw['shop_id'] = $res['shop_id'] ?? $shopId;

        return new TokenDTO(
            accessToken: (string) ($res['access_token'] ?? ''),
            refreshToken: ($res['refresh_token'] ?? null) ? (string) $res['refresh_token'] : null,
            expiresAt: CarbonImmutable::now()->addSeconds($expireIn),
            refreshExpiresAt: CarbonImmutable::now()->addDays(30), // Shopee refresh ~30d
            scope: null,
            raw: $raw,
        );
    }

    /** @param array<string,mixed> $res get_shop_info `response` */
    public static function shopInfo(array $res, string $shopId): ShopInfoDTO
    {
        return new ShopInfoDTO(
            externalShopId: $shopId,
            name: (string) ($res['shop_name'] ?? ('Shopee '.$shopId)),
            region: (string) ($res['region'] ?? 'VN'),
            sellerType: isset($res['shop_type']) ? (string) $res['shop_type'] : null,
            raw: $res,
        );
    }
}
```

- [ ] **Step 6: Write ShopeeConnector skeleton + OAuth methods**

`app/app/Integrations/Channels/Shopee/ShopeeConnector.php` (full skeleton; non-OAuth methods throw `UnsupportedOperation` for now and are implemented in Tasks 5-8):
```php
<?php

namespace CMBcoreSeller\Integrations\Channels\Shopee;

use CMBcoreSeller\Integrations\Channels\Contracts\ChannelConnector;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\OrderDTO;
use CMBcoreSeller\Integrations\Channels\DTO\Page;
use CMBcoreSeller\Integrations\Channels\DTO\ShopInfoDTO;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;
use CMBcoreSeller\Integrations\Channels\DTO\WebhookEventDTO;
use CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Symfony\Component\HttpFoundation\Request;

/**
 * Shopee Open Platform v2 connector. Mirrors Lazada/TikTok. See docs/04-channels/shopee.md
 * + spec docs/superpowers/specs/2026-05-20-shopee-channel-connector-design.md.
 */
class ShopeeConnector implements ChannelConnector
{
    public function __construct(private ShopeeClient $client) {}

    public function code(): string
    {
        return 'shopee';
    }

    public function displayName(): string
    {
        return 'Shopee';
    }

    public function capabilities(): array
    {
        $cfg = $this->client->cfg();
        $fulfill = (bool) ($cfg['fulfillment_enabled'] ?? true);

        return [
            'orders.fetch' => true, 'orders.webhook' => true, 'orders.confirm' => false,
            'shipping.arrange' => $fulfill, 'shipping.ready_to_ship' => false,
            'shipping.document' => $fulfill, 'shipping.tracking' => true,
            'listings.fetch' => true, 'listings.publish' => false,
            'listings.updateStock' => true, 'listings.updatePrice' => false,
            'finance.settlements' => (bool) ($cfg['finance_enabled'] ?? false),
            'returns.fetch' => false,
        ];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    public function buildAuthorizationUrl(string $state, array $opts = []): string
    {
        $redirect = (string) ($opts['redirect_uri'] ?? $this->client->redirectUri());
        $redirect .= (str_contains($redirect, '?') ? '&' : '?').'state='.urlencode($state);

        return $this->client->authorizeUrl($redirect);
    }

    public function exchangeCodeForToken(string $code, array $context = []): TokenDTO
    {
        $shopId = (string) ($context['shop_id'] ?? '');

        return ShopeeMappers::token($this->client->getAccessToken($code, $shopId), $shopId);
    }

    public function refreshToken(string $refreshToken, array $context = []): TokenDTO
    {
        $shopId = (string) ($context['shop_id'] ?? '');

        return ShopeeMappers::token($this->client->refreshAccessToken($refreshToken, $shopId), $shopId);
    }

    public function fetchShopInfo(AuthContext $auth): ShopInfoDTO
    {
        $shopId = (string) ($auth->extra['token_raw']['shop_id'] ?? $auth->externalShopId);
        $shopAuth = new AuthContext(0, 'shopee', $shopId, $auth->accessToken);
        $res = $this->client->shopGet($shopAuth, $this->client->endpoint('shop_info'));

        return ShopeeMappers::shopInfo($res, $shopId);
    }

    public function registerWebhooks(AuthContext $auth): void
    {
        // Shopee push URL is configured once in the app console — nothing to subscribe per-shop.
    }

    public function revoke(AuthContext $auth): void
    {
        // No Shopee API to revoke partner authorization from our side; seller cancels in Seller Center.
    }

    public function fetchOrders(AuthContext $auth, array $query = []): Page
    {
        throw UnsupportedOperation::for($this->code(), 'fetchOrders'); // Task 5
    }

    public function fetchOrderDetail(AuthContext $auth, string $externalOrderId): OrderDTO
    {
        throw UnsupportedOperation::for($this->code(), 'fetchOrderDetail'); // Task 5
    }

    public function parseWebhook(Request $request): WebhookEventDTO
    {
        throw UnsupportedOperation::for($this->code(), 'parseWebhook'); // Task 6
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        return false; // Task 6
    }

    public function mapStatus(string $rawStatus, array $rawOrder = []): StandardOrderStatus
    {
        return ShopeeStatusMap::toStandard($rawStatus);
    }

    public function unprocessedRawStatuses(): array
    {
        return ['READY_TO_SHIP'];
    }

    public function fetchListings(AuthContext $auth, array $query = []): Page
    {
        throw UnsupportedOperation::for($this->code(), 'fetchListings'); // Task 7
    }

    public function updateStock(AuthContext $auth, string $externalSkuId, int $available, array $context = []): void
    {
        throw UnsupportedOperation::for($this->code(), 'updateStock'); // Task 7
    }

    public function arrangeShipment(AuthContext $auth, string $externalOrderId, array $params = []): array
    {
        throw UnsupportedOperation::for($this->code(), 'arrangeShipment'); // Task 6b
    }

    public function pushReadyToShip(AuthContext $auth, string $externalOrderId, array $params = []): array
    {
        throw UnsupportedOperation::for($this->code(), 'pushReadyToShip'); // Shopee has no separate RTS step
    }

    public function getShippingDocument(AuthContext $auth, string $externalOrderId, array $query = []): array
    {
        throw UnsupportedOperation::for($this->code(), 'getShippingDocument'); // Task 6b
    }

    public function fetchSettlements(AuthContext $auth, array $query = []): Page
    {
        throw UnsupportedOperation::for($this->code(), 'fetchSettlements'); // Task 8
    }
}
```

- [ ] **Step 7: Run — expect PASS (4 OAuth tests)**

Run: `php artisan test --filter=ShopeeConnectorContractTest`
Expected: PASS (4 tests).

- [ ] **Step 8: Commit**
```bash
git add app/app/Integrations/Channels/Shopee/ShopeeStatusMap.php app/app/Integrations/Channels/Shopee/ShopeeMappers.php app/app/Integrations/Channels/Shopee/ShopeeConnector.php app/tests/fixtures/Channels/shopee/ShopeeFixtures.php app/tests/Feature/Channels/ShopeeConnectorContractTest.php
git commit -m "feat(channels): Shopee OAuth (authorize/token/refresh/shop_info) + StatusMap + connector skeleton"
```

---

## Task 5: Orders — fetchOrders (15-day windowing + cursor) + fetchOrderDetail + Mappers.order

**Files:**
- Modify: `app/app/Integrations/Channels/Shopee/ShopeeConnector.php` (fetchOrders/fetchOrderDetail)
- Modify: `app/app/Integrations/Channels/Shopee/ShopeeMappers.php` (add `order()`)
- Modify: `app/tests/fixtures/Channels/shopee/ShopeeFixtures.php` (add orderList/orderDetail)
- Modify: `app/tests/Feature/Channels/ShopeeConnectorContractTest.php` (add order tests)

- [ ] **Step 1: Add fixtures** — append to `ShopeeFixtures`:
```php
    /** @return array<string,mixed> get_order_list page */
    public static function orderList(string $nextCursor = '', bool $more = false): array
    {
        return ['error' => '', 'response' => [
            'order_list' => [['order_sn' => 'SN_1'], ['order_sn' => 'SN_2']],
            'next_cursor' => $nextCursor, 'more' => $more,
        ]];
    }

    /** @return array<string,mixed> get_order_detail */
    public static function orderDetail(): array
    {
        return ['error' => '', 'response' => ['order_list' => [
            self::orderRow('SN_1', 'READY_TO_SHIP'),
            self::orderRow('SN_2', 'PROCESSED'),
        ]]];
    }

    /** @return array<string,mixed> */
    public static function orderRow(string $sn, string $status): array
    {
        return [
            'order_sn' => $sn, 'order_status' => $status, 'update_time' => 1700000000, 'create_time' => 1699990000,
            'currency' => 'VND', 'cod' => true, 'total_amount' => 250000, 'actual_shipping_fee' => 20000,
            'buyer_username' => 'buyer_'.$sn,
            'recipient_address' => ['name' => 'Nguyen Van A', 'phone' => '0900000000', 'full_address' => '12 Le Loi', 'town' => 'P1', 'district' => 'Q1', 'city' => 'HCM', 'state' => 'HCM', 'zipcode' => '700000'],
            'item_list' => [[
                'item_id' => 111, 'model_id' => 222, 'item_sku' => 'SKU-A', 'model_sku' => 'SKU-A-RED',
                'item_name' => 'Áo thun', 'model_name' => 'Đỏ / M', 'model_quantity_purchased' => 2,
                'model_discounted_price' => 115000, 'image_info' => ['image_url' => 'https://img/a.jpg'],
            ]],
            'package_list' => [['package_number' => 'PKG_1', 'shipping_carrier' => 'SPX Express']],
        ];
    }
```

- [ ] **Step 2: Write failing order tests** — append to `ShopeeConnectorContractTest`:
```php
    public function test_fetch_orders_splits_15_day_windows_and_maps_detail(): void
    {
        Http::fake([
            '*/api/v2/order/get_order_list*' => Http::response(ShopeeFixtures::orderList('', false), 200),
            '*/api/v2/order/get_order_detail*' => Http::response(ShopeeFixtures::orderDetail(), 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $page = $this->connector()->fetchOrders($auth, [
            'updatedFrom' => \Carbon\CarbonImmutable::parse('2026-01-01T00:00:00Z'),
            'updatedTo' => \Carbon\CarbonImmutable::parse('2026-01-10T00:00:00Z'),
        ]);

        $this->assertCount(2, $page->items);
        $first = $page->items[0];
        $this->assertSame('SN_1', $first->externalOrderId);
        $this->assertSame('READY_TO_SHIP', $first->rawStatus);
        $this->assertSame('shopee', $first->source);
        $this->assertTrue($first->isCod);
        $this->assertSame(250000, $first->grandTotal);
        $this->assertSame(20000, $first->shippingFee);
        $this->assertCount(1, $first->items);
        $this->assertSame('111', $first->items[0]->externalProductId);
        $this->assertSame('SKU-A-RED', $first->items[0]->externalSkuId);
        $this->assertSame(2, $first->items[0]->quantity);
        $this->assertSame('HCM', $first->shippingAddress['province']);
    }

    public function test_fetch_orders_window_over_15_days_returns_cursor_to_continue(): void
    {
        Http::fake([
            '*/api/v2/order/get_order_list*' => Http::response(ShopeeFixtures::orderList('', false), 200),
            '*/api/v2/order/get_order_detail*' => Http::response(ShopeeFixtures::orderDetail(), 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $page = $this->connector()->fetchOrders($auth, [
            'updatedFrom' => \Carbon\CarbonImmutable::parse('2026-01-01T00:00:00Z'),
            'updatedTo' => \Carbon\CarbonImmutable::parse('2026-02-15T00:00:00Z'), // 45 days -> needs >1 window
        ]);

        $this->assertTrue($page->hasMore);
        $this->assertNotNull($page->nextCursor);
        $this->assertStringContainsString(':', $page->nextCursor); // encodes window + inner cursor
    }

    public function test_fetch_order_detail_maps_single_order(): void
    {
        Http::fake(['*/api/v2/order/get_order_detail*' => Http::response(ShopeeFixtures::orderDetail(), 200)]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $order = $this->connector()->fetchOrderDetail($auth, 'SN_1');
        $this->assertSame('SN_1', $order->externalOrderId);
        $this->assertSame('READY_TO_SHIP', $order->rawStatus);
    }
```

- [ ] **Step 3: Run — expect FAIL** (`UnsupportedOperation`)

Run: `php artisan test --filter=ShopeeConnectorContractTest`
Expected: FAIL (3 new order tests).

- [ ] **Step 4: Add `order()` mapper** — append to `ShopeeMappers`:
```php
    /** @param array<string,mixed> $d a single get_order_detail order row */
    public static function order(array $d): \CMBcoreSeller\Integrations\Channels\DTO\OrderDTO
    {
        $addr = (array) ($d['recipient_address'] ?? []);
        $items = [];
        foreach ((array) ($d['item_list'] ?? []) as $it) {
            $items[] = new \CMBcoreSeller\Integrations\Channels\DTO\OrderItemDTO(
                externalItemId: (string) ($it['item_id'] ?? '').'-'.(string) ($it['model_id'] ?? '0'),
                externalProductId: isset($it['item_id']) ? (string) $it['item_id'] : null,
                externalSkuId: ($it['model_sku'] ?? '') !== '' ? (string) $it['model_sku'] : (string) ($it['model_id'] ?? ''),
                sellerSku: ($it['item_sku'] ?? '') !== '' ? (string) $it['item_sku'] : (($it['model_sku'] ?? '') !== '' ? (string) $it['model_sku'] : null),
                name: (string) ($it['item_name'] ?? ''),
                variation: ($it['model_name'] ?? '') !== '' ? (string) $it['model_name'] : null,
                quantity: (int) ($it['model_quantity_purchased'] ?? 1),
                unitPrice: (int) round((float) ($it['model_discounted_price'] ?? 0)),
                discount: 0,
                image: $it['image_info']['image_url'] ?? null,
                raw: (array) $it,
            );
        }
        $packages = [];
        foreach ((array) ($d['package_list'] ?? []) as $p) {
            $packages[] = [
                'externalPackageId' => isset($p['package_number']) ? (string) $p['package_number'] : null,
                'trackingNo' => isset($p['tracking_number']) ? (string) $p['tracking_number'] : null,
                'carrier' => isset($p['shipping_carrier']) ? (string) $p['shipping_carrier'] : null,
                'status' => isset($p['logistics_status']) ? (string) $p['logistics_status'] : null,
            ];
        }
        $isCod = (bool) ($d['cod'] ?? false);
        $grand = (int) round((float) ($d['total_amount'] ?? 0));

        return new \CMBcoreSeller\Integrations\Channels\DTO\OrderDTO(
            externalOrderId: (string) ($d['order_sn'] ?? ''),
            source: 'shopee',
            rawStatus: (string) ($d['order_status'] ?? ''),
            sourceUpdatedAt: CarbonImmutable::createFromTimestamp((int) ($d['update_time'] ?? time())),
            orderNumber: (string) ($d['order_sn'] ?? ''),
            placedAt: isset($d['create_time']) ? CarbonImmutable::createFromTimestamp((int) $d['create_time']) : null,
            paidAt: isset($d['pay_time']) ? CarbonImmutable::createFromTimestamp((int) $d['pay_time']) : null,
            buyer: ['name' => (string) ($addr['name'] ?? ($d['buyer_username'] ?? '')), 'phone' => (string) ($addr['phone'] ?? '')],
            shippingAddress: [
                'fullName' => (string) ($addr['name'] ?? ''), 'phone' => (string) ($addr['phone'] ?? ''),
                'line1' => (string) ($addr['full_address'] ?? ''), 'ward' => (string) ($addr['town'] ?? ''),
                'district' => (string) ($addr['district'] ?? ''), 'province' => (string) ($addr['state'] ?? ($addr['city'] ?? '')),
                'country' => 'VN', 'zip' => (string) ($addr['zipcode'] ?? ''),
            ],
            shippingFee: (int) round((float) ($d['actual_shipping_fee'] ?? $d['estimated_shipping_fee'] ?? 0)),
            codAmount: $isCod ? $grand : 0,
            grandTotal: $grand,
            isCod: $isCod,
            items: $items,
            packages: $packages,
            raw: $d,
        );
    }
```

- [ ] **Step 5: Implement fetchOrders + fetchOrderDetail** in `ShopeeConnector` (replace the two throwing stubs):
```php
    public function fetchOrders(AuthContext $auth, array $query = []): Page
    {
        $cfg = $this->client->cfg();
        $windowDays = (int) ($cfg['order_window_days'] ?? 15);
        $pageSize = min(100, max(1, (int) ($query['pageSize'] ?? 50)));
        $from = $query['updatedFrom'] ?? CarbonImmutable::now()->subDays($windowDays);
        $to = $query['updatedTo'] ?? CarbonImmutable::now();

        // cursor encodes "windowStartUnix:innerCursor"; first call has no cursor.
        [$winStart, $inner] = $this->decodeCursor((string) ($query['cursor'] ?? ''), $from);
        $winEnd = min($to->getTimestamp(), $winStart + $windowDays * 86400);

        $params = [
            'time_range_field' => 'update_time', 'time_from' => $winStart, 'time_to' => $winEnd,
            'page_size' => $pageSize, 'cursor' => $inner,
        ];
        if (! empty($query['statuses'])) {
            $params['order_status'] = (string) $query['statuses'][0];
        }
        $list = $this->client->shopGet($auth, $this->client->endpoint('order_list'), $params);

        $sns = array_values(array_filter(array_map(fn ($o) => (string) ($o['order_sn'] ?? ''), (array) ($list['order_list'] ?? []))));
        $orders = $sns === [] ? [] : $this->loadDetails($auth, $sns);

        $innerNext = (string) ($list['next_cursor'] ?? '');
        $hasInnerMore = (bool) ($list['more'] ?? false) && $innerNext !== '';
        if ($hasInnerMore) {
            return new Page($orders, $winStart.':'.$innerNext, true);
        }
        if ($winEnd < $to->getTimestamp()) {
            return new Page($orders, ($winEnd).':', true); // advance to next window
        }

        return new Page($orders, null, false);
    }

    public function fetchOrderDetail(AuthContext $auth, string $externalOrderId): OrderDTO
    {
        $orders = $this->loadDetails($auth, [$externalOrderId]);
        if ($orders === []) {
            throw new ShopeeApiException("Shopee order not found: {$externalOrderId}", 'error_not_found');
        }

        return $orders[0];
    }

    /**
     * @param  list<string>  $sns
     * @return list<OrderDTO>
     */
    private function loadDetails(AuthContext $auth, array $sns): array
    {
        $out = [];
        foreach (array_chunk($sns, 50) as $chunk) {
            $res = $this->client->shopGet($auth, $this->client->endpoint('order_detail'), [
                'order_sn_list' => implode(',', $chunk),
                'response_optional_fields' => 'buyer_username,recipient_address,item_list,package_list,pay_time,total_amount,actual_shipping_fee,estimated_shipping_fee,cod,order_status,update_time,create_time',
            ]);
            foreach ((array) ($res['order_list'] ?? []) as $row) {
                $out[] = ShopeeMappers::order((array) $row);
            }
        }

        return $out;
    }

    /**
     * @return array{0:int,1:string} [windowStartUnix, innerCursor]
     */
    private function decodeCursor(string $cursor, CarbonImmutable $from): array
    {
        if ($cursor === '') {
            return [$from->getTimestamp(), ''];
        }
        $parts = explode(':', $cursor, 2);

        return [(int) $parts[0], $parts[1] ?? ''];
    }
```
Add `use Carbon\CarbonImmutable;` to the connector's imports.

- [ ] **Step 6: Run — expect PASS**

Run: `php artisan test --filter=ShopeeConnectorContractTest`
Expected: PASS (7 tests total).

- [ ] **Step 7: Commit**
```bash
git add app/app/Integrations/Channels/Shopee/ShopeeConnector.php app/app/Integrations/Channels/Shopee/ShopeeMappers.php app/tests/fixtures/Channels/shopee/ShopeeFixtures.php app/tests/Feature/Channels/ShopeeConnectorContractTest.php
git commit -m "feat(channels): Shopee fetchOrders (15-day windowing+cursor) + fetchOrderDetail + order mapper"
```

---

## Task 6: Webhook — verifier + parse

**Files:**
- Create: `app/app/Integrations/Channels/Shopee/ShopeeWebhookVerifier.php`
- Modify: `app/app/Integrations/Channels/Shopee/ShopeeConnector.php` (wire verify/parse; inject verifier)
- Modify: `app/tests/Feature/Channels/ShopeeConnectorContractTest.php` (webhook tests)

- [ ] **Step 1: Write failing webhook tests** — append to `ShopeeConnectorContractTest`:
```php
    private function signedPush(array $body): \Illuminate\Http\Request
    {
        $raw = json_encode($body, JSON_UNESCAPED_SLASHES);
        $pushUrl = 'https://partner.test-stable.shopeemobile.com/webhook/shopee';
        config(['integrations.shopee.push_url' => $pushUrl]);
        $sign = hash_hmac('sha256', $pushUrl.'|'.$raw, 'PARTNER_KEY');
        $req = \Illuminate\Http\Request::create($pushUrl, 'POST', content: $raw);
        $req->headers->set('Authorization', $sign);

        return $req;
    }

    public function test_verify_webhook_signature_ok_and_reject(): void
    {
        $this->connector(); // configure
        $req = $this->signedPush(['code' => 3, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode(['ordersn' => 'SN_9', 'status' => 'READY_TO_SHIP'])]);
        $this->assertTrue($this->connector()->verifyWebhookSignature($req));

        $bad = \Illuminate\Http\Request::create('https://partner.test-stable.shopeemobile.com/webhook/shopee', 'POST', content: '{}');
        $bad->headers->set('Authorization', 'deadbeef');
        $this->assertFalse($this->connector()->verifyWebhookSignature($bad));
    }

    public function test_parse_webhook_order_status_update(): void
    {
        $req = $this->signedPush(['code' => 3, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode(['ordersn' => 'SN_9', 'status' => 'READY_TO_SHIP'])]);
        $evt = $this->connector()->parseWebhook($req);

        $this->assertSame('order_status_update', $evt->type);
        $this->assertSame('55', $evt->externalShopId);
        $this->assertSame('SN_9', $evt->externalOrderId);
        $this->assertSame('READY_TO_SHIP', $evt->orderRawStatus);
    }

    public function test_parse_webhook_deauthorized(): void
    {
        $req = $this->signedPush(['code' => 1, 'shop_id' => 55, 'timestamp' => 1700000000, 'data' => json_encode(['success' => 1])]);
        $this->assertSame('shop_deauthorized', $this->connector()->parseWebhook($req)->type);
    }
```

- [ ] **Step 2: Run — expect FAIL**

Run: `php artisan test --filter=ShopeeConnectorContractTest`
Expected: FAIL (3 new webhook tests; verify returns false, parse throws).

- [ ] **Step 3: Implement ShopeeWebhookVerifier** (mirror `Lazada/LazadaWebhookVerifier.php` structure)

`app/app/Integrations/Channels/Shopee/ShopeeWebhookVerifier.php`:
```php
<?php

namespace CMBcoreSeller\Integrations\Channels\Shopee;

use CMBcoreSeller\Integrations\Channels\DTO\WebhookEventDTO;
use Carbon\CarbonImmutable;
use Symfony\Component\HttpFoundation\Request;

/**
 * Shopee push verification + parsing. Signature: Authorization header == HMAC-SHA256(push_url|raw_body, partner_key).
 * Body: { code:int, shop_id:int, timestamp:int, data: "<json-string>" }. See docs/04-channels/shopee.md §4.
 */
class ShopeeWebhookVerifier
{
    public function verify(Request $request): bool
    {
        $cfg = (array) config('integrations.shopee', []);
        $partnerKey = (string) ($cfg['partner_key'] ?? '');
        $pushUrl = (string) ($cfg['push_url'] ?? url('/webhook/shopee'));
        $raw = $request->getContent();
        $provided = trim((string) $request->headers->get('Authorization', ''));
        if ($partnerKey === '' || $provided === '') {
            return (string) ($cfg['webhook_verify_mode'] ?? 'strict') === 'lenient';
        }
        $expected = hash_hmac('sha256', $pushUrl.'|'.$raw, $partnerKey);
        $ok = hash_equals($expected, strtolower($provided));
        if (! $ok && (string) ($cfg['webhook_verify_mode'] ?? 'strict') === 'lenient') {
            return true;
        }

        return $ok;
    }

    public function parse(Request $request): WebhookEventDTO
    {
        $body = (array) ($request->json()?->all() ?? json_decode($request->getContent() ?: '[]', true) ?? []);
        $code = (int) ($body['code'] ?? -1);
        $map = (array) config('integrations.shopee.webhook_event_types', []);
        $type = (string) ($map[$code] ?? WebhookEventDTO::TYPE_UNKNOWN);

        $data = $body['data'] ?? [];
        if (is_string($data)) {
            $data = json_decode($data, true) ?: [];
        }
        $orderSn = isset($data['ordersn']) ? (string) $data['ordersn'] : (isset($data['order_sn']) ? (string) $data['order_sn'] : null);

        return new WebhookEventDTO(
            provider: 'shopee',
            type: $type,
            externalShopId: isset($body['shop_id']) ? (string) $body['shop_id'] : null,
            externalOrderId: $orderSn,
            occurredAt: isset($body['timestamp']) ? CarbonImmutable::createFromTimestamp((int) $body['timestamp']) : null,
            orderRawStatus: isset($data['status']) ? (string) $data['status'] : null,
            raw: $body,
        );
    }
}
```

- [ ] **Step 4: Wire verifier into the connector**

In `ShopeeConnector`, change the constructor + the two webhook methods:
```php
    public function __construct(private ShopeeClient $client, private ShopeeWebhookVerifier $verifier = new ShopeeWebhookVerifier()) {}
```
```php
    public function parseWebhook(Request $request): WebhookEventDTO
    {
        return $this->verifier->parse($request);
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        return $this->verifier->verify($request);
    }
```
> Note: `Symfony\Component\HttpFoundation\Request` (interface signature) is satisfied by `Illuminate\Http\Request` used in tests (subclass). Keep the import as-is.

- [ ] **Step 5: Run — expect PASS**

Run: `php artisan test --filter=ShopeeConnectorContractTest`
Expected: PASS (10 tests).

- [ ] **Step 6: Commit**
```bash
git add app/app/Integrations/Channels/Shopee/ShopeeWebhookVerifier.php app/app/Integrations/Channels/Shopee/ShopeeConnector.php app/tests/Feature/Channels/ShopeeConnectorContractTest.php
git commit -m "feat(channels): Shopee webhook verify (url|body HMAC) + parse push codes"
```

---

## Task 6b: Fulfillment — arrangeShipment + getShippingDocument (async)

**Files:**
- Modify: `app/app/Integrations/Channels/Shopee/ShopeeConnector.php`
- Modify: `app/tests/fixtures/Channels/shopee/ShopeeFixtures.php`
- Modify: `app/tests/Feature/Channels/ShopeeConnectorContractTest.php`

- [ ] **Step 1: Add fixtures** — append to `ShopeeFixtures`:
```php
    public static function shippingParameter(): array
    {
        return ['error' => '', 'response' => ['dropoff' => [], 'pickup' => ['address_list' => [['address_id' => 9, 'time_slot_list' => [['pickup_time_id' => 'T1']]]]]]];
    }

    public static function shipOrder(): array
    {
        return ['error' => '', 'response' => []];
    }

    public static function trackingNumber(): array
    {
        return ['error' => '', 'response' => ['tracking_number' => 'TRK123', 'first_mile_tracking_number' => null]];
    }

    public static function createDocument(): array
    {
        return ['error' => '', 'response' => ['result_list' => [['order_sn' => 'SN_1', 'package_number' => 'PKG_1']]]];
    }

    public static function documentResult(string $status = 'READY'): array
    {
        return ['error' => '', 'response' => ['result_list' => [['order_sn' => 'SN_1', 'package_number' => 'PKG_1', 'status' => $status]]]];
    }
```

- [ ] **Step 2: Write failing fulfillment tests** — append to `ShopeeConnectorContractTest`:
```php
    public function test_arrange_shipment_ships_and_returns_tracking(): void
    {
        Http::fake([
            '*/api/v2/logistics/get_shipping_parameter*' => Http::response(ShopeeFixtures::shippingParameter(), 200),
            '*/api/v2/logistics/ship_order*' => Http::response(ShopeeFixtures::shipOrder(), 200),
            '*/api/v2/logistics/get_tracking_number*' => Http::response(ShopeeFixtures::trackingNumber(), 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $res = $this->connector()->arrangeShipment($auth, 'SN_1', ['packages' => [['externalPackageId' => 'PKG_1']]]);
        $this->assertSame('TRK123', $res['tracking_no']);
        $this->assertSame('PROCESSED', $res['raw_status']);
    }

    public function test_get_shipping_document_polls_then_downloads(): void
    {
        Http::fake([
            '*/api/v2/logistics/create_shipping_document*' => Http::response(ShopeeFixtures::createDocument(), 200),
            '*/api/v2/logistics/get_shipping_document_result*' => Http::response(ShopeeFixtures::documentResult('READY'), 200),
            '*/api/v2/logistics/download_shipping_document*' => Http::response('%PDF-1.4 fake', 200, ['Content-Type' => 'application/pdf']),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $doc = $this->connector()->getShippingDocument($auth, 'SN_1', ['externalPackageId' => 'PKG_1']);
        $this->assertSame('application/pdf', $doc['mime']);
        $this->assertStringContainsString('%PDF', $doc['bytes']);
        $this->assertStringEndsWith('.pdf', $doc['filename']);
    }

    public function test_get_shipping_document_failed_throws(): void
    {
        config(['integrations.shopee.document_poll_attempts' => 2, 'integrations.shopee.document_poll_sleep_ms' => 0]);
        Http::fake([
            '*/api/v2/logistics/create_shipping_document*' => Http::response(ShopeeFixtures::createDocument(), 200),
            '*/api/v2/logistics/get_shipping_document_result*' => Http::response(ShopeeFixtures::documentResult('FAILED'), 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $this->expectException(\CMBcoreSeller\Integrations\Channels\Shopee\ShopeeApiException::class);
        $this->connector()->getShippingDocument($auth, 'SN_1', ['externalPackageId' => 'PKG_1']);
    }

    public function test_push_ready_to_ship_unsupported(): void
    {
        $this->expectException(\CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation::class);
        $this->connector()->pushReadyToShip(new AuthContext(1, 'shopee', '55', 'ACCESS_1'), 'SN_1');
    }
```

- [ ] **Step 3: Run — expect FAIL**

Run: `php artisan test --filter=ShopeeConnectorContractTest`
Expected: FAIL (fulfillment tests).

- [ ] **Step 4: Implement arrangeShipment + getShippingDocument** (replace stubs in `ShopeeConnector`):
```php
    public function arrangeShipment(AuthContext $auth, string $externalOrderId, array $params = []): array
    {
        $cfg = $this->client->cfg();
        $packageNumber = (string) ($params['packages'][0]['externalPackageId'] ?? '');

        if ((string) ($cfg['fulfillment_mode'] ?? 'auto') !== 'refetch_only') {
            $param = $this->client->shopGet($auth, $this->client->endpoint('shipping_parameter'), array_filter(['order_sn' => $externalOrderId, 'package_number' => $packageNumber ?: null]));
            $body = ['order_sn' => $externalOrderId];
            if ($packageNumber !== '') {
                $body['package_number'] = $packageNumber;
            }
            // Prefer dropoff when offered, else pickup with first available slot.
            if (! empty($param['dropoff'])) {
                $body['dropoff'] = (object) [];
            } else {
                $addr = (array) ($param['pickup']['address_list'][0] ?? []);
                $body['pickup'] = array_filter([
                    'address_id' => $addr['address_id'] ?? null,
                    'pickup_time_id' => $addr['time_slot_list'][0]['pickup_time_id'] ?? null,
                ]);
            }
            $this->client->shopPost($auth, $this->client->endpoint('ship_order'), [], $body);
        }

        $track = $this->client->shopGet($auth, $this->client->endpoint('tracking_number'), array_filter([
            'order_sn' => $externalOrderId, 'package_number' => $packageNumber ?: null,
        ]));

        return [
            'tracking_no' => ($track['tracking_number'] ?? null) ? (string) $track['tracking_number'] : null,
            'carrier' => null,
            'raw_status' => 'PROCESSED',
            'package_id' => $packageNumber ?: $externalOrderId,
        ];
    }

    public function getShippingDocument(AuthContext $auth, string $externalOrderId, array $query = []): array
    {
        $cfg = $this->client->cfg();
        $packageNumber = (string) ($query['externalPackageId'] ?? '');
        $docType = (string) ($cfg['document_type'] ?? 'NORMAL_AIR_WAYBILL');
        $orderEntry = array_filter(['order_sn' => $externalOrderId, 'package_number' => $packageNumber ?: null, 'shipping_document_type' => $docType]);

        $this->client->shopPost($auth, $this->client->endpoint('create_document'), [], ['order_list' => [$orderEntry]]);

        $attempts = (int) ($cfg['document_poll_attempts'] ?? 6);
        $sleepMs = (int) ($cfg['document_poll_sleep_ms'] ?? 1000);
        $ready = false;
        for ($i = 0; $i < $attempts; $i++) {
            $res = $this->client->shopPost($auth, $this->client->endpoint('get_document_result'), [], [
                'order_list' => [array_filter(['order_sn' => $externalOrderId, 'package_number' => $packageNumber ?: null])],
            ]);
            $status = (string) ($res['result_list'][0]['status'] ?? 'PROCESSING');
            if ($status === 'READY') {
                $ready = true;
                break;
            }
            if ($status === 'FAILED') {
                throw new ShopeeApiException("Shopee shipping document FAILED for {$externalOrderId}", 'document_failed');
            }
            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }
        if (! $ready) {
            throw new ShopeeApiException("Shopee shipping document not ready for {$externalOrderId} after {$attempts} attempts", 'document_timeout');
        }

        $bytes = $this->client->shopPostRaw($auth, $this->client->endpoint('download_document'), [
            'order_list' => [array_filter(['order_sn' => $externalOrderId, 'package_number' => $packageNumber ?: null, 'shipping_document_type' => $docType])],
        ]);

        return ['filename' => 'shopee-'.$externalOrderId.'.pdf', 'mime' => 'application/pdf', 'bytes' => $bytes];
    }
```

- [ ] **Step 5: Run — expect PASS**

Run: `php artisan test --filter=ShopeeConnectorContractTest`
Expected: PASS (14 tests).

- [ ] **Step 6: Commit**
```bash
git add app/app/Integrations/Channels/Shopee/ShopeeConnector.php app/tests/fixtures/Channels/shopee/ShopeeFixtures.php app/tests/Feature/Channels/ShopeeConnectorContractTest.php
git commit -m "feat(channels): Shopee arrangeShipment + getShippingDocument (async create/poll/download)"
```

---

## Task 7: Listings & stock — fetchListings + updateStock

**Files:**
- Modify: `app/app/Integrations/Channels/Shopee/ShopeeConnector.php`
- Modify: `app/app/Integrations/Channels/Shopee/ShopeeMappers.php` (add `listings()`)
- Modify: `app/tests/fixtures/Channels/shopee/ShopeeFixtures.php`
- Modify: `app/tests/Feature/Channels/ShopeeConnectorContractTest.php`

- [ ] **Step 1: Add fixtures** — append to `ShopeeFixtures`:
```php
    public static function itemList(): array
    {
        return ['error' => '', 'response' => ['item' => [['item_id' => 111]], 'next_offset' => 0, 'has_next_page' => false]];
    }

    public static function itemBaseInfo(): array
    {
        return ['error' => '', 'response' => ['item_list' => [[
            'item_id' => 111, 'item_name' => 'Áo thun', 'item_sku' => 'SKU-A', 'item_status' => 'NORMAL',
            'image' => ['image_url_list' => ['https://img/a.jpg']], 'price_info' => [['current_price' => 120000]],
        ]]]];
    }

    public static function modelList(): array
    {
        return ['error' => '', 'response' => ['model' => [
            ['model_id' => 222, 'model_sku' => 'SKU-A-RED', 'price_info' => [['current_price' => 115000]], 'stock_info_v2' => ['summary_info' => ['total_available_stock' => 7]]],
        ]]];
    }
```

- [ ] **Step 2: Write failing tests** — append to `ShopeeConnectorContractTest`:
```php
    public function test_fetch_listings_returns_one_entry_per_model(): void
    {
        Http::fake([
            '*/api/v2/product/get_item_list*' => Http::response(ShopeeFixtures::itemList(), 200),
            '*/api/v2/product/get_item_base_info*' => Http::response(ShopeeFixtures::itemBaseInfo(), 200),
            '*/api/v2/product/get_model_list*' => Http::response(ShopeeFixtures::modelList(), 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $page = $this->connector()->fetchListings($auth, ['pageSize' => 50]);
        $this->assertCount(1, $page->items);
        $l = $page->items[0];
        $this->assertSame('SKU-A-RED', $l->externalSkuId);
        $this->assertSame('111', $l->externalProductId);
        $this->assertSame(115000, $l->price);
        $this->assertSame(7, $l->channelStock);
        $this->assertTrue($l->isActive);
    }

    public function test_update_stock_posts_model_stock(): void
    {
        Http::fake(['*/api/v2/product/update_stock*' => Http::response(['error' => '', 'response' => []], 200)]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $this->connector()->updateStock($auth, '222', 9, ['external_product_id' => '111']);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/api/v2/product/update_stock')
            && $r['item_id'] === 111
            && $r['stock_list'][0]['model_id'] === 222
            && $r['stock_list'][0]['seller_stock'][0]['stock'] === 9);
    }
```

- [ ] **Step 3: Run — expect FAIL**

Run: `php artisan test --filter=ShopeeConnectorContractTest`
Expected: FAIL.

- [ ] **Step 4: Add `listings()` mapper** — append to `ShopeeMappers`:
```php
    /**
     * @param  array<string,mixed>  $itemBase  one get_item_base_info item
     * @param  array<string,mixed>  $modelRes  get_model_list `response`
     * @return list<\CMBcoreSeller\Integrations\Channels\DTO\ChannelListingDTO>
     */
    public static function listings(array $itemBase, array $modelRes): array
    {
        $itemId = (string) ($itemBase['item_id'] ?? '');
        $title = (string) ($itemBase['item_name'] ?? '');
        $image = $itemBase['image']['image_url_list'][0] ?? null;
        $active = (string) ($itemBase['item_status'] ?? 'NORMAL') === 'NORMAL';
        $models = (array) ($modelRes['model'] ?? []);
        $out = [];
        if ($models === []) {
            $out[] = new \CMBcoreSeller\Integrations\Channels\DTO\ChannelListingDTO(
                externalSkuId: $itemId, externalProductId: $itemId,
                sellerSku: ($itemBase['item_sku'] ?? '') !== '' ? (string) $itemBase['item_sku'] : null,
                title: $title, variation: null,
                price: (int) round((float) ($itemBase['price_info'][0]['current_price'] ?? 0)),
                channelStock: null, image: $image, isActive: $active, raw: $itemBase,
            );

            return $out;
        }
        foreach ($models as $m) {
            $out[] = new \CMBcoreSeller\Integrations\Channels\DTO\ChannelListingDTO(
                externalSkuId: (string) ($m['model_id'] ?? ''),
                externalProductId: $itemId,
                sellerSku: ($m['model_sku'] ?? '') !== '' ? (string) $m['model_sku'] : (($itemBase['item_sku'] ?? '') !== '' ? (string) $itemBase['item_sku'] : null),
                title: $title,
                variation: ($m['model_name'] ?? '') !== '' ? (string) $m['model_name'] : null,
                price: (int) round((float) ($m['price_info'][0]['current_price'] ?? 0)),
                channelStock: isset($m['stock_info_v2']['summary_info']['total_available_stock']) ? (int) $m['stock_info_v2']['summary_info']['total_available_stock'] : null,
                image: $image, isActive: $active, raw: $m,
            );
        }

        return $out;
    }
```

- [ ] **Step 5: Implement fetchListings + updateStock** (replace stubs):
```php
    public function fetchListings(AuthContext $auth, array $query = []): Page
    {
        $pageSize = min(100, max(1, (int) ($query['pageSize'] ?? 50)));
        $offset = (int) ($query['cursor'] ?? 0);
        $list = $this->client->shopGet($auth, $this->client->endpoint('item_list'), [
            'offset' => $offset, 'page_size' => $pageSize, 'item_status' => 'NORMAL',
        ]);
        $itemIds = array_values(array_filter(array_map(fn ($i) => (int) ($i['item_id'] ?? 0), (array) ($list['item'] ?? []))));
        $items = [];
        if ($itemIds !== []) {
            $base = $this->client->shopGet($auth, $this->client->endpoint('item_base_info'), ['item_id_list' => implode(',', $itemIds)]);
            foreach ((array) ($base['item_list'] ?? []) as $itemBase) {
                $models = $this->client->shopGet($auth, $this->client->endpoint('model_list'), ['item_id' => (int) ($itemBase['item_id'] ?? 0)]);
                foreach (ShopeeMappers::listings((array) $itemBase, $models) as $dto) {
                    $items[] = $dto;
                }
            }
        }
        $hasMore = (bool) ($list['has_next_page'] ?? false);

        return new Page($items, $hasMore ? (string) ((int) ($list['next_offset'] ?? ($offset + $pageSize))) : null, $hasMore);
    }

    public function updateStock(AuthContext $auth, string $externalSkuId, int $available, array $context = []): void
    {
        $itemId = (int) ($context['external_product_id'] ?? 0);
        if ($itemId === 0) {
            throw new ShopeeApiException('Shopee updateStock requires external_product_id (item_id).', 'error_param');
        }
        $this->client->shopPost($auth, $this->client->endpoint('update_stock'), [], [
            'item_id' => $itemId,
            'stock_list' => [[
                'model_id' => (int) $externalSkuId,
                'seller_stock' => [['stock' => max(0, $available)]],
            ]],
        ]);
    }
```

- [ ] **Step 6: Run — expect PASS**

Run: `php artisan test --filter=ShopeeConnectorContractTest`
Expected: PASS (16 tests).

- [ ] **Step 7: Commit**
```bash
git add app/app/Integrations/Channels/Shopee/ShopeeConnector.php app/app/Integrations/Channels/Shopee/ShopeeMappers.php app/tests/fixtures/Channels/shopee/ShopeeFixtures.php app/tests/Feature/Channels/ShopeeConnectorContractTest.php
git commit -m "feat(channels): Shopee fetchListings (item+model) + updateStock"
```

---

## Task 8: Finance — fetchSettlements (escrow, gated)

**Files:**
- Modify: `app/app/Integrations/Channels/Shopee/ShopeeConnector.php`
- Modify: `app/app/Integrations/Channels/Shopee/ShopeeMappers.php` (add `settlement()`)
- Modify: `app/tests/fixtures/Channels/shopee/ShopeeFixtures.php`
- Modify: `app/tests/Feature/Channels/ShopeeConnectorContractTest.php`

- [ ] **Step 1: Add fixtures** — append to `ShopeeFixtures`:
```php
    public static function escrowList(): array
    {
        return ['error' => '', 'response' => ['order_sn_list' => ['SN_1'], 'more' => false]];
    }

    public static function escrowDetail(): array
    {
        return ['error' => '', 'response' => [
            'order_sn' => 'SN_1',
            'order_income' => [
                'escrow_amount' => 210000, 'buyer_total_amount' => 250000,
                'commission_fee' => 15000, 'service_fee' => 5000, 'seller_transaction_fee' => 2000,
                'actual_shipping_fee' => 20000, 'shopee_shipping_rebate' => 18000,
                'voucher_from_seller' => 0, 'voucher_from_shopee' => 0,
            ],
        ]];
    }
```

- [ ] **Step 2: Write failing test** — append to `ShopeeConnectorContractTest`:
```php
    public function test_fetch_settlements_maps_escrow_to_settlement_dto(): void
    {
        config(['integrations.shopee.finance_enabled' => true]);
        Http::fake([
            '*/api/v2/payment/get_escrow_list*' => Http::response(ShopeeFixtures::escrowList(), 200),
            '*/api/v2/payment/get_escrow_detail*' => Http::response(ShopeeFixtures::escrowDetail(), 200),
        ]);
        $auth = new AuthContext(1, 'shopee', '55', 'ACCESS_1');

        $page = $this->connector()->fetchSettlements($auth, [
            'from' => \Carbon\CarbonImmutable::parse('2026-01-01T00:00:00Z'),
            'to' => \Carbon\CarbonImmutable::parse('2026-01-15T00:00:00Z'),
        ]);
        $this->assertCount(1, $page->items);
        $s = $page->items[0];
        $this->assertSame(210000, $s->totalPayout);
        $this->assertNotEmpty($s->lines);
        $types = array_map(fn ($l) => $l->feeType, $s->lines);
        $this->assertContains('commission', $types);
        $this->assertContains('revenue', $types);
    }
```

- [ ] **Step 3: Run — expect FAIL**

Run: `php artisan test --filter=ShopeeConnectorContractTest`
Expected: FAIL.

- [ ] **Step 4: Add `settlement()` mapper** — append to `ShopeeMappers`:
```php
    use CMBcoreSeller\Integrations\Channels\DTO\SettlementDTO;  // (place with the other use-statements at top of file)
    use CMBcoreSeller\Integrations\Channels\DTO\SettlementLineDTO;
```
(then the method):
```php
    /**
     * @param  list<array<string,mixed>>  $escrows  list of get_escrow_detail `response`
     */
    public static function settlement(array $escrows, CarbonImmutable $from, CarbonImmutable $to): SettlementDTO
    {
        $lines = [];
        $payout = 0;
        $revenue = 0;
        $fee = 0;
        $ship = 0;
        foreach ($escrows as $e) {
            $sn = (string) ($e['order_sn'] ?? '');
            $inc = (array) ($e['order_income'] ?? []);
            $payout += (int) round((float) ($inc['escrow_amount'] ?? 0));
            $add = function (string $type, int $amount, ?string $sn) use (&$lines, &$revenue, &$fee, &$ship) {
                if ($amount === 0) {
                    return;
                }
                $lines[] = new SettlementLineDTO(feeType: $type, amount: $amount, externalOrderId: $sn);
                if ($type === SettlementLineDTO::TYPE_REVENUE) {
                    $revenue += $amount;
                } elseif ($type === SettlementLineDTO::TYPE_SHIPPING_FEE || $type === SettlementLineDTO::TYPE_SHIPPING_SUBSIDY) {
                    $ship += $amount;
                } else {
                    $fee += $amount;
                }
            };
            $add(SettlementLineDTO::TYPE_REVENUE, (int) round((float) ($inc['buyer_total_amount'] ?? 0)), $sn);
            $add(SettlementLineDTO::TYPE_COMMISSION, -(int) round((float) ($inc['commission_fee'] ?? 0)), $sn);
            $add(SettlementLineDTO::TYPE_PAYMENT_FEE, -(int) round((float) ($inc['seller_transaction_fee'] ?? 0)), $sn);
            $add(SettlementLineDTO::TYPE_OTHER, -(int) round((float) ($inc['service_fee'] ?? 0)), $sn);
            $add(SettlementLineDTO::TYPE_SHIPPING_FEE, -(int) round((float) ($inc['actual_shipping_fee'] ?? 0)), $sn);
            $add(SettlementLineDTO::TYPE_SHIPPING_SUBSIDY, (int) round((float) ($inc['shopee_shipping_rebate'] ?? 0)), $sn);
            $add(SettlementLineDTO::TYPE_VOUCHER_SELLER, -(int) round((float) ($inc['voucher_from_seller'] ?? 0)), $sn);
            $add(SettlementLineDTO::TYPE_VOUCHER_PLATFORM, (int) round((float) ($inc['voucher_from_shopee'] ?? 0)), $sn);
        }

        return new SettlementDTO(
            externalId: null, periodStart: $from, periodEnd: $to,
            totalPayout: $payout, totalRevenue: $revenue, totalFee: $fee, totalShippingFee: $ship,
            lines: $lines, raw: ['escrows' => $escrows],
        );
    }
```

- [ ] **Step 5: Implement fetchSettlements** (replace stub):
```php
    public function fetchSettlements(AuthContext $auth, array $query = []): Page
    {
        if (! $this->supports('finance.settlements')) {
            throw UnsupportedOperation::for($this->code(), 'fetchSettlements');
        }
        $from = $query['from'] ?? CarbonImmutable::now()->subDays(15);
        $to = $query['to'] ?? CarbonImmutable::now();
        $list = $this->client->shopGet($auth, $this->client->endpoint('escrow_list'), [
            'release_time_from' => $from->getTimestamp(), 'release_time_to' => $to->getTimestamp(), 'page_size' => 100,
        ]);
        $sns = array_values(array_filter(array_map('strval', (array) ($list['order_sn_list'] ?? []))));
        $escrows = [];
        foreach ($sns as $sn) {
            $detail = $this->client->shopGet($auth, $this->client->endpoint('escrow_detail'), ['order_sn' => $sn]);
            $escrows[] = $detail;
        }
        $settlement = ShopeeMappers::settlement($escrows, $from, $to);

        return new Page([$settlement], null, false);
    }
```

- [ ] **Step 6: Run — expect PASS**

Run: `php artisan test --filter=ShopeeConnectorContractTest`
Expected: PASS (17 tests).

- [ ] **Step 7: Commit**
```bash
git add app/app/Integrations/Channels/Shopee/ShopeeConnector.php app/app/Integrations/Channels/Shopee/ShopeeMappers.php app/tests/fixtures/Channels/shopee/ShopeeFixtures.php app/tests/Feature/Channels/ShopeeConnectorContractTest.php
git commit -m "feat(channels): Shopee fetchSettlements (escrow → SettlementDTO, gated)"
```

---

## Task 9: Feature DB sync test (pipeline integration)

**Files:**
- Create: `app/tests/Feature/Channels/ShopeeSyncTest.php`

> Read `app/tests/Feature/Channels/TikTokSyncTest.php` first and mirror its structure (RefreshDatabase, create ChannelAccount, dispatch SyncOrdersForShop, assert Order). Use ShopeeFixtures for Http::fake.

- [ ] **Step 1: Write the test**

`app/tests/Feature/Channels/ShopeeSyncTest.php`:
```php
<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeConnector;
use CMBcoreSeller\Modules\Channels\Jobs\SyncOrdersForShop;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\SyncRun;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\Channels\Shopee\ShopeeFixtures;
use Tests\TestCase;

class ShopeeSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_creates_orders_from_shopee(): void
    {
        ShopeeFixtures::configure();
        app(ChannelRegistry::class)->register('shopee', ShopeeConnector::class);
        Http::fake([
            '*/api/v2/order/get_order_list*' => Http::response(ShopeeFixtures::orderList('', false), 200),
            '*/api/v2/order/get_order_detail*' => Http::response(ShopeeFixtures::orderDetail(), 200),
        ]);

        $tenant = Tenant::create(['name' => 'SP Shop']);
        $account = ChannelAccount::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->getKey(), 'provider' => 'shopee', 'external_shop_id' => '55',
            'shop_name' => 'Shopee VN', 'status' => 'active', 'access_token' => 'ACCESS_1', 'refresh_token' => 'REFRESH_1',
        ]);

        SyncOrdersForShop::dispatchSync((int) $account->getKey(), null, SyncRun::TYPE_BACKFILL);

        $this->assertDatabaseHas('orders', ['channel_account_id' => $account->getKey(), 'external_order_id' => 'SN_1']);
        $order = Order::withoutGlobalScopes()->where('external_order_id', 'SN_1')->first();
        $this->assertSame('pending', $order->status);   // READY_TO_SHIP -> pending
    }
}
```

- [ ] **Step 2: Run — expect FAIL or PASS depending on column names**

Run: `php artisan test --filter=ShopeeSyncTest`
Expected: PASS. If it fails on column/status assertions, READ `TikTokSyncTest.php` + the `orders` schema and align field names (e.g. status column, `external_order_id`), then re-run. Do NOT weaken assertions — fix the mapping/test to match the real schema.

- [ ] **Step 3: Commit**
```bash
git add app/tests/Feature/Channels/ShopeeSyncTest.php
git commit -m "test(channels): Shopee end-to-end order sync (DB) via SyncOrdersForShop"
```

---

## Task 10: Docs + full regression

**Files:**
- Modify: `docs/04-channels/shopee.md`, `docs/04-channels/README.md`, `docs/03-domain/order-status-state-machine.md`

- [ ] **Step 1: Update `docs/04-channels/shopee.md`** — change Status line to "Implemented (faithful theo docs v2) — chờ verify sandbox" and fill the connector file path `app/Integrations/Channels/Shopee/` + reference this spec; keep the §1-7 detail (already accurate) and add a note that `INTEGRATIONS_CHANNELS` chưa bật shopee mặc định + finance gated.

- [ ] **Step 2: Update `docs/04-channels/README.md` §5** — Shopee row: status → "Implemented (Phase 4, faithful docs v2) — chờ verify sandbox; code `app/Integrations/Channels/Shopee/`".

- [ ] **Step 3: Update `docs/03-domain/order-status-state-machine.md` §4** — add a Shopee column mapping the raw statuses per §3.4 of the spec (UNPAID→unpaid, READY_TO_SHIP→pending, PROCESSED→processing, SHIPPED→shipped, TO_CONFIRM_RECEIVE→delivered, COMPLETED→completed, CANCELLED→cancelled, TO_RETURN→returning, IN_CANCEL→processing, RETRY_SHIP→processing). If the file's table format differs, follow it.

- [ ] **Step 4: Full regression — Channels + new Shopee suites**

Run: `php artisan test --filter='Channel|Shopee|Lazada|TikTok'`
Expected: PASS for all new Shopee tests + no NEW failures vs. baseline. (Pre-existing failures unrelated to this branch — `LazadaConnectorContractTest > unprocessed raw statuses default`, `SyncUnprocessedOrdersTest` — may still be red; confirm they are the SAME ones present before this branch and not caused by these changes.)

- [ ] **Step 5: Commit**
```bash
git add docs/04-channels/shopee.md docs/04-channels/README.md docs/03-domain/order-status-state-machine.md
git commit -m "docs(channels): Shopee connector implemented (faithful v2) + status map"
```

- [ ] **Step 6: Finish branch** — REQUIRED SUB-SKILL: `superpowers:finishing-a-development-branch`.

---

## Self-Review

- **Spec coverage:** §1 OAuth (Task 4), orders+window (Task 5), webhook (Task 6), fulfillment+async doc (Task 6b), listings/stock (Task 7), finance (Task 8); seam §2 (Task 1); config §4 (Task 3); capabilities/statusmap §3 (Tasks 3/4); tests §8 (Tasks 2,4-9); docs §1/§9 (Task 10). Acceptance §9 each maps to a task. ✔
- **Placeholder scan:** No TBD/TODO. "Mirror file X" references give the exact reference path + exact Shopee deltas/code — not placeholders. Webhook codes 4-13 intentionally default to `unknown` per spec (verify-sandbox) — explicit, not a gap. ✔
- **Type consistency:** DTO constructors match the real signatures read from source (TokenDTO/OrderDTO/OrderItemDTO/ChannelListingDTO/ShopInfoDTO/SettlementDTO/SettlementLineDTO/WebhookEventDTO/Page/AuthContext). `ShopeeMappers::token($res,$shopId)`, `order($d)`, `listings($itemBase,$modelRes)`, `settlement($escrows,$from,$to)` used consistently between mapper definition and connector calls. `ShopeeClient::shopGet/shopPost/shopPostRaw/publicPost/authorizeUrl/getAccessToken/refreshAccessToken/endpoint/cfg/redirectUri/pushUrl` names consistent between client and connector. Cursor format `"windowStart:inner"` consistent between encode (fetchOrders) and decode. ✔
