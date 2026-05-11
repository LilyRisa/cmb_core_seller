<?php

namespace CMBcoreSeller\Integrations\Channels\TikTok;

use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
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

    public function authorizeUrl(string $state, string $redirectUri): string
    {
        $base = (string) ($this->cfg['authorize_url'] ?? 'https://services.tiktokshop.com/open/authorize');
        $params = array_filter([
            'service_id' => $this->cfg['service_id'] ?? null,
            'app_key' => $this->cfg['app_key'] ?? null,
            'state' => $state,
            'redirect_uri' => $redirectUri,
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
     * @param  array<string, scalar|null>  $query   extra query params (beyond app_key/timestamp/sign[/shop_cipher])
     * @param  array<string, mixed>|null   $body    JSON body for POST/PUT
     * @return array<string, mixed>                 the `data` object from the envelope
     */
    public function request(string $method, string $path, AuthContext $auth, array $query = [], ?array $body = null, bool $shopScoped = true): array
    {
        $this->throttle($auth);

        $bodyJson = $body === null ? '' : (string) json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $query = array_merge($query, ['app_key' => $this->appKey(), 'timestamp' => (string) now()->timestamp]);
        if ($shopScoped && isset($auth->extra['shop_cipher'])) {
            $query['shop_cipher'] = $auth->extra['shop_cipher'];
        }
        $query = array_map(fn ($v) => is_array($v) ? implode(',', $v) : $v, $query);
        $query['sign'] = TikTokSigner::sign($this->appSecret(), $path, $query, $bodyJson);

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

        $json = $resp->json() ?? [];
        if (! $resp->successful() || ($json['code'] ?? -1) !== 0) {
            $this->fail($path, $json, $resp->status());
        }

        return (array) ($json['data'] ?? []);
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
        $perMin = (int) config('integrations.throttle.tiktok', 600);
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
        // Couldn't get a slot — let the call through; TikTok's 429 + job retry handle it.
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
