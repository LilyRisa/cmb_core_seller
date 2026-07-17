# J&T Express Carrier Integration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add J&T Express as a new `CarrierConnector` in the Fulfillment integration layer, following the exact Connector+Registry pattern already used by GHN/GHTK/Viettel Post/Ahamove — shipped inert (no working credentials yet) behind `JT_API_ACCOUNT`/`JT_PRIVATE_KEY`.

**Architecture:** `JtExpressSigner` (pure request-signing logic, isolated because the exact signing formula is unverified against a real J&T account — see Global Constraints) + `JtExpressStatusMap` (pure status mapping, covers both J&T's published status table and the wider set of codes seen in J&T's own real response examples) + `JtExpressClient` (thin HTTP client, form-encoded requests) + `JtExpressConnector` (implements `CarrierConnector` via `AbstractCarrierConnector`, wires the three together). Registered in `CarrierRegistry` via `IntegrationsServiceProvider`; webhook reuses the existing generic `tracking_lookup` auth mode in `CarrierWebhookController` — zero changes to shared core files. Address handling uses ONLY J&T's `selfAddress=1` mode (new 2-level national administrative addresses) — no ID-resolution service needed (unlike GHN/VTP), because J&T accepts plain province/ward name strings in this mode.

**Tech Stack:** Laravel 11 (PHP), `Illuminate\Support\Facades\Http` fake-based testing, React/Ant Design (`resources/js/pages/CarrierAccountsPage.tsx`).

## Global Constraints

- Spec: `docs/specs/0042-jt-express-carrier-integration.md` — every behavior below traces back to it. Source data: `docs/superpowers/research/2026-07-17-jt-express-api-reference.md` (full crawl of J&T's Open API docs).
- Core golden rule: core never hard-codes a carrier name. No changes to `CarrierWebhookController`, `CarrierConnector` interface, or `ShipmentService` core logic.
- Carrier code is `'jt'`. Namespace/folder is `CMBcoreSeller\Integrations\Carriers\JtExpress` (`app/app/Integrations/Carriers/JtExpress/`) — this exact name is already referenced as a commented-out placeholder in `IntegrationsServiceProvider.php`, keep it consistent.
- Money = integer VND everywhere (no floats) except J&T's own `packageInfo.weight`/`getComCost.weight` (string-encoded KG, J&T's own API requirement).
- All PHP commands run from `app/` (`cd app` first). PSR-4 `CMBcoreSeller\` maps to `app/app/`.
- Quality gate before calling any task "done": `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test --filter=<relevant>`.
- User-facing strings Vietnamese; code/identifiers English.
- **Signing formula is UNVERIFIED** (J&T's public docs don't specify how `privateKey` is concatenated before MD5, nor how `password` should be encoded). `JtExpressSigner` implements the literal documented formula (`json_encode($bizContent) . $privateKey`, raw MD5 bytes, then base64) with a prominent comment — do not treat this as confirmed correct; it can only be verified once a real UAT account exists (tracked in SPEC 0042 §11, out of scope for this plan to resolve).
- Connector must be **inert** (not throw uncaught 500s) when `config('integrations.jt.api_account')` or `config('integrations.jt.private_key')` is empty — the default in every environment until J&T issues real credentials.
- Only `selfAddress=1` (national administrative addresses) is supported — never send `city`, only `prov` (province name) + `area` (ward name) + `address` (street detail).
- `payType` is a **per-account** setting (`CarrierAccount.meta.pay_type`, `'PP_CASH'` default | `'PP_PM'`), not hard-coded — chosen via a Radio in the account form, per UI convention (`ui-avoid-select-prefer-radio` — Radio.Group over Select for small option sets).

---

### Task 1: `JtExpressSigner`

**Files:**
- Create: `app/app/Integrations/Carriers/JtExpress/JtExpressSigner.php`
- Test: `app/tests/Unit/Carriers/JtExpressSignerTest.php`

**Interfaces:**
- Produces: `JtExpressSigner::sign(string $bizContentJson, string $privateKey): string` — used by Task 3 (`JtExpressClient`).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Carriers;

use CMBcoreSeller\Integrations\Carriers\JtExpress\JtExpressSigner;
use Tests\TestCase;

class JtExpressSignerTest extends TestCase
{
    // J&T docs: "digest=base64(md5(business params Json+privateKey))" — first MD5 to byte array, then
    // base64-encode. No confirmed expected value exists yet (no real UAT account) — these tests assert
    // the documented PROPERTIES of the formula, not a specific golden output. See Global Constraints.

    public function test_sign_is_deterministic_for_same_input(): void
    {
        $a = JtExpressSigner::sign('{"customerCode":"024E000014"}', 'Z354nbj1');
        $b = JtExpressSigner::sign('{"customerCode":"024E000014"}', 'Z354nbj1');

        $this->assertSame($a, $b);
    }

    public function test_sign_changes_when_biz_content_changes(): void
    {
        $a = JtExpressSigner::sign('{"customerCode":"024E000014"}', 'Z354nbj1');
        $b = JtExpressSigner::sign('{"customerCode":"024E000015"}', 'Z354nbj1');

        $this->assertNotSame($a, $b);
    }

    public function test_sign_changes_when_private_key_changes(): void
    {
        $a = JtExpressSigner::sign('{"customerCode":"024E000014"}', 'Z354nbj1');
        $b = JtExpressSigner::sign('{"customerCode":"024E000014"}', 'DIFFERENT-KEY');

        $this->assertNotSame($a, $b);
    }

    public function test_sign_returns_base64_of_a_16_byte_md5_digest(): void
    {
        $digest = JtExpressSigner::sign('{"a":1}', 'key');
        $raw = base64_decode($digest, true);

        $this->assertNotFalse($raw, 'digest phải là base64 hợp lệ');
        $this->assertSame(16, strlen($raw), 'MD5 raw digest luôn 16 byte');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd app && php artisan test --filter=JtExpressSignerTest`
Expected: FAIL — class `CMBcoreSeller\Integrations\Carriers\JtExpress\JtExpressSigner` not found.

- [ ] **Step 3: Write the implementation**

```php
<?php

namespace CMBcoreSeller\Integrations\Carriers\JtExpress;

/**
 * Ký request J&T Express Open API. Theo tài liệu (open.jtexpress.vn/apiDoc): "digest=base64(md5(business
 * params Json+privateKey))" — MD5 trước ra mảng byte, base64-encode mảng byte đó (KHÔNG phải base64 của
 * chuỗi hex). Cách nối `privateKey` (cuối chuỗi JSON hay field riêng) và cách encode `password` bên trong
 * `bizContent` KHÔNG được tài liệu J&T xác nhận rõ — implement literal theo mô tả, đánh dấu CHƯA VERIFY với
 * tài khoản UAT thật (SPEC 0042 §11). Đây là NƠI DUY NHẤT cần sửa nếu có tài khoản thật để xác nhận công
 * thức khác.
 */
final class JtExpressSigner
{
    public static function sign(string $bizContentJson, string $privateKey): string
    {
        return base64_encode(md5($bizContentJson.$privateKey, true));
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd app && php artisan test --filter=JtExpressSignerTest`
Expected: PASS (4 tests).

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Carriers/JtExpress/JtExpressSigner.php app/tests/Unit/Carriers/JtExpressSignerTest.php
git commit -m "feat(fulfillment): add JtExpressSigner (unverified formula, see SPEC 0042)"
```

---

### Task 2: `JtExpressStatusMap`

**Files:**
- Create: `app/app/Integrations/Carriers/JtExpress/JtExpressStatusMap.php`
- Test: `app/tests/Unit/Fulfillment/JtExpressStatusMapTest.php`

**Interfaces:**
- Produces: `JtExpressStatusMap::toShipmentStatus(int|string|null $scanTypeCode): ?string` — used by Task 4 (`JtExpressConnector::getTracking`/`parseWebhook`).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\JtExpress\JtExpressStatusMap;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use Tests\TestCase;

class JtExpressStatusMapTest extends TestCase
{
    public function test_maps_documented_status_table(): void
    {
        // Bảng công bố ở trang Webhook/Tracking Query (open.jtexpress.vn/apiDoc/logistics/statusFeedback).
        $cases = [
            103 => Shipment::STATUS_CREATED,       // Order Placed
            104 => Shipment::STATUS_FAILED,        // Pickup Failure
            105 => Shipment::STATUS_CANCELLED,     // Cancel Order
            106 => Shipment::STATUS_PICKED_UP,     // Picked Up
            109 => Shipment::STATUS_IN_TRANSIT,    // Departure
            110 => Shipment::STATUS_IN_TRANSIT,    // Arrival
            112 => Shipment::STATUS_IN_TRANSIT,    // On Delivery
            113 => Shipment::STATUS_DELIVERED,     // Delivered
            116 => Shipment::STATUS_RETURNING,     // Returning
            117 => Shipment::STATUS_RETURNED,      // Returned Sign
            118 => Shipment::STATUS_FAILED,        // Delivery Problem
        ];
        foreach ($cases as $code => $expected) {
            $this->assertSame($expected, JtExpressStatusMap::toShipmentStatus($code), "code {$code}");
        }
    }

    public function test_maps_real_world_codes_not_in_the_published_table(): void
    {
        // Ví dụ response THẬT trong tài liệu J&T (Tracking Query) dùng bộ scanTypeCode khác hẳn bảng công
        // bố 103-121 — cùng ý nghĩa vĩ mô (nhận/vận chuyển/giao) nhưng mã số khác. Xem file tham khảo
        // docs/superpowers/research/2026-07-17-jt-express-api-reference.md §5.3.
        $cases = [
            10 => Shipment::STATUS_PICKED_UP,   // "Nhận hàng" / 快件揽收
            50 => Shipment::STATUS_IN_TRANSIT,  // "Gửi hàng"
            92 => Shipment::STATUS_IN_TRANSIT,  // "Hàng đến"
            94 => Shipment::STATUS_IN_TRANSIT,  // "Quét phát hàng"
            100 => Shipment::STATUS_DELIVERED,  // "Ký nhận"
        ];
        foreach ($cases as $code => $expected) {
            $this->assertSame($expected, JtExpressStatusMap::toShipmentStatus($code), "code {$code}");
        }
    }

    public function test_unmapped_codes_return_null_not_a_guess(): void
    {
        // 120 (Return Problem) và 121 (FINISH, chỉ là marker kết thúc — trạng thái thật đã set qua 113/117
        // trước đó) cố ý KHÔNG map — an toàn hơn đoán sai. Mã lạ hoàn toàn cũng phải trả null.
        $this->assertNull(JtExpressStatusMap::toShipmentStatus(120));
        $this->assertNull(JtExpressStatusMap::toShipmentStatus(121));
        $this->assertNull(JtExpressStatusMap::toShipmentStatus(999999));
    }

    public function test_null_or_empty_input_returns_null(): void
    {
        $this->assertNull(JtExpressStatusMap::toShipmentStatus(null));
        $this->assertNull(JtExpressStatusMap::toShipmentStatus(''));
    }

    public function test_accepts_numeric_string_code(): void
    {
        $this->assertSame(Shipment::STATUS_DELIVERED, JtExpressStatusMap::toShipmentStatus('113'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd app && php artisan test --filter=JtExpressStatusMapTest`
Expected: FAIL — class not found.

- [ ] **Step 3: Write the implementation**

```php
<?php

namespace CMBcoreSeller\Integrations\Carriers\JtExpress;

use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;

/**
 * Map `scanTypeCode` (Tracking Query + Webhook) sang trạng thái shipment chuẩn. Nguồn:
 * docs/superpowers/research/2026-07-17-jt-express-api-reference.md §5.3.
 *
 * ⚠️ J&T công bố bảng 103-121 nhưng ví dụ response THẬT của chính họ dùng bộ mã khác hẳn (10/50/92/94/100)
 * cho cùng ý nghĩa — 2 bộ mã không phải tập con của nhau. Map CẢ HAI. Mã lạ (kể cả 120/121, xem test) →
 * null thay vì đoán — CarrierWebhookController coi null là "không đổi status", chỉ ghi event. Bổ sung dần
 * khi gặp mã mới qua log thật (xem JtExpressConnector::getTracking/parseWebhook).
 */
final class JtExpressStatusMap
{
    /** @var array<int, string> */
    private const MAP = [
        // Bảng công bố.
        103 => Shipment::STATUS_CREATED,
        104 => Shipment::STATUS_FAILED,
        105 => Shipment::STATUS_CANCELLED,
        106 => Shipment::STATUS_PICKED_UP,
        109 => Shipment::STATUS_IN_TRANSIT,
        110 => Shipment::STATUS_IN_TRANSIT,
        112 => Shipment::STATUS_IN_TRANSIT,
        113 => Shipment::STATUS_DELIVERED,
        116 => Shipment::STATUS_RETURNING,
        117 => Shipment::STATUS_RETURNED,
        118 => Shipment::STATUS_FAILED,
        // 120 (Return Problem), 121 (FINISH — chỉ là marker) cố ý KHÔNG map, xem docblock lớp.
        // Mã thực tế quan sát trong ví dụ response thật (khác bảng công bố).
        10 => Shipment::STATUS_PICKED_UP,
        50 => Shipment::STATUS_IN_TRANSIT,
        92 => Shipment::STATUS_IN_TRANSIT,
        94 => Shipment::STATUS_IN_TRANSIT,
        100 => Shipment::STATUS_DELIVERED,
    ];

    public static function toShipmentStatus(int|string|null $scanTypeCode): ?string
    {
        if ($scanTypeCode === null || $scanTypeCode === '') {
            return null;
        }

        return self::MAP[(int) $scanTypeCode] ?? null;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd app && php artisan test --filter=JtExpressStatusMapTest`
Expected: PASS (5 tests).

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Carriers/JtExpress/JtExpressStatusMap.php app/tests/Unit/Fulfillment/JtExpressStatusMapTest.php
git commit -m "feat(fulfillment): add JtExpressStatusMap"
```

---

### Task 3: `JtExpressClient`

**Files:**
- Create: `app/app/Integrations/Carriers/JtExpress/JtExpressClient.php`
- Test: `app/tests/Unit/Carriers/JtExpressClientTest.php`

**Interfaces:**
- Consumes: `JtExpressSigner::sign()` (Task 1).
- Produces (used by Task 4):
  - `new JtExpressClient(string $apiAccount, string $privateKey, ?string $baseUrl = null)`
  - `addOrder(array $bizContent): array`
  - `cancelOrder(array $bizContent): array`
  - `getComCost(array $bizContent): array`
  - `printOrder(array $bizContent): array`
  - `trace(array $bizContent): array`
  - Each returns the decoded `data` payload; throws `RuntimeException` (message = J&T's `msg`) when `code !== '1'`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Carriers;

use CMBcoreSeller\Integrations\Carriers\JtExpress\JtExpressClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class JtExpressClientTest extends TestCase
{
    private function client(): JtExpressClient
    {
        return new JtExpressClient('669375073659916329', '6e93e0d4344e47f0a4af7e4e75af955e', 'https://demoopenapi.jtexpress.vn/webopenplatformapi');
    }

    public function test_add_order_posts_form_fields_and_returns_data(): void
    {
        $captured = null;
        Http::fake(function ($req) use (&$captured) {
            $captured = $req->data();

            return Http::response(['code' => '1', 'msg' => 'success', 'data' => [
                'txlogisticId' => '123456789101', 'billCode' => '802400616352', 'sortLine' => '800-028A04-',
                'inquiryFee' => 15, 'codFee' => 0, 'insuranceFee' => 0,
            ]]);
        });

        $data = $this->client()->addOrder(['customerCode' => '024E000014', 'txlogisticId' => '123456789101']);

        $this->assertSame('802400616352', $data['billCode']);
        $this->assertSame('669375073659916329', $captured['apiAccount']);
        $this->assertArrayHasKey('digest', $captured);
        $this->assertArrayHasKey('timestamp', $captured);
        $biz = json_decode($captured['bizContent'], true);
        $this->assertSame('024E000014', $biz['customerCode']);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/api/order/addOrder'));
    }

    public function test_cancel_order_posts_correct_path(): void
    {
        Http::fake(['*/api/order/cancelOrder' => Http::response(['code' => '1', 'msg' => 'success', 'data' => ['txlogisticId' => 'X', 'billCode' => 'X']])]);

        $data = $this->client()->cancelOrder(['txlogisticId' => 'X', 'reason' => 'test']);

        $this->assertSame('X', $data['billCode']);
    }

    public function test_get_com_cost_posts_correct_path(): void
    {
        Http::fake(['*/api/spmComCost/getComCost' => Http::response(['code' => '1', 'msg' => 'success', 'data' => ['price' => 100000, 'codFee' => 0, 'insuranceFee' => 5]])]);

        $data = $this->client()->getComCost(['weight' => 10]);

        $this->assertSame(100000, $data['price']);
    }

    public function test_print_order_posts_correct_path(): void
    {
        Http::fake(['*/api/order/printOrder' => Http::response(['code' => '1', 'msg' => 'success', 'data' => [
            'txlogisticId' => 'X', 'billCode' => 'B1', 'base64EncodeContent' => base64_encode('%PDF-FAKE'),
        ]])]);

        $data = $this->client()->printOrder(['txlogisticId' => 'X']);

        $this->assertSame('B1', $data['billCode']);
    }

    public function test_trace_posts_correct_path_and_returns_list(): void
    {
        Http::fake(['*/api/logistics/trace' => Http::response(['code' => '1', 'msg' => 'success', 'data' => [
            ['billCode' => 'B1', 'details' => [['scanTime' => '2024-06-05 15:57:04', 'scanTypeCode' => 113]]],
        ]])]);

        $data = $this->client()->trace(['billcodes' => 'B1']);

        $this->assertSame('B1', $data[0]['billCode']);
    }

    public function test_throws_runtime_exception_with_jt_message_on_error_code(): void
    {
        Http::fake(['*/api/order/addOrder' => Http::response(['code' => '999001030', 'msg' => 'customerCode or password is wrong'])]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('customerCode or password is wrong');

        $this->client()->addOrder(['customerCode' => 'bad']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd app && php artisan test --filter=JtExpressClientTest`
Expected: FAIL — class not found.

- [ ] **Step 3: Write the implementation**

```php
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
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd app && php artisan test --filter=JtExpressClientTest`
Expected: PASS (6 tests).

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Carriers/JtExpress/JtExpressClient.php app/tests/Unit/Carriers/JtExpressClientTest.php
git commit -m "feat(fulfillment): add JtExpressClient"
```

---

### Task 4: `JtExpressConnector` — quote + createShipment

**Files:**
- Create: `app/app/Integrations/Carriers/JtExpress/JtExpressConnector.php`
- Test: `app/tests/Feature/Fulfillment/JtExpressConnectorTest.php`

**Interfaces:**
- Consumes: `JtExpressClient` (Task 3), `JtExpressStatusMap` (Task 2), `CMBcoreSeller\Integrations\Carriers\Support\AbstractCarrierConnector`.
- Produces (used by Task 5/6/7): `JtExpressConnector` class implementing `code()`, `displayName()`, `capabilities()`, `webhookAuthMode()`, `quote()`, `createShipment()`, `cancel()`, `getLabel()`, `getTracking()`, `parseWebhook()`, `verifyCredentials()`. `capabilities()` returns `['createShipment', 'cancel', 'quote', 'getLabel', 'getTracking', 'webhook']`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\JtExpress\JtExpressConnector;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class JtExpressConnectorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('integrations.jt.api_account', 'TEST-ACC');
        Config::set('integrations.jt.private_key', 'TEST-KEY');
    }

    private function account(array $overrides = []): array
    {
        return array_replace_recursive([
            'id' => 1, 'carrier' => 'jt',
            'credentials' => ['customerCode' => '024E000014', 'password' => 'secret'],
            'default_service' => null,
            'meta' => [
                'pay_type' => 'PP_CASH',
                'from_address' => [
                    'name' => 'CMBcore Shop', 'phone' => '0901234567', 'address' => '7/28 Thành Thái',
                    'province_name' => 'Hồ Chí Minh', 'ward_name' => 'Phường 14',
                ],
            ],
        ], $overrides);
    }

    public function test_quote_returns_fee_from_getcomcost(): void
    {
        $captured = null;
        Http::fake(function ($req) use (&$captured) {
            $captured = $req->data();

            return Http::response(['code' => '1', 'msg' => 'success', 'data' => ['price' => 100000, 'codFee' => 0, 'insuranceFee' => 5]]);
        });

        $result = (new JtExpressConnector)->quote($this->account(), [
            'recipient' => ['province' => 'Hà Nội', 'ward' => 'Phường Hàng Trống'],
        ]);

        $this->assertCount(1, $result);
        $this->assertSame(100000, $result[0]['fee']);
        $biz = json_decode($captured['bizContent'], true);
        $this->assertSame('Hồ Chí Minh', $biz['sender']['prov']);
        $this->assertSame('Hà Nội', $biz['receiver']['prov']);
        $this->assertSame(1, $biz['selfAddress']);
        $this->assertArrayNotHasKey('city', $biz['sender']);
    }

    public function test_quote_returns_empty_when_recipient_address_missing(): void
    {
        $this->assertSame([], (new JtExpressConnector)->quote($this->account(), ['recipient' => []]));
    }

    public function test_quote_returns_empty_when_from_address_missing(): void
    {
        $account = $this->account();
        $account['meta']['from_address'] = [];

        $this->assertSame([], (new JtExpressConnector)->quote($account, ['recipient' => ['province' => 'Hà Nội', 'ward' => 'X']]));
    }

    public function test_create_shipment_posts_addorder_and_returns_tracking(): void
    {
        $captured = null;
        Http::fake(function ($req) use (&$captured) {
            $captured = $req->data();

            return Http::response(['code' => '1', 'msg' => 'success', 'data' => [
                'txlogisticId' => 'ORD1', 'billCode' => '802400616352', 'sortLine' => '800-028A04-',
                'inquiryFee' => 15, 'codFee' => 0, 'insuranceFee' => 20000,
            ]]);
        });

        $result = (new JtExpressConnector)->createShipment($this->account(), [
            'client_order_code' => 'ORD1',
            'recipient' => ['name' => 'Trần B', 'phone' => '0912345000', 'address' => '475A Điện Biên Phủ', 'province' => 'Hồ Chí Minh', 'ward' => 'Phường 25'],
            'parcel' => ['weight_grams' => 800],
            'cod_amount' => 150000,
            'items' => [['name' => 'Áo M', 'quantity' => 2, 'price' => 150000]],
        ]);

        $this->assertSame('802400616352', $result['tracking_no']);
        $this->assertSame('jt', $result['carrier']);
        $biz = json_decode($captured['bizContent'], true);
        $this->assertSame('ORD1', $biz['txlogisticId']);
        $this->assertSame(1, $biz['selfAddress']);
        $this->assertArrayNotHasKey('city', $biz['receiver']);
        $this->assertSame('Phường 25', $biz['receiver']['area']);
        $this->assertSame('Hồ Chí Minh', $biz['sender']['prov']);
        $this->assertSame(150000, $biz['codMoney']);
        $this->assertSame('PP_CASH', $biz['payType']);
        $this->assertSame('EXPRESS', $biz['productType']);
    }

    public function test_create_shipment_uses_pay_type_from_account_meta(): void
    {
        Http::fake(['*' => Http::response(['code' => '1', 'msg' => 'success', 'data' => ['billCode' => 'B1']])]);
        $captured = null;
        Http::fake(function ($req) use (&$captured) {
            $captured = $req->data();

            return Http::response(['code' => '1', 'msg' => 'success', 'data' => ['billCode' => 'B1']]);
        });

        (new JtExpressConnector)->createShipment($this->account(['meta' => ['pay_type' => 'PP_PM']]), [
            'client_order_code' => 'ORD2',
            'recipient' => ['name' => 'X', 'phone' => '090', 'address' => 'test', 'province' => 'Hồ Chí Minh', 'ward' => 'Phường 1'],
        ]);

        $biz = json_decode($captured['bizContent'], true);
        $this->assertSame('PP_PM', $biz['payType']);
    }

    public function test_create_shipment_throws_clear_error_when_recipient_missing_ward(): void
    {
        $this->expectExceptionMessage('Tỉnh/Phường');

        (new JtExpressConnector)->createShipment($this->account(), [
            'client_order_code' => 'ORD3',
            'recipient' => ['name' => 'X', 'phone' => '090', 'address' => 'test', 'province' => 'Hồ Chí Minh'],
        ]);
    }

    public function test_create_shipment_throws_clear_error_when_from_address_missing(): void
    {
        $account = $this->account();
        $account['meta']['from_address'] = [];

        $this->expectExceptionMessage('kho gửi');

        (new JtExpressConnector)->createShipment($account, [
            'client_order_code' => 'ORD4',
            'recipient' => ['name' => 'X', 'phone' => '090', 'address' => 'test', 'province' => 'Hồ Chí Minh', 'ward' => 'Phường 1'],
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd app && php artisan test --filter=JtExpressConnectorTest`
Expected: FAIL — class `JtExpressConnector` not found.

- [ ] **Step 3: Write the implementation**

```php
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
            'packageInfo' => $packageInfo !== [] ? $packageInfo : null,
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
     * cho support J&T (vd `.../webhook/carriers/jt?secret=XXXX`) — đọc qua `$request->query('secret')`.
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
        $secret = (string) $request->query('secret', '');

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
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd app && php artisan test --filter=JtExpressConnectorTest`
Expected: PASS (7 tests). Also re-run Tasks 1–3 tests to confirm no regression: `php artisan test --filter=JtExpress`.

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Carriers/JtExpress/JtExpressConnector.php app/tests/Feature/Fulfillment/JtExpressConnectorTest.php
git commit -m "feat(fulfillment): add JtExpressConnector (quote/createShipment/cancel/getLabel/getTracking/webhook)"
```

---

### Task 5: Registry + config wiring, INERT behavior

**Files:**
- Modify: `app/app/Integrations/IntegrationsServiceProvider.php` (add import + `$carrierConnectors` entry, replacing the commented-out placeholder line)
- Modify: `app/config/integrations.php` (add `'jt'` config block)
- Test: `app/tests/Feature/Fulfillment/JtExpressInertConfigTest.php`

**Interfaces:**
- Consumes: `JtExpressConnector::class` (Task 4).
- Produces: `CarrierRegistry::for('jt')` resolves to `JtExpressConnector`; `config('integrations.jt.api_account')` / `config('integrations.jt.private_key')` / `config('integrations.jt.base_url')` / `config('integrations.jt.http.timeout')` available app-wide.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Carriers\JtExpress\JtExpressConnector;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class JtExpressInertConfigTest extends TestCase
{
    public function test_jt_connector_is_registered(): void
    {
        app(CarrierRegistry::class)->register('jt', JtExpressConnector::class);
        $registry = app(CarrierRegistry::class);

        $this->assertTrue($registry->has('jt'));
        $this->assertInstanceOf(JtExpressConnector::class, $registry->for('jt'));
        $this->assertSame(['createShipment', 'cancel', 'quote', 'getLabel', 'getTracking', 'webhook'], $registry->for('jt')->capabilities());
    }

    public function test_adding_jt_account_without_credentials_configured_is_inert_not_500(): void
    {
        Config::set('integrations.jt.api_account', '');
        Config::set('integrations.jt.private_key', '');
        app(CarrierRegistry::class)->register('jt', JtExpressConnector::class);

        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'JtShop']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);

        $res = $this->actingAs($owner)->withHeaders(['X-Tenant-Id' => (string) $tenant->getKey()])
            ->postJson('/api/v1/carrier-accounts', [
                'carrier' => 'jt', 'name' => 'J&T chính',
                'credentials' => ['customerCode' => '024E000014', 'password' => 'secret'],
                'meta' => ['pay_type' => 'PP_CASH', 'from_address' => ['name' => 'Shop', 'phone' => '0900000001', 'address' => 'Số 1', 'province_name' => 'Hồ Chí Minh', 'ward_name' => 'Phường 1']],
            ]);

        $res->assertCreated();   // không 500 — account vẫn tạo được, chỉ is_active=false
        $this->assertFalse((bool) $res->json('data.is_active'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd app && php artisan test --filter=JtExpressInertConfigTest`
Expected: FAIL — `JtExpressConnector` class exists (from Task 4) but is not yet wired into `config('integrations.jt.*')` defaults, so the second test may already pass while the base config keys are undefined; run it and confirm both tests fail or error for a config-related reason (missing `integrations.jt.*` keys), not because the class is missing.

- [ ] **Step 3: Write the implementation**

In `app/app/Integrations/IntegrationsServiceProvider.php`, add the import alphabetically among the `Carriers\` imports (`Ghn` → `Ghtk` → `JtExpress` → `Manual` → `ViettelPost`):

```php
use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Carriers\Ghn\GhnConnector;
use CMBcoreSeller\Integrations\Carriers\Ghtk\GhtkConnector;
use CMBcoreSeller\Integrations\Carriers\JtExpress\JtExpressConnector;
use CMBcoreSeller\Integrations\Carriers\Manual\ManualCarrierConnector;
use CMBcoreSeller\Integrations\Carriers\ViettelPost\ViettelPostConnector;
```

Then replace the commented-out placeholder line in `$carrierConnectors`:

```php
    protected array $carrierConnectors = [
        'manual' => ManualCarrierConnector::class,
        'ghn' => GhnConnector::class,
        'ghtk' => GhtkConnector::class,
        'viettelpost' => ViettelPostConnector::class,
        'jt' => JtExpressConnector::class,
    ];
```

In `app/config/integrations.php`, insert this block immediately before the `'throttle' => [` line (works whether or not an `'ahamove'` block from another plan has already been inserted between `'viettelpost'` and `'throttle'` — this anchor stays valid either way):

```php
    /*
    |--------------------------------------------------------------------------
    | J&T Express Open API — SPEC 0042
    |--------------------------------------------------------------------------
    |
    | api_account/private_key: cặp CẤP ỨNG DỤNG do J&T duyệt cho CMBcoreSeller (App Management trên J&T
    | Console) — 1 cặp cho cả platform, KHÔNG phải per-tenant (khác GHN/GHTK/VTP nơi mỗi tenant tự dán
    | token của họ; giống mô hình Ahamove). customerCode/password (per-tenant, merchant) sống ở
    | carrier_accounts.credentials. Rỗng api_account/private_key ⇒ connector TRƠ (inert): verifyCredentials()/
    | mọi thao tác trả lỗi rõ "chưa cấu hình" thay vì lỗi 500. base_url mặc định UAT — đổi sang
    | https://ylopenapi.jtexpress.vn/webopenplatformapi khi có credentials Production thật.
    |
    */
    'jt' => [
        'api_account' => env('JT_API_ACCOUNT', ''),
        'private_key' => env('JT_PRIVATE_KEY', ''),
        'base_url' => env('JT_BASE_URL', 'https://demoopenapi.jtexpress.vn/webopenplatformapi'),
        'http' => [
            'timeout' => (int) env('JT_HTTP_TIMEOUT', 20),
        ],
    ],

```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd app && php artisan test --filter=JtExpressInertConfigTest`
Expected: PASS (2 tests).

- [ ] **Step 5: Run the full carrier test suite to check no regression**

Run: `cd app && php artisan test --filter=Fulfillment`
Expected: PASS — existing GHN/GHTK/VTP tests unaffected (`jt` key addition is additive only).

- [ ] **Step 6: Commit**

```bash
git add app/app/Integrations/IntegrationsServiceProvider.php app/config/integrations.php app/tests/Feature/Fulfillment/JtExpressInertConfigTest.php
git commit -m "feat(fulfillment): register JtExpress connector, inert until JT_API_ACCOUNT/JT_PRIVATE_KEY are set"
```

---

### Task 6: Full feature test — order ship / label / cancel / webhook

**Files:**
- Test: `app/tests/Feature/Fulfillment/ManualOrderJtExpressFulfillmentTest.php`

**Interfaces:**
- Consumes: everything from Tasks 1–5. No production code changes in this task — pure test coverage of the already-implemented connector wired through the real `POST /api/v1/orders/{id}/ship`, `POST /api/v1/shipments/{id}/cancel`, `POST /webhook/carriers/jt` HTTP endpoints (mirrors `ManualOrderViettelPostFulfillmentTest`).

- [ ] **Step 1: Write the test**

```php
<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Carriers\JtExpress\JtExpressConnector;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Fulfillment\Models\CarrierAccount;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Full flow đơn tự tạo dùng J&T Express (SPEC 0042). Khác GHN/GHTK/VTP:
 *   - Xác thực 2 tầng: apiAccount/privateKey cấp platform (config) + customerCode/password per-tenant.
 *   - Địa chỉ: chỉ selfAddress=1, không cần resolver ID (prov/area gửi thẳng tên).
 *   - Webhook: body {billCode, details:[...]}, KHÔNG có secret chuẩn — luôn ack + log cảnh báo.
 */
class ManualOrderJtExpressFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Sku $sku;

    private CarrierAccount $jtAccount;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([PushStockForSku::class]);
        Config::set('integrations.jt.api_account', 'TEST-ACC');
        Config::set('integrations.jt.private_key', 'TEST-KEY');
        app(CarrierRegistry::class)->register('jt', JtExpressConnector::class);

        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'JtShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->sku = Sku::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'sku_code' => 'AO-01', 'name' => 'Áo thun', 'weight_grams' => 300,
        ]);
        app(InventoryLedgerService::class)->adjust((int) $this->tenant->getKey(), (int) $this->sku->getKey(), null, 50);

        $this->jtAccount = CarrierAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'carrier' => 'jt',
            'name' => 'J&T — Kho Q10',
            'credentials' => ['customerCode' => '024E000014', 'password' => 'secret', 'webhook_secret' => 'WHSECRET'],
            'is_default' => true,
            'is_active' => true,
            'meta' => [
                'pay_type' => 'PP_CASH',
                'from_address' => [
                    'name' => 'CMBcore Shop', 'phone' => '0901234567', 'address' => '7/28 Thành Thái',
                    'province_name' => 'Hồ Chí Minh', 'ward_name' => 'Phường 14',
                ],
            ],
        ]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function createManualOrderWithAddress(): int
    {
        $orderId = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/orders', [
            'buyer' => ['name' => 'Trần B', 'phone' => '0912345678', 'address' => '475A Điện Biên Phủ', 'province' => 'Hà Nội'],
            'items' => [['sku_id' => $this->sku->getKey(), 'name' => 'Áo M', 'quantity' => 2, 'unit_price' => 150000]],
            'shipping_fee' => 20000,
        ])->assertCreated()->json('data.id');

        $order = Order::withoutGlobalScope(TenantScope::class)->find($orderId);
        $order->shipping_address = array_merge((array) $order->shipping_address, [
            'province' => 'Hà Nội', 'ward' => 'Phường Hàng Trống', 'district' => null,
        ]);
        $order->save();

        return $orderId;
    }

    private function fakeJt(string $billCode = '802400616352'): void
    {
        Http::fake([
            '*/api/order/addOrder' => Http::response(['code' => '1', 'msg' => 'success', 'data' => [
                'txlogisticId' => 'internal', 'billCode' => $billCode, 'sortLine' => '800-028A04-',
                'inquiryFee' => 15, 'codFee' => 0, 'insuranceFee' => 20000,
            ]]),
            '*/api/order/printOrder' => Http::response(['code' => '1', 'msg' => 'success', 'data' => [
                'txlogisticId' => 'internal', 'billCode' => $billCode, 'base64EncodeContent' => base64_encode('%PDF-JT-FAKE'),
            ]]),
            '*/api/order/cancelOrder' => Http::response(['code' => '1', 'msg' => 'success', 'data' => ['txlogisticId' => 'internal', 'billCode' => $billCode]]),
        ]);
    }

    public function test_prepare_creates_jt_order_and_stores_label(): void
    {
        $this->fakeJt('JT000001');
        $orderId = $this->createManualOrderWithAddress();

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->jtAccount->getKey()])
            ->assertCreated()
            ->assertJsonPath('data.carrier', 'manual_jt')
            ->assertJsonPath('data.tracking_no', 'JT000001')
            ->assertJsonPath('data.status', 'created');

        $this->assertSame('processing', Order::withoutGlobalScope(TenantScope::class)->find($orderId)->status->value);

        $sh = Shipment::withoutGlobalScope(TenantScope::class)->where('tracking_no', 'JT000001')->first();
        $this->assertNotNull($sh->label_path, 'Tem J&T phải được lưu tự động khi tạo vận đơn.');

        Http::assertSent(function ($req) {
            if (! str_contains($req->url(), '/api/order/addOrder')) {
                return false;
            }
            $biz = json_decode($req->data()['bizContent'], true);

            return ($biz['receiver']['prov'] ?? null) === 'Hà Nội'
                && ($biz['receiver']['area'] ?? null) === 'Phường Hàng Trống'
                && ! isset($biz['receiver']['city'])
                && ($biz['payType'] ?? null) === 'PP_CASH'
                && (int) ($biz['selfAddress'] ?? -1) === 1;
        });
    }

    public function test_cancel_calls_cancel_order_with_reason(): void
    {
        $this->fakeJt('JT000002');
        $orderId = $this->createManualOrderWithAddress();
        $shipId = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->jtAccount->getKey()])
            ->json('data.id');

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/shipments/{$shipId}/cancel")->assertOk();

        Http::assertSent(function ($req) {
            if (! str_contains($req->url(), '/api/order/cancelOrder')) {
                return false;
            }
            $biz = json_decode($req->data()['bizContent'], true);

            return ($biz['billCode'] ?? null) === 'JT000002' && ! empty($biz['reason']);
        });
    }

    public function test_webhook_syncs_status_and_always_acks(): void
    {
        $this->fakeJt('JT000003');
        $orderId = $this->createManualOrderWithAddress();
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->jtAccount->getKey()])->assertCreated();

        // scanTypeCode 113 = Delivered. `?secret=` là quy ước riêng của app (nhúng vào URL tự cung cấp cho
        // J&T đăng ký) — không phải cơ chế J&T công bố, xem JtExpressConnector::parseWebhook.
        $this->postJson('/webhook/carriers/jt?secret=WHSECRET', [
            'billCode' => 'JT000003',
            'details' => [['scanTime' => '2026-07-17 10:00:00', 'scanTypeCode' => 113, 'desc' => 'Đã giao']],
        ])->assertOk()->assertJsonPath('data.acknowledged', true);

        $sh = Shipment::withoutGlobalScope(TenantScope::class)->where('tracking_no', 'JT000003')->first();
        $this->assertSame('delivered', $sh->status);
        $this->assertSame('delivered', Order::withoutGlobalScope(TenantScope::class)->find($orderId)->status->value);
    }

    public function test_webhook_rejects_mismatched_secret(): void
    {
        $this->fakeJt('JT000005');
        $orderId = $this->createManualOrderWithAddress();
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->jtAccount->getKey()]);

        $this->postJson('/webhook/carriers/jt?secret=WRONG', [
            'billCode' => 'JT000005',
            'details' => [['scanTime' => '2026-07-17 10:00:00', 'scanTypeCode' => 113]],
        ])->assertStatus(401)->assertJsonPath('error.code', 'WEBHOOK_SIGNATURE_INVALID');
    }

    public function test_webhook_idempotent_duplicates_no_op(): void
    {
        $this->fakeJt('JT000004');
        $orderId = $this->createManualOrderWithAddress();
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $this->jtAccount->getKey()]);

        $body = ['billCode' => 'JT000004', 'details' => [['scanTime' => '2026-07-17 09:00:00', 'scanTypeCode' => 106, 'desc' => 'Đã lấy hàng']]];
        $this->postJson('/webhook/carriers/jt', $body)->assertOk();
        $this->postJson('/webhook/carriers/jt', $body)->assertOk();

        $sh = Shipment::withoutGlobalScope(TenantScope::class)->where('tracking_no', 'JT000004')->first();
        $this->assertSame(1, $sh->events()->where('code', '106')->count(), 'Webhook trùng phải dedupe.');
    }

    public function test_webhook_without_billcode_acks_without_error(): void
    {
        $this->postJson('/webhook/carriers/jt', ['details' => [['scanTypeCode' => 106]]])
            ->assertOk()->assertJsonPath('data.acknowledged', true);
    }
}
```

- [ ] **Step 2: Run test to verify it fails, then investigate real failures (not just "class not found")**

Run: `cd app && php artisan test --filter=ManualOrderJtExpressFulfillmentTest`
Expected first run: likely mostly PASS since Tasks 1–5 are already implemented — this task is pure test-writing. If any test fails, read the failure message carefully:
- If `carrier` comes back `manual_jt` mismatch → check `ShipmentService`'s carrier-prefixing logic is generic (it already is — confirms wiring only, no change needed).
- If `label_path` is null → check `ShipmentService`'s "ship" flow actually calls `getLabel()` automatically when the connector supports it (it does for GHN/VTP — this test only confirms J&T is treated the same way via `capabilities()`).
- If the webhook status test fails to update `Order` status → re-check `CarrierWebhookController::syncOrderStatus`'s map includes `Shipment::STATUS_DELIVERED` (it already does, core untouched).

- [ ] **Step 3: Fix any real bugs found in Tasks 1–5 code (not the test) and re-run until green**

Run: `cd app && php artisan test --filter=ManualOrderJtExpressFulfillmentTest`
Expected: PASS (6 tests).

- [ ] **Step 4: Commit**

```bash
git add app/tests/Feature/Fulfillment/ManualOrderJtExpressFulfillmentTest.php
git commit -m "test(fulfillment): full order/label/cancel/webhook flow for J&T Express"
```

---

### Task 7: Frontend — J&T Express option in Carrier settings

**Files:**
- Modify: `app/resources/js/pages/CarrierAccountsPage.tsx` (several small edits, see below)

**Interfaces:**
- Consumes: nothing new from the backend (uses the existing generic carrier-account CRUD endpoints).
- Produces: J&T selectable in the "Thêm tài khoản ĐVVC" flow with `customerCode`/`password` credential fields, a `pay_type` Radio packed into `meta.pay_type`, and a "Địa chỉ kho hàng" section (reused generic fields — no J&T-specific address component needed, unlike GHN/VTP, because J&T takes plain province/ward name strings).

- [ ] **Step 1: Remove `jt` from `COMING_SOON`**

In `app/resources/js/pages/CarrierAccountsPage.tsx`, around line 60-65, remove the `jt` entry:

```tsx
const COMING_SOON: Array<{ code: string; name: string }> = [
    { code: 'spx', name: 'SPX Express' },
    { code: 'vnpost', name: 'VNPost' },
    { code: 'ahamove', name: 'Ahamove' },
];
```

- [ ] **Step 2: Add `jt` to `CRED_FIELDS`**

Around line 38-44, right after the `viettelpost` entry (before the closing `};` of `CRED_FIELDS`):

```tsx
    viettelpost: [
        { key: 'username', label: 'Tài khoản (Username)', required: false, placeholder: 'SĐT/Tài khoản Partner Viettel Post' },
        { key: 'password', label: 'Mật khẩu', required: false, placeholder: 'Mật khẩu tài khoản Partner', secret: true },
        { key: 'token', label: 'Hoặc Token web VTP', required: false, placeholder: 'Token tạo trên viettelpost.vn (nếu không dùng user/mật khẩu)', secret: true },
        { key: 'webhook_secret', label: 'Webhook secret (tuỳ chọn)', required: false, placeholder: 'Secret VTP gửi kèm webhook để xác thực', secret: true },
    ],
    // J&T Express: xác thực merchant per-tenant. apiAccount/privateKey cấp ứng dụng nằm ở config server
    // (integrations.jt.*), KHÔNG nhập ở đây. J&T không công bố cơ chế secret webhook nào — webhook_secret
    // ở đây là quy ước RIÊNG của app: seller tự nhúng giá trị này vào URL webhook (query `?secret=`) lúc
    // gửi cho J&T đăng ký, xem JtExpressConnector::parseWebhook.
    jt: [
        { key: 'customerCode', label: 'Mã khách hàng (customerCode)', required: true, placeholder: 'Do J&T cấp khi ký hợp đồng' },
        { key: 'password', label: 'Mật khẩu', required: true, placeholder: 'Mật khẩu tài khoản J&T', secret: true },
        { key: 'webhook_secret', label: 'Webhook secret (tuỳ chọn)', required: false, placeholder: 'Tự đặt, nhúng vào URL webhook gửi J&T (?secret=...)', secret: true },
    ],
};
```

- [ ] **Step 3: Add `jt: true` to `FROM_ADDRESS_REQUIRED`**

Around line 48:

```tsx
const FROM_ADDRESS_REQUIRED: Record<string, boolean> = { ghn: true, ghtk: true, viettelpost: true, jt: true };
```

- [ ] **Step 4: Add "Cách trả cước" Radio for J&T, right after the `default_service` field**

Around line 692-694, add a new conditional `Form.Item` right after the existing `default_service` one:

```tsx
                        <Form.Item name="default_service" label="Mã dịch vụ mặc định (tuỳ chọn)" extra="VD: 2 = GHN Standard service_type_id">
                            <Input placeholder="Để trống nếu chưa rõ" />
                        </Form.Item>

                        {code === 'jt' && (
                            <Form.Item name="jt_pay_type" label="Cách trả cước vận chuyển" extra="Trả trước tiền mặt phù hợp với hầu hết seller mới. Đối soát tháng chỉ dùng được nếu bạn đã ký hợp đồng riêng với J&T.">
                                <Radio.Group options={[
                                    { value: 'PP_CASH', label: 'Trả trước tiền mặt' },
                                    { value: 'PP_PM', label: 'Đối soát theo tháng' },
                                ]} />
                            </Form.Item>
                        )}
```

- [ ] **Step 5: Prefill `jt_pay_type` on edit, default to `PP_CASH` on create**

In the edit-prefill `useEffect` (around line 481-505), add `pay_type` to the `form.setFieldsValue` call:

```tsx
        form.setFieldsValue({
            name: state.edit.name,
            default_service: state.edit.default_service ?? undefined,
            is_default: state.edit.is_default,
            from_name: fa.name, from_phone: fa.phone, from_address: fa.address,
            from_province_name: fa.province_name, from_district_name: fa.district_name, from_ward_name: fa.ward_name,
            from_district_id: fa.district_id, from_ward_code: fa.ward_code,
            from_province_id: fa.province_id, from_ward_id: fa.ward_id,
            jt_pay_type: (state.edit.meta as Record<string, unknown> | undefined)?.pay_type ?? 'PP_CASH',
        });
```

Then, in the "Tạo mới" branch — find the `useEffect` right below it that runs `if (!state.open || isEdit || ...) return;` (around line 471-476, the one that auto-fills from the default sender profile) and add a sibling effect right after it that defaults `jt_pay_type` to `PP_CASH` for brand-new accounts:

```tsx
    useEffect(() => {
        if (!state.open || isEdit || code !== 'jt') return;
        form.setFieldsValue({ jt_pay_type: 'PP_CASH' });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [state.open, isEdit, code]);
```

- [ ] **Step 6: Pack `meta.pay_type` at submit**

In the `submit` function (around line 552-553), right after the `if (Object.keys(fromAddress).length > 0) meta.from_address = fromAddress;` line and before the `meta.defaults = {` assignment:

```tsx
            if (Object.keys(fromAddress).length > 0) meta.from_address = fromAddress;
        }
        if (code === 'jt') {
            meta.pay_type = v.jt_pay_type === 'PP_PM' ? 'PP_PM' : 'PP_CASH';
        }
```

- [ ] **Step 7: Typecheck, lint, build**

Run: `cd app && npm run typecheck && npm run lint && npm run build`
Expected: all green.

- [ ] **Step 8: Manual smoke check (per `verify` skill philosophy — exercise the actual UI)**

Run: `cd app && composer dev` (starts serve+queue+pail+vite), then in browser: Cài đặt → Đơn vị vận chuyển → Thêm tài khoản → J&T Express should appear as a selectable card (not in "sắp có"), form should show Mã khách hàng + Mật khẩu + Cách trả cước (Radio) + Địa chỉ kho fields. Since `JT_API_ACCOUNT`/`JT_PRIVATE_KEY` are empty in dev `.env`, saving should succeed but show a clear "chưa cấu hình" verify message — confirm no 500/white-screen.

- [ ] **Step 9: Commit**

```bash
git add app/resources/js/pages/CarrierAccountsPage.tsx
git commit -m "feat(fulfillment): J&T Express carrier option in settings UI"
```

---

### Task 8: Docs + final verification

**Files:**
- Modify: `docs/05-api/endpoints.md` — note that `carrier=jt` supports the same shipment/label/webhook shape as GHN/VTP, plus the `pay_type` account field.
- Modify: `docs/03-domain/fulfillment-and-printing.md` — add J&T to the list of supported carriers.
- Modify: `docs/specs/0042-jt-express-carrier-integration.md` — flip `Trạng thái` from `Draft` to `Implemented` once all tests are green.

**Interfaces:** None — documentation only.

- [ ] **Step 1: Update `05-api/endpoints.md`**

Find the section documenting carrier accounts (search for `viettelpost` or `default_service` in that doc) and add a line noting `carrier=jt` accounts accept `meta.pay_type` (`'PP_CASH'` | `'PP_PM'`) in addition to the common `credentials`/`meta.from_address` shape.

- [ ] **Step 2: Update `03-domain/fulfillment-and-printing.md`**

Find the list of supported carriers (GHN/GHTK/Viettel Post/Ahamove) and add: "J&T Express (SPEC 0042) — mô hình bưu cục như GHN/VTP (tem in + COD), xác thực 2 tầng như Ahamove (apiAccount/privateKey cấp platform + customerCode/password per-tenant). Chỉ hỗ trợ địa chỉ hành chính quốc gia mới (selfAddress=1). Trơ tới khi có JT_API_ACCOUNT/JT_PRIVATE_KEY thật."

- [ ] **Step 3: Update spec status**

In `docs/specs/0042-jt-express-carrier-integration.md`, change:
```
- **Trạng thái:** Draft
```
to:
```
- **Trạng thái:** Implemented (code xong; chờ apiAccount/privateKey Production thật từ J&T để verify công thức ký + test sandbox — xem §11)
```

- [ ] **Step 4: Full quality gate**

Run from `app/`:
```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse
php artisan test --filter=JtExpress
php artisan test --filter=Fulfillment
npm run lint && npm run typecheck && npm run build
```
Expected: all green. Fix any `pint`/`phpstan` issues in the new PHP files before proceeding (run `vendor/bin/pint` without `--test` to auto-fix formatting, then re-check `phpstan`).

- [ ] **Step 5: Commit**

```bash
git add docs/05-api/endpoints.md docs/03-domain/fulfillment-and-printing.md docs/specs/0042-jt-express-carrier-integration.md
git commit -m "docs(fulfillment): update endpoints/domain docs + mark SPEC 0042 implemented"
```
