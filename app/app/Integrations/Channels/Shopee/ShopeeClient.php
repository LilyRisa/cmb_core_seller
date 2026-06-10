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
 * Sandbox vs prod = cờ `sandbox` (env SHOPEE_SANDBOX + DB system_setting) → baseUrl() switch host. Never logs secrets. See docs/04-channels/shopee.md.
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

        // Shopee returns HTTP 200 with a JSON `error` envelope even for binary endpoints on app-level errors;
        // a real document body is binary (not valid JSON) so $resp->json() is null for it.
        $json = $resp->json();
        if (! $resp->successful() || (is_array($json) && (string) ($json['error'] ?? '') !== '')) {
            $this->fail($path, is_array($json) ? $json : [], $resp->status());
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

        // Token endpoints (publicPost) put data at the root (no `response` key); shop endpoints wrap in `response`.
        return array_key_exists('response', $json) ? (array) ($json['response'] ?? []) : $json;
    }

    /**
     * Host tự switch theo cờ `sandbox` ĐÃ RESOLVE (env SHOPEE_SANDBOX + đè bằng DB
     * system_setting marketplace.shopee.sandbox — xem constructor). Sandbox (VN/Global):
     * https://openplatform.sandbox.test-stable.shopee.sg · Prod: https://partner.shopeemobile.com.
     * (KHÔNG dùng partner.test-stable.shopeemobile.com — sai host.)
     *
     * Một base_url tường minh (chỉ test set qua config) vẫn được ưu tiên để Http::fake host cố định.
     */
    protected function baseUrl(): string
    {
        $explicit = (string) ($this->cfg['base_url'] ?? '');
        if ($explicit !== '') {
            return $explicit;
        }

        return ($this->cfg['sandbox'] ?? false)
            ? 'https://openplatform.sandbox.test-stable.shopee.sg'
            : 'https://partner.shopeemobile.com';
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
        $perMin = (int) system_setting('throttle.shopee_per_min', config('integrations.throttle.shopee', 600));
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

        // Giữ envelope (gồm `response.result_list`) để caller bóc lý do thật của batch error (vd
        // create_shipping_document trả `common.batch_api_all_failed`, detail nằm trong result_list[].fail_*).
        $response = is_array($json['response'] ?? null) ? (array) $json['response'] : $json;

        throw new ShopeeApiException("Shopee API error on {$path}: [{$error}] {$message}".($requestId ? " (request_id={$requestId})" : ''), $error, $httpStatus, $response);
    }
}
