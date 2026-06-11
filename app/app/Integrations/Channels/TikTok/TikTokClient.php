<?php

namespace CMBcoreSeller\Integrations\Channels\TikTok;

use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\Exceptions\MarketplaceApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

/**
 * Thin HTTP client for the TikTok Shop Partner Open API + the auth host.
 *
 * - Shop-scoped Open API calls (open-api.tiktokglobalshop.com): signed with
 *   HMAC-SHA256 ({@see TikTokSigner}); common query params `app_key` +
 *   `timestamp` + `sign` (+ `shop_cipher` for shop endpoints); access token in
 *   the `x-tts-access-token` header; JSON body. Envelope `{code,message,data,request_id}`;
 *   `code != 0` throws {@see TikTokApiException}.
 * - Token endpoints (auth.tiktok-shops.com): NOT signed — `app_key` + `app_secret`
 *   in the query string.
 *
 * Sandbox vs production = config only (`integrations.tiktok.base_url` / `auth_base_url`).
 * Never logs tokens/secrets. Versioned per `integrations.tiktok.version.*`. See
 * docs/04-channels/tiktok-shop.md.
 */
class TikTokClient
{
    /** @var array<string, mixed> */
    protected array $cfg;

    public function __construct()
    {
        $this->cfg = (array) config('integrations.tiktok', []);

        // Spec 2026-05-17 — super-admin có thể đổi secrets nóng qua /admin/settings.
        // Đọc qua `system_setting()`: nếu DB có → dùng; nếu DB rỗng → fallback config (env).
        $this->cfg['app_key'] = system_setting('marketplace.tiktok.app_key', $this->cfg['app_key'] ?? null);
        $this->cfg['app_secret'] = system_setting('marketplace.tiktok.app_secret', $this->cfg['app_secret'] ?? null);
        $this->cfg['service_id'] = system_setting('marketplace.tiktok.service_id', $this->cfg['service_id'] ?? null);
        $this->cfg['sandbox'] = (bool) system_setting('marketplace.tiktok.sandbox', $this->cfg['sandbox'] ?? false);
    }

    public function appKey(): string
    {
        return (string) ($this->cfg['app_key'] ?? throw new RuntimeException('TikTok app_key is not configured (TIKTOK_APP_KEY).'));
    }

    protected function appSecret(): string
    {
        return (string) ($this->cfg['app_secret'] ?? throw new RuntimeException('TikTok app_secret is not configured (TIKTOK_APP_SECRET).'));
    }

    public function versionFor(string $group): string
    {
        return (string) ($this->cfg['version'][$group] ?? '202309');
    }

    /**
     * Seller-authorization URL for the TikTok Shop Partner "service" flow:
     *   https://services.tiktokshop.com/open/authorize?service_id={service_id}&state={state}
     * The seller logs in, picks shop(s), authorizes; TikTok then redirects to the
     * **callback/redirect URL configured in the Partner Center app** (NOT a `redirect_uri`
     * query param — the service flow doesn't take one) with ?app_key=&code=&state=&shop_region=…
     * So the Partner Center "Authorization Settings → Redirect URL" must be exactly
     *   https://<APP_URL host>/oauth/tiktok/callback   (e.g. https://app.cmbcore.com/oauth/tiktok/callback).
     */
    public function authorizeUrl(string $state): string
    {
        $base = (string) ($this->cfg['authorize_url'] ?? 'https://services.tiktokshop.com/open/authorize');
        $params = array_filter([
            'service_id' => $this->cfg['service_id'] ?? null,
            'app_key' => $this->cfg['app_key'] ?? null,
            'state' => $state,
        ], fn ($v) => $v !== null && $v !== '');

        return $base.'?'.http_build_query($params);
    }

    // --- Token endpoints (auth host, unsigned) -------------------------------

    /** @return array<string, mixed> token data */
    public function getAccessToken(string $authCode): array
    {
        return $this->authCall('/api/v2/token/get', ['grant_type' => 'authorized_code', 'auth_code' => $authCode]);
    }

    /** @return array<string, mixed> token data */
    public function refreshAccessToken(string $refreshToken): array
    {
        return $this->authCall('/api/v2/token/refresh', ['grant_type' => 'refresh_token', 'refresh_token' => $refreshToken]);
    }

    /** @param array<string,mixed> $extraQuery @return array<string,mixed> */
    protected function authCall(string $path, array $extraQuery): array
    {
        $url = rtrim((string) ($this->cfg['auth_base_url'] ?? 'https://auth.tiktok-shops.com'), '/').$path;
        $query = array_merge($extraQuery, ['app_key' => $this->appKey(), 'app_secret' => $this->appSecret()]);

        $resp = $this->http()->get($url, $query);
        $json = $resp->json() ?? [];
        if (! $resp->successful() || ($json['code'] ?? -1) !== 0) {
            $this->fail($path, $json, $resp->status());
        }

        return (array) ($json['data'] ?? []);
    }

    // --- Signed Open API calls ----------------------------------------------

    /**
     * @param  array<string, scalar|null>  $query  extra query params (beyond app_key/timestamp/sign[/shop_cipher])
     * @param  array<string, mixed>|null  $body  JSON body for POST/PUT
     * @return array<string, mixed> the `data` object from the envelope
     */
    public function request(string $method, string $path, AuthContext $auth, array $query = [], ?array $body = null, bool $shopScoped = true): array
    {
        $json = $this->requestRaw($method, $path, $auth, $query, $body, $shopScoped);
        if (($json['code'] ?? -1) !== 0) {
            $this->fail($path, $json, (int) ($json['__http_status'] ?? 0));
        }

        return (array) ($json['data'] ?? []);
    }

    /**
     * Same signed call as {@see request()} but returns the FULL decoded envelope
     * `{code,message,data,request_id}` WITHOUT throwing on `code != 0`. Lets a caller
     * (e.g. the product-publishing connector) inspect the envelope and raise a
     * provider-agnostic {@see MarketplaceApiException}.
     * The `__http_status` key carries the HTTP status for callers that need it.
     *
     * @param  array<string, scalar|null>  $query
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>
     */
    public function requestRaw(string $method, string $path, AuthContext $auth, array $query = [], ?array $body = null, bool $shopScoped = true): array
    {
        $this->throttle($auth);

        $bodyJson = $body === null ? '' : (string) json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $query = $this->signedQuery($path, $auth, $query, $bodyJson, $shopScoped);

        $url = rtrim((string) ($this->cfg['base_url'] ?? 'https://open-api.tiktokglobalshop.com'), '/').$path;

        $req = $this->http()
            ->withQueryParameters($query)
            ->withHeaders(array_filter(['Content-Type' => 'application/json', 'x-tts-access-token' => $auth->accessToken ?: null]));

        $resp = match (strtoupper($method)) {
            'GET' => $req->get($url),
            'DELETE' => $req->delete($url),
            'PUT' => $req->withBody($bodyJson, 'application/json')->put($url),
            default => $req->withBody($bodyJson, 'application/json')->post($url),
        };

        $json = (array) ($resp->json() ?? []);
        $json['__http_status'] = $resp->status();

        return $json;
    }

    /**
     * Multipart upload (e.g. product image upload). Downloads $imageUrlOrPath (URL) or
     * reads a local path into a temp file and POSTs it as multipart under $fileField,
     * reusing the exact signing logic — signing for multipart EXCLUDES the body
     * ({@see TikTokSigner::sign()} `multipart: true`). Returns the FULL envelope (no throw).
     *
     * @param  array<string, scalar|null>  $extra  extra form fields (e.g. use_case)
     * @return array<string, mixed>
     */
    public function uploadMultipart(string $path, AuthContext $auth, string $fileField, string $imageUrlOrPath, array $extra = [], bool $shopScoped = false): array
    {
        $this->throttle($auth);

        $binary = str_starts_with($imageUrlOrPath, 'http://') || str_starts_with($imageUrlOrPath, 'https://')
            ? (string) Http::timeout(20)->get($imageUrlOrPath)->body()
            : (string) file_get_contents($imageUrlOrPath);

        // Form fields participate in the signature like query params do; the binary body does not.
        $query = $this->signedQuery($path, $auth, $extra, '', $shopScoped, multipart: true);

        $url = rtrim((string) ($this->cfg['base_url'] ?? 'https://open-api.tiktokglobalshop.com'), '/').$path;

        $req = $this->http()
            ->withQueryParameters($query)
            ->withHeaders(array_filter(['x-tts-access-token' => $auth->accessToken ?: null]))
            ->attach($fileField, $binary, 'upload.jpg');
        foreach ($extra as $k => $v) {
            $req = $req->attach((string) $k, (string) $v);
        }

        $resp = $req->post($url);

        $json = (array) ($resp->json() ?? []);
        $json['__http_status'] = $resp->status();

        return $json;
    }

    /**
     * Build the common signed query (app_key + timestamp [+ shop_cipher] + sign).
     *
     * @param  array<string, scalar|null>  $query
     * @return array<string, scalar|null>
     */
    protected function signedQuery(string $path, AuthContext $auth, array $query, string $bodyJson, bool $shopScoped, bool $multipart = false): array
    {
        $query = array_merge($query, ['app_key' => $this->appKey(), 'timestamp' => (string) now()->timestamp]);
        if ($shopScoped && isset($auth->extra['shop_cipher'])) {
            $query['shop_cipher'] = $auth->extra['shop_cipher'];
        }
        $query = array_map(fn ($v) => is_array($v) ? implode(',', $v) : $v, $query);
        $query['sign'] = TikTokSigner::sign($this->appSecret(), $path, $query, $bodyJson, $multipart);

        return $query;
    }

    /** @param array<string,scalar|null> $query @return array<string,mixed> */
    public function get(string $path, AuthContext $auth, array $query = [], bool $shopScoped = true): array
    {
        return $this->request('GET', $path, $auth, $query, null, $shopScoped);
    }

    /** @param array<string,mixed> $body @param array<string,scalar|null> $query @return array<string,mixed> */
    public function post(string $path, AuthContext $auth, array $body = [], array $query = [], bool $shopScoped = true): array
    {
        return $this->request('POST', $path, $auth, $query, $body, $shopScoped);
    }

    /** @param array<string,mixed> $body @param array<string,scalar|null> $query @return array<string,mixed> */
    public function put(string $path, AuthContext $auth, array $body = [], array $query = [], bool $shopScoped = true): array
    {
        return $this->request('PUT', $path, $auth, $query, $body, $shopScoped);
    }

    /** @param array<string,scalar|null> $query @return array<string,mixed> */
    public function delete(string $path, AuthContext $auth, array $query = [], bool $shopScoped = true): array
    {
        return $this->request('DELETE', $path, $auth, $query, null, $shopScoped);
    }

    // --- internals -----------------------------------------------------------

    protected function http(): PendingRequest
    {
        $http = (array) ($this->cfg['http'] ?? []);

        return Http::timeout((int) ($http['timeout'] ?? 20))
            ->connectTimeout(10)
            ->retry((int) ($http['retries'] ?? 2), (int) ($http['retry_sleep_ms'] ?? 500), throw: false)
            ->acceptJson();
    }

    /** Best-effort per-(provider, shop) rate limit so one shop can't starve the queue. */
    protected function throttle(AuthContext $auth): void
    {
        $perMin = (int) system_setting('throttle.tiktok_per_min', config('integrations.throttle.tiktok', 600));
        if ($perMin <= 0) {
            return;
        }
        $key = "tiktok:rate:{$auth->channelAccountId}";
        for ($i = 0; $i < 50; $i++) {
            if (! RateLimiter::tooManyAttempts($key, $perMin)) {
                RateLimiter::hit($key, 60);

                return;
            }
            usleep(200_000);
        }
        throw new RuntimeException("TikTok rate limit: không lấy được slot sau 10s cho shop {$auth->channelAccountId}. Job sẽ được retry.");
    }

    /** @param array<string,mixed> $json */
    protected function fail(string $path, array $json, int $httpStatus): never
    {
        $code = $json['code'] ?? null;
        $message = $json['message'] ?? 'unknown error';
        Log::warning('tiktok.api.error', ['path' => $path, 'http' => $httpStatus, 'code' => $code]);

        throw new TikTokApiException("TikTok API error on {$path}: [{$code}] {$message}", (int) ($code ?? 0), $httpStatus);
    }
}
