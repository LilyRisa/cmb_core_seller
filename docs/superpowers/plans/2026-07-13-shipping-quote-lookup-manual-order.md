# Nút tra cứu cước vận chuyển tham khảo (GHN/GHTK/VTP) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm nút "Tra cứu cước vận chuyển" ở form tạo đơn thủ công, mở modal liệt kê cước ước tính từ mọi tài khoản ĐVVC đang active (GHN/GHTK/VTP), thuần tham khảo.

**Architecture:** Mỗi connector (GHN/GHTK/VTP) tự implement `quote()` trả về mảng gói cước, đọc cân nặng/kích thước từ `carrier_account.meta.defaults.package` (không phụ thuộc giỏ hàng). `ShipmentService::quoteAllShippingFees()` lặp mọi tài khoản active của tenant, gộp kết quả thành 1 mảng phẳng. FE gọi 1 API duy nhất, hiển thị bảng trong modal.

**Tech Stack:** Laravel 11 (PHPUnit, `Http::fake()`), React + TypeScript + Ant Design + TanStack Query.

## Global Constraints

- Mọi lệnh PHP/Node chạy từ `app/`, không phải repo root.
- Không có JS test runner trong repo — task frontend verify bằng `npm run typecheck && npm run lint && npm run build` + kiểm thủ công qua trình duyệt.
- `quote()` của cả 3 connector đọc cân nặng/kích thước từ `account.meta.defaults.package` (`weight_grams`, `length_cm`, `width_cm`, `height_cm`) — **không** từ giỏ hàng/`$request`. `$request` truyền vào `quote()` chỉ còn `{ recipient: {...} }`.
- GHN chỉ trả **1 mức giá** (`name: null`) — API tính phí chính thức không có tham số tách theo Nhanh/Chuẩn/Tiết kiệm (đã xác nhận qua `api.ghn.vn/home/docs/detail?id=95`, không suy đoán thêm).
- Endpoint cũ `POST /fulfillment/quote` (đơn-tài-khoản) bị xoá hoàn toàn (0 call site còn lại sau commit 82bebe24) — thay bằng `POST /fulfillment/quote-all`.
- Spec đầy đủ: `docs/specs/2026-07-13-shipping-quote-lookup-manual-order.md`.

---

### Task 1: Backend — `GhnClient::fee()` + `GhnConnector::quote()` (mới)

**Files:**
- Modify: `app/app/Integrations/Carriers/Ghn/GhnClient.php`
- Modify: `app/app/Integrations/Carriers/Ghn/GhnConnector.php`
- Test: `app/tests/Feature/Fulfillment/GhnConnectorQuoteTest.php`

**Interfaces:**
- Consumes: `GhnAddressResolver::resolve(array $address): array{province_id:?int, district_id:?int, ward_code:?string, matched:array}` (đã có, cùng namespace `Carriers\Ghn`, không cần `use`).
- Produces: `GhnClient::fee(array $payload): array` (trả GHN `data` object: `total, service_fee, insurance_fee, ...`); `GhnConnector::quote(array $account, array $request): array` trả `list<array{carrier:string, fee:int, insurance_fee:int, name:null}>` — 0 hoặc 1 phần tử. Task 4 (FE hook) tiêu thụ shape này gián tiếp qua `ShipmentService::quoteAllShippingFees()` (Task 3).

- [ ] **Step 1: Viết test thất bại**

Tạo `app/tests/Feature/Fulfillment/GhnConnectorQuoteTest.php`:

```php
<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\Ghn\GhnConnector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GhnConnectorQuoteTest extends TestCase
{
    private function fakeMasterDataAndFee(int $fee = 28000, int $insuranceFee = 0): void
    {
        Cache::flush();
        Http::fake(function ($request) use ($fee, $insuranceFee) {
            $url = $request->url();
            if (str_contains($url, 'master-data/province')) {
                return Http::response(['code' => 200, 'data' => [
                    ['ProvinceID' => 201, 'ProvinceName' => 'Hà Nội', 'NameExtension' => ['Hà Nội']],
                ]]);
            }
            if (str_contains($url, 'master-data/district')) {
                return Http::response(['code' => 200, 'data' => [
                    ['DistrictID' => 1442, 'DistrictName' => 'Quận Cầu Giấy'],
                ]]);
            }
            if (str_contains($url, 'master-data/ward')) {
                return Http::response(['code' => 200, 'data' => [
                    ['WardCode' => '21211', 'DistrictID' => 1442, 'WardName' => 'Phường Dịch Vọng'],
                ]]);
            }
            if (str_contains($url, 'shipping-order/fee')) {
                return Http::response(['code' => 200, 'message' => 'Success', 'data' => [
                    'total' => $fee, 'service_fee' => $fee, 'insurance_fee' => $insuranceFee,
                ]]);
            }

            return Http::response(['code' => 200, 'data' => []]);
        });
    }

    private function account(): array
    {
        return [
            'id' => 1, 'carrier' => 'ghn',
            'credentials' => ['token' => 'TEST-TOKEN', 'shop_id' => 9999],
            'default_service' => null,
            'meta' => [
                'from_address' => ['name' => 'Shop', 'phone' => '0900000000', 'address' => 'Số 1', 'district_id' => 1442, 'ward_code' => '21211'],
                'defaults' => ['goods_type' => 'light', 'package' => ['weight_grams' => 500, 'length_cm' => 15, 'width_cm' => 15, 'height_cm' => 10]],
            ],
        ];
    }

    public function test_quote_returns_single_fee_for_light_goods(): void
    {
        $this->fakeMasterDataAndFee(fee: 28000, insuranceFee: 0);

        $result = (new GhnConnector)->quote($this->account(), [
            'recipient' => ['province' => 'Hà Nội', 'district' => 'Quận Cầu Giấy', 'ward' => 'Phường Dịch Vọng'],
        ]);

        $this->assertCount(1, $result);
        $this->assertSame('ghn', $result[0]['carrier']);
        $this->assertSame(28000, $result[0]['fee']);
        $this->assertSame(0, $result[0]['insurance_fee']);
        $this->assertNull($result[0]['name']);
    }

    public function test_quote_uses_service_type_id_5_for_heavy_goods(): void
    {
        $this->fakeMasterDataAndFee(fee: 40000);
        $captured = null;
        Http::fake(function ($request) use (&$captured) {
            if (str_contains($request->url(), 'shipping-order/fee')) {
                $captured = json_decode($request->body(), true);

                return Http::response(['code' => 200, 'data' => ['total' => 40000, 'insurance_fee' => 0]]);
            }
            if (str_contains($request->url(), 'master-data/province')) {
                return Http::response(['code' => 200, 'data' => [['ProvinceID' => 201, 'ProvinceName' => 'Hà Nội']]]);
            }
            if (str_contains($request->url(), 'master-data/district')) {
                return Http::response(['code' => 200, 'data' => [['DistrictID' => 1442, 'DistrictName' => 'Quận Cầu Giấy']]]);
            }
            if (str_contains($request->url(), 'master-data/ward')) {
                return Http::response(['code' => 200, 'data' => [['WardCode' => '21211', 'DistrictID' => 1442, 'WardName' => 'Phường Dịch Vọng']]]);
            }

            return Http::response(['code' => 200, 'data' => []]);
        });

        $account = $this->account();
        $account['meta']['defaults']['goods_type'] = 'heavy';
        (new GhnConnector)->quote($account, [
            'recipient' => ['province' => 'Hà Nội', 'district' => 'Quận Cầu Giấy', 'ward' => 'Phường Dịch Vọng'],
        ]);

        $this->assertSame(5, $captured['service_type_id']);
    }

    public function test_quote_returns_empty_when_recipient_address_unresolvable(): void
    {
        Cache::flush();
        Http::fake(fn () => Http::response(['code' => 200, 'data' => []]));

        $result = (new GhnConnector)->quote($this->account(), [
            'recipient' => ['province' => 'Không Tồn Tại', 'district' => 'X', 'ward' => 'Y'],
        ]);

        $this->assertSame([], $result);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận thất bại**

Run (từ `app/`): `php artisan test --filter=GhnConnectorQuoteTest`
Expected: FAIL — `GhnConnector::quote()` chưa override (default `AbstractCarrierConnector::quote()` trả `[]` luôn, test 1 và 2 fail vì mong 1 phần tử).

- [ ] **Step 3: Thêm `GhnClient::fee()`**

Trong `app/app/Integrations/Carriers/Ghn/GhnClient.php`, thêm method mới ngay sau `createOrder()` (sau dòng `}` đóng của `createOrder`, trước `orderDetail()`):

```php
    /** POST /v2/shipping-order/fee — tính cước tham khảo. @return array<string,mixed> GHN `data` object (total, service_fee, insurance_fee, ...) */
    public function fee(array $payload): array
    {
        $res = $this->http()->post('/shiip/public-api/v2/shipping-order/fee', $payload);
        $body = $res->json();
        if (! $res->successful() || (int) ($body['code'] ?? 0) !== 200) {
            throw new RuntimeException('GHN tính phí lỗi: '.($body['message'] ?? $res->status()));
        }

        return (array) ($body['data'] ?? []);
    }
```

- [ ] **Step 4: Thêm `'quote'` vào `capabilities()` + implement `quote()` trong `GhnConnector`**

Trong `app/app/Integrations/Carriers/Ghn/GhnConnector.php`, sửa dòng cuối `capabilities()`:

Tìm:
```php
        return ['createShipment', 'getLabel', 'getTracking', 'cancel', 'awaiting_pickup_flow', 'webhook', 'failed_delivery_collect'];
```
Thay bằng:
```php
        return ['createShipment', 'getLabel', 'getTracking', 'cancel', 'quote', 'awaiting_pickup_flow', 'webhook', 'failed_delivery_collect'];
```

Thêm method `quote()` ngay sau `client()` (sau dòng `}` đóng `client()`, trước `createShipment()`):

```php
    /**
     * Tính cước tham khảo (SPEC 2026-07-13) — 1 mức giá duy nhất (GHN Calculate Fee API chỉ nhận
     * service_type_id 2/5, không tách theo tên gói Nhanh/Chuẩn/Tiết kiệm). Cân nặng/kích thước lấy từ
     * account.meta.defaults.package (KHÔNG từ giỏ hàng). Thiếu địa chỉ/không resolve được ⇒ trả [].
     */
    public function quote(array $account, array $request): array
    {
        $s = (array) ($account['meta']['from_address'] ?? []);
        if (empty($s['district_id']) || empty($s['ward_code'])) {
            return [];
        }
        $resolved = (new GhnAddressResolver($this->client($account)))->resolve(
            (array) ($request['recipient'] ?? [])
        );
        if (empty($resolved['district_id']) || empty($resolved['ward_code'])) {
            return [];
        }
        $defaults = (array) ($account['meta']['defaults'] ?? []);
        $pkg = (array) ($defaults['package'] ?? []);
        $serviceTypeId = (($defaults['goods_type'] ?? 'light') === 'heavy') ? 5 : 2;

        try {
            $data = $this->client($account)->fee([
                'service_type_id' => $serviceTypeId,
                'from_district_id' => (int) $s['district_id'],
                'from_ward_code' => (string) $s['ward_code'],
                'to_district_id' => (int) $resolved['district_id'],
                'to_ward_code' => (string) $resolved['ward_code'],
                'weight' => (int) ($pkg['weight_grams'] ?? 500),
                'length' => (int) ($pkg['length_cm'] ?? 10),
                'width' => (int) ($pkg['width_cm'] ?? 10),
                'height' => (int) ($pkg['height_cm'] ?? 10),
            ]);
        } catch (\Throwable) {
            return [];
        }

        return [[
            'carrier' => 'ghn',
            'fee' => (int) ($data['total'] ?? 0),
            'insurance_fee' => (int) ($data['insurance_fee'] ?? 0),
            'name' => null,
        ]];
    }
```

- [ ] **Step 5: Chạy test, xác nhận pass**

Run: `php artisan test --filter=GhnConnectorQuoteTest`
Expected: PASS — 3/3 test.

- [ ] **Step 6: Quality gate**

Run: `vendor/bin/pint --test app/Integrations/Carriers/Ghn/GhnClient.php app/Integrations/Carriers/Ghn/GhnConnector.php tests/Feature/Fulfillment/GhnConnectorQuoteTest.php`
Run: `vendor/bin/phpstan analyse app/Integrations/Carriers/Ghn/GhnClient.php app/Integrations/Carriers/Ghn/GhnConnector.php`
Expected: pint pass; phpstan không có lỗi mới trong 2 file này (nếu có lỗi pre-existing không liên quan dòng vừa sửa, ghi chú lại nhưng không cần fix).

- [ ] **Step 7: Commit**

```bash
git add app/app/Integrations/Carriers/Ghn/GhnClient.php app/app/Integrations/Carriers/Ghn/GhnConnector.php app/tests/Feature/Fulfillment/GhnConnectorQuoteTest.php
git commit -m "feat(fulfillment): thêm GhnConnector::quote() tính cước tham khảo GHN"
```

---

### Task 2: Backend — đổi nguồn cân nặng của `GhtkConnector::quote()` + `ViettelPostConnector::quote()`

**Files:**
- Modify: `app/app/Integrations/Carriers/Ghtk/GhtkConnector.php`
- Modify: `app/app/Integrations/Carriers/ViettelPost/ViettelPostConnector.php`
- Test: `app/tests/Feature/Fulfillment/GhtkConnectorQuoteTest.php`
- Test: `app/tests/Feature/Fulfillment/ViettelPostConnectorQuoteTest.php`

**Interfaces:**
- Consumes: không đổi (`GhtkClient::fee()`, `ViettelPostClient::getPriceAll()`, `ViettelPostAddressResolver::resolve()` — tất cả đã có sẵn, không sửa).
- Produces: `GhtkConnector::quote(array $account, array $request): array` và `ViettelPostConnector::quote(...)` — chữ ký PHP không đổi, nhưng `$request` giờ chỉ cần `{ recipient: {...} }` (bỏ phụ thuộc `weight_grams`/`value`). Task 3 gọi cả 2 với `$request = ['recipient' => [...]]` duy nhất.

- [ ] **Step 1: Viết test thất bại cho GHTK**

Tạo `app/tests/Feature/Fulfillment/GhtkConnectorQuoteTest.php`:

```php
<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\Ghtk\GhtkConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GhtkConnectorQuoteTest extends TestCase
{
    private function account(): array
    {
        return [
            'id' => 1, 'carrier' => 'ghtk',
            'credentials' => ['token' => 'GHTK-TOKEN', 'client_source' => 'S1'],
            'default_service' => null,
            'meta' => [
                'from_address' => ['province_name' => 'Hà Nội', 'district_name' => 'Quận Hai Bà Trưng'],
                'defaults' => ['package' => ['weight_grams' => 800]],
            ],
        ];
    }

    public function test_quote_uses_account_default_package_weight_not_request(): void
    {
        $captured = null;
        Http::fake(function ($request) use (&$captured) {
            $captured = $request->data();

            return Http::response(['success' => true, 'fee' => ['name' => 'area1', 'fee' => 26400, 'insurance_fee' => 0]]);
        });

        $result = (new GhtkConnector)->quote($this->account(), [
            'recipient' => ['province' => 'Hà Nội', 'district' => 'Quận Cầu Giấy'],
        ]);

        $this->assertSame(800, $captured['weight']);
        $this->assertCount(1, $result);
        $this->assertSame(26400, $result[0]['fee']);
        $this->assertSame('area1', $result[0]['name']);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận thất bại**

Run: `php artisan test --filter=GhtkConnectorQuoteTest`
Expected: FAIL — `$captured['weight']` hiện đang lấy từ `$request['weight_grams']` (không truyền trong test) nên ra `0`, không phải `800`.

- [ ] **Step 3: Sửa `GhtkConnector::quote()`**

Trong `app/app/Integrations/Carriers/Ghtk/GhtkConnector.php`, tìm:

```php
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
```

Thay bằng:

```php
    public function quote(array $account, array $request): array
    {
        $s = (array) ($account['meta']['from_address'] ?? []);
        $r = (array) ($request['recipient'] ?? []);
        $pkg = (array) ($account['meta']['defaults']['package'] ?? []);
        $params = array_filter([
            'pick_province' => $s['province_name'] ?? null,
            'pick_district' => $s['district_name'] ?? null,
            'pick_ward' => $s['ward_name'] ?? null,
            'pick_address' => $s['address'] ?? null,
            'province' => $r['province'] ?? null,
            'district' => $r['district'] ?? null,
            'ward' => $r['ward'] ?? null,
            'address' => $r['address'] ?? null,
            'weight' => (int) ($pkg['weight_grams'] ?? 500),  // GRAM — từ cấu hình tài khoản, không từ giỏ hàng
            'transport' => 'road',
        ], fn ($v) => $v !== null && $v !== '');
```

Cập nhật docblock ngay phía trên (dòng `/** Tính phí gợi ý. $request: ... */`):

Tìm:
```php
    /**
     * Tính phí gợi ý. $request: { weight_grams, value?, recipient:{province,district,ward?,address?} }.
     * Sender lấy từ account.meta.from_address. Trả list 1 quote (fee + insurance_fee).
     */
```
Thay bằng:
```php
    /**
     * Tính phí gợi ý tham khảo (SPEC 2026-07-13). $request: { recipient:{province,district,ward?,address?} }.
     * Sender + cân nặng lấy từ account.meta.from_address/defaults.package (KHÔNG từ giỏ hàng). Trả list 1 quote.
     */
```

- [ ] **Step 4: Chạy test GHTK, xác nhận pass**

Run: `php artisan test --filter=GhtkConnectorQuoteTest`
Expected: PASS.

- [ ] **Step 5: Viết test thất bại cho ViettelPost**

Tạo `app/tests/Feature/Fulfillment/ViettelPostConnectorQuoteTest.php`:

```php
<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\ViettelPost\ViettelPostConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ViettelPostConnectorQuoteTest extends TestCase
{
    private function account(): array
    {
        return [
            'id' => 1, 'carrier' => 'viettelpost',
            'credentials' => ['token' => 'VTP-TOKEN'],
            'default_service' => null,
            'meta' => [
                'from_address' => ['province_id' => 1, 'district_id' => 12, 'ward_id' => 49876],
                'defaults' => ['package' => ['weight_grams' => 1200]],
            ],
        ];
    }

    public function test_quote_uses_account_default_package_weight_and_returns_all_tiers(): void
    {
        $captured = null;
        Http::fake(function ($request) use (&$captured) {
            if (str_contains($request->url(), 'order/getPriceAll')) {
                $captured = $request->data();

                return Http::response([
                    ['MA_DV_CHINH' => 'PHS', 'TEN_DICHVU' => 'Nội tỉnh tiết kiệm', 'GIA_CUOC' => 26400, 'THOI_GIAN' => '48 giờ'],
                    ['MA_DV_CHINH' => 'VCN', 'TEN_DICHVU' => 'Chuyển phát nhanh', 'GIA_CUOC' => 35000, 'THOI_GIAN' => '24 giờ'],
                ]);
            }

            return Http::response([]);
        });

        $result = (new ViettelPostConnector)->quote($this->account(), [
            'recipient' => ['province' => 'Hà Nội', 'district' => 'Quận Cầu Giấy', 'ward' => 'Phường X'],
        ]);

        $this->assertSame(1200, $captured['PRODUCT_WEIGHT']);
        $this->assertCount(2, $result, 'Phải trả ĐỦ các gói VTP, không cắt còn 1.');
        $this->assertSame('Nội tỉnh tiết kiệm', $result[0]['name']);
        $this->assertSame('Chuyển phát nhanh', $result[1]['name']);
    }
}
```

Lưu ý: test này gọi `ViettelPostAddressResolver` bên trong `quote()` — resolver sẽ gọi thêm API danh mục địa danh VTP (v2/v3). Vì `Http::fake()` không match cụ thể các URL đó sẽ trả `Http::response([])` mặc định (rỗng) — cần đảm bảo resolver không throw khi rỗng (đã là hành vi hiện tại, trả `null` cho field không tìm thấy). Nếu bước 6 chạy test thất bại vì lý do khác `PRODUCT_WEIGHT`/số lượng gói (vd resolver trả về rỗng khiến `quote()` return `[]` sớm ở dòng `empty($resolved['province_id'])`), điều chỉnh fake để endpoint danh mục địa danh (`categories/listProvinceNew`, `categories/listWardsNew`, `categories/listProvince`, `categories/listDistrict`, `categories/listWards`) trả dữ liệu tối thiểu khớp `'Hà Nội'/'Quận Cầu Giấy'/'Phường X'` — xem `ViettelPostAddressResolver.php` để biết đúng field/URL cần fake.

- [ ] **Step 6: Chạy test VTP, xác nhận thất bại rồi sửa code**

Run: `php artisan test --filter=ViettelPostConnectorQuoteTest`
Expected trước khi sửa: FAIL (`$captured['PRODUCT_WEIGHT']` hiện là `0` vì code cũ đọc `$request['weight_grams']`).

Trong `app/app/Integrations/Carriers/ViettelPost/ViettelPostConnector.php`, tìm:

```php
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
```

Thay bằng:

```php
    public function quote(array $account, array $request): array
    {
        $s = (array) ($account['meta']['from_address'] ?? []);
        $resolved = (new ViettelPostAddressResolver($this->client($account)))->resolve(
            (array) ($request['recipient'] ?? [])
        );
        if (empty($s['province_id']) || empty($s['ward_id']) || empty($resolved['province_id']) || empty($resolved['ward_id'])) {
            return [];
        }
        $pkg = (array) ($account['meta']['defaults']['package'] ?? []);
        $list = $this->client($account)->getPriceAll([
            'SENDER_PROVINCE' => (int) $s['province_id'],
            'SENDER_DISTRICT' => isset($s['district_id']) ? (int) $s['district_id'] : null,
            'SENDER_WARD' => (int) $s['ward_id'],
            'RECEIVER_PROVINCE' => (int) $resolved['province_id'],
            'RECEIVER_DISTRICT' => $resolved['district_id'],
            'RECEIVER_WARD' => (int) $resolved['ward_id'],
            'PRODUCT_TYPE' => 'HH',
            'PRODUCT_WEIGHT' => (int) ($pkg['weight_grams'] ?? 500),
            'TYPE' => 1,
        ]);
```

- [ ] **Step 7: Chạy lại test VTP, xác nhận pass**

Run: `php artisan test --filter=ViettelPostConnectorQuoteTest`
Expected: PASS. Nếu vẫn fail do resolver trả rỗng, làm theo ghi chú ở Step 5 (fake thêm endpoint danh mục địa danh VTP).

- [ ] **Step 8: Quality gate**

Run: `vendor/bin/pint --test app/Integrations/Carriers/Ghtk/GhtkConnector.php app/Integrations/Carriers/ViettelPost/ViettelPostConnector.php tests/Feature/Fulfillment/GhtkConnectorQuoteTest.php tests/Feature/Fulfillment/ViettelPostConnectorQuoteTest.php`
Run: `vendor/bin/phpstan analyse app/Integrations/Carriers/Ghtk/GhtkConnector.php app/Integrations/Carriers/ViettelPost/ViettelPostConnector.php`

- [ ] **Step 9: Commit**

```bash
git add app/app/Integrations/Carriers/Ghtk/GhtkConnector.php app/app/Integrations/Carriers/ViettelPost/ViettelPostConnector.php app/tests/Feature/Fulfillment/GhtkConnectorQuoteTest.php app/tests/Feature/Fulfillment/ViettelPostConnectorQuoteTest.php
git commit -m "refactor(fulfillment): quote() GHTK/VTP đọc cân nặng từ cấu hình tài khoản, không từ giỏ hàng"
```

---

### Task 3: Backend — endpoint gộp `POST /fulfillment/quote-all` + xoá endpoint cũ

**Files:**
- Modify: `app/app/Modules/Fulfillment/Services/ShipmentService.php`
- Modify: `app/app/Modules/Fulfillment/Http/Controllers/ShipmentController.php`
- Modify: `app/routes/api.php`
- Test: `app/tests/Feature/Fulfillment/ShipmentQuoteAllTest.php`

**Interfaces:**
- Consumes: `CarrierConnector::quote(array $account, array $request): array` (Task 1+2, mỗi phần tử `{carrier, fee, insurance_fee, name?}` hoặc thêm `service_code`/`eta` với VTP); `CarrierAccount::toConnectorArray(): array`; `CarrierRegistry::has(string): bool` / `::for(string): CarrierConnector`.
- Produces: `ShipmentService::quoteAllShippingFees(int $tenantId, array $recipient): array` trả `list<array{carrier_account_id:int, carrier:string, carrier_name:string, account_name:string, service_name?:?string, fee?:int, insurance_fee?:int, eta?:?string, error?:string}>`. Route `POST /api/v1/fulfillment/quote-all` → `{ data: [...] }`. Task 4 (FE hook `useShippingQuoteAll`) tiêu thụ đúng shape phẳng này.

- [ ] **Step 1: Viết test thất bại**

Tạo `app/tests/Feature/Fulfillment/ShipmentQuoteAllTest.php`:

```php
<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Carriers\Ghtk\GhtkConnector;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Fulfillment\Models\CarrierAccount;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShipmentQuoteAllTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        app(CarrierRegistry::class)->register('ghtk', GhtkConnector::class);

        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function makeGhtkAccount(string $name, bool $active = true): CarrierAccount
    {
        return CarrierAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'carrier' => 'ghtk', 'name' => $name,
            'credentials' => ['token' => 'T', 'client_source' => 'S1'], 'is_active' => $active,
            'meta' => ['from_address' => ['province_name' => 'Hà Nội', 'district_name' => 'Q1'], 'defaults' => ['package' => ['weight_grams' => 500]]],
        ]);
    }

    public function test_aggregates_quotes_from_all_active_accounts(): void
    {
        $ok = $this->makeGhtkAccount('GHTK — Kho A');
        $this->makeGhtkAccount('GHTK — Kho B (inactive)', active: false);
        $failing = $this->makeGhtkAccount('GHTK — Kho lỗi');

        Http::fake(function ($request) use ($ok, $failing) {
            $body = $request->data();
            // Phân biệt account theo pick_district (mỗi account cùng 1 pick_district trong test này nên
            // dùng thứ tự request thay thế: account đầu OK, account sau lỗi — giả lập qua đếm lần gọi.
            static $calls = 0;
            $calls++;

            return $calls === 1
                ? Http::response(['success' => true, 'fee' => ['name' => 'area1', 'fee' => 26400, 'insurance_fee' => 0]])
                : Http::response(['success' => false, 'message' => 'Địa chỉ không hỗ trợ'], 400);
        });

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/fulfillment/quote-all', ['recipient' => ['province' => 'Hà Nội', 'district' => 'Cầu Giấy']])
            ->assertOk();

        $rows = $res->json('data');
        $this->assertCount(2, $rows, 'Chỉ 2 tài khoản active (inactive bị loại hoàn toàn).');
        $names = collect($rows)->pluck('account_name')->all();
        $this->assertContains('GHTK — Kho A', $names);
        $this->assertContains('GHTK — Kho lỗi', $names);
        $this->assertNotContains('GHTK — Kho B (inactive)', $names);

        $errorRow = collect($rows)->firstWhere('account_name', 'GHTK — Kho lỗi');
        $this->assertArrayHasKey('error', $errorRow);
    }

    public function test_does_not_leak_other_tenant_accounts(): void
    {
        $otherTenant = Tenant::create(['name' => 'Other']);
        CarrierAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $otherTenant->getKey(), 'carrier' => 'ghtk', 'name' => 'Other shop GHTK', 'is_active' => true,
            'credentials' => ['token' => 'T'], 'meta' => ['from_address' => ['province_name' => 'HCM'], 'defaults' => ['package' => ['weight_grams' => 500]]],
        ]);

        Http::fake(fn () => Http::response(['success' => true, 'fee' => ['name' => 'area1', 'fee' => 1000, 'insurance_fee' => 0]]));

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/fulfillment/quote-all', ['recipient' => ['province' => 'Hà Nội', 'district' => 'Cầu Giấy']])
            ->assertOk();

        $this->assertCount(0, $res->json('data'));
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận thất bại**

Run: `php artisan test --filter=ShipmentQuoteAllTest`
Expected: FAIL — route `fulfillment/quote-all` chưa tồn tại (404).

- [ ] **Step 3: Thêm `ShipmentService::quoteAllShippingFees()`**

Trong `app/app/Modules/Fulfillment/Services/ShipmentService.php`, thêm method mới ngay sau `quoteShippingFee()` (sau dòng `}` đóng method đó, dòng ~617):

```php
    /**
     * Tra cứu cước tham khảo từ MỌI tài khoản ĐVVC active của tenant (SPEC 2026-07-13). Mỗi tài khoản có
     * thể sinh nhiều dòng (VTP nhiều gói); tài khoản lỗi/không hỗ trợ vẫn có mặt trong danh sách với field
     * `error` thay vì bị bỏ sót âm thầm — TRỪ carrier không hỗ trợ capability `quote` (bị lọc hẳn, không
     * có khái niệm "tính phí online", vd carrier thủ công/tự giao).
     *
     * @param  array{province?:string,district?:?string,ward?:?string,address?:?string}  $recipient
     * @return list<array<string,mixed>>
     */
    public function quoteAllShippingFees(int $tenantId, array $recipient): array
    {
        $accounts = CarrierAccount::query()->where('tenant_id', $tenantId)->where('is_active', true)->get();
        $out = [];
        foreach ($accounts as $account) {
            if (! $this->carriers->has($account->carrier)) {
                continue;
            }
            $connector = $this->carriers->for($account->carrier);
            if (! ($connector instanceof AbstractCarrierConnector) || ! $connector->supports('quote')) {
                continue;
            }
            $base = [
                'carrier_account_id' => $account->id,
                'carrier' => $account->carrier,
                'carrier_name' => $connector->displayName(),
                'account_name' => $account->name,
            ];
            try {
                $quotes = $connector->quote($account->toConnectorArray(), ['recipient' => $recipient]);
            } catch (\Throwable $e) {
                Log::warning('shipment.quote_all_failed', ['tenant' => $tenantId, 'carrier_account_id' => $account->id, 'error' => $e->getMessage()]);
                $out[] = $base + ['error' => 'Không lấy được cước cho tuyến này.'];

                continue;
            }
            if ($quotes === []) {
                $out[] = $base + ['error' => 'Không lấy được cước cho tuyến này.'];

                continue;
            }
            foreach ($quotes as $q) {
                $out[] = $base + [
                    'service_name' => $q['name'] ?? null,
                    'fee' => (int) ($q['fee'] ?? 0),
                    'insurance_fee' => (int) ($q['insurance_fee'] ?? 0),
                    'eta' => $q['eta'] ?? null,
                ];
            }
        }

        return $out;
    }
```

- [ ] **Step 4: Thêm route + controller action**

Trong `app/routes/api.php`, tìm dòng:

```php
            Route::post('fulfillment/quote', [ShipmentController::class, 'quote'])->name('fulfillment.quote');                          // gợi ý phí ship (carrier-agnostic)
```

Thay bằng:

```php
            Route::post('fulfillment/quote-all', [ShipmentController::class, 'quoteAll'])->name('fulfillment.quote-all');                // tra cứu cước tham khảo tất cả ĐVVC (SPEC 2026-07-13)
```

Trong `app/app/Modules/Fulfillment/Http/Controllers/ShipmentController.php`, tìm toàn bộ method `quote()` (từ dòng comment `/** POST /api/v1/fulfillment/quote ... */` tới dòng `}` đóng, khoảng dòng 184-206):

```php
    /**
     * POST /api/v1/fulfillment/quote — gợi ý phí ship (carrier-agnostic). Dùng ở màn tạo đơn.
     * Trả `{ data: { carrier, carrier_name, fee, insurance_fee } }` hoặc `{ data: null }` nếu carrier
     * không hỗ trợ tính phí / lỗi / chưa có tài khoản ĐVVC — FE tự ẩn gợi ý, không chặn tạo đơn.
     */
    public function quote(Request $request, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('orders.create') || $request->user()?->can('fulfillment.ship'), 403, 'Bạn không có quyền.');
        $data = $request->validate([
            'carrier_account_id' => ['sometimes', 'nullable', 'integer'],
            'weight_grams' => ['required', 'integer', 'min:1', 'max:1000000'],
            'value' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:999999999'],
            'recipient' => ['required', 'array'],
            'recipient.province' => ['required', 'string', 'max:120'],
            // Địa chỉ 2 cấp (Tỉnh + Phường, sau cải cách 2025) không có quận/huyện ⇒ district nullable.
            // GHTK nhận theo tên: cần Tỉnh + (Quận HOẶC Phường).
            'recipient.district' => ['sometimes', 'nullable', 'string', 'max:120', 'required_without:recipient.ward'],
            'recipient.ward' => ['sometimes', 'nullable', 'string', 'max:120', 'required_without:recipient.district'],
            'recipient.address' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        return response()->json(['data' => $this->service->quoteShippingFee((int) $tenant->id(), $data['carrier_account_id'] ?? null, $data)]);
    }
```

Thay bằng:

```php
    /**
     * POST /api/v1/fulfillment/quote-all — tra cứu cước tham khảo TẤT CẢ tài khoản ĐVVC active của tenant
     * (SPEC 2026-07-13). Trả `{ data: [...] }` — mỗi phần tử 1 dòng cước (VTP có thể nhiều dòng/tài khoản),
     * tài khoản lỗi có field `error` thay vì bị bỏ sót. Thuần tham khảo, không ghi/áp dụng vào đơn.
     */
    public function quoteAll(Request $request, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('orders.create') || $request->user()?->can('fulfillment.ship'), 403, 'Bạn không có quyền.');
        $data = $request->validate([
            'recipient' => ['required', 'array'],
            'recipient.province' => ['required', 'string', 'max:120'],
            'recipient.district' => ['sometimes', 'nullable', 'string', 'max:120', 'required_without:recipient.ward'],
            'recipient.ward' => ['sometimes', 'nullable', 'string', 'max:120', 'required_without:recipient.district'],
            'recipient.address' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        return response()->json(['data' => $this->service->quoteAllShippingFees((int) $tenant->id(), $data['recipient'])]);
    }
```

- [ ] **Step 5: Chạy test, xác nhận pass**

Run: `php artisan test --filter=ShipmentQuoteAllTest`
Expected: PASS — 2/2 test.

- [ ] **Step 6: Xoá `ShipmentService::quoteShippingFee()` (dead code, 0 call site còn lại)**

Trong `app/app/Modules/Fulfillment/Services/ShipmentService.php`, xoá toàn bộ method `quoteShippingFee()` (comment docblock phía trên + method body, dòng ~577-617 trước khi thêm Step 3 — sau khi thêm `quoteAllShippingFees` ở Step 3, method cũ nằm ngay phía trên method mới, xoá nguyên khối từ `/**\n * Gợi ý phí ship...` tới dòng `}` đóng `quoteShippingFee`).

- [ ] **Step 7: Quality gate**

Run: `vendor/bin/pint --test app/Modules/Fulfillment/Services/ShipmentService.php app/Modules/Fulfillment/Http/Controllers/ShipmentController.php app/routes/api.php tests/Feature/Fulfillment/ShipmentQuoteAllTest.php`
Run: `vendor/bin/phpstan analyse app/Modules/Fulfillment/Services/ShipmentService.php app/Modules/Fulfillment/Http/Controllers/ShipmentController.php`
Run full Fulfillment suite để bắt hồi quy: `php artisan test --testsuite=Feature --filter=Fulfillment`

- [ ] **Step 8: Commit**

```bash
git add app/app/Modules/Fulfillment/Services/ShipmentService.php app/app/Modules/Fulfillment/Http/Controllers/ShipmentController.php app/routes/api.php app/tests/Feature/Fulfillment/ShipmentQuoteAllTest.php
git commit -m "feat(fulfillment): endpoint quote-all gộp cước tham khảo mọi ĐVVC, xoá endpoint quote đơn-tài-khoản cũ"
```

---

### Task 4: Frontend — hook `useShippingQuoteAll` (thay `useShippingQuote`)

**Files:**
- Modify: `app/resources/js/lib/fulfillment.tsx`

**Interfaces:**
- Consumes: `POST /fulfillment/quote-all { recipient }` (Task 3) → `{ data: QuoteAllItem[] }`.
- Produces: `export interface QuoteAllItem { carrier_account_id: number; carrier: string; carrier_name: string; account_name: string; service_name?: string | null; fee?: number; insurance_fee?: number; eta?: string | null; error?: string }`; `export function useShippingQuoteAll()` (mutation) → `Promise<QuoteAllItem[]>`. Task 5 (modal component) tiêu thụ trực tiếp.

- [ ] **Step 1: Xoá hook cũ, thêm hook mới**

Trong `app/resources/js/lib/fulfillment.tsx`, tìm:

```tsx
export interface ShippingQuote { carrier: string; carrier_name: string; fee: number; insurance_fee: number }

/**
 * Gợi ý phí ship (carrier-agnostic). Trả null nếu ĐVVC không hỗ trợ tính phí / lỗi / chưa cấu hình —
 * caller (màn tạo đơn) tự ẩn gợi ý, KHÔNG chặn tạo đơn. Hiện chỉ GHTK trả phí.
 */
export function useShippingQuote() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (vars: {
            carrier_account_id?: number | null;
            weight_grams: number;
            value?: number;
            // province bắt buộc + (district HOẶC ward) — địa chỉ VN có thể 2 cấp (Tỉnh+Phường) hay 3 cấp.
            recipient: { province?: string; district?: string; ward?: string; address?: string };
        }): Promise<ShippingQuote | null> => {
            const { data } = await api!.post<{ data: ShippingQuote | null }>('/fulfillment/quote', vars);
            return data.data;
        },
    });
}
```

Thay bằng:

```tsx
export interface QuoteAllItem {
    carrier_account_id: number; carrier: string; carrier_name: string; account_name: string;
    service_name?: string | null; fee?: number; insurance_fee?: number; eta?: string | null; error?: string;
}

/**
 * Tra cứu cước tham khảo TẤT CẢ tài khoản ĐVVC active của tenant (SPEC 2026-07-13). Cân nặng/kích thước
 * server tự lấy từ cấu hình mặc định từng tài khoản — không truyền từ FE. Thuần tham khảo, không áp dụng
 * vào đơn.
 */
export function useShippingQuoteAll() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (vars: {
            // province bắt buộc + (district HOẶC ward) — địa chỉ VN có thể 2 cấp (Tỉnh+Phường) hay 3 cấp.
            recipient: { province?: string; district?: string; ward?: string; address?: string };
        }): Promise<QuoteAllItem[]> => {
            const { data } = await api!.post<{ data: QuoteAllItem[] }>('/fulfillment/quote-all', vars);
            return data.data;
        },
    });
}
```

- [ ] **Step 2: Typecheck**

Run (từ `app/`): `npm run typecheck`
Expected: có thể báo lỗi ở `CreateOrderPage.tsx` nếu file đó còn import `useShippingQuote`/`ShippingQuote` — bỏ qua ở bước này (Task 6 sẽ xoá import cũ), miễn `lib/fulfillment.tsx` tự nó không lỗi cú pháp/type nội tại. Nếu muốn typecheck sạch hoàn toàn ở bước này, chạy `npx tsc --noEmit resources/js/lib/fulfillment.tsx` KHÔNG khả thi (cần cả project context) — chấp nhận lỗi tạm ở `CreateOrderPage.tsx` cho tới hết Task 6, ghi rõ trong báo cáo.

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/lib/fulfillment.tsx
git commit -m "feat(fulfillment): hook useShippingQuoteAll thay useShippingQuote đơn-tài-khoản"
```

---

### Task 5: Frontend — component `ShippingQuoteModal`

**Files:**
- Create: `app/resources/js/components/ShippingQuoteModal.tsx`

**Interfaces:**
- Consumes: `useShippingQuoteAll` + `QuoteAllItem` (Task 4, từ `@/lib/fulfillment`); `CarrierLogo` (đã có, từ `@/components/CarrierLogo`, props `{ code: string; size?: number; rounded?: boolean }`).
- Produces: `export function ShippingQuoteModal({ open, onClose, recipient }: { open: boolean; onClose: () => void; recipient: { province?: string; district?: string; ward?: string; address?: string } })`. Task 6 mount trực tiếp trong `CreateOrderPage.tsx`.

- [ ] **Step 1: Viết component**

Tạo `app/resources/js/components/ShippingQuoteModal.tsx`:

```tsx
import { useEffect } from 'react';
import { Modal, Skeleton, Table, Typography, Empty } from 'antd';
import { CarrierLogo } from '@/components/CarrierLogo';
import { useShippingQuoteAll, type QuoteAllItem } from '@/lib/fulfillment';

const vnd = (n: number) => `${(n || 0).toLocaleString('vi-VN')} đ`;

/**
 * Modal tra cứu cước vận chuyển THAM KHẢO (SPEC 2026-07-13) — liệt kê cước từ mọi tài khoản ĐVVC active
 * của tenant cho địa chỉ nhận hiện tại. Không có hành động "Áp dụng" — chỉ xem, đóng bằng nút Đóng/X.
 */
export function ShippingQuoteModal({ open, onClose, recipient }: {
    open: boolean;
    onClose: () => void;
    recipient: { province?: string; district?: string; ward?: string; address?: string };
}) {
    const quoteAll = useShippingQuoteAll();

    useEffect(() => {
        if (open) quoteAll.mutate({ recipient });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]);

    const rows = quoteAll.data ?? [];

    return (
        <Modal open={open} onCancel={onClose} onOk={onClose} okText="Đóng" cancelButtonProps={{ style: { display: 'none' } }}
            title="Tra cứu cước vận chuyển (tham khảo)" width={640}>
            {quoteAll.isPending ? (
                <Skeleton active paragraph={{ rows: 4 }} />
            ) : rows.length === 0 ? (
                <Empty description="Chưa có tài khoản ĐVVC nào hỗ trợ tính cước" />
            ) : (
                <Table
                    rowKey={(r) => `${r.carrier_account_id}-${r.service_name ?? 'default'}`}
                    dataSource={rows}
                    pagination={false}
                    size="small"
                    columns={[
                        {
                            title: 'Đơn vị vận chuyển', dataIndex: 'account_name',
                            render: (_, r: QuoteAllItem) => (
                                <span style={{ display: 'inline-flex', alignItems: 'center', gap: 8 }}>
                                    <CarrierLogo code={r.carrier} size={20} />
                                    <span>{r.account_name}</span>
                                </span>
                            ),
                        },
                        { title: 'Gói', dataIndex: 'service_name', render: (v: string | null) => v ?? '—' },
                        {
                            title: 'Cước', dataIndex: 'fee', align: 'right',
                            render: (_, r: QuoteAllItem) => r.error
                                ? <Typography.Text type="danger">{r.error}</Typography.Text>
                                : <b>{vnd(r.fee ?? 0)}</b>,
                        },
                        {
                            title: 'Phí khai giá', dataIndex: 'insurance_fee', align: 'right',
                            render: (_, r: QuoteAllItem) => r.error ? '—' : vnd(r.insurance_fee ?? 0),
                        },
                        { title: 'Thời gian', dataIndex: 'eta', render: (v: string | null) => v ?? '—' },
                    ]}
                />
            )}
        </Modal>
    );
}
```

- [ ] **Step 2: Typecheck**

Run: `npm run typecheck`
Expected: không lỗi mới trong `ShippingQuoteModal.tsx` (import `QuoteAllItem`/`useShippingQuoteAll` đã tồn tại từ Task 4).

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/components/ShippingQuoteModal.tsx
git commit -m "feat(fulfillment): component ShippingQuoteModal hiển thị cước tham khảo tất cả ĐVVC"
```

---

### Task 6: Frontend — gắn nút + modal vào `CreateOrderPage.tsx`

**Files:**
- Modify: `app/resources/js/pages/CreateOrderPage.tsx`

**Interfaces:**
- Consumes: `ShippingQuoteModal` (Task 5, từ `@/components/ShippingQuoteModal`); biến `shipAddress` đã có sẵn trong component.
- Produces: không (chỉ wiring UI).

- [ ] **Step 1: Thêm import**

Tìm dòng (trong khối import ở đầu file):

```tsx
import { AddressPicker, type PickedAddress } from '@/components/AddressPicker';
```

Thêm ngay sau:

```tsx
import { AddressPicker, type PickedAddress } from '@/components/AddressPicker';
import { ShippingQuoteModal } from '@/components/ShippingQuoteModal';
```

- [ ] **Step 2: Thêm biến điều kiện đủ địa chỉ + state modal**

Tìm khối tính `addressBlock` (bắt đầu bằng `const addressBlock = (`), tìm dòng chứa:

```tsx
    // Khối địa chỉ nhận (autocomplete + picker Tỉnh/Quận/Phường) — dùng chung cho cả card "Nhận hàng"
    // (full) lẫn card gộp "Khách hàng / Nhận hàng" (compact, khung chat).
    const addressBlock = (
```

Thay bằng (thêm 2 dòng `useMemo` + `useState` phía trước, giữ nguyên `const addressBlock = (` và phần bên trong):

```tsx
    // SPEC 2026-07-13 — điều kiện "đủ thông tin" để hiện nút tra cứu cước: tỉnh + (quận/huyện HOẶC
    // phường/xã), tái dùng đúng logic đang validate ô địa chỉ picker (status warning) bên dưới.
    const recipientAddressReady = useMemo(() => {
        const old = shipAddress.format === 'old';
        return !!shipAddress.province && (!old || !!shipAddress.district || !!shipAddress.district_code) && (!!shipAddress.ward || !!shipAddress.ward_code);
    }, [shipAddress]);
    const [quoteModalOpen, setQuoteModalOpen] = useState(false);

    // Khối địa chỉ nhận (autocomplete + picker Tỉnh/Quận/Phường) — dùng chung cho cả card "Nhận hàng"
    // (full) lẫn card gộp "Khách hàng / Nhận hàng" (compact, khung chat).
    const addressBlock = (
```

- [ ] **Step 3: Thêm nút cạnh "Phí vận chuyển"**

Tìm:

```tsx
                                    <PayRow label="Phí vận chuyển" name="shipping_fee" disabled={!!summary?.free_shipping} />
```

Thay bằng:

```tsx
                                    <PayRow label="Phí vận chuyển" name="shipping_fee" disabled={!!summary?.free_shipping} />
                                    {recipientAddressReady && (
                                        <Button size="small" type="link" icon={<CalculatorOutlined />} style={{ padding: '0 0 8px' }}
                                            onClick={() => setQuoteModalOpen(true)}>Tra cứu cước vận chuyển</Button>
                                    )}
```

- [ ] **Step 4: Thêm import icon `CalculatorOutlined`**

Tìm dòng import icon từ `@ant-design/icons` (khối nhiều dòng bắt đầu `ArrowLeftOutlined, BarcodeOutlined, ...`), thêm `CalculatorOutlined` vào danh sách theo đúng thứ tự alphabet hiện có (chèn giữa `BarcodeOutlined` và `CheckCircleFilled`):

Tìm:
```tsx
    ArrowLeftOutlined, BarcodeOutlined, CheckCircleFilled, CloseCircleFilled,
```
Thay bằng:
```tsx
    ArrowLeftOutlined, BarcodeOutlined, CalculatorOutlined, CheckCircleFilled, CloseCircleFilled,
```

- [ ] **Step 5: Mount modal**

Tìm đoạn mount `OrderDetailModal` (đã có từ tính năng trước, gần cuối JSX return, sau sticky bottom bar):

```tsx
            <OrderDetailModal orderId={dupOrderModalId} open={dupOrderModalId != null} onClose={() => setDupOrderModalId(null)} />
```

Thay bằng:

```tsx
            <OrderDetailModal orderId={dupOrderModalId} open={dupOrderModalId != null} onClose={() => setDupOrderModalId(null)} />
            <ShippingQuoteModal open={quoteModalOpen} onClose={() => setQuoteModalOpen(false)} recipient={{
                province: shipAddress.province, district: shipAddress.district, ward: shipAddress.ward, address: (form.getFieldValue('recipient_address') as string) || undefined,
            }} />
```

- [ ] **Step 6: Typecheck + lint + build**

Run: `npm run typecheck && npm run lint && npm run build`
Expected: sạch — đặc biệt `npm run lint` không còn báo `useShippingQuote`/`ShippingQuote` không tồn tại (đã xoá ở Task 4) hay import thừa nào.

- [ ] **Step 7: Verify thủ công qua trình duyệt**

Mở `/orders/new`: (1) chưa nhập địa chỉ nhận → nút "Tra cứu cước vận chuyển" KHÔNG hiện; (2) nhập đủ tỉnh + quận/huyện (hoặc phường/xã) → nút hiện; (3) bấm nút → modal mở, hiện bảng cước theo số tài khoản ĐVVC dev đang có (VTP nếu nhiều gói thì nhiều dòng); (4) modal không có nút "Áp dụng", đóng bằng nút Đóng.

- [ ] **Step 8: Commit**

```bash
git add app/resources/js/pages/CreateOrderPage.tsx
git commit -m "feat(fulfillment): nút tra cứu cước vận chuyển tham khảo ở tạo đơn thủ công"
```

---

## Self-Review

**Spec coverage:**
- GHN có `quote()` mới, 1 mức giá, không tên gói → Task 1. ✅
- GHTK/VTP `quote()` đọc cân nặng từ `meta.defaults.package` → Task 2. ✅
- VTP trả đủ nhiều gói (không cắt còn 1) → Task 2 test + Task 3 aggregation giữ nguyên mảng. ✅
- Endpoint gộp `quote-all`, xoá endpoint cũ → Task 3. ✅
- Nút chỉ hiện khi đủ địa chỉ, modal thuần tham khảo không Áp dụng → Task 6. ✅
- Testing (Unit/Feature backend qua Http::fake, FE typecheck/lint/build/manual) → mỗi task có bước riêng. ✅

**Placeholder scan:** không TBD/TODO. Task 2 Step 5 có 1 đoạn ghi chú "nếu vẫn fail... điều chỉnh fake" — đây là hướng dẫn xử lý rủi ro thật (resolver VTP gọi thêm API danh mục địa danh chưa biết fixture chính xác tới khi chạy thử), không phải placeholder che giấu thiếu sót; nội dung sửa (nếu cần) đã trỏ đúng file cần đọc (`ViettelPostAddressResolver.php`).

**Type consistency:** `QuoteAllItem` (Task 4) khớp field `account_name/service_name/fee/insurance_fee/eta/error` dùng trong `ShippingQuoteModal` (Task 5) và trong response `ShipmentService::quoteAllShippingFees()` (Task 3, cùng field names). `useShippingQuoteAll(): Promise<QuoteAllItem[]>` khớp cách gọi ở Task 5 (`quoteAll.mutate({ recipient })`, `quoteAll.data`).
