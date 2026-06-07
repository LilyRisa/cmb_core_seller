<?php

namespace CMBcoreSeller\Integrations\Carriers\ViettelPost;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin HTTP client cho Viettel Post Open API (partner.viettelpost.vn — tài liệu partner2.viettelpost.vn).
 * Credentials per tenant CarrierAccount, hỗ trợ 2 cách lấy token (xem `partnerToken`):
 *   - `username` + `password` → POST /v2/user/Login → POST /v2/user/ownerconnect (token dài hạn ~1 năm)
 *   - `token` (secret tạo trên web viettelpost.vn) → POST /v2/user/LoginVTP
 *
 * Partner token cache theo hash credentials, TTL suy từ claim `exp` của JWT (fallback ~330 ngày). Header
 * `Token` gửi kèm mọi API cần xác thực. Envelope VTP: `{status, error, message, data}`.
 *
 * Base URL override: system_setting('carriers.viettelpost.base_url') → config('integrations.viettelpost.base_url').
 * Dev: https://partnerdev.viettelpost.vn.
 */
class ViettelPostClient
{
    /**
     * @param  array<string,mixed>  $credentials  ['username','password'] hoặc ['token']
     */
    public function __construct(
        private readonly array $credentials,
        private readonly ?string $baseUrl = null,
    ) {}

    private function base(): string
    {
        $configured = (string) system_setting(
            'carriers.viettelpost.base_url',
            config('integrations.viettelpost.base_url', 'https://partner.viettelpost.vn')
        );

        return rtrim($this->baseUrl ?: $configured, '/');
    }

    private function http(bool $withToken = true): PendingRequest
    {
        $req = Http::baseUrl($this->base())
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout((int) config('integrations.viettelpost.http.timeout', 20))
            ->acceptJson();
        if ($withToken) {
            $req = $req->withHeaders(['Token' => $this->partnerToken()]);
        }

        return $req;
    }

    /**
     * Trả partner token còn hạn (cache). Đăng nhập lại khi cache miss. `$fresh=true` bỏ qua cache (verify).
     */
    public function partnerToken(bool $fresh = false): string
    {
        $key = 'vtp.token.'.substr(hash('sha256', $this->credentialFingerprint()), 0, 24);
        if (! $fresh) {
            $cached = Cache::get($key);
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        }
        $token = $this->login();
        // TTL theo exp của JWT (trừ 1 ngày phòng lệch giờ), fallback 330 ngày.
        $exp = self::jwtExp($token);
        $ttl = $exp ? max(60, $exp - time() - 86400) : 330 * 86400;
        Cache::put($key, $token, $ttl);

        return $token;
    }

    /** Đăng nhập theo credentials đã cấu hình → trả token. Ném RuntimeException nếu sai. */
    private function login(): string
    {
        $username = (string) ($this->credentials['username'] ?? '');
        $password = (string) ($this->credentials['password'] ?? '');
        $webToken = (string) ($this->credentials['token'] ?? '');

        if ($username !== '' && $password !== '') {
            // B1: token ngắn hạn.
            $short = $this->postData('/v2/user/Login', ['USERNAME' => $username, 'PASSWORD' => $password], withToken: false);
            $shortToken = (string) ($short['token'] ?? '');
            if ($shortToken === '') {
                throw new RuntimeException('VTP Login không trả token.');
            }
            // B2: token dài hạn (ownerconnect cần header Token = token B1).
            $long = Http::baseUrl($this->base())
                ->withHeaders(['Content-Type' => 'application/json', 'Token' => $shortToken])
                ->timeout((int) config('integrations.viettelpost.http.timeout', 20))
                ->acceptJson()
                ->post('/v2/user/ownerconnect', ['USERNAME' => $username, 'PASSWORD' => $password]);
            $longBody = $this->unwrap($long->json(), $long->status());
            $longToken = (string) ($longBody['token'] ?? '');

            return $longToken !== '' ? $longToken : $shortToken;
        }

        if ($webToken !== '') {
            $data = $this->postData('/v2/user/LoginVTP', ['token' => $webToken], withToken: false);
            $token = (string) ($data['token'] ?? '');
            if ($token === '') {
                throw new RuntimeException('VTP LoginVTP không trả token.');
            }

            return $token;
        }

        throw new RuntimeException('Tài khoản VTP chưa có Username/Password hoặc Token.');
    }

    // ---- Danh mục địa danh ------------------------------------------------

    /** @return list<array<string,mixed>> V2 (cũ) — PROVINCE_ID, PROVINCE_NAME. */
    public function listProvince(): array
    {
        return $this->getData('/v2/categories/listProvince', withToken: false);
    }

    /** @return list<array<string,mixed>> V2 — DISTRICT_ID, DISTRICT_NAME, PROVINCE_ID. */
    public function listDistrict(int $provinceId): array
    {
        return $this->getData('/v2/categories/listDistrict', ['provinceId' => $provinceId], withToken: false);
    }

    /** @return list<array<string,mixed>> V2 — WARDS_ID, WARDS_NAME, DISTRICT_ID. */
    public function listWards(int $districtId): array
    {
        return $this->getData('/v2/categories/listWards', ['districtId' => $districtId], withToken: false);
    }

    /** @return list<array<string,mixed>> V3 (đơn vị HC mới) — PROVINCE_ID, PROVINCE_CODE, PROVINCE_NAME. */
    public function listProvinceNew(): array
    {
        return $this->getData('/v3/categories/listProvinceNew', withToken: false);
    }

    /** @return list<array<string,mixed>> V3 — WARDS_ID, WARDS_NAME, DISTRICT_ID (theo provinceId). */
    public function listWardsNew(int $provinceId): array
    {
        return $this->getData('/v3/categories/listWardsNew', ['provinceId' => $provinceId], withToken: false);
    }

    // ---- Dịch vụ / cước ---------------------------------------------------

    /**
     * Danh sách dịch vụ + giá phù hợp tuyến (POST /v2/order/getPriceAll). Trả array dịch vụ.
     *
     * @param  array<string,mixed>  $payload
     * @return list<array<string,mixed>>
     */
    public function getPriceAll(array $payload): array
    {
        $res = $this->http()->post('/v2/order/getPriceAll', $payload);
        $body = $res->json();
        // getPriceAll trả thẳng mảng (không bọc data) theo tài liệu.
        if (is_array($body) && array_is_list($body)) {
            return $body;
        }

        return (array) ($this->unwrap($body, $res->status()) ?: []);
    }

    /**
     * Tính cước 1 dịch vụ (POST /v2/order/getPrice).
     *
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    public function getPrice(array $payload): array
    {
        return (array) $this->postData('/v2/order/getPrice', $payload);
    }

    // ---- Đơn hàng ---------------------------------------------------------

    /**
     * Tạo đơn dùng địa chỉ ID (POST /v2/order/createOrder). Trả data (ORDER_NUMBER, MONEY_TOTAL, ...).
     *
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    public function createOrder(array $payload): array
    {
        return (array) $this->postData('/v2/order/createOrder', $payload);
    }

    /**
     * Cập nhật trạng thái vận đơn (POST /v2/order/UpdateOrder). TYPE=4 hủy đơn.
     *
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    public function updateOrder(array $payload): array
    {
        $res = $this->http()->post('/v2/order/UpdateOrder', $payload);
        $body = $res->json();
        // UpdateOrder trả message thành công + data=null; ném lỗi nếu error=true.
        if (! $res->successful() || (is_array($body) && ($body['error'] ?? false) === true)) {
            throw new RuntimeException('VTP UpdateOrder lỗi: '.$this->errMsg($body, $res->status()));
        }

        return is_array($body) ? $body : [];
    }

    /**
     * Lấy mã in cho danh sách vận đơn (POST /v2/order/printing-code). Trả printCode (field `message`).
     *
     * @param  list<string>  $orderCodes
     */
    public function printingCode(array $orderCodes, int $expiryTimeMs): string
    {
        $res = $this->http()->post('/v2/order/printing-code', [
            'EXPIRY_TIME' => $expiryTimeMs,
            'ORDER_ARRAY' => $orderCodes,
        ]);
        $body = $res->json();
        if (! $res->successful() || (is_array($body) && ($body['error'] ?? false) === true)) {
            throw new RuntimeException('VTP printing-code lỗi: '.$this->errMsg($body, $res->status()));
        }
        $code = (string) ($body['message'] ?? '');
        if ($code === '') {
            throw new RuntimeException('VTP không trả mã in.');
        }

        return $code;
    }

    // ---- Helpers ----------------------------------------------------------

    /**
     * GET + unwrap envelope → trả `data` (list/array).
     *
     * @param  array<string,mixed>  $query
     * @return list<array<string,mixed>>|array<string,mixed>
     */
    private function getData(string $path, array $query = [], bool $withToken = true): array
    {
        $res = $this->http($withToken)->get($path, $query);

        return (array) ($this->unwrap($res->json(), $res->status()) ?: []);
    }

    /**
     * POST + unwrap envelope → trả `data`.
     *
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function postData(string $path, array $payload, bool $withToken = true): array
    {
        $res = $this->http($withToken)->post($path, $payload);

        return (array) ($this->unwrap($res->json(), $res->status()) ?: []);
    }

    /**
     * Kiểm envelope VTP `{status,error,message,data}` → trả `data`. Ném RuntimeException khi error.
     *
     * @param  mixed  $body
     * @return mixed
     */
    private function unwrap($body, int $httpStatus)
    {
        if (! is_array($body)) {
            throw new RuntimeException('VTP response không hợp lệ (HTTP '.$httpStatus.').');
        }
        if (($body['error'] ?? false) === true || ($httpStatus >= 400)) {
            throw new RuntimeException('VTP lỗi: '.$this->errMsg($body, $httpStatus));
        }

        return $body['data'] ?? $body;
    }

    /**
     * @param  mixed  $body
     */
    private function errMsg($body, int $httpStatus): string
    {
        $msg = is_array($body) ? ($body['message'] ?? null) : null;
        if (is_array($msg)) {
            $msg = implode('; ', array_filter(array_map('strval', $msg)));
        }

        return (string) ($msg ?: ('HTTP '.$httpStatus));
    }

    private function credentialFingerprint(): string
    {
        return (string) ($this->credentials['username'] ?? '')
            .'|'.(string) ($this->credentials['token'] ?? '')
            .'|'.$this->base();
    }

    /** Trả timestamp `exp` (unix seconds) từ payload JWT, hoặc null. */
    public static function jwtExp(string $jwt): ?int
    {
        $parts = explode('.', $jwt);
        if (count($parts) < 2) {
            return null;
        }
        $payload = json_decode((string) base64_decode(strtr($parts[1], '-_', '+/'), true), true);

        return is_array($payload) && isset($payload['exp']) ? (int) $payload['exp'] : null;
    }
}
