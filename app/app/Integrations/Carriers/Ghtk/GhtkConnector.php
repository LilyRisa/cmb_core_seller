<?php

namespace CMBcoreSeller\Integrations\Carriers\Ghtk;

use CMBcoreSeller\Integrations\Carriers\Support\AbstractCarrierConnector;
use Illuminate\Support\Carbon;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

/**
 * GHTK (Giao Hàng Tiết Kiệm) carrier connector — real public API (api.ghtk.vn/docs).
 * Credentials per tenant CarrierAccount: `token` + `client_source` (mã shop/partner).
 *
 * Khác GHN: GHTK nhận địa chỉ bằng TÊN tỉnh/huyện/xã trực tiếp ⇒ KHÔNG cần address resolver.
 * COD = `pick_money`; tem trả PDF trực tiếp; có API tính phí (`quote`). Webhook gửi `status_id`
 * (số) + `label_id`/`partner_id`, KHÔNG có Token header ⇒ auth theo label_id (xem webhookAuthMode).
 */
class GhtkConnector extends AbstractCarrierConnector
{
    public function code(): string
    {
        return 'ghtk';
    }

    public function displayName(): string
    {
        return 'Giao Hàng Tiết Kiệm (GHTK)';
    }

    public function capabilities(): array
    {
        return ['createShipment', 'getLabel', 'getTracking', 'cancel', 'quote', 'awaiting_pickup_flow', 'webhook'];
    }

    /**
     * Webhook GHTK không gửi secret/Token header — không thể verify như GHN (token header).
     * 'tracking_lookup': CarrierWebhookController resolve tenant theo label_id (tracking_no) đã lưu,
     * và verify nhẹ header `X-Client-Source` nếu có. Core không hard-code 'ghtk' — chỉ đọc mode này.
     */
    public function webhookAuthMode(): string
    {
        return 'tracking_lookup';
    }

    /** Trả null nếu OK, string error (tiếng Việt) nếu thiếu field bắt buộc của GHTK. */
    private function validateShipmentPayload(array $shipment): ?string
    {
        $r = (array) ($shipment['recipient'] ?? []);
        $s = (array) ($shipment['sender'] ?? []);
        $miss = [];
        foreach (['name' => 'tên', 'phone' => 'SĐT', 'address' => 'địa chỉ', 'province' => 'tỉnh/thành', 'district' => 'quận/huyện'] as $k => $lbl) {
            if (empty($r[$k])) {
                $miss[] = 'người nhận thiếu '.$lbl;
            }
        }
        foreach (['name' => 'tên', 'phone' => 'SĐT', 'address' => 'địa chỉ', 'province_name' => 'tỉnh/thành', 'district_name' => 'quận/huyện'] as $k => $lbl) {
            if (empty($s[$k])) {
                $miss[] = 'kho gửi thiếu '.$lbl;
            }
        }

        return $miss === [] ? null : 'Thiếu thông tin GHTK: '.implode('; ', $miss).'.';
    }

    private function client(array $account): GhtkClient
    {
        $c = $account['credentials'] ?? [];
        $token = (string) ($c['token'] ?? '');
        if ($token === '') {
            throw new RuntimeException('Tài khoản GHTK chưa có token.');
        }

        return new GhtkClient($token, isset($c['client_source']) ? (string) $c['client_source'] : null);
    }

    public function createShipment(array $account, array $shipment): array
    {
        if ($err = $this->validateShipmentPayload($shipment)) {
            throw new RuntimeException($err);
        }
        $r = (array) ($shipment['recipient'] ?? []);
        $s = (array) ($shipment['sender'] ?? []);
        $p = (array) ($shipment['parcel'] ?? []);

        // products[]: GHTK dùng weight KG (double). buildCreatePayload trả weight theo gram ⇒ /1000.
        $products = array_values(array_map(fn ($it) => array_filter([
            'name' => (string) ($it['name'] ?? 'Hàng'),
            'weight' => round(max(1, (int) ($it['weight'] ?? 200)) / 1000, 3),
            'quantity' => max(1, (int) ($it['quantity'] ?? 1)),
            'product_code' => $it['code'] ?? null,
            'price' => isset($it['price']) ? max(0, (int) $it['price']) : null,
        ], fn ($v) => $v !== null && $v !== ''), (array) ($shipment['items'] ?? [])));
        if ($products === []) {
            $products = [['name' => (string) ($shipment['content'] ?? 'Hàng'), 'weight' => round(max(1, (int) ($p['weight_grams'] ?? 500)) / 1000, 3), 'quantity' => 1]];
        }

        $order = array_filter([
            'id' => $shipment['client_order_code'] ?? null,
            'pick_name' => $s['name'] ?? null,
            'pick_tel' => $s['phone'] ?? null,
            'pick_address' => $s['address'] ?? null,
            'pick_province' => $s['province_name'] ?? null,
            'pick_district' => $s['district_name'] ?? null,
            'pick_ward' => $s['ward_name'] ?? null,
            'name' => $r['name'] ?? null,
            'tel' => $r['phone'] ?? null,
            'address' => $r['address'] ?? null,
            'province' => $r['province'] ?? null,
            'district' => $r['district'] ?? null,
            'ward' => $r['ward'] ?? null,
            'hamlet' => 'Khác',
            'pick_money' => (int) ($shipment['cod_amount'] ?? 0),   // tiền thu hộ (COD)
            'is_freeship' => 0,
            'value' => isset($shipment['insurance_value']) ? max(0, (int) $shipment['insurance_value']) : 0,
            'transport' => 'road',
            'weight_option' => 'gram',
            'total_weight' => (int) ($p['weight_grams'] ?? 500),
            'note' => $this->trimNote($shipment['note'] ?? ($shipment['content'] ?? null)),
        ], fn ($v) => $v !== null && $v !== '');

        $data = $this->client($account)->createOrder(['products' => $products, 'order' => $order]);
        $label = (string) ($data['label'] ?? '');
        if ($label === '') {
            throw new RuntimeException('GHTK không trả về mã vận đơn.');
        }

        return [
            'tracking_no' => $label,
            'carrier' => 'ghtk',
            'status' => 'created',
            'fee' => (int) ($data['fee'] ?? 0),
            'raw' => $data,
        ];
    }

    /**
     * Tính phí gợi ý. $request: { weight_grams, value?, recipient:{province,district,ward?,address?} }.
     * Sender lấy từ account.meta.from_address. Trả list 1 quote (fee + insurance_fee).
     */
    public function quote(array $account, array $request): array
    {
        $s = (array) ($account['meta']['from_address'] ?? []);
        $r = (array) ($request['recipient'] ?? $request);
        $params = array_filter([
            'pick_province' => $s['province_name'] ?? null,
            'pick_district' => $s['district_name'] ?? null,
            'pick_ward' => $s['ward_name'] ?? null,
            'pick_address' => $s['address'] ?? null,
            'province' => $r['province'] ?? null,
            'district' => $r['district'] ?? null,
            'ward' => $r['ward'] ?? null,
            'address' => $r['address'] ?? null,
            'weight' => (int) ($request['weight_grams'] ?? $request['weight'] ?? 0),  // GRAM
            'value' => isset($request['value']) ? max(0, (int) $request['value']) : null,
            'transport' => 'road',
        ], fn ($v) => $v !== null && $v !== '');

        $fee = $this->client($account)->fee($params);

        return [[
            'carrier' => 'ghtk',
            'fee' => (int) ($fee['fee'] ?? 0),
            'insurance_fee' => (int) ($fee['insurance_fee'] ?? 0),
            'name' => $fee['name'] ?? null,
        ]];
    }

    public function getLabel(array $account, string $trackingNo, string $format = 'A6'): array
    {
        $bytes = $this->client($account)->label($trackingNo, $format);

        // GHTK trả PDF trực tiếp ⇒ mime application/pdf, ShipmentService lưu thẳng (không qua Gotenberg).
        return ['filename' => "ghtk-{$trackingNo}.pdf", 'mime' => 'application/pdf', 'bytes' => $bytes];
    }

    public function getTracking(array $account, string $trackingNo): array
    {
        $data = $this->client($account)->track($trackingNo);
        $statusId = $data['status'] ?? $data['status_id'] ?? null;

        return [
            'status' => GhtkStatusMap::toShipmentStatus($statusId),
            'events' => [],
            'raw' => $data,
        ];
    }

    public function cancel(array $account, string $trackingNo): void
    {
        $this->client($account)->cancel($trackingNo);
    }

    /**
     * Parse webhook GHTK. Body: `{ partner_id, label_id, status_id, action_time, reason, weight, fee, pick_money }`.
     * Verify do CarrierWebhookController (tracking_lookup + X-Client-Source). Ở đây chỉ chuẩn hoá.
     *
     * @return array{tracking_no:?string, raw_status:?string, status:?string, occurred_at:string, raw:array}
     */
    public function parseWebhook(Request $request): array
    {
        $body = (array) ($request->toArray() ?: $request->getPayload()->all());
        $tracking = (string) ($body['label_id'] ?? $body['partner_id'] ?? '');
        $statusId = $body['status_id'] ?? null;

        return [
            'tracking_no' => $tracking !== '' ? $tracking : null,
            'raw_status' => $statusId !== null ? (string) $statusId : null,
            'status' => GhtkStatusMap::toShipmentStatus($statusId),
            'occurred_at' => $this->parseTime($body['action_time'] ?? null),
            'raw' => $body,
        ];
    }

    public function verifyCredentials(array $account): array
    {
        $c = $account['credentials'] ?? [];
        $token = (string) ($c['token'] ?? '');
        if ($token === '') {
            return ['ok' => false, 'message' => 'Chưa nhập Token cho GHTK.', 'error_code' => 'invalid_credentials', 'expires_at' => null];
        }
        try {
            $client = new GhtkClient($token, isset($c['client_source']) ? (string) $c['client_source'] : null);
            $body = $client->listPickAddr();
            if ($body['success'] ?? false) {
                return ['ok' => true, 'message' => 'Kết nối GHTK OK.', 'expires_at' => null];
            }
            $msg = (string) ($body['message'] ?? 'GHTK trả lỗi không rõ.');

            return ['ok' => false, 'message' => 'GHTK từ chối: '.$msg, 'error_code' => 'invalid_credentials', 'expires_at' => null];
        } catch (\Throwable $e) {
            $m = $e->getMessage();
            $isAuth = stripos($m, 'token') !== false || stripos($m, '401') !== false;

            return [
                'ok' => false,
                'message' => $isAuth ? 'Token GHTK không hợp lệ hoặc đã bị thu hồi.' : 'Lỗi kết nối GHTK: '.$m,
                'error_code' => $isAuth ? 'invalid_credentials' : 'network',
                'expires_at' => null,
            ];
        }
    }

    private function trimNote(?string $note): ?string
    {
        if ($note === null) {
            return null;
        }
        $note = trim($note);

        return $note === '' ? null : mb_substr($note, 0, 120);
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
