<?php

namespace CMBcoreSeller\Integrations\Carriers\Ghn;

use CMBcoreSeller\Integrations\Carriers\Support\AbstractCarrierConnector;
use Illuminate\Support\Carbon;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

/**
 * GHN (Giao Hàng Nhanh) carrier connector — real public API. Credentials per tenant
 * CarrierAccount: `token` (API token) + `shop_id`. The shipment payload built by
 * ShipmentService must carry GHN address codes (`to_district_id`, `to_ward_code`) — v1
 * doesn't resolve free-text addresses to GHN codes; that's a follow-up. See SPEC 0006.
 */
class GhnConnector extends AbstractCarrierConnector
{
    public function code(): string
    {
        return 'ghn';
    }

    public function displayName(): string
    {
        return 'Giao Hàng Nhanh (GHN)';
    }

    public function capabilities(): array
    {
        // SPEC 0021 — `awaiting_pickup_flow`: sau khi user "Sẵn sàng bàn giao", shipment vào trạng thái
        //   `awaiting_pickup` (Chờ lấy hàng) thay vì `packed` — đợi shipper GHN tới lấy. Không phải gọi
        //   thêm API GHN (createOrder ở "Chuẩn bị hàng" đã đăng ký package vào hệ thống GHN).
        // `webhook`: GHN có webhook callback trạng thái — bật để CarrierWebhookController nhận & ingest.
        // Carrier khác (GHTK/J&T/manual) muốn dùng luồng này chỉ cần thêm capability tương ứng — core
        // không hard-code 'ghn'.
        return ['createShipment', 'getLabel', 'getTracking', 'cancel', 'awaiting_pickup_flow', 'webhook'];
    }

    /**
     * Validate địa chỉ GHN-required fields trước khi gọi createOrder — fail sớm với message tiếng Việt rõ ràng.
     * Trả null nếu OK, string error nếu thiếu. ShipmentService gọi trước `createShipment`.
     */
    public function validateShipmentPayload(array $shipment): ?string
    {
        $r = (array) ($shipment['recipient'] ?? []);
        if (empty($r['district_id']) && empty($shipment['to_district_id'])) {
            return 'Đơn thiếu mã quận của GHN (district_id) — cập nhật địa chỉ giao hàng theo chuẩn GHN.';
        }
        if (empty($r['ward_code']) && empty($shipment['to_ward_code'])) {
            return 'Đơn thiếu mã phường của GHN (ward_code) — cập nhật địa chỉ giao hàng theo chuẩn GHN.';
        }
        $s = (array) ($shipment['sender'] ?? []);
        if (empty($s['district_id'])) {
            return 'Cài đặt GHN chưa có "Mã quận kho hàng" (from_district_id). Vào Cài đặt → ĐVVC để bổ sung.';
        }
        if (empty($s['name']) || empty($s['phone']) || empty($s['address'])) {
            return 'Cài đặt GHN thiếu tên/SĐT/địa chỉ kho hàng. Vào Cài đặt → ĐVVC để bổ sung.';
        }

        return null;
    }

    private function client(array $account): GhnClient
    {
        $c = $account['credentials'] ?? [];
        $token = (string) ($c['token'] ?? '');
        if ($token === '') {
            throw new RuntimeException('Tài khoản GHN chưa có token.');
        }

        return new GhnClient($token, isset($c['shop_id']) ? (int) $c['shop_id'] : null);
    }

    public function createShipment(array $account, array $shipment): array
    {
        // Fail-fast với message tiếng Việt rõ ràng nếu thiếu dữ liệu bắt buộc của GHN.
        if ($err = $this->validateShipmentPayload($shipment)) {
            throw new RuntimeException($err);
        }
        $r = $shipment['recipient'] ?? [];
        $s = $shipment['sender'] ?? [];
        $p = $shipment['parcel'] ?? [];
        $cod = (int) ($shipment['cod_amount'] ?? 0);
        $payload = array_filter([
            'payment_type_id' => $cod > 0 ? 2 : 1,           // 2 = người nhận trả phí (thường COD), 1 = shop trả phí
            'required_note' => $shipment['required_note'] ?? 'KHONGCHOXEMHANG',
            // Người gửi (kho hàng của shop) — GHN yêu cầu khi shop chưa setup default pickup ở dashboard.
            'from_name' => $s['name'] ?? null,
            'from_phone' => $s['phone'] ?? null,
            'from_address' => $s['address'] ?? null,
            'from_ward_name' => $s['ward_name'] ?? null,
            'from_district_name' => $s['district_name'] ?? null,
            'from_province_name' => $s['province_name'] ?? null,
            // Người nhận (buyer).
            'to_name' => $r['name'] ?? null,
            'to_phone' => $r['phone'] ?? null,
            'to_address' => $r['address'] ?? null,
            'to_ward_code' => $r['ward_code'] ?? ($shipment['to_ward_code'] ?? null),
            'to_district_id' => isset($r['district_id']) ? (int) $r['district_id'] : ($shipment['to_district_id'] ?? null),
            'weight' => (int) ($p['weight_grams'] ?? 500),
            'length' => (int) ($p['length_cm'] ?? 10),
            'width' => (int) ($p['width_cm'] ?? 10),
            'height' => (int) ($p['height_cm'] ?? 10),
            'service_type_id' => isset($shipment['service']) ? (int) $shipment['service'] : 2,
            'cod_amount' => $cod,
            'content' => $shipment['content'] ?? null,
            'client_order_code' => $shipment['client_order_code'] ?? null,
            'items' => $shipment['items'] ?? null,
        ], fn ($v) => $v !== null);

        $data = $this->client($account)->createOrder($payload);
        $orderCode = (string) ($data['order_code'] ?? '');
        if ($orderCode === '') {
            throw new RuntimeException('GHN không trả về mã vận đơn.');
        }

        return [
            'tracking_no' => $orderCode,
            'carrier' => 'ghn',
            'status' => 'created',
            'fee' => (int) ($data['total_fee'] ?? 0),
            'raw' => $data,
        ];
    }

    /**
     * Parse GHN webhook push (callback URL cấu hình ở GHN dashboard hoặc qua API setShopWebhook).
     * GHN gửi JSON body: `{ "CODStatusID":?, "OrderCode":"...", "Status":"picking|picked|delivering|...",
     *   "Time":"YYYY-MM-DDTHH:MM:SS+07:00", ... }`. Verify token = header `Token` (so với credential.token).
     * Controller xử lý verify; ở đây chỉ parse + chuẩn hoá.
     *
     * @return array{tracking_no:?string, raw_status:?string, status:?string, occurred_at:string, raw:array}
     */
    public function parseWebhook(Request $request): array
    {
        $body = (array) ($request->toArray() ?: $request->getPayload()->all());
        $tracking = (string) ($body['OrderCode'] ?? $body['order_code'] ?? '');
        $rawStatus = (string) ($body['Status'] ?? $body['status'] ?? '');
        $occurredAt = $this->parseTime($body['Time'] ?? $body['time'] ?? null);

        return [
            'tracking_no' => $tracking !== '' ? $tracking : null,
            'raw_status' => $rawStatus !== '' ? $rawStatus : null,
            'status' => $rawStatus !== '' ? GhnStatusMap::toShipmentStatus($rawStatus) : null,
            'occurred_at' => $occurredAt,
            'raw' => $body,
        ];
    }

    public function getLabel(array $account, string $trackingNo, string $format = 'A6'): array
    {
        $client = $this->client($account);
        $token = $client->genPrintToken([$trackingNo]);
        $bytes = $client->printLabel($token, in_array(strtoupper($format), ['A5', 'A6'], true) ? strtoupper($format) : 'A6');

        return ['filename' => "ghn-{$trackingNo}.pdf", 'mime' => 'application/pdf', 'bytes' => $bytes];
    }

    public function getTracking(array $account, string $trackingNo): array
    {
        $data = $this->client($account)->orderDetail($trackingNo);
        $events = [];
        foreach ((array) ($data['log'] ?? []) as $log) {
            $code = (string) ($log['status'] ?? '');
            if ($code === '') {
                continue;
            }
            $events[] = [
                'code' => $code,
                'description' => $log['status'] ?? null,
                'status' => GhnStatusMap::toShipmentStatus($code),
                'occurred_at' => $this->parseTime($log['updated_date'] ?? null),
                'raw' => $log,
            ];
        }

        return [
            'status' => GhnStatusMap::toShipmentStatus($data['status'] ?? null),
            'events' => $events,
            'raw' => $data,
        ];
    }

    public function cancel(array $account, string $trackingNo): void
    {
        $this->client($account)->cancel($trackingNo);
    }

    /**
     * A2 — Kiểm tra credentials GHN còn dùng được không. Gọi `/master-data/province` (endpoint nhẹ, không
     * thay đổi state, chỉ cần Token header đúng). GHN trả `code=200` ⇒ token hợp lệ; `code=401`/`code=400`
     * với message cụ thể ⇒ token sai/revoked.
     *
     * GHN dùng API key tĩnh (không expiry) — nếu user revoke trong dashboard GHN, mọi call sẽ trả 401.
     * Không có `expires_at` thật, nên trả null (UI hiện "Không xác định").
     */
    public function verifyCredentials(array $account): array
    {
        $c = $account['credentials'] ?? [];
        $token = (string) ($c['token'] ?? '');
        if ($token === '') {
            return ['ok' => false, 'message' => 'Chưa nhập API Token cho GHN.', 'error_code' => 'invalid_credentials', 'expires_at' => null];
        }
        try {
            $client = new GhnClient($token, isset($c['shop_id']) ? (int) $c['shop_id'] : null);
            $body = $client->getProvinces();
            $code = (int) ($body['code'] ?? 0);
            if ($code === 200) {
                return ['ok' => true, 'message' => 'Kết nối GHN OK.', 'expires_at' => null];
            }
            $msg = (string) ($body['message'] ?? 'GHN trả lỗi không rõ.');
            $err = stripos($msg, 'token') !== false || $code === 401 ? 'invalid_credentials' : 'rate_limit';

            return ['ok' => false, 'message' => 'GHN từ chối: '.$msg, 'error_code' => $err, 'expires_at' => null];
        } catch (\Throwable $e) {
            $m = $e->getMessage();
            $isAuth = stripos($m, 'token') !== false || stripos($m, '401') !== false || stripos($m, 'unauth') !== false;

            return [
                'ok' => false,
                'message' => $isAuth ? 'Token GHN không hợp lệ hoặc đã bị thu hồi.' : 'Lỗi kết nối GHN: '.$m,
                'error_code' => $isAuth ? 'invalid_credentials' : 'network',
                'expires_at' => null,
            ];
        }
    }

    private function parseTime(?string $v): string
    {
        try {
            return $v ? Carbon::parse($v)->toIso8601String() : now()->toIso8601String();
        } catch (\Throwable) {
            return now()->toIso8601String();
        }
    }
}
