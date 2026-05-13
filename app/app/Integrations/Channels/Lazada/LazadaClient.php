<?php

namespace CMBcoreSeller\Integrations\Channels\Lazada;

use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

/**
 * Thin HTTP client for the Lazada Open Platform.
 *
 * - REST gateway (Vietnam: https://api.lazada.vn/rest): every call is signed with
 *   HMAC-SHA256 ({@see LazadaSigner}); common params `app_key` + `timestamp` (ms) +
 *   `sign_method=sha256` + `sign` (+ `access_token` for shop-scoped calls). Business
 *   params go in the query string (GET) or form body (POST). Envelope
 *   `{ code, type, message, request_id, data }` — `code != "0"` throws {@see LazadaApiException}.
 * - Token endpoints (auth.lazada.com/rest/auth/token/create|refresh): same signing,
 *   no access_token.
 * - The seller authorization page (auth.lazada.com/oauth/authorize) DOES take a
 *   `redirect_uri` — must equal https://<APP_URL host>/oauth/lazada/callback.
 *
 * Sandbox vs production = config only (`integrations.lazada.api_base_url` / `auth_base_url`).
 * Never logs tokens/secrets. See docs/04-channels/lazada.md, SPEC 0008.
 */
class LazadaClient
{
    /** @var array<string, mixed> */
    protected array $cfg;

    public function __construct()
    {
        $this->cfg = (array) config('integrations.lazada', []);
    }

    public function appKey(): string
    {
        return (string) ($this->cfg['app_key'] ?? throw new RuntimeException('Lazada app_key is not configured (LAZADA_APP_KEY).'));
    }

    protected function appSecret(): string
    {
        return (string) ($this->cfg['app_secret'] ?? throw new RuntimeException('Lazada app_secret is not configured (LAZADA_APP_SECRET).'));
    }

    /** redirect_uri registered in the Lazada app console — used in the authorize URL and token exchange. */
    public function redirectUri(): string
    {
        return (string) ($this->cfg['redirect_uri'] ?? url('/oauth/lazada/callback'));
    }

    /**
     * Seller authorization URL theo tài liệu chính thức Lazada Open Platform (Seller authorization
     * introduction — docId=108260): `https://auth.lazada.com/oauth/authorize?response_type=code
     * &force_auth=true&redirect_uri={callback}&client_id={app_key}` + `state` + (tuỳ chọn) `country`.
     *
     * Notes:
     *  - `force_auth=true` (mặc định) ⇒ Lazada làm mới session cookie & buộc đăng nhập lại; tài liệu
     *    chính thức khuyên dùng — tránh trường hợp seller đang đăng nhập tài khoản khác.
     *  - `country=vn` (tuỳ chọn) ⇒ lọc consent về VN cho gọn (Lazada hỗ trợ csv `sg,my,th,vn,ph,id,cb`).
     *  - `redirect_uri` **phải khớp byte-for-byte** với URL Callback đã đăng ký trong app console — lệch
     *    (http↔https, domain, dấu /, encoding) ⇒ Lazada trả "tham số không hợp lệ".
     */
    public function authorizeUrl(string $state, ?string $redirectUriOverride = null): string
    {
        $base = (string) ($this->cfg['authorize_url'] ?? 'https://auth.lazada.com/oauth/authorize');
        $forceAuth = (bool) ($this->cfg['authorize_force_auth'] ?? true);
        $country = trim((string) ($this->cfg['authorize_country'] ?? ''));

        return $base.'?'.http_build_query(array_filter([
            'response_type' => 'code',
            'force_auth' => $forceAuth ? 'true' : null,
            'redirect_uri' => $redirectUriOverride ?: $this->redirectUri(),
            'client_id' => $this->appKey(),
            'state' => $state,
            'country' => $country !== '' ? $country : null,
        ], fn ($v) => $v !== null));
    }

    // --- Token endpoints (auth host) -----------------------------------------

    /** @return array<string,mixed> token data */
    public function getAccessToken(string $code): array
    {
        return $this->call('POST', '/auth/token/create', null, ['code' => $code], authHost: true);
    }

    /** @return array<string,mixed> token data */
    public function refreshAccessToken(string $refreshToken): array
    {
        return $this->call('POST', '/auth/token/refresh', null, ['refresh_token' => $refreshToken], authHost: true);
    }

    // --- Signed REST calls ---------------------------------------------------

    /**
     * Send a signed request to the Lazada REST gateway.
     *
     * Layout giống y hệt SDK chính thức (`sdk_lazada_php/lazop/LazopClient::execute()`):
     *  - **GET**: tất cả tham số (system + business + sign) trong query string.
     *  - **POST**: chỉ tham số **system + sign** trong query string; tham số **business** ở body
     *    `application/x-www-form-urlencoded`. (SDK upstream dùng multipart; gateway Lazada nhận cả hai —
     *    form-urlencoded gọn hơn và là cách Laravel HTTP client xử lý tự nhiên.)
     *
     * Lazada parse tham số system từ URL nên nếu nhét hết vào body (cách cũ), endpoint `/auth/token/create`
     * và một vài endpoint REST trả "tham số không hợp lệ" / sai sign ⇒ kẹt bước cấp quyền. SPEC 0008.
     *
     * @param  array<string, mixed>  $params  business params (arrays are JSON-encoded; merged with system params + signed)
     * @return array<string, mixed> the `data` object from the envelope
     */
    public function call(string $method, string $apiPath, ?AuthContext $auth, array $params = [], bool $authHost = false): array
    {
        if ($auth) {
            $this->throttle($auth);
        }

        // System params — khớp đúng `LazopClient::execute()` (SDK chính thức `sdk_lazada_php`). `partner_id`
        // BẮT BUỘC nằm trong tập tham số ký để một số endpoint (đặc biệt `/auth/token/create|refresh` ở host
        // auth.lazada.com) chấp nhận — thiếu nó thường bị trả "tham số không hợp lệ" / sai sign.
        $sysParams = [
            'app_key' => $this->appKey(),
            'timestamp' => (string) (now()->getTimestampMs()),
            'sign_method' => 'sha256',
            'partner_id' => (string) ($this->cfg['partner_id'] ?? 'cmb-core-seller-php-1.0'),
        ];
        if ($auth && $auth->accessToken !== '') {
            $sysParams['access_token'] = $auth->accessToken;
        }
        // string-ify business params; drop nulls
        $biz = [];
        foreach ($params as $k => $v) {
            if ($v === null) {
                continue;
            }
            $biz[$k] = is_array($v) ? (string) json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : (string) $v;
        }
        $all = array_merge($sysParams, $biz);
        $sign = LazadaSigner::sign($this->appSecret(), $apiPath, $all);
        $sysParams['sign'] = $sign;

        $base = $authHost
            ? rtrim((string) ($this->cfg['auth_base_url'] ?? 'https://auth.lazada.com/rest'), '/')
            : rtrim((string) ($this->cfg['api_base_url'] ?? 'https://api.lazada.vn/rest'), '/');
        $url = $base.$apiPath;

        // Trace nhẹ — chỉ tên path + tên tham số (không log giá trị secret / token / code). Bật bằng
        // `LAZADA_LOG_REQUESTS=true` khi cần soi luồng cấp quyền không hoạt động.
        if ((bool) ($this->cfg['log_requests'] ?? false)) {
            Log::info('lazada.api.request', [
                'method' => strtoupper($method), 'path' => $apiPath, 'auth_host' => $authHost,
                'sys_keys' => array_keys($sysParams), 'biz_keys' => array_keys($biz),
            ]);
        }

        // Retry tự động khi Lazada trả `SellerCallLimit` / `ApiCallLimit` / `SystemBusy` — message thường
        // ghi "ban will last N seconds"; ta sleep ~1.2× số đó rồi thử lại tối đa 2 lần. (HTTP 429 hiếm gặp
        // — Lazada thường trả HTTP 200 với `code != "0"` cho rate-limit.)
        $maxRetries = 2;
        $attempt = 0;
        while (true) {
            $req = $this->http();
            if (strtoupper($method) === 'GET') {
                $resp = $req->get($url, array_merge($sysParams, $biz));   // sys + biz + sign cùng query string
            } else {
                // POST: system params + sign trong query string; business params trong body **multipart/form-data**.
                $urlWithSys = $url.'?'.http_build_query($sysParams, '', '&', PHP_QUERY_RFC1738);
                $multipart = [];
                foreach ($biz as $k => $v) {
                    $multipart[] = ['name' => (string) $k, 'contents' => (string) $v];
                }
                $resp = $req->asMultipart()->post($urlWithSys, $multipart);
            }

            $json = $resp->json() ?? [];
            $code = (string) ($json['code'] ?? '');
            $ok = $resp->successful() && ($code === '' || $code === '0');
            if ($ok) {
                return (array) ($json['data'] ?? $json);
            }

            // Rate-limit retry: bóc số giây từ message ("ban will last 1 seconds") nếu có.
            $isRateLimit = in_array($code, ['SellerCallLimit', 'ApiCallLimit', 'AppCallLimit', 'SystemBusy'], true)
                || $resp->status() === 429;
            if ($isRateLimit && $attempt < $maxRetries) {
                $message = (string) ($json['message'] ?? '');
                $waitSec = preg_match('/(\d+)\s*seconds?/i', $message, $m) ? (int) $m[1] : 1;
                $waitMs = max(800, min(8_000, (int) ($waitSec * 1_200)));   // clamp 0.8s..8s
                Log::info('lazada.api.rate_limit_retry', ['path' => $apiPath, 'code' => $code, 'wait_ms' => $waitMs, 'attempt' => $attempt + 1]);
                usleep($waitMs * 1_000);
                // Rebuild timestamp + sign vì timestamp đã cũ — Lazada reject nếu timestamp lệch >5 phút.
                $sysParams['timestamp'] = (string) (now()->getTimestampMs());
                unset($sysParams['sign']);
                $sign = LazadaSigner::sign($this->appSecret(), $apiPath, array_merge($sysParams, $biz));
                $sysParams['sign'] = $sign;
                $attempt++;

                continue;
            }
            $this->fail($apiPath, $json, $resp->status());
        }
    }

    /** @param array<string,scalar|null> $params @return array<string,mixed> */
    public function get(string $apiPath, AuthContext $auth, array $params = []): array
    {
        return $this->call('GET', $apiPath, $auth, $params);
    }

    /** @param array<string,scalar|null> $params @return array<string,mixed> */
    public function post(string $apiPath, AuthContext $auth, array $params = []): array
    {
        return $this->call('POST', $apiPath, $auth, $params);
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

    protected function throttle(AuthContext $auth): void
    {
        $perMin = (int) config('integrations.throttle.lazada', 600);
        if ($perMin <= 0) {
            return;
        }
        $key = "lazada:rate:{$auth->channelAccountId}";
        for ($i = 0; $i < 50; $i++) {
            if (! RateLimiter::tooManyAttempts($key, $perMin)) {
                RateLimiter::hit($key, 60);

                return;
            }
            usleep(200_000);
        }
    }

    /** @param array<string,mixed> $json */
    protected function fail(string $apiPath, array $json, int $httpStatus): never
    {
        $code = (string) ($json['code'] ?? '');
        $message = (string) ($json['message'] ?? 'unknown error');
        $requestId = (string) ($json['request_id'] ?? '');
        // Ghi `request_id` để đối chiếu với Lazada Open Platform console (Support / API log) khi gỡ lỗi
        // "tham số không hợp lệ" / sai sign — Lazada tra theo request_id, không tra theo path.
        Log::warning('lazada.api.error', [
            'path' => $apiPath, 'http' => $httpStatus, 'code' => $code,
            'message_excerpt' => substr($message, 0, 200),
            'request_id' => $requestId,
        ]);

        throw new LazadaApiException("Lazada API error on {$apiPath}: [{$code}] {$message}".($requestId ? " (request_id={$requestId})" : ''), $code, $httpStatus);
    }
}
