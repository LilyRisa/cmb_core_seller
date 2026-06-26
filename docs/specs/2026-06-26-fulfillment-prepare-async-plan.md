# Async "Chuẩn bị hàng" Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Đưa toàn bộ phần gọi sàn + lấy tem của "chuẩn bị hàng" (single + bulk) ra khỏi request HTTP php-fpm vào job nền `PrepareShipment`, chống orphan php-fpm, scale queue, và để FE polling cập nhật đầy đủ chip trạng thái + tình trạng in.

> **⚠ SCOPE REVISION (2026-06-26): CHỈ BULK ASYNC.** Sau khi rà code, single `POST /orders/{id}/ship` GIỮ ĐỒNG BỘ (app mobile + quét kho + markReady + 7 file test phụ thuộc shipment trả về ngay; single không gây 504). ⇒ **BỎ Task 3 (single 202), Task 7 (FE useShipOrder type), Task 10 (OrderProcessing polling + markReady)**. markReady không cần sửa (single sync vẫn trả shipment). Các task còn lại: 1, 2, 4, 5, 6, 8, 9, 11, 12.

**Architecture:** Bulk controller validate rẻ (DB) đồng bộ rồi dispatch `PrepareShipment` per đơn → trả `{queued, already_prepared, errors}`. Job chạy `$tenant->runAs(...)` rồi gọi `ShipmentService::createForOrder` (logic nặng giữ nguyên). Single `/ship` vẫn gọi thẳng `createForOrder` đồng bộ (không đổi). FE bulk map kết quả vào progress modal + kích hoạt `syncPoll` (đã có) để refetch list + stats (chip) + slip.

**Tech Stack:** Laravel 11 (queue Horizon/Redis), React 18 + TanStack Query + Ant Design, PHPUnit, Pint, PHPStan.

## Global Constraints (copy verbatim từ spec)

- Lệnh PHP/Node chạy từ `app/` (không phải repo root).
- Namespace `CMBcoreSeller\` → `app/app/`.
- Core không biết tên sàn — job dùng `ShipmentService`/connector qua capability; KHÔNG `if ($provider==='tiktok')`.
- Mọi job phải idempotent; webhook/job có thể chạy lại.
- Job chạy trong worker KHÔNG có request-bound tenant ⇒ phải `app(CurrentTenant::class)->runAs($shop, fn()=>...)` trước mọi query tenant-scoped (xác nhận: `TenantScope` ép `tenant_id ?? 0` nếu không có tenant — `app/app/Modules/Tenancy/Scopes/TenantScope.php:24`).
- Queue mới BẮT BUỘC có supervisor Horizon xử lý, nếu không job kẹt im lặng (memory `messaging-autoreply-dev-gotchas`).
- Money = integer VND; status field qua `OrderStatusSync`/`StandardOrderStatus`.
- UI: icon dùng `@ant-design/icons` (không emoji); hạn chế `<Select>`.
- Test baseline: BE chưa green toàn cục — chỉ chạy test liên quan Fulfillment (memory `test-verify-baseline`).
- `request_terminate_timeout` đặt trong khối sinh `zz-pool.conf` ở `app/docker/entrypoint.sh` (baked image, redeploy mới có hiệu lực).

---

## File Structure

**Backend (tạo mới):**
- `app/app/Modules/Fulfillment/Jobs/PrepareShipment.php` — job nền chạy phần nặng của prepare, idempotent, `runAs` tenant.

**Backend (sửa):**
- `app/app/Modules/Fulfillment/Services/ShipmentService.php` — tách `assertPreparable()` (public), thêm `bulkQueuePrepare()`; bỏ logic dispatch khỏi `bulkCreate` cũ (giữ `createForOrder` làm thân job).
- `app/app/Modules/Fulfillment/Http/Controllers/ShipmentController.php` — `createForOrder` (single) → 202 dispatch; `bulkCreate` → gọi `bulkQueuePrepare`, trả `{queued, already_prepared, errors}`.
- `app/config/horizon.php` — thêm `supervisor-fulfillment` (mọi env) + bump `supervisor-labels` prod.
- `app/docker/entrypoint.sh` — thêm `request_terminate_timeout = 115`.

**Backend (test):**
- `app/tests/Feature/Fulfillment/PrepareShipmentAsyncTest.php` — controller dispatch + validate sync + response shape.
- `app/tests/Feature/Fulfillment/PrepareShipmentJobTest.php` — job runAs + idempotency + failed().

**Frontend (sửa):**
- `app/resources/js/lib/fulfillment.tsx` — type response mới cho `useShipOrder`, `useBulkCreateShipments`.
- `app/resources/js/lib/useBulkAction.ts` — thêm status `'queued'`.
- `app/resources/js/components/BulkProgressModal.tsx` — render `'queued'` (xanh dương + icon).
- `app/resources/js/pages/OrdersPage.tsx` — map `queued/already_prepared/errors`; trigger `syncPoll` khi `queued.length>0`.
- `app/resources/js/components/OrderProcessing.tsx` — single prepare: polling cập nhật chip/slip; sửa `markReady` an toàn khi ship async (không phụ thuộc shipment trả về).

**Docs (sửa):**
- `docs/05-api/endpoints.md`, `docs/07-infra/queues-and-scheduler.md`.

---

## Task 1: Tách `assertPreparable()` (validate rẻ, dùng chung controller + job)

**Files:**
- Modify: `app/app/Modules/Fulfillment/Services/ShipmentService.php` (`createForOrder` ~591-612)
- Test: `app/tests/Feature/Fulfillment/PrepareShipmentAsyncTest.php`

**Interfaces:**
- Produces: `ShipmentService::assertPreparable(Order $order): void` — throw `RuntimeException` (message VN) nếu không thể chuẩn bị; KHÔNG gọi sàn. Không throw nếu đã có vận đơn open (caller tự xử lý idempotent).

- [ ] **Step 1: Viết test fail trước**

Tạo `app/tests/Feature/Fulfillment/PrepareShipmentAsyncTest.php`:

```php
<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Modules\Fulfillment\Services\ShipmentService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Support\Enums\StandardOrderStatus as S;
use Tests\Feature\Fulfillment\Concerns\MakesFulfillmentData; // dùng helper sẵn có nếu có; nếu không, tạo order tay
use Tests\TestCase;

class PrepareShipmentAsyncTest extends TestCase
{
    public function test_assert_preparable_throws_for_terminal_order(): void
    {
        $order = $this->makeOrder(['status' => S::Cancelled->value]); // helper tạo order + tenant context
        $this->expectException(\RuntimeException::class);
        app(ShipmentService::class)->assertPreparable($order->refresh());
    }

    public function test_assert_preparable_passes_for_pending_in_stock_order(): void
    {
        $order = $this->makeOrder(['status' => S::Pending->value]);
        app(ShipmentService::class)->assertPreparable($order->refresh());
        $this->assertTrue(true); // không throw
    }
}
```

Lưu ý: kiểm tra `app/tests/Feature/Fulfillment/` xem có trait/helper tạo Order + tenant context sẵn (vd `runAs`/factory). Nếu chưa có `makeOrder`, viết helper riêng trong file test set CurrentTenant qua `app(CurrentTenant::class)->runAs(...)` và tạo Order với `tenant_id`, `status`. Tham khảo test fulfillment hiện có để khớp pattern setup.

- [ ] **Step 2: Chạy test — phải fail**

Run: `cd app && php artisan test --filter=PrepareShipmentAsyncTest`
Expected: FAIL — `Call to undefined method ...::assertPreparable()`.

- [ ] **Step 3: Tách method**

Trong `ShipmentService.php`, thêm method public (đặt ngay trên `createForOrder`):

```php
/**
 * Validate rẻ (DB only, KHÔNG gọi sàn) để feedback đồng bộ ở controller TRƯỚC khi dispatch job.
 * Throw RuntimeException (message VN) nếu không thể chuẩn bị. KHÔNG check vận đơn open (caller xử lý
 * idempotent riêng). Cùng dùng ở đầu createForOrder (DRY — job re-validate khi chạy).
 */
public function assertPreparable(Order $order): void
{
    if ($order->status->isTerminal() || in_array($order->status, [S::Returning, S::ReturnedRefunded], true)) {
        throw new RuntimeException('Đơn ở trạng thái không thể chuẩn bị hàng / tạo vận đơn.');
    }
    if ($this->isOutOfStock($order)) {
        throw new RuntimeException('Đơn có SKU hết hàng (âm tồn) — không thể chuẩn bị hàng / lấy phiếu giao hàng. Hãy nhập thêm hàng rồi thử lại.');
    }
    if ($order->channel_account_id) {
        $this->assertChannelOrderFulfillable($order);
    }
}
```

Sửa `createForOrder` để gọi nó thay cho 2 check nội tuyến hiện tại (dòng ~599-605 + ~609). Cụ thể, thay khối:

```php
if ($order->status->isTerminal() || in_array($order->status, [S::Returning, S::ReturnedRefunded], true)) {
    throw new RuntimeException('Đơn ở trạng thái không thể chuẩn bị hàng / tạo vận đơn.');
}
if ($this->isOutOfStock($order)) {
    throw new RuntimeException('Đơn có SKU hết hàng (âm tồn) — không thể chuẩn bị hàng / lấy phiếu giao hàng. Hãy nhập thêm hàng rồi thử lại.');
}
if ($order->channel_account_id) {
    $this->assertChannelOrderFulfillable($order);

    return $this->prepareChannelOrder($order, $userId);
}
```

bằng:

```php
$this->assertPreparable($order);
if ($order->channel_account_id) {
    return $this->prepareChannelOrder($order, $userId);
}
```

(Giữ nguyên check `$existing = ...->open()->first()` ở đầu createForOrder — đó là idempotency anchor, không chuyển vào assertPreparable.) Đổi `assertChannelOrderFulfillable` từ `private` → `private` vẫn được (assertPreparable cùng class gọi được).

- [ ] **Step 4: Chạy test — phải pass**

Run: `cd app && php artisan test --filter=PrepareShipmentAsyncTest`
Expected: PASS (2 test).

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Fulfillment/Services/ShipmentService.php app/tests/Feature/Fulfillment/PrepareShipmentAsyncTest.php
git commit -m "refactor(fulfillment): tách assertPreparable() validate rẻ dùng chung"
```

---

## Task 2: Job `PrepareShipment` (runAs tenant + idempotent + failed)

**Files:**
- Create: `app/app/Modules/Fulfillment/Jobs/PrepareShipment.php`
- Test: `app/tests/Feature/Fulfillment/PrepareShipmentJobTest.php`

**Interfaces:**
- Consumes: `ShipmentService::createForOrder(Order, ?int $carrierAccountId, ?string $service, array $opts, ?int $userId): Shipment`; `CurrentTenant::runAs(Tenant, Closure)`.
- Produces: `PrepareShipment::__construct(int $orderId, ?int $carrierAccountId = null, ?string $service = null, array $opts = [], ?int $userId = null)` trên queue `fulfillment`.

- [ ] **Step 1: Viết test fail trước**

Tạo `app/tests/Feature/Fulfillment/PrepareShipmentJobTest.php`:

```php
<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Modules\Fulfillment\Jobs\PrepareShipment;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use Tests\TestCase;

class PrepareShipmentJobTest extends TestCase
{
    public function test_job_creates_shipment_for_manual_order_without_request_tenant(): void
    {
        // Tạo đơn manual + ĐVVC manual; KHÔNG set CurrentTenant (giả lập worker).
        $order = $this->makeManualOrderReadyToPrepare(); // helper: order pending, có item đủ tồn
        app(\CMBcoreSeller\Modules\Tenancy\CurrentTenant::class)->forget(); // đảm bảo không có tenant context

        (new PrepareShipment($order->id))->handle(
            app(\CMBcoreSeller\Modules\Fulfillment\Services\ShipmentService::class),
            app(\CMBcoreSeller\Modules\Tenancy\CurrentTenant::class),
        );

        $this->assertDatabaseHas('shipments', ['order_id' => $order->id]);
    }

    public function test_job_is_idempotent_no_duplicate_shipment(): void
    {
        $order = $this->makeManualOrderReadyToPrepare();
        $svc = app(\CMBcoreSeller\Modules\Fulfillment\Services\ShipmentService::class);
        $ct = app(\CMBcoreSeller\Modules\Tenancy\CurrentTenant::class);
        (new PrepareShipment($order->id))->handle($svc, $ct);
        (new PrepareShipment($order->id))->handle($svc, $ct);

        $this->assertSame(1, Shipment::withoutGlobalScope(\CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope::class)->where('order_id', $order->id)->count());
    }
}
```

Lưu ý helper `makeManualOrderReadyToPrepare` + `CurrentTenant::forget()`: kiểm `CurrentTenant` có method clear/forget; nếu không, dùng `runAs` với null hoặc tạo TestCase tách. Tham khảo test fulfillment hiện có để tạo manual order + ĐVVC `manual` (connector manual trả tracking giả lập). Nếu manual connector cần stub, dùng cách test fulfillment sẵn có đang dùng cho `createForOrder`.

- [ ] **Step 2: Chạy test — phải fail**

Run: `cd app && php artisan test --filter=PrepareShipmentJobTest`
Expected: FAIL — class `PrepareShipment` không tồn tại.

- [ ] **Step 3: Viết job**

Tạo `app/app/Modules/Fulfillment/Jobs/PrepareShipment.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Fulfillment\Jobs;

use CMBcoreSeller\Modules\Fulfillment\Services\ShipmentService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * "Chuẩn bị hàng" chạy NỀN (queue `fulfillment`): toàn bộ phần gọi sàn (arrange) + lấy tem/AWB +
 * flip status Processing — TRƯỚC đây chạy đồng bộ trong php-fpm gây 504/orphan (SPEC 2026-06-26).
 * Idempotent 3 lớp: ShouldBeUnique(orderId) dedup lúc enqueue + WithoutOverlapping(orderId) chống
 * chạy song song cùng đơn + check vận đơn open() trong createForOrder. Job chạy trong worker không có
 * request-bound tenant ⇒ runAs($shop) để query tenant-scoped (ChannelAccount...) resolve đúng.
 */
class PrepareShipment implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @return list<int> giây */
    public function backoff(): array
    {
        return [10, 30, 60, 120, 300];
    }

    /** Dedup các job trùng orderId đang chờ trong queue (TTL 60s). */
    public int $uniqueFor = 60;

    public function __construct(
        public readonly int $orderId,
        public readonly ?int $carrierAccountId = null,
        public readonly ?string $service = null,
        public readonly array $opts = [],
        public readonly ?int $userId = null,
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->orderId;
    }

    /** @return list<object> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping((string) $this->orderId))->expireAfter(180)->dontRelease()];
    }

    public function handle(ShipmentService $service, CurrentTenant $tenant): void
    {
        $order = Order::withoutGlobalScope(TenantScope::class)->find($this->orderId);
        if (! $order) {
            return;
        }
        $shop = Tenant::query()->find($order->tenant_id);
        if (! $shop) {
            return;
        }
        // Chạy như tenant của đơn để TenantScope (ChannelAccount, carrier account...) resolve đúng.
        $tenant->runAs($shop, function () use ($service, $order) {
            $service->createForOrder(
                $order,
                $this->carrierAccountId,
                $this->service,
                $this->opts,
                $this->userId,
            );
        });
    }

    /**
     * Hết tries (lỗi sàn kéo dài) ⇒ gắn cờ has_issue để FE hiện + cho user "Nhận phiếu giao hàng".
     * Status đơn giữ nguyên (job chưa flip được Processing nếu fail trước đó).
     */
    public function failed(\Throwable $e): void
    {
        $order = Order::withoutGlobalScope(TenantScope::class)->find($this->orderId);
        if ($order) {
            $order->forceFill([
                'has_issue' => true,
                'issue_reason' => Str::limit('Chuẩn bị hàng thất bại: '.$e->getMessage(), 240),
            ])->save();
        }
        Log::warning('shipment.prepare_async_failed', ['order' => $this->orderId, 'error' => $e->getMessage()]);
    }
}
```

Kiểm tra `CurrentTenant` có `runAs(Tenant, Closure)` (đã dùng ở RenderPrintJob — chắc chắn có). Nếu `createForOrder` throw `RuntimeException` (validate) trong job thì job sẽ retry vô ích → vì controller đã validate trước, trường hợp này hiếm; nhưng để tránh retry vô nghĩa khi đơn đã có vận đơn open, `createForOrder` đã short-circuit trả existing (không throw). Với lỗi validate thực sự (vd tồn đổi), job retry rồi `failed()` gắn cờ — chấp nhận.

- [ ] **Step 4: Chạy test — phải pass**

Run: `cd app && php artisan test --filter=PrepareShipmentJobTest`
Expected: PASS (2 test). Nếu lỗi tenant/manual connector, chỉnh helper test cho khớp pattern fulfillment hiện có (KHÔNG sửa job để chiều test sai).

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Fulfillment/Jobs/PrepareShipment.php app/tests/Feature/Fulfillment/PrepareShipmentJobTest.php
git commit -m "feat(fulfillment): job PrepareShipment chạy nền + runAs tenant + idempotent"
```

---

## Task 3: Controller single `POST /orders/{id}/ship` → 202 dispatch

**Files:**
- Modify: `app/app/Modules/Fulfillment/Http/Controllers/ShipmentController.php` (`createForOrder` ~160-181)
- Test: `app/tests/Feature/Fulfillment/PrepareShipmentAsyncTest.php`

**Interfaces:**
- Consumes: `ShipmentService::assertPreparable()`, `PrepareShipment::dispatch(...)`.
- Produces: response 202 `{data:{queued:true, order_id:int}}`; lỗi validate → 422 (envelope chuẩn qua ValidationException).

- [ ] **Step 1: Thêm test (Queue::fake)**

Thêm vào `PrepareShipmentAsyncTest.php`:

```php
public function test_ship_endpoint_dispatches_job_and_returns_202(): void
{
    \Illuminate\Support\Facades\Queue::fake();
    $order = $this->makeManualOrderReadyToPrepare();
    $this->actingAsUserWithShipPermission($order); // helper: user có quyền fulfillment.ship + tenant header

    $this->postJson("/api/v1/orders/{$order->id}/ship", [], $this->tenantHeaders())
        ->assertStatus(202)
        ->assertJsonPath('data.queued', true)
        ->assertJsonPath('data.order_id', $order->id);

    \Illuminate\Support\Facades\Queue::assertPushed(\CMBcoreSeller\Modules\Fulfillment\Jobs\PrepareShipment::class,
        fn ($job) => $job->orderId === $order->id);
}

public function test_ship_endpoint_returns_422_for_out_of_stock(): void
{
    \Illuminate\Support\Facades\Queue::fake();
    $order = $this->makeManualOrderOutOfStock();
    $this->actingAsUserWithShipPermission($order);

    $this->postJson("/api/v1/orders/{$order->id}/ship", [], $this->tenantHeaders())
        ->assertStatus(422);
    \Illuminate\Support\Facades\Queue::assertNotPushed(\CMBcoreSeller\Modules\Fulfillment\Jobs\PrepareShipment::class);
}
```

Helper `actingAsUserWithShipPermission` / `tenantHeaders` / `makeManualOrderOutOfStock`: theo pattern feature test hiện có (Sanctum + `X-Tenant-Id`). Xem `app/tests/Feature/Fulfillment/*ControllerTest*` để tái dùng.

- [ ] **Step 2: Chạy test — phải fail**

Run: `cd app && php artisan test --filter=PrepareShipmentAsyncTest`
Expected: FAIL — endpoint trả 201 (cũ) chứ không 202.

- [ ] **Step 3: Sửa controller**

Thay thân `createForOrder` (sau khi `$order = Order::...findOrFail($orderId);`):

```php
$order = Order::query()->whereNull('deleted_at')->findOrFail($orderId);
// Idempotent: đã có vận đơn open ⇒ coi như đã chuẩn bị (không dispatch lại).
$existing = Shipment::query()->where('order_id', $order->getKey())->open()->first();
if ($existing) {
    return response()->json(['data' => ['queued' => false, 'already_prepared' => true, 'order_id' => $order->getKey()]], 200);
}
try {
    $this->service->assertPreparable($order);
} catch (\RuntimeException $e) {
    throw ValidationException::withMessages(['order' => $e->getMessage()]);
}
\CMBcoreSeller\Modules\Fulfillment\Jobs\PrepareShipment::dispatch(
    $order->getKey(),
    $data['carrier_account_id'] ?? null,
    $data['service'] ?? null,
    ['tracking_no' => $data['tracking_no'] ?? null, 'cod_amount' => $data['cod_amount'] ?? null, 'weight_grams' => $data['weight_grams'] ?? null],
    $request->user()->getKey(),
)->onQueue('fulfillment');

return response()->json(['data' => ['queued' => true, 'order_id' => $order->getKey()]], 202);
```

Thêm `use CMBcoreSeller\Modules\Fulfillment\Jobs\PrepareShipment;` ở đầu file (hoặc dùng FQN như trên). Giữ validate `$data` như cũ.

- [ ] **Step 4: Chạy test — phải pass**

Run: `cd app && php artisan test --filter=PrepareShipmentAsyncTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Fulfillment/Http/Controllers/ShipmentController.php app/tests/Feature/Fulfillment/PrepareShipmentAsyncTest.php
git commit -m "feat(fulfillment): single ship endpoint trả 202 + dispatch PrepareShipment"
```

---

## Task 4: Controller bulk + service `bulkQueuePrepare`

**Files:**
- Modify: `app/app/Modules/Fulfillment/Services/ShipmentService.php` (thay `bulkCreate` ~702-722)
- Modify: `app/app/Modules/Fulfillment/Http/Controllers/ShipmentController.php` (`bulkCreate` ~208-223)
- Test: `app/tests/Feature/Fulfillment/PrepareShipmentAsyncTest.php`

**Interfaces:**
- Produces: `ShipmentService::bulkQueuePrepare(int $tenantId, array $orderIds, ?int $carrierAccountId, ?string $service, ?int $userId): array{queued:list<int>, already_prepared:list<int>, errors:list<array{order_id:int,message:string}>}`.
- Bulk response: `{data:{queued:int[], already_prepared:int[], errors:[{order_id,message}]}}`.

- [ ] **Step 1: Thêm test (Queue::fake)**

Thêm vào `PrepareShipmentAsyncTest.php`:

```php
public function test_bulk_dispatches_jobs_and_separates_errors(): void
{
    \Illuminate\Support\Facades\Queue::fake();
    $ok = $this->makeManualOrderReadyToPrepare();
    $oos = $this->makeManualOrderOutOfStock();
    $this->actingAsUserWithShipPermission($ok);

    $this->postJson('/api/v1/shipments/bulk-create',
        ['order_ids' => [$ok->id, $oos->id]], $this->tenantHeaders())
        ->assertOk()
        ->assertJsonPath('data.queued', [$ok->id])
        ->assertJsonFragment(['order_id' => $oos->id]);

    \Illuminate\Support\Facades\Queue::assertPushed(\CMBcoreSeller\Modules\Fulfillment\Jobs\PrepareShipment::class, 1);
}
```

- [ ] **Step 2: Chạy test — phải fail**

Run: `cd app && php artisan test --filter=PrepareShipmentAsyncTest`
Expected: FAIL — response còn shape cũ `{created, errors}`.

- [ ] **Step 3: Thay `bulkCreate` service bằng `bulkQueuePrepare`**

Trong `ShipmentService.php`, thay method `bulkCreate(...)` bằng:

```php
/**
 * Validate rẻ từng đơn (DB) rồi dispatch PrepareShipment cho đơn hợp lệ — KHÔNG gọi sàn trong request.
 * @param  list<int>  $orderIds
 * @return array{queued: list<int>, already_prepared: list<int>, errors: list<array{order_id:int,message:string}>}
 */
public function bulkQueuePrepare(int $tenantId, array $orderIds, ?int $carrierAccountId, ?string $service, ?int $userId = null): array
{
    $queued = [];
    $alreadyPrepared = [];
    $errors = [];
    $orders = Order::query()->where('tenant_id', $tenantId)->whereIn('id', $orderIds)->whereNull('deleted_at')->get()->keyBy('id');
    foreach ($orderIds as $oid) {
        $order = $orders->get($oid);
        if (! $order) {
            $errors[] = ['order_id' => (int) $oid, 'message' => 'Không tìm thấy đơn.'];

            continue;
        }
        if (Shipment::query()->where('order_id', $order->getKey())->open()->exists()) {
            $alreadyPrepared[] = (int) $oid;

            continue;
        }
        try {
            $this->assertPreparable($order);
        } catch (\RuntimeException $e) {
            $errors[] = ['order_id' => (int) $oid, 'message' => $e->getMessage()];

            continue;
        }
        PrepareShipment::dispatch((int) $oid, $carrierAccountId, $service, [], $userId)->onQueue('fulfillment');
        $queued[] = (int) $oid;
    }

    return ['queued' => $queued, 'already_prepared' => $alreadyPrepared, 'errors' => $errors];
}
```

Thêm `use CMBcoreSeller\Modules\Fulfillment\Jobs\PrepareShipment;` ở đầu `ShipmentService.php` nếu chưa có. Xoá method `bulkCreate` cũ (không còn ai gọi — kiểm `grep -rn "->bulkCreate(" app/app`).

- [ ] **Step 4: Sửa controller `bulkCreate`**

Thay thân:

```php
$res = $this->service->bulkQueuePrepare((int) $tenant->id(), array_map('intval', $data['order_ids']), $data['carrier_account_id'] ?? null, $data['service'] ?? null, $request->user()->getKey());

return response()->json(['data' => $res]);
```

(Bỏ `ShipmentResource::collection(...created...)`.)

- [ ] **Step 5: Chạy test — phải pass**

Run: `cd app && php artisan test --filter=PrepareShipmentAsyncTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Fulfillment/Services/ShipmentService.php app/app/Modules/Fulfillment/Http/Controllers/ShipmentController.php app/tests/Feature/Fulfillment/PrepareShipmentAsyncTest.php
git commit -m "feat(fulfillment): bulk-create dispatch PrepareShipment, trả {queued,already_prepared,errors}"
```

---

## Task 5: php-fpm `request_terminate_timeout` (chống orphan) — fix #2

**Files:**
- Modify: `app/docker/entrypoint.sh` (khối heredoc sinh `zz-pool.conf` ~46-55)

- [ ] **Step 1: Thêm dòng config**

Trong heredoc `cat > /usr/local/etc/php-fpm.d/zz-pool.conf <<EOF ... EOF`, thêm dòng (sau `pm.process_idle_timeout = 10s`):

```
request_terminate_timeout = ${PHP_FPM_REQUEST_TIMEOUT}
```

Và phía trên (cạnh các default `: "${PHP_FPM_MAX_CHILDREN:=40}"`), thêm:

```sh
# Giết request chạy quá lâu (mặc định 115s — DƯỚI trần nginx/NPM 120s) ⇒ không để PHP chạy orphan
# sau khi proxy đã trả 504. Mọi request >120s vốn đã bị 504 nên đây chỉ dọn cái đã hỏng.
: "${PHP_FPM_REQUEST_TIMEOUT:=115s}"
```

- [ ] **Step 2: Lint bash**

Run: `cd app && bash -n docker/entrypoint.sh`
Expected: không output (cú pháp OK).

- [ ] **Step 3: Commit**

```bash
git add app/docker/entrypoint.sh
git commit -m "fix(infra): php-fpm request_terminate_timeout=115s chống orphan sau 504"
```

---

## Task 6: Horizon `supervisor-fulfillment` + bump labels — fix #4

**Files:**
- Modify: `app/config/horizon.php` (block `defaults` + mỗi env `local`/`staging`/`production`, tham chiếu supervisor-labels ~209-334)

**Interfaces:**
- Produces: supervisor mới xử lý queue `fulfillment` ở mọi env.

- [ ] **Step 1: Đọc cấu trúc hiện tại**

Run: `cd app && grep -nE "supervisor-labels|'queue'|maxProcesses|'environments'|'defaults'" config/horizon.php`
Đọc block `supervisor-labels` trong `defaults` và từng env để copy y khuôn.

- [ ] **Step 2: Thêm `supervisor-fulfillment` vào `defaults`**

Trong `'defaults' => [...]`, cạnh `supervisor-labels`, thêm (khớp field của supervisor-labels, chỉ đổi `queue` + có thể `timeout`):

```php
'supervisor-fulfillment' => [
    'connection' => 'redis',
    'queue' => ['fulfillment'],
    'balance' => 'auto',
    'autoScalingStrategy' => 'time',
    'maxProcesses' => 1,
    'maxTime' => 0,
    'maxJobs' => 0,
    'memory' => 256,
    'tries' => 5,
    'timeout' => 120,
    'nice' => 5,
],
```

- [ ] **Step 3: Override theo env**

Trong `'environments' => ['production' => [...]]`: thêm `'supervisor-fulfillment' => ['maxProcesses' => 6, 'balanceMaxShift' => 1, 'balanceCooldown' => 3]` và bump `'supervisor-labels' => ['maxProcesses' => 6, ...]` (giữ field khác). Trong `staging`: `'supervisor-fulfillment' => ['maxProcesses' => 2]`. Trong `local`: `'supervisor-fulfillment' => ['maxProcesses' => 1]`. Khớp đúng pattern các env đang dùng cho supervisor-labels.

- [ ] **Step 4: Thêm cảnh báo wait threshold (tùy chọn)**

Nếu có block `waits` (~line 104 `'redis:labels' => 120`), thêm `'redis:fulfillment' => 120`.

- [ ] **Step 5: Verify config load**

Run: `cd app && php artisan config:clear && php -r "var_dump(array_keys(config('horizon.environments.production')));"`
Expected: mảng có cả `supervisor-fulfillment`.

- [ ] **Step 6: Commit**

```bash
git add app/config/horizon.php
git commit -m "feat(infra): Horizon supervisor-fulfillment cho queue prepare + bump labels"
```

---

## Task 7: FE — types response mới (`useShipOrder`, `useBulkCreateShipments`)

**Files:**
- Modify: `app/resources/js/lib/fulfillment.tsx` (`useShipOrder` ~265-274, `useBulkCreateShipments` ~298-307)

**Interfaces:**
- Produces: `useShipOrder().mutateAsync(...)` → `{ queued: boolean; order_id: number; already_prepared?: boolean }`.
- `useBulkCreateShipments().mutateAsync(...)` → `{ queued: number[]; already_prepared: number[]; errors: Array<{order_id:number;message:string}> }`.

- [ ] **Step 1: Sửa `useShipOrder`**

```tsx
export function useShipOrder() {
    const api = useScopedApi();
    const invalidate = useFulfillmentInvalidate();
    return useMutation({
        mutationFn: async ({ orderId, ...vars }: { orderId: number; carrier_account_id?: number | null; service?: string | null; tracking_no?: string | null; cod_amount?: number; weight_grams?: number }) => {
            const { data } = await api!.post<{ data: { queued: boolean; order_id: number; already_prepared?: boolean } }>(`/orders/${orderId}/ship`, vars); return data.data;
        },
        onSuccess: invalidate,
    });
}
```

- [ ] **Step 2: Sửa `useBulkCreateShipments`**

```tsx
export function useBulkCreateShipments() {
    const api = useScopedApi();
    const invalidate = useFulfillmentInvalidate();
    return useMutation({
        mutationFn: async (vars: { order_ids: number[]; carrier_account_id?: number | null; service?: string | null }) => {
            const { data } = await api!.post<{ data: { queued: number[]; already_prepared: number[]; errors: Array<{ order_id: number; message: string }> } }>('/shipments/bulk-create', vars); return data.data;
        },
        onSuccess: invalidate,
    });
}
```

- [ ] **Step 3: Typecheck (sẽ đỏ ở callers — sửa ở Task 8/9)**

Run: `cd app && npm run typecheck`
Expected: lỗi type tại `OrdersPage.tsx` (`r.created`) và `OrderProcessing.tsx` (`s.id`). Đây là tín hiệu đúng cho 2 task sau. KHÔNG commit khi typecheck đỏ — gộp commit ở Task 9 sau khi callers sửa xong. (Bỏ qua commit ở task này.)

---

## Task 8: FE — `useBulkAction` thêm status `'queued'` + render modal

**Files:**
- Modify: `app/resources/js/lib/useBulkAction.ts` (type `BulkItemStatus`, `BulkServerResult`)
- Modify: `app/resources/js/components/BulkProgressModal.tsx`

**Interfaces:**
- Produces: `BulkItemStatus` thêm `'queued'`; `BulkServerResult.status` thêm `'queued'`.

- [ ] **Step 1: Thêm `'queued'` vào types**

`useBulkAction.ts`:

```ts
export type BulkItemStatus = 'pending' | 'running' | 'queued' | 'ok' | 'skipped' | 'error';
```

```ts
export interface BulkServerResult {
    id: number;
    status: 'queued' | 'ok' | 'skipped' | 'error';
    reason?: string;
    technical?: string;
}
```

- [ ] **Step 2: Render `'queued'` trong BulkProgressModal**

Đọc `BulkProgressModal.tsx` tìm map icon/màu theo status (vd switch trên `it.status`). Thêm nhánh `queued`: icon `@ant-design/icons` `ClockCircleOutlined` màu xanh dương (`#1677ff`), nhãn "Đang xử lý nền". Tính `done` (progress bar) coi `queued` là đã xong gửi (đếm vào done) — vì request đã nhận. Ví dụ nếu có hàm `isDone = (s) => ['ok','skipped','error'].includes(s)` thì đổi thành `['ok','skipped','error','queued']`.

- [ ] **Step 3: Typecheck (vẫn đỏ tại OrdersPage — sửa Task 9)**

Run: `cd app && npm run typecheck`
Expected: chỉ còn lỗi tại `OrdersPage.tsx`/`OrderProcessing.tsx`. Chưa commit (gộp Task 9).

---

## Task 9: FE — OrdersPage map kết quả + trigger polling (chip + slip)

**Files:**
- Modify: `app/resources/js/pages/OrdersPage.tsx` (`startPreparePopup` ~284-311)

**Interfaces:**
- Consumes: `bulkPrepare.mutateAsync` → `{queued, already_prepared, errors}`; `syncPoll.start()` (đã có, refetch list + stats + tabStats).

- [ ] **Step 1: Sửa runner trong `startPreparePopup`**

Thay khối từ `const r = await bulkPrepare.mutateAsync(...)` tới `return [...skips, ...ok, ...errs];`:

```tsx
const r = await bulkPrepare.mutateAsync({ order_ids: actionable, carrier_account_id: carrierAccountId ?? undefined });
// Async: queued = đã xếp hàng xử lý nền; polling (chip trạng thái + slip) cập nhật khi job xong.
if (r.queued.length > 0) syncPoll.start();
const queued: BulkActionResult[] = r.queued.map((id) => ({ id, status: 'queued', reason: 'Đã xếp hàng — đang chuẩn bị nền.' }));
const already: BulkActionResult[] = r.already_prepared.map((id) => ({ id, status: 'skipped', reason: 'Đã có phiếu giao hàng — bỏ qua.' }));
const errs: BulkActionResult[] = r.errors.map((e) => ({ id: e.order_id, status: 'error', reason: e.message }));
return [...skips, ...queued, ...already, ...errs];
```

Kiểm `BulkActionResult` (import trong OrdersPage) chính là `BulkServerResult` từ `useBulkAction` (Task 8 đã thêm `'queued'`). Nếu là alias khác, cập nhật cho khớp.

- [ ] **Step 2: Cập nhật comment dòng ~288-290** cho đúng (giờ tem + vận đơn đều async, không "đồng bộ — invalidate tự refetch" nữa). Sửa thành ghi chú: "Chuẩn bị hàng dispatch job nền (PrepareShipment) → poll 90s để status/chip/slip tự render."

- [ ] **Step 3: Typecheck + lint**

Run: `cd app && npm run typecheck`
Expected: hết lỗi tại OrdersPage (còn OrderProcessing — Task 10).

- [ ] **Step 4: (chưa commit, gộp sau Task 10 để typecheck xanh hoàn toàn)**

---

## Task 10: FE — OrderProcessing single prepare polling + fix `markReady` async-safe

**Files:**
- Modify: `app/resources/js/components/OrderProcessing.tsx` (`OrderActions` ~108-263)

**Interfaces:**
- Consumes: `useSyncPolling` (`@/lib/syncPolling`), `useFulfillmentInvalidate`? — dùng invalidate qua queryClient. Đơn giản nhất: dùng `useSyncPolling(tick)` với `tick = invalidate fulfillment queries`.

- [ ] **Step 1: Thêm polling cho single prepare**

Ở đầu `OrderActions`, thêm:

```tsx
import { useSyncPolling } from '@/lib/syncPolling';
import { useQueryClient } from '@tanstack/react-query';
import { useCurrentTenantId } from '@/lib/tenant'; // đường dẫn theo hook hiện có (kiểm import useCurrentTenantId đang dùng ở đâu)
```

Trong component:

```tsx
const qc = useQueryClient();
const tenantId = useCurrentTenantId();
// Prepare async ⇒ status/slip đổi sau khi job nền xong ⇒ poll 90s để chip trạng thái + tình trạng in tự render.
const prepPoll = useSyncPolling(() => {
    qc.invalidateQueries({ queryKey: ['orders', tenantId] });       // list + stats (chip) đều dưới key này
    qc.invalidateQueries({ queryKey: ['fulfillment-ready', tenantId] });
    qc.invalidateQueries({ queryKey: ['shipments', tenantId] });
}, { durationMs: 90_000 });
```

(Xác nhận `useCurrentTenantId` import path bằng cách xem cách `fulfillment.tsx` import nó — memory `fe-tenant-id-use-hook-with-fallback`: PHẢI dùng `useCurrentTenantId()`.)

- [ ] **Step 2: Kích hoạt poll sau prepare**

Sửa `runPrepare`:

```tsx
const runPrepare = (carrierAccountId?: number | null) => ship.mutate(
    { orderId: order.id, ...(carrierAccountId != null ? { carrier_account_id: carrierAccountId } : {}) },
    { onSuccess: () => { message.success('Đã chuẩn bị hàng — đang lấy phiếu giao hàng. Đơn sẽ chuyển sang "Đang xử lý".'); setCarrierPicker(false); prepPoll.start(); }, onError: err },
);
```

- [ ] **Step 3: Sửa `markReady` an toàn khi ship async**

`markReady` cũ phụ thuộc shipment trả về (`(s) => pack.mutate([s.id])`) — async không còn shipment. Vì nút "Đã gói & sẵn sàng bàn giao" chỉ hiện khi đã có `sh` (nhánh dòng 213-228), nhánh `!sh` chỉ là phòng thủ. Sửa thành:

```tsx
// Đã gói & sẵn sàng bàn giao: chỉ markPacked khi đã có vận đơn. Chưa có vận đơn (đơn chưa chuẩn bị xong)
// ⇒ kích hoạt chuẩn bị nền + báo user chờ phiếu rồi mới gói (ship async không trả vận đơn ngay).
const markReady = () => {
    if (sh) {
        pack.mutate([sh.id], { onSuccess: () => message.success('Đã đánh dấu gói xong — chờ bàn giao ĐVVC'), onError: err });
        return;
    }
    runPrepare();
    message.info('Đang chuẩn bị hàng — chờ có phiếu giao hàng rồi đánh dấu "Đã gói".');
};
```

- [ ] **Step 4: Typecheck + lint + build**

Run: `cd app && npm run typecheck && npm run lint && npm run build`
Expected: tất cả xanh.

- [ ] **Step 5: Commit (gộp Task 7-10 FE)**

```bash
git add app/resources/js/lib/fulfillment.tsx app/resources/js/lib/useBulkAction.ts app/resources/js/components/BulkProgressModal.tsx app/resources/js/pages/OrdersPage.tsx app/resources/js/components/OrderProcessing.tsx
git commit -m "feat(fulfillment): FE async prepare — 202/queued, progress modal queued, polling chip+slip, markReady async-safe"
```

---

## Task 11: Docs + quality gate tổng

**Files:**
- Modify: `docs/05-api/endpoints.md`, `docs/07-infra/queues-and-scheduler.md`

- [ ] **Step 1: Cập nhật endpoints.md**

Sửa mô tả `POST /api/v1/orders/{id}/ship` (giờ 202 `{queued,order_id}`) và `POST /api/v1/shipments/bulk-create` (response `{queued[],already_prepared[],errors[]}`). Tìm dòng tương ứng: `grep -n "orders/{id}/ship\|shipments/bulk-create\|bulk-create" docs/05-api/endpoints.md`.

- [ ] **Step 2: Cập nhật queues-and-scheduler.md**

Thêm job `PrepareShipment` (queue `fulfillment`, tries 5, backoff [10,30,60,120,300], idempotent ShouldBeUnique+WithoutOverlapping) + supervisor `supervisor-fulfillment`. Tìm bảng job: `grep -n "FetchChannelLabel\|labels\|supervisor" docs/07-infra/queues-and-scheduler.md`.

- [ ] **Step 3: Quality gate BE**

Run: `cd app && vendor/bin/pint --test && vendor/bin/phpstan analyse && php artisan test --filter=Fulfillment`
Expected: pint xanh, phpstan xanh (level 5 + baseline), test Fulfillment liên quan PASS (lưu ý 7 test GHN/fulfillment cũ vốn đỏ trên main — memory `test-verify-baseline`; xác nhận KHÔNG phát sinh đỏ MỚI ngoài 7 cái đó).

- [ ] **Step 4: Quality gate FE**

Run: `cd app && npm run lint && npm run typecheck && npm run build`
Expected: tất cả xanh.

- [ ] **Step 5: Commit**

```bash
git add docs/05-api/endpoints.md docs/07-infra/queues-and-scheduler.md
git commit -m "docs(fulfillment): cập nhật endpoints + queues cho prepare async"
```

---

## Task 12: Verify trên prod (sau deploy)

> Không phải bước code — checklist xác minh sau khi deploy baked image (SSH 2-hop, sudo docker; memory `prod-ops-ssh-and-deploy`).

- [ ] **Step 1: Deploy** image mới cho web + worker (worker phải có supervisor-fulfillment). Vì Horizon config cached + chạy ở worker-1: `docker exec cmb_seller-worker-1 php artisan horizon:terminate` để nạp lại supervisor.
- [ ] **Step 2: Verify supervisor sống:** `docker exec cmb_seller-worker-1 php artisan horizon:list` (hoặc xem log) — phải thấy `supervisor-fulfillment` đang chạy. (Gotcha: thiếu ⇒ job kẹt im lặng.)
- [ ] **Step 3: Verify request nhanh:** chuẩn bị 1 đơn thật trên Uor → request `/shipments/bulk-create` hoặc `/orders/{id}/ship` trả <1s (không còn block), job `PrepareShipment` chạy trên queue `fulfillment` (xem `docker logs cmb_seller-worker-1`), đơn sang Processing + có tem sau vài giây; chip trạng thái + sub-tab phiếu tự cập nhật khi poll.
- [ ] **Step 4: Verify chống orphan:** `docker exec cmb_seller-app-1 sh -c 'cat /usr/local/etc/php-fpm.d/zz-pool.conf | grep terminate'` → `request_terminate_timeout = 115s`.

---

## Self-Review

**Spec coverage:** mục 2 (single+bulk async, hybrid validate) → Task 1/3/4; #2 terminate_timeout → Task 5; #3 bỏ usleep khỏi request → đạt gián tiếp (toàn bộ prepare vào job; usleep còn lại nằm trong job — chấp nhận, nêu ở spec §2); #4 queue scale → Task 6; FE contract + optimistic/polling + chip + slip → Task 7-10; docs → Task 11; verify prod → Task 12. Edge cases spec §7: tenant runAs (Task 2), idempotency 3 lớp (Task 2), failed→has_issue (Task 2), markReady async (Task 10), Horizon supervisor gotcha (Task 6/12), terminate an toàn (Task 5). markPacked async = ngoài phạm vi (spec §2) — không có task, đúng chủ ý.

**Placeholder scan:** không có TBD/TODO; code đầy đủ ở các bước. Một số helper test (`makeManualOrderReadyToPrepare`, `actingAsUserWithShipPermission`) yêu cầu khớp pattern test fulfillment hiện có — đã chỉ rõ nơi tham khảo thay vì để trống.

**Type consistency:** `bulkQueuePrepare` trả `{queued,already_prepared,errors}` khớp controller + FE type `useBulkCreateShipments` + map OrdersPage. `useShipOrder` → `{queued,order_id,already_prepared?}` khớp controller 202/200. `BulkItemStatus`/`BulkServerResult` thêm `'queued'` dùng nhất quán Task 8↔9. Job ctor `PrepareShipment(orderId, carrierAccountId?, service?, opts?, userId?)` khớp mọi nơi dispatch (Task 2/3/4).
