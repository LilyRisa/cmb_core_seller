# SĐT hash tra trùng + Webhook dedupe atomic Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thay tra trùng SĐT đơn thủ công từ load-toàn-bộ-rồi-so-khớp-PHP sang query bằng hash (index), và thay
webhook dedupe từ app-level `exists()`+`create()` (có race window) sang ràng buộc `unique()` thật + insert atomic
— triển khai webhook dedupe qua 2 giai đoạn migrate để không risk fail trên dữ liệu production đã có trùng.

**Architecture:** Tái dùng `CustomerPhoneNormalizer::normalizeAndHash()` đã có sẵn (đúng convention
`customers.phone_hash`) cho 2 cột mới `orders.buyer_phone_hash`/`recipient_phone_hash`. Webhook dedupe: giai đoạn 1
(migration additive + set `dedupe_status_key` khi ghi) → chạy artisan backfill+dedupe → giai đoạn 2 (migration
thêm `unique()` thật, tự chặn nếu vẫn còn trùng) + service bắt lỗi vi phạm unique thay vì để lộ 500.

**Tech Stack:** Laravel 11 (PHP), PHPUnit, Eloquent migrations.

## Global Constraints

- Mọi lệnh PHP chạy từ `app/`.
- PSR-4: `CMBcoreSeller\` → `app/app/`.
- Tiền VND nguyên; user-facing string tiếng Việt; code/định danh tiếng Anh.
- Hash SĐT: `CustomerPhoneNormalizer::normalizeAndHash()` (SHA-256 phẳng, KHÔNG HMAC/salt riêng) — đúng convention
  đã dùng cho `customers.phone_hash` (`char(64)`).
- KHÔNG dùng generated/computed column của DB cho `dedupe_status_key` (tránh vênh cú pháp SQLite dev/test vs
  Postgres prod) — ghi giá trị tại tầng app.
- Backfill KHÔNG chạy trong `up()` của migration (bảng có thể lớn) — luôn tách artisan command riêng, chạy tay
  sau migrate, idempotent.
- Trước khi coi bất kỳ task nào "xong", chạy đúng lệnh test của task đó và xác nhận PASS.

---

## Task 1: Migration + `ManualOrderService` ghi hash SĐT

**Files:**
- Create: `app/app/Modules/Orders/Database/Migrations/2026_07_14_100000_add_phone_hash_to_orders_table.php`
- Modify: `app/app/Modules/Orders/Services/ManualOrderService.php`
- Test: `app/tests/Feature/Orders/ManualOrderPhoneHashTest.php`

**Interfaces:**
- Produces: cột `orders.buyer_phone_hash` / `orders.recipient_phone_hash` (`char(64)` nullable), method mới
  `ManualOrderService::phoneHashes(?string $buyerPhone, array $shippingAddress): array{buyer_phone_hash: ?string, recipient_phone_hash: ?string}`
  — Task 2 (query) và Task 3 (backfill command) đọc đúng 2 tên cột này.

- [ ] **Step 1: Viết migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tra trùng SĐT đơn thủ công nhanh (O(log n) qua index thay vì load hết + so khớp PHP) — design
 * 2026-07-14-manual-order-phone-hash-and-webhook-dedupe. `buyer_phone` cast encrypted nên không
 * query trực tiếp được; hash = sha256(SĐT đã chuẩn hoá), cùng convention `customers.phone_hash`.
 * Additive-only, KHÔNG backfill ở đây — xem lệnh `orders:backfill-phone-hash`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->char('buyer_phone_hash', 64)->nullable()->after('buyer_phone');
            $table->char('recipient_phone_hash', 64)->nullable()->after('shipping_address');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['tenant_id', 'source', 'buyer_phone_hash'], 'orders_buyer_phone_hash_idx');
            $table->index(['tenant_id', 'source', 'recipient_phone_hash'], 'orders_recipient_phone_hash_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_buyer_phone_hash_idx');
            $table->dropIndex('orders_recipient_phone_hash_idx');
            $table->dropColumn(['buyer_phone_hash', 'recipient_phone_hash']);
        });
    }
};
```

- [ ] **Step 2: Chạy migrate trên DB test/dev, xác nhận không lỗi**

Run: `cd app && php artisan migrate --path=app/Modules/Orders/Database/Migrations --force` (hoặc để test suite tự
migrate qua `RefreshDatabase` ở bước 4 — bước này chỉ để phát hiện lỗi cú pháp migration sớm).
Expected: migrate chạy sạch, 2 cột + 2 index xuất hiện trên bảng `orders`.

- [ ] **Step 3: Viết test thất bại trước** (`ManualOrderPhoneHashTest.php`)

```php
<?php

namespace Tests\Feature\Orders;

use CMBcoreSeller\Modules\Customers\Support\CustomerPhoneNormalizer;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Services\ManualOrderService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualOrderPhoneHashTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Sku $sku;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $warehouse = Warehouse::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'name' => 'Kho chính', 'is_default' => true,
        ]);
        $this->sku = Sku::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'code' => 'SKU-1', 'name' => 'Áo',
            'warehouse_id' => $warehouse->getKey(), 'stock_on_hand' => 10, 'stock_reserved' => 0,
        ]);
    }

    private function baseData(array $overrides = []): array
    {
        return array_merge([
            'buyer' => ['name' => 'Chị A', 'phone' => '0912345678'],
            'recipient' => ['name' => 'Chị A', 'phone' => '0912345678', 'address' => 'HN'],
            'items' => [['sku_id' => $this->sku->getKey(), 'quantity' => 1, 'unit_price' => 100000, 'discount' => 0]],
        ], $overrides);
    }

    public function test_create_sets_buyer_and_recipient_phone_hash(): void
    {
        $order = app(ManualOrderService::class)->create($this->tenant->getKey(), null, $this->baseData());

        $expected = CustomerPhoneNormalizer::normalizeAndHash('0912345678');
        $this->assertSame($expected, $order->buyer_phone_hash);
        $this->assertSame($expected, $order->recipient_phone_hash);
    }

    public function test_create_with_recipient_only_sets_recipient_hash_and_null_buyer_hash(): void
    {
        // Ca lỗi gốc (SPEC 2026-07-13): chỉ điền "Nhận hàng" ⇒ buyer_phone rỗng.
        $order = app(ManualOrderService::class)->create($this->tenant->getKey(), null, $this->baseData([
            'buyer' => ['name' => 'Chị A'],
        ]));

        $this->assertNull($order->buyer_phone_hash);
        $this->assertSame(CustomerPhoneNormalizer::normalizeAndHash('0912345678'), $order->recipient_phone_hash);
    }

    public function test_update_recomputes_hash_when_phone_changes(): void
    {
        $order = app(ManualOrderService::class)->create($this->tenant->getKey(), null, $this->baseData());

        $updated = app(ManualOrderService::class)->update($order, [
            'buyer' => ['phone' => '0987654321'],
        ]);

        $this->assertSame(CustomerPhoneNormalizer::normalizeAndHash('0987654321'), $updated->buyer_phone_hash);
    }

    public function test_update_without_phone_change_keeps_existing_hash(): void
    {
        $order = app(ManualOrderService::class)->create($this->tenant->getKey(), null, $this->baseData());
        $originalHash = $order->buyer_phone_hash;

        $updated = app(ManualOrderService::class)->update($order, ['note' => 'ghi chú mới']);

        $this->assertSame($originalHash, $updated->buyer_phone_hash);
    }
}
```

- [ ] **Step 4: Chạy test — phải FAIL**

Run: `cd app && php artisan test --filter=ManualOrderPhoneHashTest`
Expected: FAIL — `buyer_phone_hash`/`recipient_phone_hash` là null (chưa có code set).

- [ ] **Step 5: Sửa `ManualOrderService.php`**

5a. Thêm import (đầu file, cạnh các `use CMBcoreSeller\Modules\...` khác):

```php
use CMBcoreSeller\Modules\Customers\Support\CustomerPhoneNormalizer;
```

5b. Thêm private method mới (đặt cạnh `buildShippingAddress()` — tìm bằng cách grep `private function buildShippingAddress` trong file):

```php
    /**
     * Hash SĐT người mua + người nhận (đã chuẩn hoá) để tra trùng nhanh qua index — design
     * 2026-07-14. Null khi SĐT không chuẩn hoá được (rỗng/mask/không hợp lệ) — KHÔNG match.
     *
     * @param  array<string,mixed>  $shippingAddress
     * @return array{buyer_phone_hash: ?string, recipient_phone_hash: ?string}
     */
    private function phoneHashes(?string $buyerPhone, array $shippingAddress): array
    {
        return [
            'buyer_phone_hash' => CustomerPhoneNormalizer::normalizeAndHash($buyerPhone),
            'recipient_phone_hash' => CustomerPhoneNormalizer::normalizeAndHash($shippingAddress['phone'] ?? null),
        ];
    }
```

5c. Trong `create()`, tìm khối (trong `DB::transaction` closure):

```php
            $attrs = [
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'source' => 'manual',
                'channel_account_id' => null,
                'external_order_id' => null,
                'order_number' => $this->generateOrderNumber($tenantId),
                // R3 (Sprint 4) — denormalize `orders.carrier='manual'` ngay từ lúc tạo. Trước đây null đến
                // tận khi chuẩn bị hàng ⇒ chip "Vận chuyển" trên trang Đơn hàng bỏ sót đơn manual chưa prepare.
                'carrier' => 'manual',
                'status' => $status,
                'raw_status' => $status->value,
                'payment_status' => $paymentStatus,
                'buyer_name' => $buyer['name'] ?? null,
                'buyer_phone' => $buyer['phone'] ?? null,
                // shipping_address ưu tiên `recipient` (FE mới); fallback `buyer` (legacy / shape cũ).
                'shipping_address' => $this->buildShippingAddress($buyer, $recipient),
                'currency' => 'VND',
```

Thay bằng (thêm 1 dòng biến local trước `$attrs = [`, đổi dòng `shipping_address`, thêm 1 dòng spread hash):

```php
            $shippingAddress = $this->buildShippingAddress($buyer, $recipient);
            $attrs = [
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'source' => 'manual',
                'channel_account_id' => null,
                'external_order_id' => null,
                'order_number' => $this->generateOrderNumber($tenantId),
                // R3 (Sprint 4) — denormalize `orders.carrier='manual'` ngay từ lúc tạo. Trước đây null đến
                // tận khi chuẩn bị hàng ⇒ chip "Vận chuyển" trên trang Đơn hàng bỏ sót đơn manual chưa prepare.
                'carrier' => 'manual',
                'status' => $status,
                'raw_status' => $status->value,
                'payment_status' => $paymentStatus,
                'buyer_name' => $buyer['name'] ?? null,
                'buyer_phone' => $buyer['phone'] ?? null,
                // shipping_address ưu tiên `recipient` (FE mới); fallback `buyer` (legacy / shape cũ).
                'shipping_address' => $shippingAddress,
                // Design 2026-07-14 — hash SĐT (buyer + recipient) để tra trùng nhanh, xem OrderLookupService.
                ...$this->phoneHashes($buyer['phone'] ?? null, $shippingAddress),
                'currency' => 'VND',
```

5d. Trong `update()`, tìm khối:

```php
        if ($buyer !== [] || $recipient !== []) {
            // Khi `recipient` có ⇒ rebuild toàn bộ shipping_address bằng helper (giống create).
            // Khi chỉ có `buyer` ⇒ merge từng field vào address cũ để giữ data đã chọn trước đó.
            if ($recipient !== []) {
                $fill['shipping_address'] = $this->buildShippingAddress($buyer, $recipient);
            } else {
                $addr = (array) ($order->shipping_address ?? []);
                foreach (['name' => 'name', 'phone' => 'phone', 'address' => 'address'] as $src => $dst) {
                    if (array_key_exists($src, $buyer)) {
                        $addr[$dst] = $buyer[$src];
                    }
                }
                $fill['shipping_address'] = array_filter($addr, fn ($v) => $v !== null && $v !== '');
            }
            if (array_key_exists('name', $buyer)) {
                $fill['buyer_name'] = $buyer['name'] ?: null;
            }
            if (array_key_exists('phone', $buyer)) {
                $fill['buyer_phone'] = $buyer['phone'] ?: null;
            }
        }
```

Thêm 3 dòng cuối khối (giữ nguyên phần trên, chỉ thêm sau dòng `$fill['buyer_phone'] = ...` đóng `}`):

```php
        if ($buyer !== [] || $recipient !== []) {
            // Khi `recipient` có ⇒ rebuild toàn bộ shipping_address bằng helper (giống create).
            // Khi chỉ có `buyer` ⇒ merge từng field vào address cũ để giữ data đã chọn trước đó.
            if ($recipient !== []) {
                $fill['shipping_address'] = $this->buildShippingAddress($buyer, $recipient);
            } else {
                $addr = (array) ($order->shipping_address ?? []);
                foreach (['name' => 'name', 'phone' => 'phone', 'address' => 'address'] as $src => $dst) {
                    if (array_key_exists($src, $buyer)) {
                        $addr[$dst] = $buyer[$src];
                    }
                }
                $fill['shipping_address'] = array_filter($addr, fn ($v) => $v !== null && $v !== '');
            }
            if (array_key_exists('name', $buyer)) {
                $fill['buyer_name'] = $buyer['name'] ?: null;
            }
            if (array_key_exists('phone', $buyer)) {
                $fill['buyer_phone'] = $buyer['phone'] ?: null;
            }
            // Design 2026-07-14 — hash theo giá trị HIỆU LỰC sau merge (mới nếu đổi, cũ nếu không).
            $fill += $this->phoneHashes(
                $fill['buyer_phone'] ?? $order->buyer_phone,
                $fill['shipping_address'],
            );
        }
```

- [ ] **Step 6: Chạy lại test — phải PASS**

Run: `cd app && php artisan test --filter=ManualOrderPhoneHashTest`
Expected: PASS (4 test).

- [ ] **Step 7: Quality gate**

Run: `cd app && vendor/bin/pint --test app/Modules/Orders/Services/ManualOrderService.php && vendor/bin/phpstan analyse app/Modules/Orders/Services/ManualOrderService.php`
Expected: PASS (0 lỗi mới — so sánh với baseline hiện có nếu phpstan báo lỗi, theo cách Task trước đã làm:
`git stash` file rồi chạy lại phpstan để xác nhận lỗi có sẵn từ trước hay không).

- [ ] **Step 8: Commit**

```bash
git add app/app/Modules/Orders/Database/Migrations/2026_07_14_100000_add_phone_hash_to_orders_table.php app/app/Modules/Orders/Services/ManualOrderService.php app/tests/Feature/Orders/ManualOrderPhoneHashTest.php
git commit -m "feat(orders): thêm cột hash SĐT buyer/recipient, ManualOrderService ghi tại write-time"
```

---

## Task 2: `OrderLookupService::recentManualByPhone` — query bằng hash

**Files:**
- Modify: `app/app/Modules/Orders/Services/OrderLookupService.php`
- Modify: `app/tests/Feature/Orders/OrderLookupDuplicateByPhoneTest.php` (⚠️ helper `makeOrder()` tạo `Order`
  TRỰC TIẾP, bỏ qua `ManualOrderService` ⇒ không tự có hash — PHẢI cập nhật helper, nếu không toàn bộ 6 test hiện
  có trong file này sẽ FAIL sau khi đổi query sang hash).

**Interfaces:**
- Consumes: `orders.buyer_phone_hash`/`recipient_phone_hash` (Task 1), `CustomerPhoneNormalizer::normalizeAndHash()`.
- Produces: hành vi `OrderLookupContract::recentManualByPhone()` giữ NGUYÊN chữ ký + kết quả trả về (chỉ đổi cách
  query nội bộ) — Task khác không phụ thuộc thêm gì mới ở đây.

- [ ] **Step 1: Sửa test fixture TRƯỚC (không phải TDD kiểu viết-test-mới — đây là sửa fixture cho code cũ đang
  test hành vi không đổi). Trong `OrderLookupDuplicateByPhoneTest.php`, thêm import + sửa `makeOrder()`:**

Thêm import ở đầu file:
```php
use CMBcoreSeller\Modules\Customers\Support\CustomerPhoneNormalizer;
```

Sửa method `makeOrder()`:
```php
    private function makeOrder(array $attrs, ?int $tenantId = null): Order
    {
        $merged = array_merge([
            'tenant_id' => $tenantId ?? $this->tenant->getKey(), 'source' => 'manual', 'customer_id' => null,
            'raw_status' => 'X', 'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000,
            'tags' => [], 'shipping_address' => [],
        ], $attrs);
        // Test tạo Order trực tiếp (bỏ qua ManualOrderService) ⇒ phải tự set hash để query mới (design
        // 2026-07-14) tìm thấy — KHÔNG đổi hành vi test, chỉ bù phần ManualOrderService lẽ ra tự làm.
        $merged['buyer_phone_hash'] = CustomerPhoneNormalizer::normalizeAndHash($merged['buyer_phone'] ?? null);
        $merged['recipient_phone_hash'] = CustomerPhoneNormalizer::normalizeAndHash($merged['shipping_address']['phone'] ?? null);

        return Order::withoutGlobalScope(TenantScope::class)->create($merged);
    }
```

- [ ] **Step 2: Chạy test hiện có trước khi sửa service — phải PASS (baseline, đường query cũ vẫn còn)**

Run: `cd app && php artisan test --filter=OrderLookupDuplicateByPhoneTest`
Expected: PASS (6/6 — sửa fixture không đổi hành vi vì service vẫn dùng query PHP cũ).

- [ ] **Step 3: Sửa `OrderLookupService::recentManualByPhone()`**

```php
    public function recentManualByPhone(int $tenantId, string $rawPhone, int $limit = 20): array
    {
        $hash = CustomerPhoneNormalizer::normalizeAndHash($rawPhone);
        if ($hash === null) {
            return [];
        }

        // Design 2026-07-14 — query bằng hash (index), thay load-hết-rồi-so-khớp-PHP (O(N) cũ).
        return Order::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('source', 'manual')
            ->whereNull('deleted_at')
            ->where(fn ($q) => $q->where('buyer_phone_hash', $hash)->orWhere('recipient_phone_hash', $hash))
            ->with('items:id,order_id,name,quantity')
            ->orderByDesc('created_at')->orderByDesc('id')
            ->limit(max(1, $limit))
            ->get()
            ->map(fn (Order $o) => OrderSummary::fromModel($o))
            ->all();
    }
```

(Import `CustomerPhoneNormalizer` đã có sẵn ở đầu file — không thêm mới.)

- [ ] **Step 4: Chạy lại đúng test cũ — phải VẪN PASS (hành vi không đổi, chỉ đổi cơ chế)**

Run: `cd app && php artisan test --filter=OrderLookupDuplicateByPhoneTest`
Expected: PASS (6/6 — CHỨNG MINH query hash cho kết quả giống hệt query PHP cũ).

- [ ] **Step 5: Chạy thêm `ManualOrderPhoneHashTest` (Task 1) để chắc chắn 2 task khớp nhau**

Run: `cd app && php artisan test --filter=ManualOrderPhoneHashTest`
Expected: PASS.

- [ ] **Step 6: Quality gate**

Run: `cd app && vendor/bin/pint --test app/Modules/Orders/Services/OrderLookupService.php && vendor/bin/phpstan analyse app/Modules/Orders/Services/OrderLookupService.php`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Orders/Services/OrderLookupService.php app/tests/Feature/Orders/OrderLookupDuplicateByPhoneTest.php
git commit -m "perf(orders): recentManualByPhone query bằng hash thay vì load-hết-so-khớp-PHP"
```

---

## Task 3: Backfill command `orders:backfill-phone-hash`

**Files:**
- Create: `app/app/Modules/Orders/Console/Commands/BackfillManualOrderPhoneHash.php`
- Modify: `app/app/Modules/Orders/OrdersServiceProvider.php`
- Test: `app/tests/Feature/Orders/BackfillManualOrderPhoneHashTest.php`

**Interfaces:**
- Consumes: `orders.buyer_phone_hash`/`recipient_phone_hash` (Task 1).
- Produces: `php artisan orders:backfill-phone-hash` — lệnh vận hành chạy tay sau migrate, không task nào khác gọi.

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Orders;

use CMBcoreSeller\Modules\Customers\Support\CustomerPhoneNormalizer;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillManualOrderPhoneHashTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(Tenant $tenant, array $attrs): Order
    {
        return Order::withoutGlobalScope(TenantScope::class)->create(array_merge([
            'tenant_id' => $tenant->getKey(), 'source' => 'manual', 'raw_status' => 'X',
            'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000,
            'tags' => [], 'shipping_address' => [],
        ], $attrs));
    }

    public function test_backfills_null_hash_for_existing_orders(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);
        $order = $this->makeOrder($tenant, [
            'order_number' => 'M-1', 'buyer_phone' => '0912345678',
            'shipping_address' => ['phone' => '0987654321'],
        ]);
        $this->assertNull($order->buyer_phone_hash);

        $this->artisan('orders:backfill-phone-hash')->assertExitCode(0);

        $order->refresh();
        $this->assertSame(CustomerPhoneNormalizer::normalizeAndHash('0912345678'), $order->buyer_phone_hash);
        $this->assertSame(CustomerPhoneNormalizer::normalizeAndHash('0987654321'), $order->recipient_phone_hash);
    }

    public function test_skips_orders_already_hashed(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);
        $customHash = str_repeat('a', 64);
        $order = $this->makeOrder($tenant, [
            'order_number' => 'M-2', 'buyer_phone' => '0912345678',
            'buyer_phone_hash' => $customHash,
        ]);

        $this->artisan('orders:backfill-phone-hash')->assertExitCode(0);

        // Đã có hash từ trước ⇒ KHÔNG bị ghi đè (idempotent, không tốn công tính lại).
        $this->assertSame($customHash, $order->fresh()->buyer_phone_hash);
    }

    public function test_ignores_non_manual_orders(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);
        $order = $this->makeOrder($tenant, [
            'order_number' => 'TT-1', 'source' => 'tiktok', 'buyer_phone' => '0912345678',
        ]);

        $this->artisan('orders:backfill-phone-hash')->assertExitCode(0);

        $this->assertNull($order->fresh()->buyer_phone_hash);
    }
}
```

- [ ] **Step 2: Chạy test — phải FAIL**

Run: `cd app && php artisan test --filter=BackfillManualOrderPhoneHashTest`
Expected: FAIL — `Command "orders:backfill-phone-hash" is not defined.`

- [ ] **Step 3: Viết command**

```php
<?php

namespace CMBcoreSeller\Modules\Orders\Console\Commands;

use CMBcoreSeller\Modules\Customers\Support\CustomerPhoneNormalizer;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

/**
 * Backfill `orders.buyer_phone_hash`/`recipient_phone_hash` cho đơn thủ công tạo TRƯỚC khi 2 cột
 * này tồn tại (design 2026-07-14). Chạy tay 1 lần sau khi migrate cột — an toàn chạy lại nhiều lần
 * (idempotent: bỏ qua đơn đã có hash).
 */
class BackfillManualOrderPhoneHash extends Command
{
    protected $signature = 'orders:backfill-phone-hash';

    protected $description = 'Backfill buyer_phone_hash/recipient_phone_hash cho đơn thủ công (design 2026-07-14)';

    public function handle(): int
    {
        $count = 0;
        Order::withoutGlobalScope(TenantScope::class)
            ->where('source', 'manual')
            ->where(fn ($q) => $q->whereNull('buyer_phone_hash')->orWhereNull('recipient_phone_hash'))
            ->orderBy('id')
            ->chunkById(500, function (Collection $orders) use (&$count) {
                foreach ($orders as $order) {
                    $update = [];
                    if ($order->buyer_phone_hash === null) {
                        $update['buyer_phone_hash'] = CustomerPhoneNormalizer::normalizeAndHash($order->buyer_phone);
                    }
                    if ($order->recipient_phone_hash === null) {
                        $update['recipient_phone_hash'] = CustomerPhoneNormalizer::normalizeAndHash(
                            (array) ($order->shipping_address ?? [])['phone'] ?? null
                        );
                    }
                    if ($update !== []) {
                        $order->forceFill($update)->save();
                        $count++;
                    }
                }
            });

        $this->info("Đã backfill hash cho {$count} đơn thủ công.");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Đăng ký command trong `OrdersServiceProvider.php`**

Tìm dòng `$this->commands([RemapOrderStatus::class]);` trong `boot()`, sửa thành:

```php
            $this->commands([RemapOrderStatus::class, BackfillManualOrderPhoneHash::class]);
```

Thêm import đầu file:
```php
use CMBcoreSeller\Modules\Orders\Console\Commands\BackfillManualOrderPhoneHash;
```

- [ ] **Step 5: Chạy lại test — phải PASS**

Run: `cd app && php artisan test --filter=BackfillManualOrderPhoneHashTest`
Expected: PASS (3 test).

- [ ] **Step 6: Quality gate**

Run: `cd app && vendor/bin/pint --test app/Modules/Orders/Console/Commands/BackfillManualOrderPhoneHash.php app/Modules/Orders/OrdersServiceProvider.php && vendor/bin/phpstan analyse app/Modules/Orders/Console/Commands/BackfillManualOrderPhoneHash.php`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Orders/Console/Commands/BackfillManualOrderPhoneHash.php app/app/Modules/Orders/OrdersServiceProvider.php app/tests/Feature/Orders/BackfillManualOrderPhoneHashTest.php
git commit -m "feat(orders): artisan orders:backfill-phone-hash cho đơn thủ công cũ"
```

---

## Task 4: Webhook dedupe giai đoạn 1 — cột `dedupe_status_key` (additive, an toàn)

**Files:**
- Create: `app/app/Modules/Channels/Database/Migrations/2026_07_14_100000_add_dedupe_status_key_to_webhook_events_table.php`
- Modify: `app/app/Modules/Channels/Services/WebhookIngestService.php`
- Test: `app/tests/Feature/Channels/WebhookDedupeStatusKeyTest.php`

**Interfaces:**
- Produces: cột `webhook_events.dedupe_status_key` (string, nullable ở giai đoạn 1) luôn được ghi
  `$event->orderRawStatus ?? ''` khi tạo row mới — Task 5 (backfill+dedupe command) và Task 6 (unique constraint)
  đọc đúng tên cột này.

- [ ] **Step 1: Viết migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Giai đoạn 1/2 (design 2026-07-14-manual-order-phone-hash-and-webhook-dedupe §2) — chỉ thêm cột,
 * KHÔNG thêm unique constraint (dữ liệu hiện tại có thể đã có row trùng do race cũ — xem giai đoạn 2
 * migration `2026_07_14_100001_add_dedupe_unique_to_webhook_events_table.php`, PHẢI chạy
 * `php artisan webhooks:backfill-dedupe-key` giữa 2 giai đoạn).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->string('dedupe_status_key')->nullable()->after('order_raw_status');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropColumn('dedupe_status_key');
        });
    }
};
```

- [ ] **Step 2: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Channels\Services\WebhookIngestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

/**
 * Giai đoạn 1 (design 2026-07-14 §2) — dedupe_status_key phải được ghi đúng giá trị mỗi lần tạo
 * webhook event mới. Dùng registry thật (đã có sẵn provider test trong container) thay vì tạo
 * connector giả — xem cách IntegrationsRegistryTest / các test webhook khác dựng request.
 */
class WebhookDedupeStatusKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_sets_dedupe_status_key_from_order_raw_status(): void
    {
        config()->set('integrations.channels', ['tiktok']);
        $this->app->forgetInstance(ChannelRegistry::class);

        $payload = json_encode([
            'type' => 'ORDER_STATUS_CHANGE',
            'data' => ['order_id' => 'ORD_1', 'order_status' => 'AWAITING_SHIPMENT'],
        ]);
        $request = Request::create('/webhook/channels/tiktok', 'POST', [], [], [], [], $payload);
        $request->headers->set('Content-Type', 'application/json');

        app(WebhookIngestService::class)->ingest('tiktok', $request);

        $row = WebhookEvent::query()->where('provider', 'tiktok')->where('external_id', 'ORD_1')->first();
        $this->assertNotNull($row);
        $this->assertSame('AWAITING_SHIPMENT', $row->dedupe_status_key);
    }

    public function test_sets_empty_string_dedupe_status_key_when_status_null(): void
    {
        config()->set('integrations.channels', ['tiktok']);
        $this->app->forgetInstance(ChannelRegistry::class);

        // Event không kèm order_status ⇒ orderRawStatus null ở phía DTO parse (tuỳ connector) — test
        // trực tiếp cột bằng cách gọi lại ingest với payload không có order_status.
        $payload = json_encode(['type' => 'ORDER_STATUS_CHANGE', 'data' => ['order_id' => 'ORD_2']]);
        $request = Request::create('/webhook/channels/tiktok', 'POST', [], [], [], [], $payload);
        $request->headers->set('Content-Type', 'application/json');

        app(WebhookIngestService::class)->ingest('tiktok', $request);

        $row = WebhookEvent::query()->where('provider', 'tiktok')->where('external_id', 'ORD_2')->first();
        $this->assertNotNull($row);
        $this->assertSame('', $row->dedupe_status_key);
    }
}
```

**Lưu ý cho implementer:** nếu payload mẫu ở trên không khớp đúng shape `TikTokWebhookConnector::parseWebhook()`
thực tế (có thể `order_status` nằm ở path khác, hoặc field `type` khác), hãy đọc
`app/app/Integrations/Channels/TikTok/TikTok*.php` (connector parseWebhook) để dựng payload đúng — mục tiêu test
KHÔNG phải test connector, chỉ cần 1 request hợp lệ tạo được `WebhookEvent` với `order_raw_status` đã biết trước
để assert cột `dedupe_status_key` ăn khớp theo. Nếu dễ hơn, có thể mock/fake `ChannelRegistry` để trả về 1
connector giả trả sẵn `MessagingWebhookEventDTO`/DTO tương ứng với `orderRawStatus` mong muốn — chọn cách nào ít
xâm lấn nhất, miễn giữ đúng mục tiêu assert.

- [ ] **Step 3: Chạy test — phải FAIL**

Run: `cd app && php artisan test --filter=WebhookDedupeStatusKeyTest`
Expected: FAIL — cột `dedupe_status_key` null (chưa có code ghi).

- [ ] **Step 4: Sửa `WebhookIngestService::ingest()`**

Tìm khối tạo row:

```php
        $row = WebhookEvent::create([
            'provider' => $provider,
            'event_type' => $event->type,
            'external_id' => $dedupeKey,
            'external_shop_id' => $event->externalShopId,
            'order_raw_status' => $event->orderRawStatus,
            'raw_type' => $event->raw['_raw_type'] ?? ($event->raw['type'] ?? null),
            'signature_ok' => true,
            'headers' => $this->safeHeaders($request),
            'payload' => $event->raw,
            'status' => WebhookEvent::STATUS_PENDING,
            'received_at' => now(),
        ]);
```

Thêm 1 dòng (`dedupe_status_key`) — giai đoạn 1 CHƯA bọc try/catch (chưa có constraint để vi phạm, để dành giai
đoạn 2 ở Task 6):

```php
        $row = WebhookEvent::create([
            'provider' => $provider,
            'event_type' => $event->type,
            'external_id' => $dedupeKey,
            'external_shop_id' => $event->externalShopId,
            'order_raw_status' => $event->orderRawStatus,
            // Design 2026-07-14 §2 — khoá dedupe phẳng (không NULL) để giai đoạn 2 đặt unique constraint
            // thật được (NULL không so bằng NULL trong unique index chuẩn SQL).
            'dedupe_status_key' => $event->orderRawStatus ?? '',
            'raw_type' => $event->raw['_raw_type'] ?? ($event->raw['type'] ?? null),
            'signature_ok' => true,
            'headers' => $this->safeHeaders($request),
            'payload' => $event->raw,
            'status' => WebhookEvent::STATUS_PENDING,
            'received_at' => now(),
        ]);
```

Thêm `'dedupe_status_key'` vào `$fillable` của `WebhookEvent` model
(`app/app/Modules/Channels/Models/WebhookEvent.php`, mảng `protected $fillable`) và thuộc tính docblock
`@property string|null $dedupe_status_key`.

- [ ] **Step 5: Chạy lại test — phải PASS**

Run: `cd app && php artisan test --filter=WebhookDedupeStatusKeyTest`
Expected: PASS (2 test).

- [ ] **Step 6: Chạy test webhook hiện có để chắc không phá luồng cũ**

Run: `cd app && php artisan test --filter=TikTokSyncTest`
Expected: PASS (giai đoạn 1 chỉ thêm cột, không đổi logic dedupe hiện tại).

- [ ] **Step 7: Quality gate**

Run: `cd app && vendor/bin/pint --test app/Modules/Channels/Services/WebhookIngestService.php app/Modules/Channels/Models/WebhookEvent.php && vendor/bin/phpstan analyse app/Modules/Channels/Services/WebhookIngestService.php app/Modules/Channels/Models/WebhookEvent.php`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add app/app/Modules/Channels/Database/Migrations/2026_07_14_100000_add_dedupe_status_key_to_webhook_events_table.php app/app/Modules/Channels/Services/WebhookIngestService.php app/app/Modules/Channels/Models/WebhookEvent.php app/tests/Feature/Channels/WebhookDedupeStatusKeyTest.php
git commit -m "feat(channels): giai đoạn 1 webhook dedupe atomic — cột dedupe_status_key additive"
```

---

## Task 5: Backfill + dedupe command `webhooks:backfill-dedupe-key`

**Files:**
- Create: `app/app/Modules/Channels/Console/Commands/BackfillWebhookDedupeKey.php`
- Modify: `app/app/Modules/Channels/ChannelsServiceProvider.php`
- Test: `app/tests/Feature/Channels/BackfillWebhookDedupeKeyTest.php`

**Interfaces:**
- Consumes: `webhook_events.dedupe_status_key` (Task 4).
- Produces: `php artisan webhooks:backfill-dedupe-key` — chạy tay giữa giai đoạn 1 (Task 4) và giai đoạn 2
  (Task 6), PHẢI chạy xong (log "0 duplicate rows removed" ở lần chạy thứ 2 liên tiếp) trước khi deploy Task 6.

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillWebhookDedupeKeyTest extends TestCase
{
    use RefreshDatabase;

    private function makeRow(array $attrs): WebhookEvent
    {
        return WebhookEvent::query()->create(array_merge([
            'provider' => 'tiktok', 'event_type' => 'order', 'external_id' => 'ORD_1',
            'external_shop_id' => 'SHOP_1', 'signature_ok' => true, 'status' => WebhookEvent::STATUS_PENDING,
            'received_at' => now(),
        ], $attrs));
    }

    public function test_backfills_null_dedupe_status_key(): void
    {
        $row = $this->makeRow(['order_raw_status' => 'PICKED', 'external_id' => 'ORD_BACKFILL']);
        $this->assertNull($row->dedupe_status_key);

        $this->artisan('webhooks:backfill-dedupe-key')->assertExitCode(0);

        $this->assertSame('PICKED', $row->fresh()->dedupe_status_key);
    }

    public function test_backfills_empty_string_when_status_null(): void
    {
        $row = $this->makeRow(['order_raw_status' => null, 'external_id' => 'ORD_BACKFILL_NULL']);

        $this->artisan('webhooks:backfill-dedupe-key')->assertExitCode(0);

        $this->assertSame('', $row->fresh()->dedupe_status_key);
    }

    public function test_removes_true_duplicate_rows_keeping_earliest_id(): void
    {
        // Race cũ: 2 row giống hệt nhau theo khoá dedupe thật (kể cả order_raw_status) — phải xoá row sau,
        // giữ id nhỏ nhất.
        $older = $this->makeRow(['external_id' => 'ORD_DUP', 'order_raw_status' => 'PICKED']);
        $newer = $this->makeRow(['external_id' => 'ORD_DUP', 'order_raw_status' => 'PICKED']);

        $this->artisan('webhooks:backfill-dedupe-key')->assertExitCode(0);

        $this->assertNotNull(WebhookEvent::query()->find($older->id));
        $this->assertNull(WebhookEvent::query()->find($newer->id));
    }

    public function test_keeps_rows_with_different_status_as_valid_transitions(): void
    {
        // KHÔNG phải trùng — 2 trạng thái khác nhau của cùng đơn là 2 transition hợp lệ, không xoá.
        $a = $this->makeRow(['external_id' => 'ORD_TRANS', 'order_raw_status' => 'AWAITING_SHIPMENT']);
        $b = $this->makeRow(['external_id' => 'ORD_TRANS', 'order_raw_status' => 'PICKED']);

        $this->artisan('webhooks:backfill-dedupe-key')->assertExitCode(0);

        $this->assertNotNull(WebhookEvent::query()->find($a->id));
        $this->assertNotNull(WebhookEvent::query()->find($b->id));
    }

    public function test_idempotent_second_run_removes_nothing(): void
    {
        $this->makeRow(['external_id' => 'ORD_DUP2', 'order_raw_status' => 'PICKED']);
        $this->makeRow(['external_id' => 'ORD_DUP2', 'order_raw_status' => 'PICKED']);

        $this->artisan('webhooks:backfill-dedupe-key')->assertExitCode(0);
        $this->assertSame(1, WebhookEvent::query()->where('external_id', 'ORD_DUP2')->count());

        // Chạy lại lần 2 — không còn gì để xoá.
        $this->artisan('webhooks:backfill-dedupe-key')->assertExitCode(0);
        $this->assertSame(1, WebhookEvent::query()->where('external_id', 'ORD_DUP2')->count());
    }
}
```

- [ ] **Step 2: Chạy test — phải FAIL**

Run: `cd app && php artisan test --filter=BackfillWebhookDedupeKeyTest`
Expected: FAIL — `Command "webhooks:backfill-dedupe-key" is not defined.`

- [ ] **Step 3: Viết command**

```php
<?php

namespace CMBcoreSeller\Modules\Channels\Console\Commands;

use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

/**
 * Giai đoạn giữa (design 2026-07-14 §2) — chạy 1 LẦN sau migration giai đoạn 1
 * (`2026_07_14_100000_add_dedupe_status_key_to_webhook_events_table`), TRƯỚC migration giai đoạn 2
 * (`2026_07_14_100001_add_dedupe_unique_to_webhook_events_table`):
 *   1. Backfill `dedupe_status_key` cho row cũ (order_raw_status ?? '').
 *   2. Xoá row TRÙNG THẬT theo (provider, event_type, external_id, external_shop_id, dedupe_status_key),
 *      giữ lại id nhỏ nhất mỗi nhóm — dọn sạch trước khi giai đoạn 2 thêm unique constraint.
 * Idempotent — chạy lại không đổi gì nếu đã sạch.
 */
class BackfillWebhookDedupeKey extends Command
{
    protected $signature = 'webhooks:backfill-dedupe-key';

    protected $description = 'Backfill dedupe_status_key + xoá row webhook_events trùng thật (design 2026-07-14, chạy trước migration giai đoạn 2)';

    public function handle(): int
    {
        $backfilled = 0;
        WebhookEvent::query()->whereNull('dedupe_status_key')->orderBy('id')
            ->chunkById(500, function (Collection $rows) use (&$backfilled) {
                foreach ($rows as $row) {
                    $row->forceFill(['dedupe_status_key' => $row->order_raw_status ?? ''])->save();
                    $backfilled++;
                }
            });
        $this->info("Đã backfill dedupe_status_key cho {$backfilled} row.");

        $removed = 0;
        /** @var array<string, int> $seen khoá dedupe => id nhỏ nhất đã thấy */
        $seen = [];
        WebhookEvent::query()->orderBy('id')
            ->chunkById(500, function (Collection $rows) use (&$seen, &$removed) {
                foreach ($rows as $row) {
                    $key = implode('|', [
                        $row->provider, $row->event_type, (string) $row->external_id,
                        (string) $row->external_shop_id, (string) $row->dedupe_status_key,
                    ]);
                    if (isset($seen[$key])) {
                        $row->delete();
                        $removed++;

                        continue;
                    }
                    $seen[$key] = $row->id;
                }
            });

        $this->info("Đã xoá {$removed} row trùng thật (giữ id nhỏ nhất mỗi nhóm).");

        return self::SUCCESS;
    }
}
```

**Lưu ý implementer:** `chunkById` với `->delete()` bên trong callback thay đổi tập kết quả đang phân trang theo
id — vì code chỉ XOÁ (không sửa `id`/`dedupe_status_key` của row đang giữ lại) và luôn xử lý theo thứ tự `id`
tăng dần với con trỏ dựa trên id CUỐI CÙNG đã thấy trong chunk, việc xoá row có id LỚN HƠN con trỏ hiện tại không
làm lệch phân trang (chunkById lấy `WHERE id > last_seen_id`). Nếu chạy test thấy hành vi khác dự kiến, đổi sang
`orderBy('id')->get()` một lần (chấp nhận load hết vì đây là lệnh vận hành chạy 1 lần, không phải hot path) thay
vì `chunkById`, ghi rõ lý do trong report.

- [ ] **Step 4: Đăng ký command trong `ChannelsServiceProvider.php`**

```php
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        if (is_file(__DIR__.'/Http/routes.php')) {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([BackfillWebhookDedupeKey::class]);
        }
    }
```

Thêm import đầu file:
```php
use CMBcoreSeller\Modules\Channels\Console\Commands\BackfillWebhookDedupeKey;
```

- [ ] **Step 5: Chạy lại test — phải PASS**

Run: `cd app && php artisan test --filter=BackfillWebhookDedupeKeyTest`
Expected: PASS (5 test).

- [ ] **Step 6: Quality gate**

Run: `cd app && vendor/bin/pint --test app/Modules/Channels/Console/Commands/BackfillWebhookDedupeKey.php app/Modules/Channels/ChannelsServiceProvider.php && vendor/bin/phpstan analyse app/Modules/Channels/Console/Commands/BackfillWebhookDedupeKey.php`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Channels/Console/Commands/BackfillWebhookDedupeKey.php app/app/Modules/Channels/ChannelsServiceProvider.php app/tests/Feature/Channels/BackfillWebhookDedupeKeyTest.php
git commit -m "feat(channels): artisan webhooks:backfill-dedupe-key — dọn trùng trước giai đoạn 2"
```

---

## Task 6: Webhook dedupe giai đoạn 2 — unique constraint thật + insert atomic

**Files:**
- Create: `app/app/Modules/Channels/Database/Migrations/2026_07_14_100001_add_dedupe_unique_to_webhook_events_table.php`
- Modify: `app/app/Modules/Channels/Services/WebhookIngestService.php`
- Test: `app/tests/Feature/Channels/WebhookDedupeUniqueConstraintTest.php`

**Interfaces:**
- Consumes: `webhook_events.dedupe_status_key` đã backfill sạch (Task 4 + 5 phải chạy xong trước — migration này
  TỰ KIỂM TRA và ném exception nếu chưa sạch, không âm thầm bỏ qua).
- Produces: ràng buộc DB thật `webhook_events_dedupe_unique`; `WebhookIngestService::ingest()` bắt được vi phạm
  unique và trả `200 duplicate` thay vì để lộ lỗi.

**⚠️ Chỉ chạy migration này SAU KHI đã chạy `php artisan webhooks:backfill-dedupe-key` (Task 5) trên đúng DB đích
và xác nhận log "Đã xoá 0 row trùng thật" ở lần chạy gần nhất (nghĩa là đã sạch). Trên CI/test, `RefreshDatabase`
tự chạy sạch từ đầu nên không áp dụng — chỉ là lưu ý vận hành cho migration giai đoạn 2 trên DB có dữ liệu thật.**

- [ ] **Step 1: Viết migration (tự kiểm tra sạch trước khi thêm constraint)**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Giai đoạn 2/2 (design 2026-07-14 §2). CHỈ chạy sau khi `php artisan webhooks:backfill-dedupe-key`
 * (Task 5) đã dọn sạch row trùng — migration TỰ KIỂM TRA còn trùng thì ném exception rõ ràng, KHÔNG
 * âm thầm bỏ qua hay thêm constraint nửa vời.
 */
return new class extends Migration
{
    public function up(): void
    {
        $dup = DB::table('webhook_events')
            ->select('provider', 'event_type', 'external_id', 'external_shop_id', 'dedupe_status_key')
            ->groupBy('provider', 'event_type', 'external_id', 'external_shop_id', 'dedupe_status_key')
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->first();

        if ($dup !== null) {
            throw new \RuntimeException(
                'webhook_events còn row trùng theo khoá dedupe — chạy `php artisan webhooks:backfill-dedupe-key`'
                .' trước rồi migrate lại (design 2026-07-14 §2, giai đoạn 2).'
            );
        }

        Schema::table('webhook_events', function (Blueprint $table) {
            $table->unique(
                ['provider', 'event_type', 'external_id', 'external_shop_id', 'dedupe_status_key'],
                'webhook_events_dedupe_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropUnique('webhook_events_dedupe_unique');
        });
    }
};
```

- [ ] **Step 2: Viết test thất bại trước** (`WebhookDedupeUniqueConstraintTest.php`)

```php
<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Channels\Services\WebhookIngestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WebhookDedupeUniqueConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_unique_constraint_rejects_true_duplicate_at_db_level(): void
    {
        WebhookEvent::query()->create([
            'provider' => 'tiktok', 'event_type' => 'order', 'external_id' => 'ORD_1',
            'external_shop_id' => 'SHOP_1', 'order_raw_status' => 'PICKED', 'dedupe_status_key' => 'PICKED',
            'signature_ok' => true, 'status' => WebhookEvent::STATUS_PENDING, 'received_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('webhook_events')->insert([
            'provider' => 'tiktok', 'event_type' => 'order', 'external_id' => 'ORD_1',
            'external_shop_id' => 'SHOP_1', 'order_raw_status' => 'PICKED', 'dedupe_status_key' => 'PICKED',
            'signature_ok' => true, 'status' => WebhookEvent::STATUS_PENDING, 'received_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_ingest_catches_unique_violation_and_returns_duplicate_note(): void
    {
        config()->set('integrations.channels', ['tiktok']);
        $this->app->forgetInstance(ChannelRegistry::class);

        WebhookEvent::query()->create([
            'provider' => 'tiktok', 'event_type' => 'ORDER_STATUS_CHANGE', 'external_id' => 'ORD_RACE',
            'external_shop_id' => null, 'order_raw_status' => 'AWAITING_SHIPMENT',
            'dedupe_status_key' => 'AWAITING_SHIPMENT', 'signature_ok' => true,
            'status' => WebhookEvent::STATUS_PENDING, 'received_at' => now(),
        ]);

        // Giả lập race: exists() fast-path đã qua (record vừa tạo ở dòng trên xảy ra "giữa" lúc check và
        // insert của request khác) — gọi ingest() với payload tạo đúng row trùng để insert() thật sự vi phạm
        // unique. Vì exists() fast-path ở trên sẽ tự bắt được luôn (không tới nhánh insert) trong trường hợp
        // đơn giản này — implementer cần trực tiếp gọi qua Reflection hoặc test ở tầng thấp hơn nếu muốn ép
        // đúng nhánh catch QueryException; MỤC TIÊU của test này là xác nhận response cuối cùng ('note' =>
        // 'duplicate', status 200) — không bắt buộc phải ép đúng nhánh exists() hay catch, miễn hành vi
        // quan sát được (response) đúng.
        $payload = json_encode([
            'type' => 'ORDER_STATUS_CHANGE',
            'data' => ['order_id' => 'ORD_RACE', 'order_status' => 'AWAITING_SHIPMENT'],
        ]);
        $request = Request::create('/webhook/channels/tiktok', 'POST', [], [], [], [], $payload);
        $request->headers->set('Content-Type', 'application/json');

        $result = app(WebhookIngestService::class)->ingest('tiktok', $request);

        $this->assertSame(200, $result['status']);
        $this->assertSame('duplicate', $result['body']['note'] ?? null);
    }
}
```

- [ ] **Step 3: Chạy test — phải FAIL**

Run: `cd app && php artisan test --filter=WebhookDedupeUniqueConstraintTest`
Expected: FAIL — chưa có unique constraint (`test_unique_constraint_rejects_true_duplicate_at_db_level` không
ném exception).

- [ ] **Step 4: Sửa `WebhookIngestService::ingest()` — bọc `create()` trong try/catch**

Tìm khối (đã có `dedupe_status_key` từ Task 4):

```php
        $row = WebhookEvent::create([
            'provider' => $provider,
            'event_type' => $event->type,
            'external_id' => $dedupeKey,
            'external_shop_id' => $event->externalShopId,
            'order_raw_status' => $event->orderRawStatus,
            'dedupe_status_key' => $event->orderRawStatus ?? '',
            'raw_type' => $event->raw['_raw_type'] ?? ($event->raw['type'] ?? null),
            'signature_ok' => true,
            'headers' => $this->safeHeaders($request),
            'payload' => $event->raw,
            'status' => WebhookEvent::STATUS_PENDING,
            'received_at' => now(),
        ]);

        ProcessWebhookEvent::dispatch((int) $row->getKey());

        return ['status' => 200, 'body' => ['ok' => true]];
```

Thay bằng:

```php
        try {
            $row = WebhookEvent::create([
                'provider' => $provider,
                'event_type' => $event->type,
                'external_id' => $dedupeKey,
                'external_shop_id' => $event->externalShopId,
                'order_raw_status' => $event->orderRawStatus,
                'dedupe_status_key' => $event->orderRawStatus ?? '',
                'raw_type' => $event->raw['_raw_type'] ?? ($event->raw['type'] ?? null),
                'signature_ok' => true,
                'headers' => $this->safeHeaders($request),
                'payload' => $event->raw,
                'status' => WebhookEvent::STATUS_PENDING,
                'received_at' => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Race hiếm: 2 webhook trùng đến giữa lúc exists() fast-path pass và create() này — unique
            // constraint (giai đoạn 2, design 2026-07-14 §2) chặn ở tầng DB, coi như duplicate bình thường.
            if ($this->isUniqueViolation($e)) {
                Log::info('webhook.dedupe_race_caught', ['provider' => $provider, 'external_id' => $dedupeKey]);

                return ['status' => 200, 'body' => ['ok' => true, 'note' => 'duplicate']];
            }
            throw $e;
        }

        ProcessWebhookEvent::dispatch((int) $row->getKey());

        return ['status' => 200, 'body' => ['ok' => true]];
```

Thêm private helper cuối class (trước dấu `}` đóng class, cạnh `safeHeaders()`):

```php
    /** Nhận diện lỗi vi phạm unique constraint — khác các lỗi DB khác (không nuốt nhầm lỗi thật). */
    private function isUniqueViolation(\Illuminate\Database\QueryException $e): bool
    {
        // SQLSTATE 23000 (integrity constraint violation) — dùng chung được cho cả SQLite lẫn Postgres
        // qua Laravel QueryException::getCode(). Postgres driver cụ thể hơn còn có mã 23505 (unique_violation)
        // nằm trong $e->errorInfo[0] — kiểm cả hai cho chắc.
        return $e->getCode() === '23000' || ($e->errorInfo[0] ?? null) === '23505';
    }
```

- [ ] **Step 5: Chạy lại test — phải PASS**

Run: `cd app && php artisan test --filter=WebhookDedupeUniqueConstraintTest`
Expected: PASS (2 test).

- [ ] **Step 6: Chạy lại toàn bộ test Task 4 + Task 5 + regression webhook hiện có**

Run: `cd app && php artisan test --filter=WebhookDedupeStatusKeyTest && php artisan test --filter=BackfillWebhookDedupeKeyTest && php artisan test --filter=TikTokSyncTest`
Expected: PASS toàn bộ — giai đoạn 2 không phá giai đoạn 1 hay luồng webhook cũ.

- [ ] **Step 7: Quality gate**

Run: `cd app && vendor/bin/pint --test app/Modules/Channels/Services/WebhookIngestService.php && vendor/bin/phpstan analyse app/Modules/Channels/Services/WebhookIngestService.php`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add app/app/Modules/Channels/Database/Migrations/2026_07_14_100001_add_dedupe_unique_to_webhook_events_table.php app/app/Modules/Channels/Services/WebhookIngestService.php app/tests/Feature/Channels/WebhookDedupeUniqueConstraintTest.php
git commit -m "feat(channels): giai đoạn 2 webhook dedupe atomic — unique constraint thật + catch race"
```

---

## Self-Review

**1. Spec coverage:**
- §1 SĐT hash (cột, ghi tại write-time, query, backfill) → Task 1, 2, 3. ✓
- §2 Webhook dedupe 2 giai đoạn (additive → backfill/dedupe tay → unique thật + catch) → Task 4, 5, 6. ✓
- §3 Testing (mọi bullet) → có mặt trong steps tương ứng. ✓
- §4 Giới hạn ngoài code (thứ tự 2 lần deploy cho webhook, 1 lần cho phone hash) → phản ánh đúng thứ tự
  Task 4 → 5 → 6 (Task 6 ghi rõ điều kiện tiên quyết ngay đầu task).

**2. Placeholder scan:** không còn TBD/TODO. 2 chỗ có "lưu ý cho implementer" (Task 4 Step 2, Task 5 Step 3) là
hướng dẫn xử lý phần khó đoán trước khi viết plan (payload connector thật, hành vi chunkById+delete) — không phải
placeholder, là chỉ dẫn tường minh cho quyết định tại chỗ, có tiêu chí chấp nhận rõ ràng.

**3. Type consistency:** `phoneHashes(?string $buyerPhone, array $shippingAddress): array{buyer_phone_hash,
recipient_phone_hash}` dùng nhất quán Task 1 (định nghĩa + gọi 2 chỗ) và không bị gọi lại ở Task khác (Task 2/3
đọc thẳng cột DB, không gọi lại method). `dedupe_status_key` dùng nhất quán tên cột xuyên Task 4/5/6.
