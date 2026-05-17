<?php

namespace CMBcoreSeller\Integrations\Carriers\Ghn;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin HTTP client for the GHN public API (https://api.ghn.vn). Carrier credentials
 * (`token`, `shop_id`) come from the tenant's CarrierAccount. See SPEC 0006 §3.
 */
class GhnClient
{
    public function __construct(
        private readonly string $token,
        private readonly ?int $shopId = null,
        private readonly ?string $baseUrl = null,
    ) {}

    private function http(bool $withShop = true): PendingRequest
    {
        $headers = ['Token' => $this->token, 'Content-Type' => 'application/json'];
        if ($withShop && $this->shopId) {
            $headers['ShopId'] = (string) $this->shopId;
        }

        // Spec 2026-05-17 — base URL có thể override qua /admin/settings (carriers.ghn.base_url).
        $configuredBase = (string) system_setting('carriers.ghn.base_url', config('fulfillment.ghn_base_url'));

        return Http::baseUrl(rtrim($this->baseUrl ?: $configuredBase, '/'))
            ->withHeaders($headers)->timeout(20)->acceptJson();
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> the GHN `data` object */
    public function createOrder(array $payload): array
    {
        $res = $this->http()->post('/shiip/public-api/v2/shipping-order/create', $payload);
        $body = $res->json();
        if (! $res->successful() || (int) ($body['code'] ?? 0) !== 200) {
            throw new RuntimeException('GHN tạo vận đơn lỗi: '.($body['message'] ?? $res->status()));
        }

        return (array) ($body['data'] ?? []);
    }

    /** @return array<string,mixed> the GHN order detail `data` (status + log[]) */
    public function orderDetail(string $orderCode): array
    {
        $res = $this->http()->post('/shiip/public-api/v2/shipping-order/detail', ['order_code' => $orderCode]);
        $body = $res->json();
        if (! $res->successful() || (int) ($body['code'] ?? 0) !== 200) {
            throw new RuntimeException('GHN tra cứu vận đơn lỗi: '.($body['message'] ?? $res->status()));
        }

        return (array) ($body['data'] ?? []);
    }

    public function cancel(string $orderCode): void
    {
        $res = $this->http()->post('/shiip/public-api/v2/switch-status/cancel', ['order_codes' => [$orderCode]]);
        $body = $res->json();
        if (! $res->successful() || (int) ($body['code'] ?? 0) !== 200) {
            throw new RuntimeException('GHN huỷ vận đơn lỗi: '.($body['message'] ?? $res->status()));
        }
    }

    /**
     * A2 — Endpoint nhẹ dùng để xác thực credentials (master-data global, không cần shop_id, không thay đổi
     * state). Trả nguyên body để caller check `code`. Lỗi mạng ⇒ ném exception (caller bắt).
     *
     * @return array<string,mixed>
     */
    public function getProvinces(): array
    {
        $res = $this->http(withShop: false)->get('/shiip/public-api/master-data/province');
        $body = $res->json();
        if (! is_array($body)) {
            throw new RuntimeException('GHN response không hợp lệ (status '.$res->status().').');
        }

        return $body;
    }

    /**
     * Liệt kê tất cả shop (gian hàng) gắn với token API hiện tại. Một token GHN có thể có NHIỀU shop —
     * mỗi shop = 1 gian hàng có ShopId riêng. Khi tạo vận đơn phải khai báo header `ShopId` của shop muốn
     * gửi từ. Form thêm tài khoản dùng endpoint này để user chọn shop thay vì gõ ShopId tay.
     *
     * GHN docs: POST /shiip/public-api/v2/shop/all
     *   Headers: Token (KHÔNG cần ShopId — đây là endpoint global theo token)
     *   Body: { offset?: int, limit?: int (≤50), client_phone?: string }
     *   Response data: { shops: [{ _id, name, phone, address, district_id, ward_code, version, ... }], total }
     *
     * @return array<string,mixed>
     */
    public function getShops(int $offset = 0, int $limit = 50, string $clientPhone = ''): array
    {
        $payload = ['offset' => max(0, $offset), 'limit' => min(50, max(1, $limit))];
        if ($clientPhone !== '') {
            $payload['client_phone'] = $clientPhone;
        }
        $res = $this->http(withShop: false)->post('/shiip/public-api/v2/shop/all', $payload);
        $body = $res->json();
        if (! is_array($body)) {
            throw new RuntimeException('GHN response không hợp lệ (status '.$res->status().').');
        }

        return $body;
    }

    /**
     * Districts trong 1 tỉnh — dùng để dựng dropdown cascading khi user thiết lập tài khoản GHN.
     *
     * @return array<string,mixed>
     */
    public function getDistricts(int $provinceId): array
    {
        $res = $this->http(withShop: false)->post('/shiip/public-api/master-data/district', ['province_id' => $provinceId]);
        $body = $res->json();
        if (! is_array($body)) {
            throw new RuntimeException('GHN response không hợp lệ (status '.$res->status().').');
        }

        return $body;
    }

    /**
     * Wards trong 1 quận — dùng để dựng dropdown cascading.
     *
     * @return array<string,mixed>
     */
    public function getWards(int $districtId): array
    {
        $res = $this->http(withShop: false)->post('/shiip/public-api/master-data/ward', ['district_id' => $districtId]);
        $body = $res->json();
        if (! is_array($body)) {
            throw new RuntimeException('GHN response không hợp lệ (status '.$res->status().').');
        }

        return $body;
    }

    /** Generate a print token for one or more order codes (A5/A6 labels). */
    public function genPrintToken(array $orderCodes): string
    {
        $res = $this->http()->post('/shiip/public-api/v2/a5/gen-token', ['order_codes' => array_values($orderCodes)]);
        $body = $res->json();
        $token = $body['data']['token'] ?? null;
        if (! $res->successful() || ! $token) {
            throw new RuntimeException('GHN lấy token in tem lỗi: '.($body['message'] ?? $res->status()));
        }

        return (string) $token;
    }

    /**
     * Fetch the label HTML/PDF bytes for a print token. $size = 'A5' | 'A6' | '80x80'.
     *
     * GHN print endpoints (docs api.ghn.vn):
     *   - A5  : GET /a5/public-api/printA5?token=...
     *   - A6  : GET /a5/public-api/printA6?token=...   ← cùng prefix `/a5/`, KHÔNG phải `/a6/`
     *   - 80x80: GET /a5/public-api/print80x80?token=...
     * Endpoint trả HTML page (in trực tiếp từ browser); Gotenberg renders sang PDF nếu cần.
     */
    public function printLabel(string $printToken, string $size = 'A6'): string
    {
        $size = strtoupper($size);
        $path = match ($size) {
            'A5' => '/a5/public-api/printA5',
            '80X80', '80x80' => '/a5/public-api/print80x80',
            default => '/a5/public-api/printA6',
        };
        $res = $this->http(withShop: false)->get($path, ['token' => $printToken]);
        if (! $res->successful() || $res->body() === '') {
            throw new RuntimeException('GHN tải tem lỗi: '.$res->status());
        }

        return $res->body();
    }
}
