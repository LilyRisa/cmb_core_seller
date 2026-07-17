<?php

namespace CMBcoreSeller\Integrations\Carriers\JtExpress;

use CMBcoreSeller\Integrations\Carriers\Support\AbstractCarrierConnector;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

/**
 * J&T Express — mô hình "bưu cục" giống GHN/VTP (có tem in, có COD) nhưng xác thực 2 tầng như Ahamove:
 *   - Cấp ứng dụng: `apiAccount`+`privateKey`, 1 cặp cho cả platform (config('integrations.jt.*'), do J&T
 *     duyệt cho CMBcoreSeller — KHÔNG phải per-tenant).
 *   - Cấp merchant: `customerCode`+`password`, per-tenant (`CarrierAccount.credentials`).
 * Chỉ hỗ trợ `selfAddress=1` (địa chỉ hành chính quốc gia mới, 2 cấp) — `sender`/`receiver` chỉ gửi
 * `prov`(tỉnh)/`area`(phường-xã)/`address`, KHÔNG gửi `city`. Không cần address-ID resolver (khác GHN/VTP).
 *
 * Trơ (inert) tới khi `JT_API_ACCOUNT`/`JT_PRIVATE_KEY` được điền thật. Xem SPEC 0042.
 */
class JtExpressConnector extends AbstractCarrierConnector
{
    public function code(): string
    {
        return 'jt';
    }

    public function displayName(): string
    {
        return 'J&T Express';
    }

    public function capabilities(): array
    {
        return ['createShipment', 'cancel', 'quote', 'getLabel', 'getTracking', 'webhook'];
    }

    /** J&T không có header/secret chuẩn cho webhook — khớp tenant theo tracking_no (billCode). */
    public function webhookAuthMode(): string
    {
        return 'tracking_lookup';
    }

    private function apiAccount(): string
    {
        $v = (string) config('integrations.jt.api_account', '');
        if ($v === '') {
            throw new RuntimeException('J&T Express chưa được cấu hình ở hệ thống — thiếu JT_API_ACCOUNT.');
        }

        return $v;
    }

    private function privateKey(): string
    {
        $v = (string) config('integrations.jt.private_key', '');
        if ($v === '') {
            throw new RuntimeException('J&T Express chưa được cấu hình ở hệ thống — thiếu JT_PRIVATE_KEY.');
        }

        return $v;
    }

    private function client(): JtExpressClient
    {
        return new JtExpressClient($this->apiAccount(), $this->privateKey());
    }

    /** @return array{customerCode:string,password:string} */
    private function merchant(array $account): array
    {
        $c = (array) ($account['credentials'] ?? []);
        $code = (string) ($c['customerCode'] ?? '');
        $password = (string) ($c['password'] ?? '');
        if ($code === '' || $password === '') {
            throw new RuntimeException('Tài khoản J&T Express chưa nhập Mã khách hàng/Mật khẩu.');
        }

        return ['customerCode' => $code, 'password' => $password];
    }

    private function payType(array $account): string
    {
        $v = (string) (($account['meta'] ?? [])['pay_type'] ?? 'PP_CASH');

        return in_array($v, ['PP_CASH', 'PP_PM'], true) ? $v : 'PP_CASH';
    }

    /**
     * Chuẩn hoá 1 điểm gửi/nhận sang field J&T (`prov`/`area`). Chấp nhận CẢ 2 nguồn có shape khác nhau:
     * `account.meta.from_address` (form CarrierAccountsPage dùng field `_name` hậu tố: `province_name`,
     * `ward_name` — để hỗ trợ chung với GHN/VTP cần thêm ID) và `shipment.recipient` (ShipmentService
     * chuẩn hoá không hậu tố: `province`, `ward`). KHÔNG gửi `city` (đúng quy ước selfAddress=1).
     *
     * @return array{name:string,mobile:string,prov:string,area:string,address:string}
     */
    private function point(array $p): array
    {
        return [
            'name' => (string) ($p['name'] ?? ''),
            'mobile' => (string) ($p['phone'] ?? $p['mobile'] ?? ''),
            'prov' => (string) ($p['province'] ?? $p['province_name'] ?? ''),
            'area' => (string) ($p['ward'] ?? $p['ward_name'] ?? ''),
            'address' => (string) ($p['address'] ?? ''),
        ];
    }

    public function quote(array $account, array $request): array
    {
        $from = (array) (($account['meta'] ?? [])['from_address'] ?? []);
        $recipient = (array) ($request['recipient'] ?? []);
        if (empty($from['address']) || empty($recipient['province']) || empty($recipient['ward'])) {
            return [];
        }
        try {
            $merchant = $this->merchant($account);
        } catch (\Throwable) {
            return [];
        }
        $sender = $this->point($from);
        $receiver = $this->point($recipient);
        $pkg = (array) (($account['meta'] ?? [])['defaults']['package'] ?? []);
        $weightKg = round(max(0.01, ((float) ($pkg['weight_grams'] ?? 500)) / 1000), 2);

        try {
            $data = $this->client()->getComCost([
                ...$merchant,
                'weight' => $weightKg,
                'selfAddress' => 1,
                'isInsured' => 0,
                'goodsValue' => 0,
                'goodsType' => 'bm000010',
                'productType' => 'EXPRESS',
                'sender' => ['prov' => $sender['prov'], 'area' => $sender['area']],
                'receiver' => ['prov' => $receiver['prov'], 'area' => $receiver['area']],
            ]);
        } catch (\Throwable) {
            return [];
        }

        return [[
            'carrier' => 'jt',
            'fee' => (int) ($data['price'] ?? 0),
            'insurance_fee' => (int) ($data['insuranceFee'] ?? 0),
            'name' => null,
            'eta' => null,
        ]];
    }

    public function createShipment(array $account, array $shipment): array
    {
        $merchant = $this->merchant($account);
        $from = (array) (($account['meta'] ?? [])['from_address'] ?? []);
        if (empty($from['address'])) {
            throw new RuntimeException('Cài đặt J&T Express thiếu địa chỉ kho gửi. Vào Cài đặt → ĐVVC để bổ sung.');
        }
        $sender = $this->point($from);
        if ($sender['name'] === '' || $sender['mobile'] === '') {
            throw new RuntimeException('Cài đặt J&T Express thiếu tên/SĐT kho gửi. Vào Cài đặt → ĐVVC để bổ sung.');
        }
        if ($sender['prov'] === '' || $sender['area'] === '') {
            throw new RuntimeException('Cài đặt J&T Express thiếu Tỉnh/Phường kho gửi. Vào Cài đặt → ĐVVC để bổ sung.');
        }

        $recipient = (array) ($shipment['recipient'] ?? []);
        $receiver = $this->point($recipient);
        if ($receiver['address'] === '' || $receiver['prov'] === '' || $receiver['area'] === '') {
            throw new RuntimeException('Đơn thiếu Tỉnh/Phường hoặc địa chỉ chi tiết của người nhận.');
        }

        $txlogisticId = (string) ($shipment['client_order_code'] ?? '');
        if ($txlogisticId === '') {
            throw new RuntimeException('Thiếu mã đơn nội bộ để tạo vận đơn J&T.');
        }

        $p = (array) ($shipment['parcel'] ?? []);
        $weightKg = round(max(0.01, ((float) ($p['weight_grams'] ?? 500)) / 1000), 2);
        $cod = (int) ($shipment['cod_amount'] ?? 0);
        $items = (array) ($shipment['items'] ?? []);
        $totalValue = (int) array_sum(array_map(
            fn ($it) => max(0, (int) ($it['price'] ?? 0)) * max(1, (int) ($it['quantity'] ?? 1)),
            $items
        ));

        $packageInfo = array_filter([
            'weight' => (string) $weightKg,
            'length' => isset($p['length_cm']) ? (float) $p['length_cm'] : null,
            'width' => isset($p['width_cm']) ? (float) $p['width_cm'] : null,
            'height' => isset($p['height_cm']) ? (float) $p['height_cm'] : null,
        ], fn ($v) => $v !== null);

        $itemLines = array_values(array_map(fn ($it) => [
            'itemName' => (string) ($it['name'] ?? 'Hàng'),
            'englishName' => (string) ($it['name'] ?? 'Item'),
            'number' => (string) max(1, (int) ($it['quantity'] ?? 1)),
            'itemValue' => (int) ($it['price'] ?? 0),
        ], $items));

        $payload = array_filter([
            ...$merchant,
            'txlogisticId' => $txlogisticId,
            'orderType' => 1,
            'selfAddress' => 1,
            'serviceType' => 1,
            'payType' => $this->payType($account),
            'productType' => 'EXPRESS',
            'goodsType' => 'bm000010',
            'deliveryType' => 1,
            'sender' => array_filter($sender),
            'receiver' => array_filter($receiver),
            'isInsured' => 0,
            'goodsValue' => max(0, $totalValue),
            'codMoney' => $cod > 0 ? $cod : null,
            'remark' => trim((string) ($shipment['delivery_note'] ?? $shipment['content'] ?? '')) ?: null,
            // $packageInfo luôn có ít nhất 'weight' (không bao giờ rỗng) — không cần ternary.
            'packageInfo' => $packageInfo,
            'items' => $itemLines !== [] ? $itemLines : null,
        ], fn ($v) => $v !== null && $v !== '');

        $data = $this->client()->addOrder($payload);
        $billCode = (string) ($data['billCode'] ?? '');
        if ($billCode === '') {
            throw new RuntimeException('J&T Express không trả về mã vận đơn.');
        }

        return [
            'tracking_no' => $billCode,
            'carrier' => 'jt',
            'status' => 'created',
            // ⚠️ Tên field response addOrder (inquiryFee/codFee/insuranceFee) canh lệch dòng trong tài liệu
            // J&T — CHƯA verify field nào là tổng phí thật. Dùng insuranceFee tạm (xem SPEC 0042 §2.1).
            'fee' => (int) ($data['insuranceFee'] ?? 0),
            'raw' => $data,
        ];
    }

    public function cancel(array $account, string $trackingNo): void
    {
        $merchant = $this->merchant($account);
        $this->client()->cancelOrder([
            ...$merchant,
            'txlogisticId' => $trackingNo,
            'billCode' => $trackingNo,
            'reason' => 'Người bán huỷ đơn qua CMBcore Seller',
        ]);
    }

    public function getLabel(array $account, string $trackingNo, string $format = 'A6'): array
    {
        $merchant = $this->merchant($account);
        $data = $this->client()->printOrder([...$merchant, 'txlogisticId' => $trackingNo]);
        $encoded = (string) ($data['base64EncodeContent'] ?? '');
        if ($encoded === '') {
            throw new RuntimeException('J&T Express không trả về nội dung tem in.');
        }
        $bytes = base64_decode($encoded, true);
        if ($bytes === false) {
            throw new RuntimeException('J&T Express trả về tem in không đúng định dạng base64.');
        }
        $isPdf = str_starts_with($bytes, '%PDF');
        if (! $isPdf) {
            // Định dạng file thật chưa xác nhận (xem SPEC 0042 §7) — log để phát hiện sớm khi có tài khoản
            // UAT thật, không chặn luồng "Chuẩn bị hàng".
            Log::warning('jt.label.unexpected_format', ['tracking_no' => $trackingNo]);
        }

        return [
            'filename' => 'jt-'.$trackingNo.($isPdf ? '.pdf' : '.bin'),
            'mime' => $isPdf ? 'application/pdf' : 'application/octet-stream',
            'bytes' => $bytes,
        ];
    }

    public function getTracking(array $account, string $trackingNo): array
    {
        $merchant = $this->merchant($account);
        $data = $this->client()->trace([...$merchant, 'billcodes' => $trackingNo]);
        $row = $data[0] ?? null;
        $details = is_array($row) ? (array) ($row['details'] ?? []) : [];
        $last = $details !== [] ? end($details) : null;
        $status = is_array($last) ? JtExpressStatusMap::toShipmentStatus($last['scanTypeCode'] ?? null) : null;

        $events = array_values(array_map(fn ($d) => [
            'code' => (string) ($d['scanTypeCode'] ?? ''),
            'description' => (string) ($d['desc'] ?? $d['scanTypeName'] ?? ''),
            'occurred_at' => $this->parseTime((string) ($d['scanTime'] ?? '')),
        ], $details));

        return ['status' => $status, 'events' => $events, 'raw' => $data];
    }

    /**
     * Parse webhook J&T. Body: `{billCode, txlogisticId, details: Object|Array}`. J&T KHÔNG công bố cơ chế
     * secret/signature nào trong PAYLOAD — nhưng vì URL webhook là do MÌNH cung cấp cho J&T đăng ký thủ
     * công (không phải J&T cấp sẵn), seller có thể tự nhúng 1 query string bí mật vào chính URL đó lúc gửi
     * cho support J&T (vd `.../webhook/carriers/jt?secret=XXXX`) — đọc qua `$request->query->get('secret')`.
     * Rỗng (seller chưa tự đặt) ⇒ controller chấp nhận + log cảnh báo (giống GHTK/VTP khi thiếu
     * `webhook_secret`). Xem SPEC 0042 §6/§11.
     *
     * @return array{tracking_no:?string, raw_status:?string, status:?string, occurred_at:string, cod_collected:?int, failed_collect_collected:?int, return_fee:?int, secret:?string, raw:array}
     */
    public function parseWebhook(Request $request): array
    {
        $body = (array) ($request->toArray() ?: $request->getPayload()->all());
        $tracking = (string) ($body['billCode'] ?? '');
        $details = (array) ($body['details'] ?? []);
        // `details` có thể là 1 object đơn (không phải mảng) theo tài liệu "Object | Array" — chuẩn hoá.
        $last = isset($details[0]) ? end($details) : ($details !== [] ? $details : null);
        $rawStatus = is_array($last) ? (string) ($last['scanTypeCode'] ?? '') : '';
        // `$request` được type ở mức Symfony (Contract), không phải Illuminate\Http\Request — đọc qua
        // property `query` (InputBag) thay vì method `query()` (chỉ có ở Laravel) để hợp PHPStan.
        $secret = (string) $request->query->get('secret', '');

        return [
            'tracking_no' => $tracking !== '' ? $tracking : null,
            'raw_status' => $rawStatus !== '' ? $rawStatus : null,
            'status' => $rawStatus !== '' ? JtExpressStatusMap::toShipmentStatus($rawStatus) : null,
            'occurred_at' => is_array($last) ? $this->parseTime((string) ($last['scanTime'] ?? '')) : now()->toIso8601String(),
            'cod_collected' => null,
            'failed_collect_collected' => null,
            'return_fee' => null,
            'secret' => $secret !== '' ? $secret : null,
            'raw' => $body,
        ];
    }

    private function parseTime(string $v): string
    {
        if ($v === '') {
            return now()->toIso8601String();
        }
        try {
            return Carbon::parse($v, app_display_tz())->toIso8601String();
        } catch (\Throwable) {
            return now()->toIso8601String();
        }
    }

    public function verifyCredentials(array $account): array
    {
        try {
            $this->apiAccount();
            $this->privateKey();
            $merchant = $this->merchant($account);
        } catch (RuntimeException $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'error_code' => 'invalid_credentials', 'expires_at' => null];
        }
        try {
            // Không có endpoint "whoami" — dùng getComCost với payload tối thiểu làm phép thử xác thực.
            $this->client()->getComCost([
                ...$merchant, 'weight' => 1, 'selfAddress' => 1, 'isInsured' => 0, 'goodsValue' => 0,
                'goodsType' => 'bm000010', 'productType' => 'EXPRESS',
                'sender' => ['prov' => 'Hồ Chí Minh', 'area' => 'Phường Bến Nghé'],
                'receiver' => ['prov' => 'Hà Nội', 'area' => 'Phường Hàng Trống'],
            ]);

            return ['ok' => true, 'message' => 'Kết nối J&T Express OK.', 'expires_at' => null];
        } catch (\Throwable $e) {
            $m = $e->getMessage();
            $isAuth = stripos($m, 'customerCode or password') !== false || stripos($m, 'signature') !== false
                || stripos($m, 'account does not exist') !== false || stripos($m, 'disable') !== false || stripos($m, 'locked') !== false;

            return [
                'ok' => false,
                'message' => $isAuth ? $m : 'Lỗi kết nối J&T Express: '.$m,
                'error_code' => $isAuth ? 'invalid_credentials' : 'network',
                'expires_at' => null,
            ];
        }
    }
}
