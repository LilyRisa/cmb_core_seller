<?php

namespace CMBcoreSeller\Integrations\Carriers\ViettelPost;

use CMBcoreSeller\Integrations\Carriers\Support\AbstractCarrierConnector;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Viettel Post (VTP) carrier connector — Open API mới (partner.viettelpost.vn). Credentials per tenant
 * CarrierAccount: `{username,password}` HOẶC `{token}` (secret web VTP), tuỳ chọn `webhook_secret`.
 *
 * Địa chỉ dùng ID chuẩn hoá (giống GHN): `ViettelPostAddressResolver` map TÊN người nhận → ID VTP, tạo đơn
 * bằng /v2/order/createOrder. Kho gửi lấy từ `account.meta.from_address` (đã có province_id/ward_id).
 *
 * Giới hạn: Open API set này KHÔNG có endpoint pull trạng thái ⇒ không hỗ trợ getTracking; trạng thái dựa
 * webhook (xem ViettelPostStatusMap + CarrierWebhookController, mode tracking_lookup + verify body TOKEN).
 * Sửa đơn KHÔNG đẩy ngược lên VTP (yêu cầu nghiệp vụ) — chỉ cancel tác động thật. Xem SPEC 0034.
 */
class ViettelPostConnector extends AbstractCarrierConnector
{
    public function code(): string
    {
        return 'viettelpost';
    }

    public function displayName(): string
    {
        return 'Viettel Post (VTP)';
    }

    public function capabilities(): array
    {
        // KHÔNG 'getTracking' (không có API pull). 'webhook' nhận push trạng thái; 'awaiting_pickup_flow'
        // để "Sẵn sàng bàn giao" đưa shipment vào trạng thái chờ VTP tới lấy (như GHN/GHTK).
        // 'failed_delivery_collect' — hỗ trợ thu tiền nếu khách không nhận hàng qua dịch vụ XMG (EXTRA_MONEY).
        return ['createShipment', 'getLabel', 'cancel', 'quote', 'awaiting_pickup_flow', 'webhook', 'failed_delivery_collect'];
    }

    /** VTP gửi secret trong body.TOKEN + ORDER_NUMBER ⇒ resolve theo tracking; verify secret ở controller. */
    public function webhookAuthMode(): string
    {
        return 'tracking_lookup';
    }

    private function client(array $account): ViettelPostClient
    {
        $c = (array) ($account['credentials'] ?? []);
        $hasUserPass = ($c['username'] ?? '') !== '' && ($c['password'] ?? '') !== '';
        if (! $hasUserPass && ($c['token'] ?? '') === '') {
            throw new RuntimeException('Tài khoản VTP chưa có Username/Password hoặc Token.');
        }

        return new ViettelPostClient($c);
    }

    /** Trả null nếu OK, string lỗi (tiếng Việt) nếu thiếu dữ liệu bắt buộc của VTP. */
    private function validateShipmentPayload(array $shipment): ?string
    {
        $r = (array) ($shipment['recipient'] ?? []);
        $s = (array) ($shipment['sender'] ?? []);
        if (empty($r['name']) || empty($r['phone']) || empty($r['address'])) {
            return 'Đơn thiếu tên/SĐT/địa chỉ người nhận.';
        }
        if (empty($r['province_id']) || empty($r['ward_id'])) {
            return 'Không nhận diện được Tỉnh/Phường của người nhận trên hệ thống Viettel Post — kiểm tra lại địa chỉ giao hàng.';
        }
        if (empty($s['name']) || empty($s['phone']) || empty($s['address'])) {
            return 'Cài đặt VTP thiếu tên/SĐT/địa chỉ kho gửi. Vào Cài đặt → ĐVVC để bổ sung.';
        }
        if (empty($s['province_id']) || empty($s['ward_id'])) {
            return 'Cài đặt VTP thiếu Tỉnh/Phường kho gửi (mã VTP). Vào Cài đặt → ĐVVC chọn lại địa chỉ kho.';
        }

        return null;
    }

    /**
     * Resolve TÊN tỉnh/quận/phường người nhận → ID VTP nếu chưa có. Idempotent. Lỗi network không throw.
     *
     * @param  array<string,mixed>  $account
     * @param  array<string,mixed>  $shipment
     * @return array<string,mixed>
     */
    private function autoResolveRecipient(array $account, array $shipment): array
    {
        $r = (array) ($shipment['recipient'] ?? []);
        if (! empty($r['province_id']) && ! empty($r['ward_id'])) {
            return $shipment;
        }
        try {
            $res = (new ViettelPostAddressResolver($this->client($account)))->resolve([
                'province' => (string) ($r['province'] ?? ''),
                'district' => (string) ($r['district'] ?? ''),
                'ward' => (string) ($r['ward'] ?? ''),
            ]);
            $shipment['recipient']['province_id'] = $res['province_id'];
            $shipment['recipient']['district_id'] = $res['district_id'];
            $shipment['recipient']['ward_id'] = $res['ward_id'];
        } catch (\Throwable) {
            // để validateShipmentPayload báo lỗi rõ ràng.
        }

        return $shipment;
    }

    public function createShipment(array $account, array $shipment): array
    {
        $shipment = $this->autoResolveRecipient($account, $shipment);
        if ($err = $this->validateShipmentPayload($shipment)) {
            throw new RuntimeException($err);
        }
        $r = (array) $shipment['recipient'];
        $s = (array) $shipment['sender'];
        $p = (array) ($shipment['parcel'] ?? []);
        $cod = (int) ($shipment['cod_amount'] ?? 0);
        $weight = (int) ($p['weight_grams'] ?? 500);

        $items = (array) ($shipment['items'] ?? []);
        $listItem = array_values(array_map(fn ($it) => array_filter([
            'PRODUCT_NAME' => (string) ($it['name'] ?? 'Hàng'),
            'PRODUCT_QUANTITY' => max(1, (int) ($it['quantity'] ?? 1)),
            'PRODUCT_PRICE' => isset($it['price']) ? max(0, (int) $it['price']) : null,
            'PRODUCT_WEIGHT' => max(1, (int) ($it['weight'] ?? $weight)),
        ], fn ($v) => $v !== null), $items));
        $totalQty = array_sum(array_map(fn ($it) => max(1, (int) ($it['quantity'] ?? 1)), $items)) ?: 1;
        $totalValue = array_sum(array_map(fn ($it) => max(0, (int) ($it['price'] ?? 0)) * max(1, (int) ($it['quantity'] ?? 1)), $items));

        $service = (string) ($shipment['service'] ?? $account['default_service'] ?? '');
        if ($service === '') {
            $service = $this->pickService($account, $s, $r, $weight);
        }

        $payload = $this->buildVtpPayload($shipment, $cod, $listItem, $totalQty, $totalValue, $weight, $service);

        $data = $this->client($account)->createOrder($payload);
        $orderNumber = (string) ($data['ORDER_NUMBER'] ?? '');
        if ($orderNumber === '') {
            throw new RuntimeException('Viettel Post không trả về mã vận đơn.');
        }

        return [
            'tracking_no' => $orderNumber,
            'carrier' => 'viettelpost',
            'status' => 'created',
            'fee' => (int) ($data['MONEY_TOTAL'] ?? 0),
            'raw' => $data,
        ];
    }

    /**
     * Dựng payload /v2/order/createOrder từ shipment chuẩn hoá. `$listItem` là danh sách sản phẩm (PRODUCT_NAME,...),
     * KHÔNG liên quan tới dịch vụ mở rộng XMG bên dưới (field payload khác nhau).
     *
     * @param  array<int,array<string,mixed>>  $listItem
     */
    protected function buildVtpPayload(array $shipment, int $cod, array $listItem, int $totalQty, int $totalValue, int $weight, string $service): array
    {
        $r = (array) $shipment['recipient'];
        $s = (array) $shipment['sender'];
        $p = (array) ($shipment['parcel'] ?? []);

        $payload = array_filter([
            'ORDER_NUMBER' => (string) ($shipment['client_order_code'] ?? ''),
            'CHECK_UNIQUE' => true,
            // Người gửi (kho).
            'SENDER_FULLNAME' => $s['name'] ?? null,
            'SENDER_PHONE' => $s['phone'] ?? null,
            'SENDER_ADDRESS' => $s['address'] ?? null,
            'SENDER_PROVINCE' => (int) $s['province_id'],
            'SENDER_DISTRICT' => isset($s['district_id']) ? (int) $s['district_id'] : null,
            'SENDER_WARD' => (int) $s['ward_id'],
            // Người nhận.
            'RECEIVER_FULLNAME' => $r['name'] ?? null,
            'RECEIVER_PHONE' => $r['phone'] ?? null,
            'RECEIVER_ADDRESS' => $r['address'] ?? null,
            'RECEIVER_PROVINCE' => (int) $r['province_id'],
            'RECEIVER_DISTRICT' => isset($r['district_id']) ? (int) $r['district_id'] : null,
            'RECEIVER_WARD' => (int) $r['ward_id'],
            // Hàng hoá.
            'PRODUCT_NAME' => (string) ($shipment['content'] ?? 'Đơn hàng'),
            'PRODUCT_QUANTITY' => (int) $totalQty,
            'PRODUCT_PRICE' => (int) max($totalValue, $cod),
            'PRODUCT_WEIGHT' => $weight,
            'PRODUCT_LENGTH' => (int) ($p['length_cm'] ?? 0),
            'PRODUCT_WIDTH' => (int) ($p['width_cm'] ?? 0),
            'PRODUCT_HEIGHT' => (int) ($p['height_cm'] ?? 0),
            'PRODUCT_TYPE' => 'HH',
            // Phí ship là nội bộ (đã gộp vào COD đẩy ĐVVC) — shop trả cước. ORDER_PAYMENT (docs
            // partner.viettelpost.vn): 1=không thu hộ, 2=thu COD+cước, 3=thu COD KHÔNG thu cước (shop trả
            // cước), 4=thu cước không thu COD. ⇒ có COD dùng 3 (khớp GHN payment_type_id=1), không COD dùng 1.
            'ORDER_PAYMENT' => $cod > 0 ? 3 : 1,
            'ORDER_SERVICE' => $service,
            'ORDER_SERVICE_ADD' => null,
            'MONEY_COLLECTION' => $cod,
            'ORDER_NOTE' => $this->buildOrderNote($shipment),
            'LIST_ITEM' => $listItem !== [] ? $listItem : null,
        ], fn ($v) => $v !== null);

        // Thu tiền hàng khi khách không nhận (failed_delivery_collect) — dịch vụ "Xem hàng, thu tiền" (XMG).
        // ⚠️ Mã dịch vụ XMG + field EXTRA_MONEY/LIST_ITEM_EXTRA CHƯA verify với VTP sandbox (spec §11) —
        // đây là nơi DUY NHẤT cần sửa nếu tên field/mã dịch vụ thực tế khác.
        $failed = (int) ($shipment['failed_collect_amount'] ?? 0);
        if ($failed > 0) {
            // MONEY_TOTAL (cước thực) chỉ có trong RESPONSE tạo đơn, chưa tồn tại lúc build payload này.
            // Nếu có ước tính cước (`$shipment['fee']`, vd từ quote() trước đó) thì clamp ≤ 2× ước tính;
            // nếu không có, gửi nguyên failed_collect_amount — VTP tự áp trần ≤2× cước thực ở phía server.
            $feeEstimate = (int) ($shipment['fee'] ?? 0);
            $cap = $feeEstimate > 0 ? 2 * $feeEstimate : 0;
            $payload['EXTRA_MONEY'] = $cap > 0 ? min($failed, $cap) : $failed;
            $payload['LIST_ITEM_EXTRA'] = ['XMG'];
        }

        return $payload;
    }

    /** Chọn dịch vụ chính rẻ nhất khả dụng khi user chưa đặt default_service. Lỗi → ném để báo rõ. */
    private function pickService(array $account, array $sender, array $recipient, int $weight): string
    {
        $list = $this->client($account)->getPriceAll([
            'SENDER_PROVINCE' => (int) $sender['province_id'],
            'SENDER_DISTRICT' => isset($sender['district_id']) ? (int) $sender['district_id'] : null,
            'SENDER_WARD' => (int) $sender['ward_id'],
            'RECEIVER_PROVINCE' => (int) $recipient['province_id'],
            'RECEIVER_DISTRICT' => isset($recipient['district_id']) ? (int) $recipient['district_id'] : null,
            'RECEIVER_WARD' => (int) $recipient['ward_id'],
            'PRODUCT_TYPE' => 'HH',
            'PRODUCT_WEIGHT' => $weight,
            'TYPE' => 1,
        ]);
        $code = (string) ($list[0]['MA_DV_CHINH'] ?? '');
        if ($code === '') {
            throw new RuntimeException('Không tìm được dịch vụ Viettel Post phù hợp cho tuyến này — cấu hình "Mã dịch vụ mặc định" cho tài khoản.');
        }

        return $code;
    }

    public function quote(array $account, array $request): array
    {
        $s = (array) ($account['meta']['from_address'] ?? []);
        $resolved = (new ViettelPostAddressResolver($this->client($account)))->resolve(
            (array) ($request['recipient'] ?? $request)
        );
        if (empty($s['province_id']) || empty($s['ward_id']) || empty($resolved['province_id']) || empty($resolved['ward_id'])) {
            return [];
        }
        $list = $this->client($account)->getPriceAll([
            'SENDER_PROVINCE' => (int) $s['province_id'],
            'SENDER_DISTRICT' => isset($s['district_id']) ? (int) $s['district_id'] : null,
            'SENDER_WARD' => (int) $s['ward_id'],
            'RECEIVER_PROVINCE' => (int) $resolved['province_id'],
            'RECEIVER_DISTRICT' => $resolved['district_id'],
            'RECEIVER_WARD' => (int) $resolved['ward_id'],
            'PRODUCT_TYPE' => 'HH',
            'PRODUCT_WEIGHT' => (int) ($request['weight_grams'] ?? $request['weight'] ?? 0),
            'TYPE' => 1,
        ]);

        return array_map(fn ($svc) => [
            'carrier' => 'viettelpost',
            'service_code' => (string) ($svc['MA_DV_CHINH'] ?? ''),
            'name' => $svc['TEN_DICHVU'] ?? null,
            'fee' => (int) ($svc['GIA_CUOC'] ?? 0),
            'eta' => $svc['THOI_GIAN'] ?? null,
        ], $list);
    }

    public function getLabel(array $account, string $trackingNo, string $format = 'A6'): array
    {
        $client = $this->client($account);
        // Mã in hết hạn sau 7 ngày (milliseconds).
        $code = $client->printingCode([$trackingNo], (time() + 7 * 86400) * 1000);

        $type = strtoupper($format) === 'A5' ? 1 : 2;   // 1 = A5, 2 = A6 (tài liệu sync-end-status)
        $base = rtrim((string) config('integrations.viettelpost.print_base_url', 'https://digitalize.viettelpost.vn'), '/');
        $res = Http::timeout(30)->get($base.'/DigitalizePrint/report.do', [
            'type' => $type, 'bill' => $code, 'showPostage' => 1,
        ]);
        $bytes = $res->body();
        if (! $res->successful() || $bytes === '') {
            throw new RuntimeException('Viettel Post tải nhãn in lỗi: HTTP '.$res->status());
        }
        $isPdf = stripos((string) $res->header('Content-Type'), 'pdf') !== false || str_starts_with($bytes, '%PDF');

        return [
            'filename' => 'viettelpost-'.$trackingNo.($isPdf ? '.pdf' : '.html'),
            'mime' => $isPdf ? 'application/pdf' : 'text/html',
            'bytes' => $bytes,
        ];
    }

    public function cancel(array $account, string $trackingNo): void
    {
        // TYPE=4: hủy đơn (chỉ với đơn chưa nhận thành công ORDER_STATUS < 200).
        $this->client($account)->updateOrder([
            'TYPE' => 4,
            'ORDER_NUMBER' => $trackingNo,
            'NOTE' => 'Hủy đơn từ CMBcore Seller',
        ]);
    }

    /**
     * Parse webhook VTP. Body: `{ DATA:{ORDER_NUMBER,ORDER_STATUS,STATUS_NAME,ORDER_STATUSDATE,...}, TOKEN:<secret> }`.
     * `secret` = body.TOKEN (đối tác cấu hình) — controller so với credentials.webhook_secret.
     *
     * @return array{tracking_no:?string, raw_status:?string, status:?string, occurred_at:string, cod_collected:?int, failed_collect_collected:?int, return_fee:?int, secret:?string, raw:array}
     */
    public function parseWebhook(Request $request): array
    {
        $body = (array) ($request->toArray() ?: $request->getPayload()->all());
        $data = (array) ($body['DATA'] ?? []);
        $tracking = (string) ($data['ORDER_NUMBER'] ?? $data['ORDER_REFERENCE'] ?? '');
        $statusCode = $data['ORDER_STATUS'] ?? null;
        $secret = isset($body['TOKEN']) ? (string) $body['TOKEN'] : null;

        // ⚠️ Task 10: MONEY_COLLECTION/EXTRA_MONEY trong DATA CHƯA verify với payload webhook VTP thật
        // (2 field này chỉ xác nhận là tên field REQUEST tạo đơn — xem addOrder()); return_fee để null
        // vì chưa xác định field. Đây là nơi DUY NHẤT cần sửa nếu tên field webhook thực tế khác.
        return [
            'tracking_no' => $tracking !== '' ? $tracking : null,
            'raw_status' => $statusCode !== null ? (string) $statusCode : null,
            'status' => ViettelPostStatusMap::toShipmentStatus($statusCode),
            'occurred_at' => $this->parseTime($data['ORDER_STATUSDATE'] ?? null),
            'cod_collected' => array_key_exists('MONEY_COLLECTION', $data) ? (int) $data['MONEY_COLLECTION'] : null,
            'failed_collect_collected' => array_key_exists('EXTRA_MONEY', $data) ? (int) $data['EXTRA_MONEY'] : null,
            'return_fee' => null,
            'secret' => $secret !== '' ? $secret : null,
            'raw' => $body,
        ];
    }

    public function verifyCredentials(array $account): array
    {
        $c = (array) ($account['credentials'] ?? []);
        $hasUserPass = ($c['username'] ?? '') !== '' && ($c['password'] ?? '') !== '';
        if (! $hasUserPass && ($c['token'] ?? '') === '') {
            return ['ok' => false, 'message' => 'Chưa nhập Username/Password hoặc Token cho Viettel Post.', 'error_code' => 'invalid_credentials', 'expires_at' => null];
        }
        try {
            $token = $this->client($account)->partnerToken(fresh: true);
            $exp = ViettelPostClient::jwtExp($token);
            $expiresAt = $exp ? Carbon::createFromTimestamp($exp)->toIso8601String() : null;

            return ['ok' => true, 'message' => 'Kết nối Viettel Post OK.', 'expires_at' => $expiresAt];
        } catch (\Throwable $e) {
            $m = $e->getMessage();
            $isAuth = stripos($m, 'password') !== false || stripos($m, 'token') !== false
                || stripos($m, 'owner') !== false || stripos($m, 'Invalid') !== false || stripos($m, '401') !== false;

            return [
                'ok' => false,
                'message' => $isAuth ? 'Tài khoản/mật khẩu hoặc token Viettel Post không hợp lệ.' : 'Lỗi kết nối Viettel Post: '.$m,
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

        return $note === '' ? null : mb_substr($note, 0, 150);
    }

    /**
     * ORDER_NOTE = ghi chú "cho xem hàng" (khi allow_inspection, mặc định BẬT) + ghi chú giao hàng.
     * VTP không có field xem-hàng riêng — thể hiện qua ghi chú (docs partner.viettelpost.vn, ví dụ tạo đơn).
     */
    private function buildOrderNote(array $shipment): ?string
    {
        $parts = [];
        // Ghi chú xem/thử 3 mức (chuẩn hoá GHN) — VTP không có field riêng, thể hiện qua ghi chú. Fallback
        // cờ bool allow_inspection cho đơn cũ (mặc định an toàn: cho xem, không thử).
        $requiredNote = (string) ($shipment['required_note'] ?? (($shipment['allow_inspection'] ?? true) ? 'CHOXEMHANGKHONGTHU' : 'KHONGCHOXEMHANG'));
        $parts[] = match ($requiredNote) {
            'CHOTHUHANG' => 'Cho khách xem và thử hàng khi nhận',
            'KHONGCHOXEMHANG' => 'Không cho khách xem hàng',
            default => 'Cho khách xem hàng, không cho thử',
        };
        $delivery = trim((string) ($shipment['delivery_note'] ?? $shipment['content'] ?? ''));
        if ($delivery !== '') {
            $parts[] = $delivery;
        }

        // $parts luôn có ≥1 phần tử (ghi chú xem/thử luôn được thêm) ⇒ implode trực tiếp.
        return $this->trimNote(implode('. ', $parts));
    }

    /** ORDER_STATUSDATE dạng "d/m/Y H:i:s" (vd "10/11/2025 11:07:16") — giờ VN (GMT+7). Fallback Carbon::parse. */
    private function parseTime(?string $v): string
    {
        if (! $v) {
            return now()->toIso8601String();
        }
        $v = trim((string) $v);
        foreach (['d/m/Y H:i:s', 'd/m/Y H: i: s'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $v, app_display_tz())->toIso8601String();
            } catch (\Throwable) {
                // thử format kế tiếp
            }
        }
        try {
            return Carbon::parse($v, app_display_tz())->toIso8601String();
        } catch (\Throwable) {
            return now()->toIso8601String();
        }
    }
}
