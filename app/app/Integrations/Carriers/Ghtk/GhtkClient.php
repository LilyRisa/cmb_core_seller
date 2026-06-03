<?php

namespace CMBcoreSeller\Integrations\Carriers\Ghtk;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin HTTP client for the GHTK public API (https://api.ghtk.vn/docs). Credentials per tenant
 * CarrierAccount: `token` (header `Token`) + `client_source` (header `X-Client-Source`, mã shop/partner).
 * Base URL override được qua /admin/settings (carriers.ghtk.base_url). Khác GHN: địa chỉ dùng TÊN trực tiếp,
 * tem trả PDF, có API tính phí. Staging = https://services-staging.ghtklab.com.
 */
class GhtkClient
{
    public function __construct(
        private readonly string $token,
        private readonly ?string $clientSource = null,
        private readonly ?string $baseUrl = null,
    ) {}

    private function http(): PendingRequest
    {
        $headers = ['Token' => $this->token, 'Content-Type' => 'application/json'];
        if ($this->clientSource) {
            $headers['X-Client-Source'] = $this->clientSource;
        }
        $configuredBase = (string) system_setting('carriers.ghtk.base_url', config('fulfillment.ghtk_base_url'));

        return Http::baseUrl(rtrim($this->baseUrl ?: $configuredBase, '/'))
            ->withHeaders($headers)->timeout(20)->acceptJson();
    }

    /**
     * Tạo đơn. POST /services/shipment/order/?ver=1.5 body `{products[], order{}}`.
     *
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed> GHTK `order` object (label, fee, ...)
     */
    public function createOrder(array $payload): array
    {
        $res = $this->http()->post('/services/shipment/order/?ver=1.5', $payload);
        $body = $res->json();
        if (! $res->successful() || ! ($body['success'] ?? false)) {
            throw new RuntimeException('GHTK tạo vận đơn lỗi: '.($body['message'] ?? $res->status()));
        }

        return (array) ($body['order'] ?? []);
    }

    /**
     * Tính phí. GET /services/shipment/fee với query params (weight tính bằng GRAM).
     *
     * @param  array<string,mixed>  $params
     * @return array<string,mixed> GHTK `fee` object (fee, insurance_fee, ...)
     */
    public function fee(array $params): array
    {
        $res = $this->http()->get('/services/shipment/fee', $params);
        $body = $res->json();
        if (! $res->successful() || ! ($body['success'] ?? false)) {
            throw new RuntimeException('GHTK tính phí lỗi: '.($body['message'] ?? $res->status()));
        }

        return (array) ($body['fee'] ?? []);
    }

    /**
     * Tra trạng thái. GET /services/shipment/v2/{label|partner_id}.
     *
     * @return array<string,mixed> GHTK `order` object (status, status_text, ...)
     */
    public function track(string $trackingOrder): array
    {
        $res = $this->http()->get('/services/shipment/v2/'.rawurlencode($trackingOrder));
        $body = $res->json();
        if (! $res->successful() || ! ($body['success'] ?? false)) {
            throw new RuntimeException('GHTK tra cứu vận đơn lỗi: '.($body['message'] ?? $res->status()));
        }

        return (array) ($body['order'] ?? []);
    }

    /** Huỷ đơn. GET /services/shipment/cancel/{label|partner_id} (chỉ status 1/2/12). */
    public function cancel(string $trackingOrder): void
    {
        $res = $this->http()->get('/services/shipment/cancel/'.rawurlencode($trackingOrder));
        $body = $res->json();
        if (! $res->successful() || ! ($body['success'] ?? false)) {
            throw new RuntimeException('GHTK huỷ vận đơn lỗi: '.($body['message'] ?? $res->status()));
        }
    }

    /**
     * Tải tem PDF. GET /services/label/{label}?page_size=A6 — trả PDF nhị phân trực tiếp.
     * $size = 'A5' | 'A6'.
     */
    public function label(string $trackingOrder, string $size = 'A6'): string
    {
        $size = in_array(strtoupper($size), ['A5', 'A6'], true) ? strtoupper($size) : 'A6';
        $res = $this->http()->get('/services/label/'.rawurlencode($trackingOrder), ['page_size' => $size]);
        if (! $res->successful() || $res->body() === '') {
            throw new RuntimeException('GHTK tải tem lỗi: '.$res->status());
        }

        return $res->body();
    }

    /**
     * Liệt kê địa chỉ kho (pick address) gắn với token — dùng xác thực credentials nhẹ.
     *
     * @return array<string,mixed>
     */
    public function listPickAddr(): array
    {
        $res = $this->http()->get('/services/shipment/list_pick_add');
        $body = $res->json();
        if (! is_array($body)) {
            throw new RuntimeException('GHTK response không hợp lệ (status '.$res->status().').');
        }

        return $body;
    }
}
