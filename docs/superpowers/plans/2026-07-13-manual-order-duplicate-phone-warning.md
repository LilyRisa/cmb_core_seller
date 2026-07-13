# Cảnh báo SĐT trùng đơn cũ + gỡ toggle "Cho khách xem/thử hàng" Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ở form tạo đơn thủ công, hiển thị cảnh báo mã đơn cụ thể khi SĐT đã có đơn trước đó (bấm mở modal xem nhanh), và gỡ control "Cho khách xem/thử hàng" đã hết tác dụng.

**Architecture:** Endpoint mới ở module Orders (`GET /api/v1/orders/lookup-by-customer`) tái dùng `OrderLookupService::recentByCustomer()` có sẵn — không đụng module Customers. Frontend gọi hook mới nối tiếp sau `useCustomerLookup` (đã có `customer.id`), hiển thị `Alert` dưới card Khách hàng, tái dùng `OrderDetailModal` có sẵn để mở đơn khi bấm mã.

**Tech Stack:** Laravel 11 (PHPUnit `RefreshDatabase`), React + TypeScript + Ant Design + TanStack Query.

## Global Constraints

- Mọi lệnh PHP/Node chạy từ `app/`, không phải repo root.
- Không có JS test runner trong repo (`test-verify-baseline`) — các task frontend verify bằng `npm run typecheck` + `npm run build` + kiểm thủ công qua trình duyệt, KHÔNG viết Jest/RTL test.
- Module Orders KHÔNG được `use` internals của module Customers — chỉ nhận `customer_id` đã resolve sẵn từ FE (FE gọi `/customers/lookup` trước, có `customer.id`, rồi mới gọi endpoint Orders).
- Giữ nguyên `meta.allow_inspection` validation + fallback legacy trong `ManualOrderService`/`ShipmentService::resolveRequiredNote()` — KHÔNG xoá, vẫn là đường đọc cho đơn cũ.
- Tiền = VND nguyên; timestamps ISO-8601 UTC; response envelope `{ "data": ... }` — theo `docs/05-api/conventions.md`.
- Spec đầy đủ: `docs/specs/2026-07-13-manual-order-duplicate-phone-warning-design.md`.

---

### Task 1: Backend — endpoint `GET /api/v1/orders/lookup-by-customer`

**Files:**
- Modify: `app/app/Modules/Orders/Http/Controllers/OrderController.php`
- Modify: `app/routes/api.php:183` (thêm route cạnh `orders/stats`)
- Test: `app/tests/Feature/Orders/OrderLookupByCustomerTest.php`

**Interfaces:**
- Consumes: `OrderLookupContract::recentByCustomer(int $tenantId, int $customerId, int $limit = 5): array<OrderSummary>` (đã có, bound trong `OrdersServiceProvider`); `OrderSummary::toArray(): array{id:int,number:string,status_code:string,status:string,total:int,date:?string,items:?string}` (đã có).
- Produces: `GET /api/v1/orders/lookup-by-customer?customer_id=<int>&exclude_order_id=<int?>` → `{ "data": { "latest_order": array|null, "latest_returned_order": array|null } }` (shape của mỗi phần tử = `OrderSummary::toArray()`). Task 2 (frontend hook) tiêu thụ đúng shape này.

- [ ] **Step 1: Viết test thất bại**

Tạo file `app/tests/Feature/Orders/OrderLookupByCustomerTest.php`:

```php
<?php

namespace Tests\Feature\Orders;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderLookupByCustomerTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->customer = Customer::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'name' => 'Trần B',
            'phone' => '0912345678', 'phone_hash' => hash('sha256', '0912345678'),
        ]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function makeOrder(string $number, StandardOrderStatus $status, $placedAt, ?int $customerId, ?int $tenantId = null): Order
    {
        return Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenantId ?? $this->tenant->getKey(), 'source' => 'manual', 'customer_id' => $customerId,
            'order_number' => $number, 'status' => $status, 'raw_status' => 'X',
            'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000, 'placed_at' => $placedAt,
            'tags' => [], 'source_updated_at' => $placedAt,
        ]);
    }

    public function test_returns_latest_order_and_latest_returned_order(): void
    {
        $this->makeOrder('DH-OLD', StandardOrderStatus::Completed, now()->subDays(10), $this->customer->getKey());
        $this->makeOrder('DH-RETURNED', StandardOrderStatus::ReturnedRefunded, now()->subDays(5), $this->customer->getKey());
        $latest = $this->makeOrder('DH-LATEST', StandardOrderStatus::Processing, now()->subDay(), $this->customer->getKey());

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/orders/lookup-by-customer?customer_id='.$this->customer->getKey())
            ->assertOk();

        $res->assertJsonPath('data.latest_order.id', $latest->getKey())
            ->assertJsonPath('data.latest_order.number', 'DH-LATEST')
            ->assertJsonPath('data.latest_returned_order.number', 'DH-RETURNED');
    }

    public function test_excludes_given_order_id(): void
    {
        $older = $this->makeOrder('DH-OLDER', StandardOrderStatus::Pending, now()->subDays(2), $this->customer->getKey());
        $editing = $this->makeOrder('DH-EDITING', StandardOrderStatus::Pending, now()->subHour(), $this->customer->getKey());

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/orders/lookup-by-customer?customer_id='.$this->customer->getKey().'&exclude_order_id='.$editing->getKey())
            ->assertOk();

        $res->assertJsonPath('data.latest_order.id', $older->getKey());
    }

    public function test_no_orders_returns_nulls(): void
    {
        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/orders/lookup-by-customer?customer_id='.$this->customer->getKey())
            ->assertOk();

        $res->assertJsonPath('data.latest_order', null)->assertJsonPath('data.latest_returned_order', null);
    }

    public function test_does_not_leak_other_tenant_orders(): void
    {
        $otherTenant = Tenant::create(['name' => 'Other']);
        $otherCustomer = Customer::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $otherTenant->getKey(), 'name' => 'X',
            'phone' => '0900000000', 'phone_hash' => hash('sha256', '0900000000'),
        ]);
        $this->makeOrder('OTHER-1', StandardOrderStatus::Pending, now(), $otherCustomer->getKey(), $otherTenant->getKey());

        // customer_id thuộc tenant KHÁC — filter tenant_id trong OrderLookupService phải chặn, không chỉ dựa customer_id.
        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/orders/lookup-by-customer?customer_id='.$otherCustomer->getKey())
            ->assertOk();

        $res->assertJsonPath('data.latest_order', null);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận thất bại**

Run (từ thư mục `app/`): `php artisan test --filter=OrderLookupByCustomerTest`
Expected: FAIL — route `orders/lookup-by-customer` chưa tồn tại (404 thay vì 200).

- [ ] **Step 3: Thêm route**

Trong `app/routes/api.php`, sửa dòng 183 (giữ nguyên `orders/stats`, thêm route mới ngay sau):

```php
            // --- Orders (Phase 1 + manual orders Phase 2) ---
            Route::get('orders/stats', [OrderController::class, 'stats'])->name('orders.stats');
            // Tra cứu đơn gần nhất + đơn hoàn gần nhất của 1 khách (customer_id) — dùng ở form tạo đơn thủ
            // công để cảnh báo SĐT đã có đơn cũ (SPEC 2026-07-13). `abilities:orders:read` như orders.index.
            Route::get('orders/lookup-by-customer', [OrderController::class, 'lookupByCustomer'])
                ->middleware('abilities:orders:read')->name('orders.lookup-by-customer');
            Route::post('orders/sync', [OrderController::class, 'sync'])->name('orders.sync');             // resync all active shops
```

- [ ] **Step 4: Thêm import + method `lookupByCustomer` vào `OrderController`**

Trong `app/app/Modules/Orders/Http/Controllers/OrderController.php`, sửa khối import (dòng 5-19) — thêm 2 dòng `use` (giữ nguyên thứ tự alphabet hiện có, chèn trước `Http\Resources\OrderResource`):

```php
use Carbon\CarbonImmutable;
use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Channels\Jobs\SyncOrdersForShop;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\SyncRun;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Orders\Contracts\OrderLookupContract;
use CMBcoreSeller\Modules\Orders\DTO\OrderSummary;
use CMBcoreSeller\Modules\Orders\Http\Resources\OrderResource;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Orders\Services\ManualOrderService;
use CMBcoreSeller\Modules\Orders\Services\OrderBulkActionService;
use CMBcoreSeller\Modules\Orders\Services\OrderProfitService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
```

Thêm method mới ngay sau `show()` (sau dòng `}` đóng `show()`, trước comment của `stats()`):

```php
    /**
     * GET /api/v1/orders/lookup-by-customer — đơn gần nhất + đơn hoàn gần nhất của 1 khách. Dùng ở form
     * tạo đơn thủ công (SPEC 2026-07-13) để cảnh báo SĐT đã có đơn cũ; `exclude_order_id` để loại chính
     * đơn đang sửa. Tái dùng OrderLookupContract có sẵn (KHÔNG đụng module Customers).
     */
    public function lookupByCustomer(Request $request, CurrentTenant $tenant, OrderLookupContract $lookup): JsonResponse
    {
        $this->authorizeView($request);

        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'min:1'],
            'exclude_order_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ]);

        $recent = $lookup->recentByCustomer((int) $tenant->id(), (int) $data['customer_id'], limit: 20);
        if (! empty($data['exclude_order_id'])) {
            $excludeId = (int) $data['exclude_order_id'];
            $recent = array_values(array_filter($recent, fn (OrderSummary $o) => $o->id !== $excludeId));
        }

        $latestOrder = $recent[0] ?? null;
        $latestReturnedOrder = null;
        foreach ($recent as $o) {
            if ($o->statusCode === StandardOrderStatus::ReturnedRefunded->value) {
                $latestReturnedOrder = $o;
                break;
            }
        }

        return response()->json(['data' => [
            'latest_order' => $latestOrder?->toArray(),
            'latest_returned_order' => $latestReturnedOrder?->toArray(),
        ]]);
    }

    /** GET /api/v1/orders/{id} */
```

(Dòng cuối `/** GET /api/v1/orders/{id} */` chỉ để định vị — đây là comment đã có sẵn ngay trước `show()`; xác nhận method mới nằm giữa `show()` và `stats()`.)

- [ ] **Step 5: Chạy test, xác nhận pass**

Run: `php artisan test --filter=OrderLookupByCustomerTest`
Expected: PASS — cả 4 test.

- [ ] **Step 6: Chạy phpstan + pint (quality gate)**

Run: `vendor/bin/phpstan analyse` và `vendor/bin/pint --test`
Expected: cả hai không báo lỗi mới liên quan file vừa sửa. Nếu pint báo lỗi format, chạy `vendor/bin/pint` để tự sửa rồi chạy lại `--test`.

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Orders/Http/Controllers/OrderController.php app/routes/api.php app/tests/Feature/Orders/OrderLookupByCustomerTest.php
git commit -m "feat(orders): endpoint tra cứu đơn gần nhất + đơn hoàn gần nhất theo customer_id"
```

---

### Task 2: Frontend — hook `useOrderLookupByCustomer`

**Files:**
- Modify: `app/resources/js/lib/orders.tsx`

**Interfaces:**
- Consumes: `GET /orders/lookup-by-customer?customer_id&exclude_order_id` (Task 1) → `{ data: { latest_order: OrderDuplicateSummary|null, latest_returned_order: OrderDuplicateSummary|null } }`.
- Produces: `export interface OrderDuplicateSummary { id: number; number: string; status_code: string; status: string; total: number; date: string | null; items: string | null }`, `export interface OrderDuplicateLookup { latest_order: OrderDuplicateSummary | null; latest_returned_order: OrderDuplicateSummary | null }`, `export function useOrderLookupByCustomer(customerId: number | undefined, excludeOrderId?: number)` → React Query result với `.data: OrderDuplicateLookup | undefined`. Task 3 tiêu thụ trực tiếp hook + 2 type này.

- [ ] **Step 1: Thêm type + hook**

Trong `app/resources/js/lib/orders.tsx`, thêm ngay sau hàm `useOrder` (sau dòng đóng `}` của `useOrder`, trước `useOrderTags`):

```tsx
export interface OrderDuplicateSummary { id: number; number: string; status_code: string; status: string; total: number; date: string | null; items: string | null }
export interface OrderDuplicateLookup { latest_order: OrderDuplicateSummary | null; latest_returned_order: OrderDuplicateSummary | null }

/** SĐT đã có đơn cũ (mọi trạng thái) — cảnh báo ở form tạo đơn thủ công (SPEC 2026-07-13). Chỉ gọi khi đã có customer_id (từ useCustomerLookup). */
export function useOrderLookupByCustomer(customerId: number | undefined, excludeOrderId?: number) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['order-lookup-by-customer', tenantId, customerId, excludeOrderId],
        enabled: api != null && customerId != null,
        queryFn: async () => {
            const { data } = await api!.get<{ data: OrderDuplicateLookup }>('/orders/lookup-by-customer', {
                params: { customer_id: customerId, exclude_order_id: excludeOrderId },
            });
            return data.data;
        },
    });
}
```

- [ ] **Step 2: Kiểm tra kiểu (typecheck)**

Run (từ thư mục `app/`): `npm run typecheck`
Expected: không lỗi TypeScript mới trong `orders.tsx` (hàm/type mới hợp lệ, không phá vỡ file — chưa có nơi nào import nên không ảnh hưởng file khác ở bước này).

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/lib/orders.tsx
git commit -m "feat(orders): hook useOrderLookupByCustomer tra cứu đơn trùng SĐT"
```

---

### Task 3: Frontend — hiển thị cảnh báo + mở modal đơn

**Files:**
- Modify: `app/resources/js/pages/CreateOrderPage.tsx`

**Interfaces:**
- Consumes: `useOrderLookupByCustomer` + `OrderDuplicateLookup` (Task 2, từ `@/lib/orders`); `OrderDetailModal` (đã có, từ `@/components/OrderDetailModal`, props `{ orderId: number | null; open: boolean; onClose: () => void }`); `customerData?.customer?.id` (đã có, từ `useCustomerLookup`); `editId`/`isEdit` (đã có, khai báo dòng 127-131 của file).
- Produces: component nội bộ `DuplicateOrderAlert` (không export, chỉ dùng trong file này).

- [ ] **Step 1: Thêm import**

Trong `app/resources/js/pages/CreateOrderPage.tsx`, sửa dòng 22 (thêm hook + type mới vào import `orders`) và thêm 1 import mới ngay sau dòng 22:

Trước:
```tsx
import { useOrder } from '@/lib/orders';
```

Sau:
```tsx
import { useOrder, useOrderLookupByCustomer, type OrderDuplicateLookup } from '@/lib/orders';
import { OrderDetailModal } from '@/components/OrderDetailModal';
```

- [ ] **Step 2: Gọi hook + state modal trong `CreateOrderForm`**

Tìm đoạn (khoảng dòng 196-200):

```tsx
    // ---- queries ----
    // Lookup theo KEY = số điện thoại (đã chuẩn hoá hash phía BE — SPEC 0021).
    // Trigger ngay khi `phone` đủ 9 chữ số. Cảnh báo render trong card "Khách hàng".
    const lookup = useCustomerLookup(phone);
    const customerData: CustomerLookupResult | undefined = lookup.data;
    const oldAddresses = customerData?.addresses ?? [];
```

Thay bằng:

```tsx
    // ---- queries ----
    // Lookup theo KEY = số điện thoại (đã chuẩn hoá hash phía BE — SPEC 0021).
    // Trigger ngay khi `phone` đủ 9 chữ số. Cảnh báo render trong card "Khách hàng".
    const lookup = useCustomerLookup(phone);
    const customerData: CustomerLookupResult | undefined = lookup.data;
    const oldAddresses = customerData?.addresses ?? [];

    // SĐT đã có đơn cũ (mọi trạng thái, kể cả đơn hoàn) — cảnh báo dưới card Khách hàng (SPEC 2026-07-13).
    // Sửa đơn ⇒ loại chính đơn đang sửa khỏi kết quả (exclude_order_id) để không tự cảnh báo về chính nó.
    const dupLookup = useOrderLookupByCustomer(customerData?.customer?.id, isEdit && editId ? editId : undefined);
    const [dupOrderModalId, setDupOrderModalId] = useState<number | null>(null);
```

- [ ] **Step 3: Thêm component `DuplicateOrderAlert`**

Trong cùng file, thêm ngay sau hàm `WarningList` (sau dòng đóng `}` cuối cùng của `WarningList`, khoảng dòng 1631):

```tsx

// SPEC 2026-07-13 — mã đơn cụ thể của SĐT đang nhập (đơn gần nhất bất kỳ trạng thái + đơn hoàn gần nhất
// nếu có), đặt NGAY DƯỚI card Khách hàng (tách biệt với thanh tỷ lệ/popover ⚠ ở tiêu đề card). Bấm mã
// đơn mở OrderDetailModal (không thay thế được widget cảnh báo cũ).
function DuplicateOrderAlert({ data, onOpenOrder }: { data: OrderDuplicateLookup | undefined; onOpenOrder: (id: number) => void }) {
    if (!data?.latest_order) return null;
    return (
        <Alert
            type="warning"
            showIcon
            style={{ marginBottom: 16 }}
            message={(
                <Space direction="vertical" size={2}>
                    <span>
                        SĐT này đã có đơn hàng trước đó:{' '}
                        <a onClick={() => onOpenOrder(data.latest_order!.id)}>#{data.latest_order.number}</a>
                    </span>
                    {data.latest_returned_order && (
                        <span>
                            Có đơn hoàn:{' '}
                            <a onClick={() => onOpenOrder(data.latest_returned_order!.id)}>#{data.latest_returned_order.number}</a>
                        </span>
                    )}
                </Space>
            )}
        />
    );
}
```

- [ ] **Step 4: Chèn `<DuplicateOrderAlert>` dưới cả 2 biến thể card Khách hàng**

Card gộp compact — giữ nguyên cấu trúc ternary hiện có, chỉ chèn thêm 1 dòng trước `</Card>`. Tìm:

```tsx
                                {addressBlock}
                                <CustomerWarning data={customerData} />
                            </Card>
                        ) : (
```

Thay bằng:

```tsx
                                {addressBlock}
                                <CustomerWarning data={customerData} />
                                <DuplicateOrderAlert data={dupLookup.data} onOpenOrder={setDupOrderModalId} />
                            </Card>
                        ) : (
```

Card đầy đủ "Khách hàng" — tìm:

```tsx
                                    <CustomerInfoModal open={infoModalOpen} onClose={() => setInfoModalOpen(false)} form={form} upload={upload} message={message} />
                                </Card>

                                {/* ---------- Nhận hàng ---------- */}
```

Thay bằng:

```tsx
                                    <CustomerInfoModal open={infoModalOpen} onClose={() => setInfoModalOpen(false)} form={form} upload={upload} message={message} />
                                </Card>
                                <DuplicateOrderAlert data={dupLookup.data} onOpenOrder={setDupOrderModalId} />

                                {/* ---------- Nhận hàng ---------- */}
```

- [ ] **Step 5: Mount `OrderDetailModal`**

Tìm đoạn cuối sticky bottom bar (khoảng dòng 1116-1122):

```tsx
                <Space>
                    <Button icon={<PrinterOutlined />} onClick={() => submit(true)} loading={submitting}>{isEdit ? 'Lưu & in' : 'In'} <kbd className="ord-kbd">F4</kbd></Button>
                    <Button type="primary" icon={<SaveOutlined />} onClick={() => submit(false)} loading={submitting}>{isEdit ? 'Lưu thay đổi' : 'Lưu'} <kbd className="ord-kbd-on-primary">F2</kbd></Button>
                </Space>
            </div>

            {/* ---------- Scoped styles ---------- */}
```

Thay bằng:

```tsx
                <Space>
                    <Button icon={<PrinterOutlined />} onClick={() => submit(true)} loading={submitting}>{isEdit ? 'Lưu & in' : 'In'} <kbd className="ord-kbd">F4</kbd></Button>
                    <Button type="primary" icon={<SaveOutlined />} onClick={() => submit(false)} loading={submitting}>{isEdit ? 'Lưu thay đổi' : 'Lưu'} <kbd className="ord-kbd-on-primary">F2</kbd></Button>
                </Space>
            </div>

            <OrderDetailModal orderId={dupOrderModalId} open={dupOrderModalId != null} onClose={() => setDupOrderModalId(null)} />

            {/* ---------- Scoped styles ---------- */}
```

- [ ] **Step 6: Typecheck + build**

Run (từ thư mục `app/`): `npm run typecheck && npm run build`
Expected: cả hai lệnh chạy xong không lỗi.

- [ ] **Step 7: Verify thủ công qua trình duyệt**

Chạy `composer dev` (từ `app/`, nếu chưa chạy sẵn), mở `/orders/new`:
1. Nhập SĐT của 1 khách đã có đơn cũ trong DB dev (bất kỳ trạng thái) → Alert vàng hiện dưới card Khách hàng với đúng mã đơn gần nhất.
2. Nếu khách đó có đơn `returned_refunded` → hiện thêm dòng "Có đơn hoàn".
3. Bấm vào mã đơn → `OrderDetailModal` mở đúng đơn đó.
4. Mở `/orders/:id/edit` với 1 đơn của khách có SĐT trùng nhiều đơn khác → Alert KHÔNG hiện mã của chính đơn đang sửa (chỉ hiện đơn khác nếu có).

- [ ] **Step 8: Commit**

```bash
git add app/resources/js/pages/CreateOrderPage.tsx
git commit -m "feat(orders): cảnh báo mã đơn cụ thể khi SĐT đã có đơn cũ + mở modal xem nhanh"
```

---

### Task 4: Frontend — gỡ "Cho khách xem/thử hàng"

**Files:**
- Modify: `app/resources/js/pages/CreateOrderPage.tsx`

**Interfaces:**
- Consumes: không (chỉ xoá code).
- Produces: không (không có API mới; backend không đổi — xem Global Constraints).

- [ ] **Step 1: Xoá const/type/helper `RequiredNote` (dòng 48-52)**

Tìm:

```tsx
// 3 mức ghi chú xem/thử hàng (chuẩn GHN required_note) — bỏ mặc định ép "cho thử hàng" cũ.
const REQUIRED_NOTE_VALUES = ['KHONGCHOXEMHANG', 'CHOXEMHANGKHONGTHU', 'CHOTHUHANG'] as const;
type RequiredNote = typeof REQUIRED_NOTE_VALUES[number];
// Mặc định an toàn khi chưa có sticky pref / chưa có cài đặt tài khoản ĐVVC mặc định.
const DEFAULT_REQUIRED_NOTE: RequiredNote = 'CHOXEMHANGKHONGTHU';

/**
 * Sticky preferences cho nút tick thanh toán (miễn phí giao / chỉ thu phí nếu hoàn) + ghi chú xem/thử:
 * trạng thái đơn VỪA TẠO trở thành mặc định cho đơn sau. Lưu localStorage theo tài khoản (tenant) để
 * không cần backend/quyền tenant.settings. Ghi chú xem/thử KHÔNG còn ép "cho thử": lần đầu lấy theo cài
 * đặt của tài khoản ĐVVC mặc định (fallback "cho xem, không thử").
 */
type OrderTogglePrefs = { free_shipping: boolean; collect_fee_on_return_only: boolean; required_note: RequiredNote };
const DEFAULT_TOGGLE_PREFS: OrderTogglePrefs = { free_shipping: false, collect_fee_on_return_only: true, required_note: DEFAULT_REQUIRED_NOTE };
const togglePrefsKey = (tenantId: number | null | undefined) => `order-toggle-prefs:${tenantId ?? 'x'}`;
function readTogglePrefs(tenantId: number | null | undefined): OrderTogglePrefs {
    try {
        const raw = localStorage.getItem(togglePrefsKey(tenantId));
        if (raw) return { ...DEFAULT_TOGGLE_PREFS, ...(JSON.parse(raw) as Partial<OrderTogglePrefs>) };
    } catch { /* localStorage chặn/hỏng ⇒ dùng mặc định */ }
    return DEFAULT_TOGGLE_PREFS;
}
/** Đã từng lưu sticky pref cho tenant này chưa? (để biết dùng cài đặt tài khoản ĐVVC mặc định cho đơn đầu). */
function hasStoredTogglePrefs(tenantId: number | null | undefined): boolean {
    try { return localStorage.getItem(togglePrefsKey(tenantId)) != null; } catch { return false; }
}
function writeTogglePrefs(tenantId: number | null | undefined, p: OrderTogglePrefs): void {
    try { localStorage.setItem(togglePrefsKey(tenantId), JSON.stringify(p)); } catch { /* ignore */ }
}
/** Chuẩn hoá 1 giá trị bất kỳ về 3 mức required_note hợp lệ (nếu không hợp lệ ⇒ mặc định an toàn). */
function toRequiredNote(v: unknown): RequiredNote {
    return (REQUIRED_NOTE_VALUES as readonly string[]).includes(v as string) ? (v as RequiredNote) : DEFAULT_REQUIRED_NOTE;
}
```

Thay bằng:

```tsx
/**
 * Sticky preferences cho nút tick thanh toán (miễn phí giao / chỉ thu phí nếu hoàn): trạng thái đơn VỪA
 * TẠO trở thành mặc định cho đơn sau. Lưu localStorage theo tài khoản (tenant) để không cần backend/quyền
 * tenant.settings.
 */
type OrderTogglePrefs = { free_shipping: boolean; collect_fee_on_return_only: boolean };
const DEFAULT_TOGGLE_PREFS: OrderTogglePrefs = { free_shipping: false, collect_fee_on_return_only: true };
const togglePrefsKey = (tenantId: number | null | undefined) => `order-toggle-prefs:${tenantId ?? 'x'}`;
function readTogglePrefs(tenantId: number | null | undefined): OrderTogglePrefs {
    try {
        const raw = localStorage.getItem(togglePrefsKey(tenantId));
        if (raw) return { ...DEFAULT_TOGGLE_PREFS, ...(JSON.parse(raw) as Partial<OrderTogglePrefs>) };
    } catch { /* localStorage chặn/hỏng ⇒ dùng mặc định */ }
    return DEFAULT_TOGGLE_PREFS;
}
function writeTogglePrefs(tenantId: number | null | undefined, p: OrderTogglePrefs): void {
    try { localStorage.setItem(togglePrefsKey(tenantId), JSON.stringify(p)); } catch { /* ignore */ }
}
```

- [ ] **Step 2: Xoá import `useCarrierAccounts` (không còn dùng)**

Tìm dòng 21:

```tsx
import { useCarrierAccounts } from '@/lib/fulfillment';
```

Xoá nguyên dòng này.

- [ ] **Step 3: Xoá prefill `required_note` khi sửa đơn (dòng ~301-305)**

Tìm:

```tsx
            free_shipping: !!meta.free_shipping,
            collect_fee_on_return_only: !!meta.collect_fee_on_return_only,
            // Ghi chú xem/thử: đơn mới lưu meta.required_note (3 mức); đơn CŨ chỉ có cờ bool allow_inspection.
            required_note: typeof meta.required_note === 'string'
                ? toRequiredNote(meta.required_note)
                : (meta.allow_inspection === false ? 'KHONGCHOXEMHANG' : 'CHOTHUHANG'),
            failed_collect_amount: o.failed_collect_amount ?? undefined,
```

Thay bằng:

```tsx
            free_shipping: !!meta.free_shipping,
            collect_fee_on_return_only: !!meta.collect_fee_on_return_only,
            failed_collect_amount: o.failed_collect_amount ?? undefined,
```

- [ ] **Step 4: Xoá `defaultAccountRequiredNote` + sticky `required_note` (dòng ~345-366)**

Tìm:

```tsx
    // Ghi chú xem/thử mặc định cho ĐƠN MỚI: theo cài đặt của tài khoản ĐVVC ĐANG MẶC ĐỊNH (is_default);
    // fallback "cho xem, không thử". (Bỏ mặc định ép "cho thử hàng" cũ.) Sticky vẫn ưu tiên nếu đã có.
    const { data: carrierAccounts } = useCarrierAccounts();
    const defaultAccountRequiredNote = useMemo<RequiredNote>(() => {
        const acc = (carrierAccounts ?? []).find((a) => a.is_default) ?? (carrierAccounts ?? [])[0];
        return toRequiredNote((acc?.meta as { defaults?: { required_note?: unknown } } | undefined)?.defaults?.required_note);
    }, [carrierAccounts]);

    // Sticky: đơn TẠO MỚI khởi tạo nút tick theo trạng thái đơn tạo gần nhất (localStorage theo tenant).
    // Áp 1 lần khi tenant đã tải (không áp cho chế độ SỬA — prefill từ đơn ghi đè sau). Đơn sửa giữ trạng thái đơn.
    const stickyAppliedRef = useRef(false);
    useEffect(() => {
        if (isEdit || stickyAppliedRef.current || !tenantDetail) return;
        stickyAppliedRef.current = true;
        const p = readTogglePrefs(tenantDetail.id);
        form.setFieldsValue({
            free_shipping: p.free_shipping,
            collect_fee_on_return_only: p.collect_fee_on_return_only,
            // Lần đầu (chưa có sticky) ⇒ theo cài đặt tài khoản ĐVVC mặc định; sau đó theo đơn gần nhất.
            required_note: hasStoredTogglePrefs(tenantDetail.id) ? p.required_note : defaultAccountRequiredNote,
        });
    }, [isEdit, tenantDetail, form, defaultAccountRequiredNote]);
```

Thay bằng:

```tsx
    // Sticky: đơn TẠO MỚI khởi tạo nút tick theo trạng thái đơn tạo gần nhất (localStorage theo tenant).
    // Áp 1 lần khi tenant đã tải (không áp cho chế độ SỬA — prefill từ đơn ghi đè sau). Đơn sửa giữ trạng thái đơn.
    const stickyAppliedRef = useRef(false);
    useEffect(() => {
        if (isEdit || stickyAppliedRef.current || !tenantDetail) return;
        stickyAppliedRef.current = true;
        const p = readTogglePrefs(tenantDetail.id);
        form.setFieldsValue({
            free_shipping: p.free_shipping,
            collect_fee_on_return_only: p.collect_fee_on_return_only,
        });
    }, [isEdit, tenantDetail, form]);
```

- [ ] **Step 5: Xoá `required_note` khỏi `buildPayload()` (dòng ~468-470)**

Tìm:

```tsx
                collect_fee_on_return_only: !!v.collect_fee_on_return_only,
                // Ghi chú xem/thử hàng (3 mức) — BE map required_note/tag/ORDER_NOTE theo từng ĐVVC ở buildCreatePayload.
                required_note: toRequiredNote(v.required_note),
                attachments: attachments.length > 0 ? attachments : undefined,
```

Thay bằng:

```tsx
                collect_fee_on_return_only: !!v.collect_fee_on_return_only,
                attachments: attachments.length > 0 ? attachments : undefined,
```

- [ ] **Step 6: Xoá `required_note` khỏi `persistTogglePrefs` (dòng ~478-482)**

Tìm:

```tsx
        const persistTogglePrefs = () => writeTogglePrefs(tenantDetail?.id, {
            free_shipping: !!payload.free_shipping,
            collect_fee_on_return_only: !!payload.meta.collect_fee_on_return_only,
            required_note: toRequiredNote(payload.meta.required_note),
        });
```

Thay bằng:

```tsx
        const persistTogglePrefs = () => writeTogglePrefs(tenantDetail?.id, {
            free_shipping: !!payload.free_shipping,
            collect_fee_on_return_only: !!payload.meta.collect_fee_on_return_only,
        });
```

- [ ] **Step 7: Xoá `required_note` khỏi `initialValues` của Form (dòng ~778-780)**

Tìm:

```tsx
            <Form form={form} layout="vertical" onValuesChange={markEditDirty} initialValues={{
                // "Chỉ thu phí nếu hoàn" mặc định BẬT; ghi chú xem/thử mặc định "cho xem, không thử"
                // (effect sticky/tài khoản ĐVVC mặc định ghi đè sau — không còn ép "cho thử hàng").
                channel_mode: 'online', sub_source: undefined, free_shipping: false, collect_fee_on_return_only: true, required_note: DEFAULT_REQUIRED_NOTE,
                shipping_fee: 0, order_discount: 0, prepaid_amount: 0, surcharge: 0,
            }}>
```

Thay bằng:

```tsx
            <Form form={form} layout="vertical" onValuesChange={markEditDirty} initialValues={{
                // "Chỉ thu phí nếu hoàn" mặc định BẬT.
                channel_mode: 'online', sub_source: undefined, free_shipping: false, collect_fee_on_return_only: true,
                shipping_fee: 0, order_discount: 0, prepaid_amount: 0, surcharge: 0,
            }}>
```

- [ ] **Step 8: Xoá control `Segmented` "Cho khách xem / thử hàng" (dòng ~884-890)**

Tìm:

```tsx
                                    <Space size={20} style={{ marginBottom: 10 }} wrap>
                                        <Form.Item name="free_shipping" valuePropName="checked" noStyle><Checkbox>Miễn phí giao hàng</Checkbox></Form.Item>
                                        <Form.Item name="collect_fee_on_return_only" valuePropName="checked" noStyle><Checkbox>Chỉ thu phí nếu hoàn</Checkbox></Form.Item>
                                    </Space>
                                    <Form.Item name="required_note" label="Cho khách xem / thử hàng" style={{ marginBottom: 10 }}>
                                        <Segmented size="small" options={[
                                            { value: 'KHONGCHOXEMHANG', label: 'Không cho xem' },
                                            { value: 'CHOXEMHANGKHONGTHU', label: 'Xem, không thử' },
                                            { value: 'CHOTHUHANG', label: 'Cho thử' },
                                        ]} />
                                    </Form.Item>
                                    {collectFeeOnReturnOnly && (
```

Thay bằng:

```tsx
                                    <Space size={20} style={{ marginBottom: 10 }} wrap>
                                        <Form.Item name="free_shipping" valuePropName="checked" noStyle><Checkbox>Miễn phí giao hàng</Checkbox></Form.Item>
                                        <Form.Item name="collect_fee_on_return_only" valuePropName="checked" noStyle><Checkbox>Chỉ thu phí nếu hoàn</Checkbox></Form.Item>
                                    </Space>
                                    {collectFeeOnReturnOnly && (
```

- [ ] **Step 9: Typecheck + lint + build**

Run (từ `app/`): `npm run typecheck && npm run lint && npm run build`
Expected: không lỗi. Đặc biệt chú ý `npm run lint` — nếu còn báo `no-unused-vars` cho bất kỳ identifier nào liên quan `RequiredNote`/`toRequiredNote`/`hasStoredTogglePrefs`/`useCarrierAccounts`/`DEFAULT_REQUIRED_NOTE`/`REQUIRED_NOTE_VALUES`, nghĩa là còn sót 1 chỗ dùng — quay lại tìm bằng cách grep các identifier này trong file và xoá nốt.

- [ ] **Step 10: Verify thủ công qua trình duyệt**

Mở `/orders/new`: xác nhận card "Thanh toán" KHÔNG còn control "Cho khách xem / thử hàng". Tạo 1 đơn thử — tạo thành công bình thường (không có field này trong payload nữa, backend vẫn nhận và lưu đơn đúng như trước).

- [ ] **Step 11: Commit**

```bash
git add app/resources/js/pages/CreateOrderPage.tsx
git commit -m "refactor(orders): gỡ toggle Cho khách xem/thử hàng đã hết tác dụng ở tạo đơn thủ công"
```

---

## Self-Review

**Spec coverage:**
- Endpoint mới trả `latest_order`/`latest_returned_order`, exclude đơn đang sửa → Task 1. ✅
- Alert dưới card Khách hàng, 2 dòng, bấm mở modal, không đụng widget cũ → Task 3. ✅
- Gỡ toggle "Cho khách xem/thử hàng" khỏi FE, giữ nguyên backend fallback cho đơn cũ → Task 4 (chỉ sửa FE, không đụng `ManualOrderService`/`ShipmentService`). ✅
- Testing (backend feature test + FE typecheck/build/manual) → Task 1 Step 1-2, Task 2 Step 2, Task 3 Step 6-7, Task 4 Step 9-10. ✅

**Placeholder scan:** không có TBD/TODO; mọi step có code đầy đủ, kèm chuỗi tìm/thay chính xác.

**Type consistency:** `OrderDuplicateLookup`/`OrderDuplicateSummary` (Task 2) khớp field `latest_order`/`latest_returned_order`/`id`/`number` dùng trong `DuplicateOrderAlert` (Task 3). `useOrderLookupByCustomer(customerId, excludeOrderId)` khớp cách gọi ở Task 3 Step 2. `OrderLookupContract::recentByCustomer` / `OrderSummary::toArray()` (đã có sẵn, không đổi signature) khớp cách dùng ở Task 1.
