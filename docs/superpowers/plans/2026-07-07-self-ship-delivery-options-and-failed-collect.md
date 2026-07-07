# Tuỳ chọn giao hàng đơn tự giao + "Giao thất bại – Thu tiền" + luồng hoàn — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cho đơn tự tạo/tự giao chọn được (per-đơn, có default shop) ghi chú giao hàng, chế độ xem hàng, ai trả phí ship, và "giao thất bại – thu tiền"; map đúng field GHN/GHTK/VTP theo capability; hoàn thiện luồng trạng thái + ghi nhận tài chính khi đơn hoàn.

**Architecture:** Options chuẩn hoá ở core (`ShipmentService`) → mỗi `CarrierConnector` tự map sang field sàn (không hard-code tên carrier ở core). Capability `failed_delivery_collect` gate tính năng thu-thất-bại. Webhook ghi kết quả hoàn vào cột shipment; state machine tách `returning` (đang hoàn) vs `returned` (đã về kho).

**Tech Stack:** Laravel 11 (PHP 8.2), PHPUnit, React 18 + Ant Design + TanStack Query. Chạy lệnh từ `app/`.

## Global Constraints

- Mọi lệnh PHP/Node chạy từ `app/`. Namespace `CMBcoreSeller\` → `app/app/`.
- Tiền = **integer VND** (không float). Chuỗi UI tiếng Việt; code/identifier tiếng Anh.
- **Core không biết tên carrier** — không thêm `if ($carrier === 'ghn')` ở core; logic đặc thù sàn nằm trong connector. (`01-architecture/extensibility-rules.md`)
- Chỉ áp cho **đơn manual** (`channel_account_id === null`). Đơn sàn bỏ qua.
- FE: dùng `@ant-design/icons` (không emoji); tập chọn nhỏ dùng `Radio.Group`/`Segmented` (không `Select`). (`ui-avoid-select-prefer-radio`)
- Quality gate trước khi coi task xong: `vendor/bin/pint --test` · `vendor/bin/phpstan analyse <file>` · test liên quan xanh. FE: `npm run typecheck && npm run lint`.
- Test baseline: KHÔNG có JS test runner; test FE = typecheck + lint + build. BE dùng PHPUnit.

---

### Task 1: Migration + model — cột options trên `orders`

**Files:**
- Create: `app/database/migrations/2026_07_07_100000_add_delivery_options_to_orders.php`
- Modify: `app/app/Modules/Orders/Models/Order.php:77` (fillable) + casts
- Test: `app/tests/Feature/Orders/OrderDeliveryOptionsTest.php`

**Interfaces:**
- Produces: cột `orders.delivery_note` (text null), `orders.delivery_inspection` (string null: `none|view|trial`), `orders.delivery_fee_payer` (string null: `shop|recipient`), `orders.failed_collect_amount` (unsignedInteger null). Order model fillable + cast `failed_collect_amount => integer`.

- [ ] **Step 1: Viết migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('delivery_note')->nullable()->after('note');
            $table->string('delivery_inspection', 16)->nullable()->after('delivery_note');   // none|view|trial
            $table->string('delivery_fee_payer', 12)->nullable()->after('delivery_inspection'); // shop|recipient
            $table->unsignedInteger('failed_collect_amount')->nullable()->after('delivery_fee_payer');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_note', 'delivery_inspection', 'delivery_fee_payer', 'failed_collect_amount']);
        });
    }
};
```

- [ ] **Step 2: Thêm vào Order `$fillable` + cast**

Trong `app/app/Modules/Orders/Models/Order.php`, thêm 4 khoá vào `$fillable`:
```php
'delivery_note', 'delivery_inspection', 'delivery_fee_payer', 'failed_collect_amount',
```
Trong `casts()` thêm: `'failed_collect_amount' => 'integer',`

- [ ] **Step 3: Viết failing test**

```php
<?php

namespace Tests\Feature\Orders;

use CMBcoreSeller\Modules\Orders\Models\Order;
use Tests\TestCase;

class OrderDeliveryOptionsTest extends TestCase
{
    public function test_order_persists_delivery_options(): void
    {
        $o = Order::factory()->create([
            'delivery_note' => 'Gọi trước khi giao',
            'delivery_inspection' => 'view',
            'delivery_fee_payer' => 'recipient',
            'failed_collect_amount' => 30000,
        ]);
        $o->refresh();
        $this->assertSame('Gọi trước khi giao', $o->delivery_note);
        $this->assertSame('view', $o->delivery_inspection);
        $this->assertSame('recipient', $o->delivery_fee_payer);
        $this->assertSame(30000, $o->failed_collect_amount);
    }
}
```
(Nếu chưa có `Order::factory()`, dùng cách tạo order như các test Feature/Orders khác trong repo — xem `tests/Feature/Orders/OrderControllerTest.php`.)

- [ ] **Step 4: Chạy migrate + test**

Run: `php artisan migrate && php artisan test --filter=OrderDeliveryOptionsTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/database/migrations/2026_07_07_100000_add_delivery_options_to_orders.php app/app/Modules/Orders/Models/Order.php app/tests/Feature/Orders/OrderDeliveryOptionsTest.php
git commit -m "feat(orders): cột tuỳ chọn giao hàng (note/inspection/fee_payer/failed_collect)"
```

---

### Task 2: Migration + model — cột kết quả hoàn trên `shipments` + status `returning`

**Files:**
- Create: `app/database/migrations/2026_07_07_100100_add_return_outcome_to_shipments.php`
- Modify: `app/app/Modules/Fulfillment/Models/Shipment.php` (fillable + hằng `STATUS_RETURNING`)
- Test: `app/tests/Unit/Fulfillment/ShipmentReturnOutcomeTest.php`

**Interfaces:**
- Produces: cột `shipments.cod_collected` (unsignedInteger null), `shipments.failed_collect_collected` (unsignedInteger null), `shipments.return_fee` (unsignedInteger null); hằng `Shipment::STATUS_RETURNING = 'returning'`.

- [ ] **Step 1: Migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->unsignedInteger('cod_collected')->nullable()->after('cod_amount');
            $table->unsignedInteger('failed_collect_collected')->nullable()->after('cod_collected');
            $table->unsignedInteger('return_fee')->nullable()->after('failed_collect_collected');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['cod_collected', 'failed_collect_collected', 'return_fee']);
        });
    }
};
```

- [ ] **Step 2: Model** — thêm hằng cạnh các `STATUS_*` khác trong `Shipment.php`:
```php
public const STATUS_RETURNING = 'returning';
```
và thêm `'cod_collected', 'failed_collect_collected', 'return_fee'` vào `$fillable`.

- [ ] **Step 3: Failing test**

```php
<?php

namespace Tests\Unit\Fulfillment;

use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use PHPUnit\Framework\TestCase;

class ShipmentReturnOutcomeTest extends TestCase
{
    public function test_returning_status_constant_exists(): void
    {
        $this->assertSame('returning', Shipment::STATUS_RETURNING);
        $this->assertNotSame(Shipment::STATUS_RETURNED, Shipment::STATUS_RETURNING);
    }
}
```

- [ ] **Step 4: migrate + test**

Run: `php artisan migrate && php artisan test --filter=ShipmentReturnOutcomeTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/database/migrations/2026_07_07_100100_add_return_outcome_to_shipments.php app/app/Modules/Fulfillment/Models/Shipment.php app/tests/Unit/Fulfillment/ShipmentReturnOutcomeTest.php
git commit -m "feat(fulfillment): cột kết quả hoàn shipment + status returning"
```

---

### Task 3: Nhận + lưu options khi tạo/sửa đơn manual

**Files:**
- Modify: `app/app/Modules/Orders/Http/Controllers/OrderController.php:91` (store validate) + `:181` (update validate)
- Modify: `app/app/Modules/Orders/Services/ManualOrderService.php` (map field vào Order — theo cách service này gán các field khác)
- Test: `app/tests/Feature/Orders/ManualOrderDeliveryOptionsApiTest.php`

**Interfaces:**
- Consumes: cột Order từ Task 1.
- Produces: API `POST/PATCH /api/v1/orders` chấp nhận `delivery_note`, `delivery_inspection`, `delivery_fee_payer`, `failed_collect_amount` và lưu vào order.

- [ ] **Step 1: Failing test**

```php
<?php

namespace Tests\Feature\Orders;

use Tests\TestCase;

class ManualOrderDeliveryOptionsApiTest extends TestCase
{
    public function test_store_persists_delivery_options(): void
    {
        // Đăng nhập + header tenant theo helper sẵn có trong tests (xem OrderControllerTest).
        [$headers, $tenantId] = $this->actingAsOwnerWithTenant();   // helper hiện có; nếu tên khác, dùng đúng helper repo

        $res = $this->postJson('/api/v1/orders', [
            'buyer_name' => 'Nguyễn Văn A',
            'buyer_phone' => '0900000000',
            'items' => [['name' => 'Bút', 'quantity' => 1, 'unit_price' => 10000]],
            'delivery_note' => 'Gọi trước khi giao',
            'delivery_inspection' => 'view',
            'delivery_fee_payer' => 'recipient',
            'failed_collect_amount' => 30000,
        ], $headers);

        $res->assertCreated();
        $this->assertDatabaseHas('orders', [
            'id' => $res->json('data.id'),
            'delivery_inspection' => 'view',
            'delivery_fee_payer' => 'recipient',
            'failed_collect_amount' => 30000,
        ]);
    }
}
```
> Ghi chú: dùng đúng cách setup auth/tenant + payload tối thiểu như `tests/Feature/Orders/OrderControllerTest.php`. Nếu store yêu cầu field khác (vd `shipping_address`), thêm cho khớp.

- [ ] **Step 2: Chạy để thấy fail**

Run: `php artisan test --filter=ManualOrderDeliveryOptionsApiTest`
Expected: FAIL (options không được lưu).

- [ ] **Step 3: Thêm validate ở store + update**

Trong `OrderController::store` (`$request->validate([...])`) và `update`, thêm:
```php
'delivery_note' => ['sometimes', 'nullable', 'string', 'max:2000'],
'delivery_inspection' => ['sometimes', 'nullable', 'in:none,view,trial'],
'delivery_fee_payer' => ['sometimes', 'nullable', 'in:shop,recipient'],
'failed_collect_amount' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:50000000'],
```

- [ ] **Step 4: Lưu vào Order trong ManualOrderService**

Trong `ManualOrderService` (nơi tạo/cập nhật Order từ `$data`), gán 4 khoá này vào order (theo pattern gán field hiện có, chỉ set khi có mặt trong `$data`):
```php
foreach (['delivery_note', 'delivery_inspection', 'delivery_fee_payer', 'failed_collect_amount'] as $k) {
    if (array_key_exists($k, $data)) {
        $order->{$k} = $data[$k];
    }
}
```
Đặt trước `$order->save()` của luồng create và update.

- [ ] **Step 5: Chạy test**

Run: `php artisan test --filter=ManualOrderDeliveryOptionsApiTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Orders/Http/Controllers/OrderController.php app/app/Modules/Orders/Services/ManualOrderService.php app/tests/Feature/Orders/ManualOrderDeliveryOptionsApiTest.php
git commit -m "feat(orders): API nhận + lưu tuỳ chọn giao hàng khi tạo/sửa đơn manual"
```

---

### Task 4: ShipmentService — đưa options chuẩn vào payload (đọc order + default shop, gate capability)

**Files:**
- Modify: `app/app/Modules/Fulfillment/Services/ShipmentService.php:1195` (`buildCreatePayload` return array) + đoạn gọi `createShipment` (~:696)
- Test: `app/tests/Unit/Fulfillment/ShipmentPayloadDeliveryOptionsTest.php` (hoặc mở rộng test payload sẵn có)

**Interfaces:**
- Consumes: cột Order (Task 1); capability `failed_delivery_collect` (Task 5-8).
- Produces: `$payload` truyền cho connector chứa khoá chuẩn: `delivery_note` (string|null), `inspection` (`none|view|trial`|null), `fee_payer` (`shop|recipient`|null), `failed_collect_amount` (int; 0 nếu tắt/không hỗ trợ). `required_note` cũ **giữ nguyên** cho tương thích nhưng suy từ `inspection`.

- [ ] **Step 1: Failing test**

```php
<?php

namespace Tests\Unit\Fulfillment;

use CMBcoreSeller\Modules\Fulfillment\Services\ShipmentService;
use PHPUnit\Framework\TestCase;

class ShipmentPayloadDeliveryOptionsTest extends TestCase
{
    public function test_inspection_maps_to_required_note_and_options_passthrough(): void
    {
        // Gọi trực tiếp helper thuần (reflection) hoặc test qua createShipment với connector fake.
        // Ở đây test map inspection→required_note qua static helper.
        $this->assertSame('CHOXEMHANGKHONGTHU', ShipmentService::inspectionToRequiredNote('view'));
        $this->assertSame('CHOTHUHANG', ShipmentService::inspectionToRequiredNote('trial'));
        $this->assertSame('KHONGCHOXEMHANG', ShipmentService::inspectionToRequiredNote('none'));
        $this->assertSame('KHONGCHOXEMHANG', ShipmentService::inspectionToRequiredNote(null));
    }
}
```

- [ ] **Step 2: Chạy để fail**

Run: `php artisan test --filter=ShipmentPayloadDeliveryOptionsTest`
Expected: FAIL (method chưa có).

- [ ] **Step 3: Thêm static helper + đưa options vào payload**

Thêm vào `ShipmentService`:
```php
/** Map chế độ xem hàng chuẩn → required_note (giá trị GHN; connector khác tự map lại). */
public static function inspectionToRequiredNote(?string $inspection): string
{
    return match ($inspection) {
        'view' => 'CHOXEMHANGKHONGTHU',
        'trial' => 'CHOTHUHANG',
        default => 'KHONGCHOXEMHANG',
    };
}
```

Trong `buildCreatePayload(...)`, trước `return [...]`, đọc options từ order + default shop (tenant.settings.shipping) với opts override:
```php
$tenantSettings = (array) (Tenant::query()->whereKey($tenantId)->value('settings') ?? []);
$shipDefaults = (array) data_get($tenantSettings, 'shipping', []);

$inspection = $opts['inspection'] ?? $order->delivery_inspection ?? ($shipDefaults['default_inspection'] ?? 'none');
$feePayer = $opts['fee_payer'] ?? $order->delivery_fee_payer ?? ($shipDefaults['default_fee_payer'] ?? 'recipient');
$deliveryNote = (string) ($opts['delivery_note'] ?? $order->delivery_note ?? '');
$failedCollect = (int) ($opts['failed_collect_amount']
    ?? $order->failed_collect_amount
    ?? (($shipDefaults['failed_collect_enabled'] ?? false) ? ($shipDefaults['failed_collect_amount'] ?? 0) : 0));
```

Trong array `return [...]` (ShipmentService:1195), thay `'required_note' => $opts['required_note'] ?? null,` và bổ sung khoá chuẩn:
```php
'required_note' => $opts['required_note'] ?? self::inspectionToRequiredNote($inspection),
'inspection' => $inspection,
'fee_payer' => $feePayer,
'delivery_note' => $deliveryNote,
'failed_collect_amount' => $failedCollect,
```
(`Tenant` đã được import ở đầu file — dùng `use CMBcoreSeller\Modules\Tenancy\Models\Tenant;` nếu chưa có.)

- [ ] **Step 4: Gate capability trước khi gọi connector**

Ngay trước `$result = $connector->createShipment($accountArr, $payload);` (ShipmentService:698):
```php
if (($payload['failed_collect_amount'] ?? 0) > 0
    && (! $connector instanceof \CMBcoreSeller\Integrations\Carriers\Support\AbstractCarrierConnector
        || ! $connector->supports('failed_delivery_collect'))) {
    $payload['failed_collect_amount'] = 0;   // ĐVVC không hỗ trợ → bỏ qua an toàn (không hard-code tên carrier)
}
```

- [ ] **Step 5: Chạy test**

Run: `php artisan test --filter=ShipmentPayloadDeliveryOptionsTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Fulfillment/Services/ShipmentService.php app/tests/Unit/Fulfillment/ShipmentPayloadDeliveryOptionsTest.php
git commit -m "feat(fulfillment): đưa tuỳ chọn giao hàng chuẩn vào payload + gate capability failed-collect"
```

---

### Task 5: GhnConnector — map note/required_note/payment_type_id/cod_failed_amount + capability

**Files:**
- Modify: `app/app/Integrations/Carriers/Ghn/GhnConnector.php:36` (capabilities) + payload build (`:167`)
- Test: `app/tests/Unit/Carriers/GhnConnectorDeliveryOptionsTest.php` (hoặc file test GHN sẵn có)

**Interfaces:**
- Consumes: payload khoá chuẩn từ Task 4 (`delivery_note`, `inspection`→`required_note`, `fee_payer`, `failed_collect_amount`).
- Produces: capability `failed_delivery_collect`; payload GHN có `note`, `required_note`, `payment_type_id` (theo fee_payer), `cod_failed_amount` (khi >0).

- [ ] **Step 1: Failing test**

```php
<?php

namespace Tests\Unit\Carriers;

use CMBcoreSeller\Integrations\Carriers\Ghn\GhnConnector;
use PHPUnit\Framework\TestCase;

class GhnConnectorDeliveryOptionsTest extends TestCase
{
    public function test_capability_includes_failed_delivery_collect(): void
    {
        $this->assertContains('failed_delivery_collect', (new GhnConnector)->capabilities());
    }
}
```

- [ ] **Step 2: Chạy để fail**

Run: `php artisan test --filter=GhnConnectorDeliveryOptionsTest`
Expected: FAIL.

- [ ] **Step 3: Thêm capability**

`GhnConnector::capabilities()` — thêm `'failed_delivery_collect'` vào mảng trả về.

- [ ] **Step 4: Map field trong payload GHN**

Trong `GhnConnector` payload (`array_filter([...])` ở :167), sửa/ bổ sung:
```php
'payment_type_id' => (($shipment['fee_payer'] ?? null) === 'shop') ? 1 : ($cod > 0 ? 2 : 1),
'required_note' => $shipment['required_note'] ?? 'KHONGCHOXEMHANG',
'note' => ($n = trim((string) ($shipment['delivery_note'] ?? ''))) !== '' ? mb_substr($n, 0, 5000) : null,
'cod_failed_amount' => (int) ($shipment['failed_collect_amount'] ?? 0) > 0 ? (int) $shipment['failed_collect_amount'] : null,
```
(`note` và `cod_failed_amount` null sẽ bị `array_filter(fn ($v) => $v !== null)` loại bỏ — an toàn.)

- [ ] **Step 5: Test map (thêm case)**

Thêm test dựng payload GHN (gọi phương thức build payload hoặc, nếu là private, test qua `createShipment` với `GhnClient` fake trả `order_code`). Khẳng định:
```php
$this->assertSame(1, $payload['payment_type_id']);            // fee_payer=shop
$this->assertSame('CHOTHUHANG', $payload['required_note']);   // inspection=trial
$this->assertSame('Gọi trước', $payload['note']);
$this->assertSame(30000, $payload['cod_failed_amount']);
```
> Nếu build payload là private: tách một method `protected function buildGhnPayload(array $shipment, int $cod, array $items): array` để test trực tiếp (refactor nhỏ, không đổi hành vi).

- [ ] **Step 6: Chạy test + verify GHN doc field**

Run: `php artisan test --filter=GhnConnectorDeliveryOptionsTest`
Expected: PASS.
> ⚠️ `cod_failed_amount`: verify tên/định dạng thật với GHN sandbox (spec §11). Nếu GHN đổi tên field, sửa tại đây — chỉ 1 nơi.

- [ ] **Step 7: Commit**

```bash
git add app/app/Integrations/Carriers/Ghn/GhnConnector.php app/tests/Unit/Carriers/GhnConnectorDeliveryOptionsTest.php
git commit -m "feat(ghn): map ghi chú/chế độ xem/ai trả ship/giao-thất-bại-thu-tiền + capability"
```

---

### Task 6: GhtkConnector — note (đã có) + is_freeship theo fee_payer, KHÔNG failed-collect

**Files:**
- Modify: `app/app/Integrations/Carriers/Ghtk/GhtkConnector.php` (payload build)
- Test: `app/tests/Unit/Carriers/GhtkConnectorDeliveryOptionsTest.php`

**Interfaces:**
- Consumes: payload chuẩn (Task 4).
- Produces: GHTK payload có `note` (từ `delivery_note`), `is_freeship` (fee_payer=shop→1, recipient→0); KHÔNG có capability `failed_delivery_collect`.

- [ ] **Step 1: Failing test**

```php
<?php

namespace Tests\Unit\Carriers;

use CMBcoreSeller\Integrations\Carriers\Ghtk\GhtkConnector;
use PHPUnit\Framework\TestCase;

class GhtkConnectorDeliveryOptionsTest extends TestCase
{
    public function test_ghtk_has_no_failed_collect_capability(): void
    {
        $this->assertNotContains('failed_delivery_collect', (new GhtkConnector)->capabilities());
    }
}
```

- [ ] **Step 2: fail** → Run: `php artisan test --filter=GhtkConnectorDeliveryOptionsTest` → PASS ngay (đã đúng); nếu vô tình có capability thì bỏ. Đây là test khẳng định.

- [ ] **Step 3: Map is_freeship + note**

Trong GHTK payload (`order` object, cạnh `'note' => ...` ở :126), thêm:
```php
'is_freeship' => (($shipment['fee_payer'] ?? 'recipient') === 'shop') ? 1 : 0,
```
`note` giữ nguyên nhưng đổi nguồn ưu tiên: `$shipment['delivery_note'] ?? $shipment['note'] ?? $shipment['content']`.

- [ ] **Step 4: Test map is_freeship**

Thêm case: fee_payer=shop → `is_freeship=1`; recipient → `0`. (Tách buildGhtkPayload protected nếu cần, như Task 5.)

- [ ] **Step 5: test** → Run: `php artisan test --filter=GhtkConnectorDeliveryOptionsTest` → PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Integrations/Carriers/Ghtk/GhtkConnector.php app/tests/Unit/Carriers/GhtkConnectorDeliveryOptionsTest.php
git commit -m "feat(ghtk): is_freeship theo fee_payer + note từ delivery_note"
```

---

### Task 7: ViettelPostConnector — ORDER_NOTE/ORDER_PAYMENT + XMG/EXTRA_MONEY + capability

**Files:**
- Modify: `app/app/Integrations/Carriers/ViettelPost/ViettelPostConnector.php:36` (capabilities) + payload (:136-166)
- Test: `app/tests/Unit/Carriers/ViettelPostConnectorDeliveryOptionsTest.php`

**Interfaces:**
- Consumes: payload chuẩn (Task 4).
- Produces: capability `failed_delivery_collect`; payload VTP: `ORDER_NOTE` từ `delivery_note`, `ORDER_PAYMENT` theo fee_payer+cod, thêm dịch vụ XMG + `EXTRA_MONEY` (clamp ≤ 2× cước) khi `failed_collect_amount>0`.

- [ ] **Step 1: Failing test**

```php
<?php

namespace Tests\Unit\Carriers;

use CMBcoreSeller\Integrations\Carriers\ViettelPost\ViettelPostConnector;
use PHPUnit\Framework\TestCase;

class ViettelPostConnectorDeliveryOptionsTest extends TestCase
{
    public function test_capability_and_order_payment_mapping(): void
    {
        $c = new ViettelPostConnector;
        $this->assertContains('failed_delivery_collect', $c->capabilities());
        // shop trả ship + COD → 4 (thu hộ tiền hàng); recipient + COD → 3 (thu hộ hàng+cước)
        $this->assertSame(4, ViettelPostConnector::orderPayment('shop', 50000));
        $this->assertSame(3, ViettelPostConnector::orderPayment('recipient', 50000));
        $this->assertSame(1, ViettelPostConnector::orderPayment('shop', 0));
        $this->assertSame(2, ViettelPostConnector::orderPayment('recipient', 0));
    }
}
```

- [ ] **Step 2: fail** → Run: `php artisan test --filter=ViettelPostConnectorDeliveryOptionsTest` → FAIL.

- [ ] **Step 3: capability + static orderPayment**

`capabilities()` — thêm `'failed_delivery_collect'`. Thêm static:
```php
/** VTP ORDER_PAYMENT: 1 shop-noCOD, 2 recipient-noCOD (thu cước), 3 recipient+COD (hàng+cước), 4 shop+COD (chỉ hàng). */
public static function orderPayment(?string $feePayer, int $cod): int
{
    $recipientPaysShip = ($feePayer ?? 'recipient') === 'recipient';
    if ($cod > 0) {
        return $recipientPaysShip ? 3 : 4;
    }
    return $recipientPaysShip ? 2 : 1;
}
```

- [ ] **Step 4: Dùng trong payload**

Sửa `'ORDER_PAYMENT' => $cod > 0 ? 3 : 1,` → `'ORDER_PAYMENT' => self::orderPayment($shipment['fee_payer'] ?? null, $cod),`
Sửa `'ORDER_NOTE' => $this->trimNote($shipment['required_note'] ?? ($shipment['content'] ?? null)),` → `'ORDER_NOTE' => $this->trimNote($shipment['delivery_note'] ?? $shipment['content'] ?? null),`
Thêm (sau khi build payload chính), khi `failed_collect_amount>0`: đính dịch vụ XMG + `EXTRA_MONEY`:
```php
$failed = (int) ($shipment['failed_collect_amount'] ?? 0);
if ($failed > 0) {
    $cap = 2 * (int) ($payload['MONEY_TOTAL'] ?? $payload['MONEY_TOTALFEE'] ?? 0);
    $payload['EXTRA_MONEY'] = $cap > 0 ? min($failed, $cap) : $failed;
    $payload['LIST_ITEM_EXTRA'] = ['XMG'];   // dịch vụ "Xem hàng, thu tiền" — verify mã dịch vụ VTP sandbox
}
```
> ⚠️ Mã dịch vụ XMG + field `EXTRA_MONEY`/`LIST_ITEM_EXTRA`: verify với VTP sandbox (spec §11); đổi chỉ tại đây.

- [ ] **Step 5: test** → Run: `php artisan test --filter=ViettelPostConnectorDeliveryOptionsTest` → PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Integrations/Carriers/ViettelPost/ViettelPostConnector.php app/tests/Unit/Carriers/ViettelPostConnectorDeliveryOptionsTest.php
git commit -m "feat(vtp): ORDER_NOTE/ORDER_PAYMENT theo fee_payer + XMG/EXTRA_MONEY + capability"
```

---

### Task 8: Status map — tách `returning` vs `returned` (GHN/GHTK/VTP)

**Files:**
- Modify: `app/app/Integrations/Carriers/Ghn/GhnStatusMap.php:26-33`
- Modify: `app/app/Integrations/Carriers/Ghtk/GhtkStatusMap.php` + `.../ViettelPost/ViettelPostStatusMap.php` (map trạng thái "đang hoàn" → RETURNING, "đã về kho" → RETURNED)
- Test: `app/tests/Unit/Carriers/ReturnStatusMapTest.php`

**Interfaces:**
- Produces: raw "đang hoàn" → `Shipment::STATUS_RETURNING`; raw "đã về kho người gửi" → `Shipment::STATUS_RETURNED`.

- [ ] **Step 1: Failing test**

```php
<?php

namespace Tests\Unit\Carriers;

use CMBcoreSeller\Integrations\Carriers\Ghn\GhnStatusMap;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use PHPUnit\Framework\TestCase;

class ReturnStatusMapTest extends TestCase
{
    public function test_ghn_returning_vs_returned(): void
    {
        $this->assertSame(Shipment::STATUS_RETURNING, GhnStatusMap::toShipmentStatus('returning'));
        $this->assertSame(Shipment::STATUS_RETURNING, GhnStatusMap::toShipmentStatus('return_transporting'));
        $this->assertSame(Shipment::STATUS_RETURNED, GhnStatusMap::toShipmentStatus('returned'));
        $this->assertSame(Shipment::STATUS_FAILED, GhnStatusMap::toShipmentStatus('delivery_fail'));
    }
}
```

- [ ] **Step 2: fail** → Run: `php artisan test --filter=ReturnStatusMapTest` → FAIL.

- [ ] **Step 3: Sửa GhnStatusMap** — đổi các dòng return:
```php
'delivery_fail' => Shipment::STATUS_FAILED,
'waiting_to_return' => Shipment::STATUS_FAILED,
'return' => Shipment::STATUS_RETURNING,
'returning' => Shipment::STATUS_RETURNING,
'return_transporting' => Shipment::STATUS_RETURNING,
'return_sorting' => Shipment::STATUS_RETURNING,
'return_fail' => Shipment::STATUS_FAILED,
'returned' => Shipment::STATUS_RETURNED,
```

- [ ] **Step 4: GHTK + VTP status map** — cập nhật tương tự: trạng thái "đang hoàn/đang chuyển hoàn" → `STATUS_RETURNING`; "đã trả về người gửi/hoàn tất hoàn" → `STATUS_RETURNED`. (GHTK: `status_id` khu vực hoàn; VTP: code 201/501/503/504 phân loại theo `ViettelPostStatusMap::MAP` — map "đang hoàn" vs "đã hoàn".) Thêm assertion cho GHTK/VTP vào test.

- [ ] **Step 5: test** → Run: `php artisan test --filter=ReturnStatusMapTest` → PASS. Chạy thêm test status map cũ để không vỡ: `php artisan test --filter=StatusMap`.

- [ ] **Step 6: Commit**

```bash
git add app/app/Integrations/Carriers/*/[A-Z]*StatusMap.php app/tests/Unit/Carriers/ReturnStatusMapTest.php
git commit -m "feat(carriers): tách trạng thái returning (đang hoàn) vs returned (đã về kho)"
```

---

### Task 9: syncOrderStatus — RETURNING→Returning, RETURNED→ReturnedRefunded

**Files:**
- Modify: `app/app/Modules/Fulfillment/Http/Controllers/CarrierWebhookController.php:192-199`
- Modify: `app/app/Modules/Fulfillment/Services/ShipmentService.php` (nếu có bảng map tương tự `syncOrderToShipmentStatus` — đồng bộ 2 nơi)
- Test: `app/tests/Feature/Fulfillment/CarrierWebhookReturnStatusTest.php`

**Interfaces:**
- Consumes: `Shipment::STATUS_RETURNING`/`STATUS_RETURNED` (Task 2, 8).
- Produces: order → `Returning` (đang hoàn) / `ReturnedRefunded` (đã về kho).

- [ ] **Step 1: Failing test** (feature, webhook GHN `returned`)

```php
<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Tests\TestCase;

class CarrierWebhookReturnStatusTest extends TestCase
{
    public function test_returned_moves_order_to_returned_refunded(): void
    {
        // Dựng shipment GHN open + order như các test webhook GHN sẵn có (xem tests/Feature/Fulfillment).
        [$order, $shipment, $headers] = $this->makeGhnShipment(['tracking' => 'GHN123']);

        $this->postJson('/webhook/carriers/ghn', [
            'CODAmount' => 0, 'Type' => 'returned', 'OrderCode' => 'GHN123',
        ], $headers)->assertOk();

        $this->assertSame(StandardOrderStatus::ReturnedRefunded->value, $order->refresh()->status);
    }
}
```
> Dùng đúng payload GHN webhook + auth `Token` như test webhook GHN hiện có trong repo.

- [ ] **Step 2: fail** → Run: `php artisan test --filter=CarrierWebhookReturnStatusTest` → FAIL (order ra `Returning`).

- [ ] **Step 3: Sửa map** trong `CarrierWebhookController::syncOrderStatus`:
```php
$map = [
    Shipment::STATUS_AWAITING_PICKUP => StandardOrderStatus::ReadyToShip,
    Shipment::STATUS_PICKED_UP => StandardOrderStatus::Shipped,
    Shipment::STATUS_IN_TRANSIT => StandardOrderStatus::Shipped,
    Shipment::STATUS_DELIVERED => StandardOrderStatus::Delivered,
    Shipment::STATUS_FAILED => StandardOrderStatus::DeliveryFailed,
    Shipment::STATUS_RETURNING => StandardOrderStatus::Returning,
    Shipment::STATUS_RETURNED => StandardOrderStatus::ReturnedRefunded,
];
```
Đồng bộ map tương tự nếu `ShipmentService` có bảng riêng.

- [ ] **Step 4: test** → Run: `php artisan test --filter=CarrierWebhookReturnStatusTest` → PASS. Chạy `php artisan test --filter=Webhook` để không vỡ case cũ.

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Fulfillment/Http/Controllers/CarrierWebhookController.php app/app/Modules/Fulfillment/Services/ShipmentService.php app/tests/Feature/Fulfillment/CarrierWebhookReturnStatusTest.php
git commit -m "feat(fulfillment): order Returning (đang hoàn) vs ReturnedRefunded (đã về kho)"
```

---

### Task 10: Ghi nhận COD/khoản-thất-bại/phí-hoàn từ webhook

**Files:**
- Modify: các `*Connector::parseWebhook` (GHN/GHTK/VTP) — trả thêm `cod_collected`, `failed_collect_collected`, `return_fee` khi payload có.
- Modify: `app/app/Modules/Fulfillment/Http/Controllers/CarrierWebhookController.php:80-88` — ghi các field này vào shipment.
- Test: `app/tests/Feature/Fulfillment/CarrierWebhookReturnOutcomeTest.php`

**Interfaces:**
- Consumes: cột shipment (Task 2).
- Produces: shipment.`cod_collected`/`failed_collect_collected`/`return_fee` cập nhật từ webhook; chỉ ghi khi giá trị non-null.

- [ ] **Step 1: Failing test** (2 nhánh: thu 30k / từ chối)

```php
<?php

namespace Tests\Feature\Fulfillment;

use Tests\TestCase;

class CarrierWebhookReturnOutcomeTest extends TestCase
{
    public function test_failed_delivery_with_collected_fee_is_recorded(): void
    {
        [$order, $shipment, $headers] = $this->makeGhnShipment(['tracking' => 'GHN200']);

        $this->postJson('/webhook/carriers/ghn', [
            'Type' => 'delivery_fail', 'OrderCode' => 'GHN200', 'CODFailedFee' => 30000,
        ], $headers)->assertOk();

        $this->assertSame(30000, (int) $shipment->refresh()->failed_collect_collected);
    }

    public function test_failed_delivery_refused_records_zero(): void
    {
        [$order, $shipment, $headers] = $this->makeGhnShipment(['tracking' => 'GHN201']);

        $this->postJson('/webhook/carriers/ghn', [
            'Type' => 'delivery_fail', 'OrderCode' => 'GHN201', 'CODFailedFee' => 0,
        ], $headers)->assertOk();

        $this->assertSame(0, (int) $shipment->refresh()->failed_collect_collected);
    }
}
```

- [ ] **Step 2: fail** → Run: `php artisan test --filter=CarrierWebhookReturnOutcomeTest` → FAIL.

- [ ] **Step 3: parseWebhook trả field outcome (GHN)**

Trong `GhnConnector::parseWebhook`, thêm vào mảng event trả về (khi payload có key tương ứng — đọc từ `$request`):
```php
'cod_collected' => $request->has('CODAmount') ? (int) $request->input('CODAmount') : null,
'failed_collect_collected' => $request->has('CODFailedFee') ? (int) $request->input('CODFailedFee') : null,
'return_fee' => $request->has('ReturnFee') ? (int) $request->input('ReturnFee') : null,
```
Làm tương tự cho GHTK (`pick_money`/fee fields) và VTP (`MONEY_COLLECTION`/`EXTRA_MONEY`) — chỉ set khi payload có.

- [ ] **Step 4: Controller ghi vào shipment**

Trong `CarrierWebhookController::handle`, sau block cập nhật status (`:88`), thêm ghi outcome (chỉ khi non-null, không xoá giá trị cũ):
```php
$outcome = array_filter([
    'cod_collected' => $event['cod_collected'] ?? null,
    'failed_collect_collected' => $event['failed_collect_collected'] ?? null,
    'return_fee' => $event['return_fee'] ?? null,
], fn ($v) => $v !== null);
if ($outcome !== []) {
    $shipment->forceFill($outcome)->save();
}
```

- [ ] **Step 5: test** → Run: `php artisan test --filter=CarrierWebhookReturnOutcomeTest` → PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Integrations/Carriers/*/[A-Z]*Connector.php app/app/Modules/Fulfillment/Http/Controllers/CarrierWebhookController.php app/tests/Feature/Fulfillment/CarrierWebhookReturnOutcomeTest.php
git commit -m "feat(fulfillment): ghi COD/khoản-thất-bại/phí-hoàn từ webhook ĐVVC"
```

---

### Task 11: Phơi capability + default shop qua API cho FE

**Files:**
- Modify: `app/app/Modules/Fulfillment/Http/Controllers/CarrierAccountController.php` (hoặc controller list carriers) — thêm `capabilities` vào resource carrier.
- Modify: settings resource của tenant — đảm bảo `settings.shipping` trả về FE.
- Test: `app/tests/Feature/Fulfillment/CarrierCapabilitiesApiTest.php`

**Interfaces:**
- Produces: API trả `capabilities: string[]` mỗi carrier (để FE ẩn field failed-collect); `settings.shipping` cho prefill default.

- [ ] **Step 1: Failing test**

```php
<?php

namespace Tests\Feature\Fulfillment;

use Tests\TestCase;

class CarrierCapabilitiesApiTest extends TestCase
{
    public function test_carrier_list_exposes_capabilities(): void
    {
        [$headers] = $this->actingAsOwnerWithTenant();
        $res = $this->getJson('/api/v1/carriers', $headers);   // dùng đúng route list carrier hiện có
        $res->assertOk();
        $ghn = collect($res->json('data'))->firstWhere('code', 'ghn');
        $this->assertContains('failed_delivery_collect', $ghn['capabilities'] ?? []);
    }
}
```
> Nếu chưa có route list carriers cho FE, xác định route hiện FE dùng để render danh sách ĐVVC (xem `resources/js/pages/CarrierAccountsPage.tsx` gọi endpoint nào) và thêm `capabilities` vào đó.

- [ ] **Step 2: fail → thêm capabilities vào resource → test PASS.**

Trong resource/array trả carrier: `'capabilities' => $registry->for($code) instanceof AbstractCarrierConnector ? $registry->for($code)->capabilities() : []`.

- [ ] **Step 3: Commit**

```bash
git add app/app/Modules/Fulfillment/Http app/tests/Feature/Fulfillment/CarrierCapabilitiesApiTest.php
git commit -m "feat(api): phơi capabilities carrier cho FE"
```

---

### Task 12: FE — form tuỳ chọn giao hàng khi tạo đơn (per-đơn + prefill default)

**Files:**
- Modify: `app/resources/js/pages/CreateOrderPage.tsx`
- Modify: `app/resources/js/lib/orders.tsx` (type payload tạo đơn — thêm 4 field)
- Modify: `app/resources/js/lib/fulfillment.tsx` (nếu type carrier cần `capabilities`)

**Interfaces:**
- Consumes: capability carrier (Task 11), `settings.shipping` default.
- Produces: form gửi `delivery_note/delivery_inspection/delivery_fee_payer/failed_collect_amount` khi tạo đơn.

- [ ] **Step 1: Thêm field vào type payload**

Trong type tạo đơn (`lib/orders.tsx`), thêm optional:
```ts
delivery_note?: string;
delivery_inspection?: 'none' | 'view' | 'trial';
delivery_fee_payer?: 'shop' | 'recipient';
failed_collect_amount?: number;
```

- [ ] **Step 2: UI mục "Tuỳ chọn giao hàng"** trong `CreateOrderPage.tsx` (đặt gần khối ĐVVC/COD). Dùng Radio + Segmented + Switch + InputNumber:
```tsx
<Form.Item label="Ghi chú giao hàng" name="delivery_note">
    <Input.TextArea rows={2} maxLength={2000} placeholder="VD: Gọi trước khi giao" />
</Form.Item>
<Form.Item label="Chế độ xem hàng" name="delivery_inspection" initialValue={shipDefaults?.default_inspection ?? 'none'}>
    <Radio.Group options={[
        { label: 'Không cho xem', value: 'none' },
        { label: 'Cho xem không thử', value: 'view' },
        { label: 'Cho thử', value: 'trial' },
    ]} optionType="button" />
</Form.Item>
<Form.Item label="Phí ship" name="delivery_fee_payer" initialValue={shipDefaults?.default_fee_payer ?? 'recipient'}>
    <Radio.Group options={[
        { label: 'Shop trả (freeship)', value: 'shop' },
        { label: 'Người nhận trả', value: 'recipient' },
    ]} optionType="button" />
</Form.Item>
{carrierSupportsFailedCollect && (
    <Form.Item label="Giao thất bại – thu tiền khách">
        <Space>
            <Switch checked={failedCollectOn} onChange={setFailedCollectOn} />
            {failedCollectOn && (
                <Form.Item name="failed_collect_amount" noStyle initialValue={shipDefaults?.failed_collect_amount ?? 30000}>
                    <InputNumber min={0} max={50000000} step={1000} addonAfter="đ" />
                </Form.Item>
            )}
        </Space>
    </Form.Item>
)}
```
- `carrierSupportsFailedCollect` = carrier đã chọn có `capabilities.includes('failed_delivery_collect')`.
- `shipDefaults` = `useCurrentTenant().settings?.shipping` (prefill).
- Khi submit: nếu `!failedCollectOn` → gửi `failed_collect_amount: 0`.

- [ ] **Step 3: Verify** `npm run typecheck && npm run lint` → xanh. Kiểm tra thủ công: chọn GHTK → field "giao thất bại thu tiền" ẩn; chọn GHN/VTP → hiện, prefill 30.000đ.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/pages/CreateOrderPage.tsx app/resources/js/lib/orders.tsx app/resources/js/lib/fulfillment.tsx
git commit -m "feat(fe): tuỳ chọn giao hàng khi tạo đơn (ẩn/hiện theo capability ĐVVC)"
```

---

### Task 13: FE — default shop (Cài đặt → ĐVVC) + hiển thị kết quả hoàn ở chi tiết đơn

**Files:**
- Modify: `app/resources/js/pages/CarrierAccountsPage.tsx` (hoặc trang Cài đặt ĐVVC) — form default `shipping.*`.
- Modify: `app/resources/js/components/OrderDetailBody.tsx` — hiển thị kết quả hoàn.

**Interfaces:**
- Consumes: PATCH `/api/v1/tenant { settings: { shipping: {...} } }`; order/shipment resource trả `cod_collected/failed_collect_collected/return_fee`.

- [ ] **Step 1: Default shop form** — nhóm "Tuỳ chọn giao hàng mặc định": Radio default_inspection, default_fee_payer, Switch failed_collect_enabled + InputNumber failed_collect_amount. Submit PATCH tenant settings (merge, đã hỗ trợ).

- [ ] **Step 2: Hiển thị kết quả hoàn** trong `OrderDetailBody.tsx` khi order ở `Returning`/`ReturnedRefunded`/`DeliveryFailed`:
```tsx
{shipment?.failed_collect_collected != null && (
    <Descriptions.Item label="Giao thất bại – đã thu">
        {shipment.failed_collect_collected > 0
            ? formatVnd(shipment.failed_collect_collected)
            : 'Khách từ chối (thu 0đ)'}
    </Descriptions.Item>
)}
{shipment?.return_fee != null && <Descriptions.Item label="Phí hoàn">{formatVnd(shipment.return_fee)}</Descriptions.Item>}
```
Đảm bảo `ShipmentResource`/order resource phơi 3 field mới (thêm vào resource nếu thiếu).

- [ ] **Step 3: Verify** `npm run typecheck && npm run lint` → xanh.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/pages/CarrierAccountsPage.tsx app/resources/js/components/OrderDetailBody.tsx app/app/Modules/Fulfillment/Http/Resources
git commit -m "feat(fe): default tuỳ chọn giao hàng cấp shop + hiển thị kết quả hoàn"
```

---

### Task 14: Cập nhật docs + chạy full quality gate

**Files:**
- Modify: `docs/05-api/endpoints.md` (field mới của order create/update), `docs/03-domain/fulfillment-and-printing.md` (capability + luồng hoàn), spec: đổi Trạng thái → Implemented.

- [ ] **Step 1: Cập nhật docs** theo thay đổi (endpoints, capability `failed_delivery_collect`, state machine returning/returned).

- [ ] **Step 2: Full gate**

Run:
```bash
cd app && vendor/bin/pint --test && vendor/bin/phpstan analyse && php artisan test --filter="Delivery|Return|Carrier|Shipment" && npm run typecheck && npm run lint && npm run build
```
Expected: PASS (phpstan: không thêm lỗi mới ở file đã sửa).

- [ ] **Step 3: Commit**

```bash
git add docs
git commit -m "docs: cập nhật endpoints/domain cho tuỳ chọn giao hàng + luồng hoàn"
```

---

## Self-review checklist (đã rà)

- **Spec coverage:** ghi chú sync (Task 5-7) · chế độ xem hàng (Task 4-7) · ai trả phí ship (Task 5-7) · giao-thất-bại-thu-tiền (Task 4,5,7 + capability) · default shop (Task 4,12,13) · state machine returning/returned (Task 8,9) · ghi nhận outcome (Task 2,10) · hiển thị (Task 13). ✔
- **Type nhất quán:** khoá chuẩn `delivery_note/inspection/fee_payer/failed_collect_amount` dùng đồng nhất từ Task 4 xuống connector; `STATUS_RETURNING` định nghĩa Task 2, dùng Task 8,9. ✔
- **Verify sandbox (rủi ro đã đánh dấu):** GHN `cod_failed_amount` (Task 5), VTP `XMG`/`EXTRA_MONEY` (Task 7) — chỉ sửa 1 nơi/connector nếu tên field khác.
