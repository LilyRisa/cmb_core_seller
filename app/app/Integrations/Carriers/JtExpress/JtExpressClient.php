<?php

namespace CMBcoreSeller\Integrations\Carriers\JtExpress;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin HTTP client cho J&T Express Open API (open.jtexpress.vn/apiDoc — xem
 * docs/superpowers/research/2026-07-17-jt-express-api-reference.md). Mọi request: POST
 * application/x-www-form-urlencoded, 3 field cấp ngoài `apiAccount`/`digest`/`timestamp` + `bizContent`
 * (JSON string của business params), ký bằng JtExpressSigner. Response envelope: `{code, msg, data}` —
 * `code !== '1'` ném RuntimeException với message của J&T (xem bảng lỗi trong file tham khảo).
 */
class JtExpressClient
{
    public function __construct(
        private readonly string $apiAccount,
        private readonly string $privateKey,
        private readonly ?string $baseUrl = null,
    ) {}

    private function base(): string
    {
        $configured = (string) config('integrations.jt.base_url', 'https://demoopenapi.jtexpress.vn/webopenplatformapi');

        return rtrim($this->baseUrl ?: $configured, '/');
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->base())
            ->asForm()
            ->timeout((int) config('integrations.jt.http.timeout', 20))
            ->acceptJson();
    }

    /** @return array<string,mixed> Nội dung `data` đã decode. */
    private function request(string $path, array $bizContent): array
    {
        $json = json_encode($bizContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $timestamp = (int) round(microtime(true) * 1000);
        $digest = JtExpressSigner::sign((string) $json, $this->privateKey);

        $res = $this->http()->post($path, [
            'apiAccount' => $this->apiAccount,
            'digest' => $digest,
            'timestamp' => $timestamp,
            'bizContent' => $json,
        ]);

        $body = (array) $res->json();
        if (($body['code'] ?? null) !== '1') {
            throw new RuntimeException((string) ($body['msg'] ?? $this->httpError($res)));
        }

        return (array) ($body['data'] ?? []);
    }

    private function httpError(Response $res): string
    {
        return 'HTTP '.$res->status();
    }

    /** POST /api/order/addOrder — tạo vận đơn. */
    public function addOrder(array $bizContent): array
    {
        return $this->request('/api/order/addOrder', $bizContent);
    }

    /** POST /api/order/cancelOrder — hủy vận đơn. */
    public function cancelOrder(array $bizContent): array
    {
        return $this->request('/api/order/cancelOrder', $bizContent);
    }

    /** POST /api/spmComCost/getComCost — ước tính phí. */
    public function getComCost(array $bizContent): array
    {
        return $this->request('/api/spmComCost/getComCost', $bizContent);
    }

    /** POST /api/order/printOrder — lấy tem base64 (1 đơn/lần). */
    public function printOrder(array $bizContent): array
    {
        return $this->request('/api/order/printOrder', $bizContent);
    }

    /** POST /api/logistics/trace — tra cứu hành trình (tối đa 30 mã/lần). @return list<array<string,mixed>> */
    public function trace(array $bizContent): array
    {
        return $this->request('/api/logistics/trace', $bizContent);
    }
}
