# RefreshStuckOrders Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development. Steps use checkbox (`- [ ]`).

**Goal:** Job nền chạy mỗi 2h, làm mới trạng thái đơn sàn đang "treo" (refetch từ sàn) để không kẹt không-thao-tác-được vĩnh viễn — cả 3 sàn.

**Architecture:** Job `RefreshStuckOrders` (per active channel account) query đơn treo → `fetchOrderDetail → mapStatus → upsertWithStatus(force:true)` trong `runAs(tenant)` (đúng đường webhook đã kiểm chứng) → clear has_issue tem/tracking lỗi thời khi đơn đã tiến lên. Thêm cờ `force` (default false) vào `OrderUpsertService` để bỏ qua CHỈ stale-guard `source_updated_at`. KHÔNG đụng SyncOrdersForShop/connector/fulfillment/FE.

**Tech Stack:** Laravel 11, queue jobs, `routes/console.php` Schedule facade, PHPUnit.

## Global Constraints
- Lệnh từ `app/`. Verify: `php artisan test --filter=...`, `vendor/bin/pint --test` + `vendor/bin/phpstan analyse` (file đổi).
- App KHÔNG chạy local (chỉ prod) → verify backend = `php artisan test` (chạy local OK), KHÔNG cần chạy app.
- `force` mặc định `false` ⇒ mọi caller hiện tại (poll/unprocessed/webhook) KHÔNG đổi hành vi.
- Vẫn tôn trọng `meta.tracking_stopped` + sticky-forward + abnormal-jump.
- `config()` không `env()`. Chuỗi VN; identifier EN. Không migration.
- `git add` đúng file mỗi task; không đụng working-tree khác (help-center md, useMessageNotifications.ts).

---

### Task 1: Thêm cờ `force` vào OrderUpsertService (bỏ qua stale-guard)

**Files:**
- Modify: `app/app/Modules/Orders/Services/OrderUpsertService.php`
- Test: `app/tests/Feature/Orders/OrderUpsertForceTest.php`

**Interfaces:**
- Produces: `upsertWithStatus(OrderDTO $dto, int $tenantId, ?int $channelAccountId, string $historySource, StandardOrderStatus $status, bool $force = false): Order` và `doUpsert(..., bool $force = false): Order`. Khi `force=true` bỏ qua chỉ guard `source_updated_at`; vẫn skip nếu `tracking_stopped`.

- [ ] **Step 1: Failing test**
```php
<?php

namespace Tests\Feature\Orders;

use CMBcoreSeller\Integrations\Channels\DTO\OrderDTO;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Services\OrderUpsertService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderUpsertForceTest extends TestCase
{
    use RefreshDatabase;

    /** Dựng OrderDTO tối thiểu cho 1 đơn shopee. Tham khảo OrderDTO constructor + các test upsert hiện có. */
    private function dto(string $ext, string $rawStatus, \Carbon\CarbonImmutable $srcUpdated): OrderDTO
    {
        // NOTE người triển khai: copy đúng chữ ký OrderDTO (xem app/app/Integrations/Channels/DTO/OrderDTO.php
        // và cách OrderUpsert*Test/connector test dựng DTO). Điền các field bắt buộc tối thiểu:
        // source='shopee', externalOrderId=$ext, rawStatus=$rawStatus, sourceUpdatedAt=$srcUpdated, items=[], packages=[], raw=[].
        return /* new OrderDTO(...) */ OrderDTOFactoryHelper::minimal($ext, $rawStatus, $srcUpdated);
    }

    public function test_force_applies_status_even_when_stale_guard_would_skip(): void
    {
        $svc = app(OrderUpsertService::class);
        $now = \Carbon\CarbonImmutable::now();
        // Seed: đơn shopee, source_updated_at = now, status pending.
        $svc->upsertWithStatus($this->dto('SP1', 'UNPAID', $now), 1, 10, 'sync', StandardOrderStatus::Unpaid);

        // dto mới có sourceUpdatedAt CŨ HƠN/BẰNG → bình thường bị stale-guard skip.
        $older = $now->subHour();
        // force=false: KHÔNG đổi (vẫn Unpaid)
        $svc->upsertWithStatus($this->dto('SP1', 'SHIPPED', $older), 1, 10, 'sync', StandardOrderStatus::Shipped, false);
        $o = Order::withoutGlobalScope(TenantScope::class)->where('external_order_id', 'SP1')->first();
        $this->assertSame(StandardOrderStatus::Unpaid, $o->status, 'force=false phải giữ stale-guard (không đổi)');

        // force=true: ÁP trạng thái mới dù timestamp không mới hơn
        $svc->upsertWithStatus($this->dto('SP1', 'SHIPPED', $older), 1, 10, 'sync', StandardOrderStatus::Shipped, true);
        $o->refresh();
        $this->assertSame(StandardOrderStatus::Shipped, $o->status, 'force=true phải áp trạng thái mới');
    }

    public function test_force_still_respects_tracking_stopped(): void
    {
        $svc = app(OrderUpsertService::class);
        $now = \Carbon\CarbonImmutable::now();
        $svc->upsertWithStatus($this->dto('SP2', 'UNPAID', $now), 1, 10, 'sync', StandardOrderStatus::Unpaid);
        $o = Order::withoutGlobalScope(TenantScope::class)->where('external_order_id', 'SP2')->first();
        $o->forceFill(['meta' => ['tracking_stopped' => true]])->save();

        $svc->upsertWithStatus($this->dto('SP2', 'SHIPPED', $now->subHour()), 1, 10, 'sync', StandardOrderStatus::Shipped, true);
        $o->refresh();
        $this->assertSame(StandardOrderStatus::Unpaid, $o->status, 'tracking_stopped phải được tôn trọng kể cả force');
    }
}
```
> Người triển khai: nếu dựng `OrderDTO` thủ công quá rườm, tạo 1 helper nhỏ trong test (factory) đọc đúng chữ ký `OrderDTO`. KHÔNG được viết test rỗng. Nếu thực sự kẹt dựng DTO, báo DONE_WITH_CONCERNS + giải thích, vẫn implement code.

- [ ] **Step 2: Run → FAIL** `php artisan test --filter=OrderUpsertForceTest` (force param chưa có / hành vi sai).

- [ ] **Step 3: Implement.** Trong `OrderUpsertService.php`:
  - Đổi chữ ký `upsertWithStatus(...)` thêm `, bool $force = false` (cuối). Bên trong, chỗ gọi `$this->doUpsert($dto, $tenantId, $channelAccountId, $historySource, $status)` → truyền thêm `$force`.
  - Đổi chữ ký `doUpsert(...)` thêm `, bool $force = false` (cuối) và `use(...)` của closure `$txn` thêm `$force`.
  - Sửa guard dòng ~146:
    ```php
    if (! $force && ! $created && $order->source_updated_at && $dto->sourceUpdatedAt->lessThanOrEqualTo($order->source_updated_at)) {
        return [$order, false, false, $order->status];
    }
    ```
  - GIỮ NGUYÊN guard `tracking_stopped` (dòng ~152) — KHÔNG thêm `$force` vào đó.

- [ ] **Step 4: Run → PASS** `php artisan test --filter=OrderUpsertForceTest`.
- [ ] **Step 5: Gate + commit** `vendor/bin/pint --test app/Modules/Orders/Services/OrderUpsertService.php && vendor/bin/phpstan analyse app/Modules/Orders/Services/OrderUpsertService.php`
```bash
git add app/app/Modules/Orders/Services/OrderUpsertService.php app/tests/Feature/Orders/OrderUpsertForceTest.php
git commit -m "feat(orders): upsertWithStatus thêm cờ force (bỏ qua stale-guard source_updated_at; giữ tracking_stopped)"
```

---

### Task 2: Job `RefreshStuckOrders` + config + scheduler

**Files:**
- Create: `app/app/Modules/Channels/Jobs/RefreshStuckOrders.php`
- Modify: `app/config/integrations.php` (thêm block `order_refresh`)
- Modify: `app/routes/console.php` (1 entry mỗi 2h)
- Test: `app/tests/Feature/Channels/RefreshStuckOrdersTest.php`

**Interfaces:**
- Consumes: `OrderUpsertService::upsertWithStatus(..., force:true)` (Task 1); `ChannelRegistry::for($provider)->fetchOrderDetail(authContext, ext)` + `->mapStatus(raw, rawArr)`; `Tenant::find($id)->runAs($shop, fn)`.
- Produces: `RefreshStuckOrders` (constructor `__construct(public int $channelAccountId)`, `ShouldBeUnique` key `refresh-stuck:{id}`), `handle()` refresh đơn treo.

- [ ] **Step 1: Failing test**
```php
<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Modules\Channels\Jobs\RefreshStuckOrders;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefreshStuckOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_refreshes_stuck_order_status_and_clears_label_issue(): void
    {
        // Seed: tenant, ChannelAccount(provider shopee, active), 1 order TREO
        // (status=processing, has_issue=true, issue_reason chứa 'phiếu giao hàng',
        //  last_synced_at = now-3h, placed_at = now-1d, external_order_id='SPX1').
        // Đăng ký 1 FAKE ChannelConnector vào ChannelRegistry mà fetchOrderDetail trả OrderDTO
        // status đã tiến lên (vd SHIPPED) cho 'SPX1'. (Mẫu register connector: xem
        // OrderResourcePrepareBlockReasonTest / AssertChannelOrderFulfillableTest.)
        [$account, $order] = $this->seedStuckOrder(/* ... */);
        $this->registerFakeShopeeReturning('SPX1', rawStatus: 'SHIPPED', standard: StandardOrderStatus::Shipped);

        (new RefreshStuckOrders((int) $account->getKey()))->handle(
            app(ChannelRegistry::class), app(\CMBcoreSeller\Modules\Orders\Services\OrderUpsertService::class)
        );

        $order->refresh();
        $this->assertSame(StandardOrderStatus::Shipped, $order->status, 'đơn treo phải được refresh sang trạng thái mới');
        $this->assertFalse((bool) $order->has_issue, 'issue tem/tracking lỗi thời phải được clear khi đã tiến lên');
    }

    public function test_skips_non_stuck_and_tracking_stopped(): void
    {
        // Đơn KHÔNG treo (has_issue=false, có tem) → fetchOrderDetail KHÔNG được gọi (fake đếm số lần gọi = 0).
        // Đơn tracking_stopped → bỏ qua.
        // (Khẳng định bằng việc trạng thái KHÔNG đổi.)
        $this->markTestIncompleteIfHelpersMissing();
    }
}
```
> Người triển khai: viết helper `seedStuckOrder()` + `registerFakeShopeeReturning()` theo mẫu test hiện có (register connector vào `ChannelRegistry`, connector fake chỉ cần implement `fetchOrderDetail` trả OrderDTO + `mapStatus` + `code/supports`; có thể `extends ShopeeConnector` và override `fetchOrderDetail`, hoặc 1 lớp fake nhỏ). Test PHẢI assert hành vi thật (status đổi, has_issue clear), KHÔNG rỗng. Kẹt thì DONE_WITH_CONCERNS + giải thích.

- [ ] **Step 2: Run → FAIL** `php artisan test --filter=RefreshStuckOrdersTest` (class chưa tồn tại).

- [ ] **Step 3a: Config.** Trong `app/config/integrations.php` thêm (cạnh block `sync` nếu có, hoặc top-level):
```php
'order_refresh' => [
    'stuck_hours'  => (int) env('ORDER_REFRESH_STUCK_HOURS', 2),
    'max_age_days' => (int) env('ORDER_REFRESH_MAX_AGE_DAYS', 30),
    'batch'        => (int) env('ORDER_REFRESH_BATCH', 200),
    'sleep_ms'     => (int) env('ORDER_REFRESH_SLEEP_MS', 300),
],
```

- [ ] **Step 3b: Job.** Tạo `app/app/Modules/Channels/Jobs/RefreshStuckOrders.php`:
```php
<?php

namespace CMBcoreSeller\Modules\Channels\Jobs;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Services\OrderUpsertService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus as S;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Làm mới trạng thái đơn SÀN đang "treo" (refetch từ sàn) để không kẹt không-thao-tác-được vĩnh viễn.
 * Chạy per active channel account, mỗi ~2h. Tách biệt SyncOrdersForShop. Dùng force=true để bỏ qua
 * stale-guard source_updated_at (vd Lazada timestamp lệch cũ) — vẫn giữ tracking_stopped + sticky-forward.
 */
class RefreshStuckOrders implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $channelAccountId) {}

    public function uniqueId(): string
    {
        return 'refresh-stuck:'.$this->channelAccountId;
    }

    public function handle(ChannelRegistry $registry, OrderUpsertService $upsert): void
    {
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($this->channelAccountId);
        if (! $account || $account->status !== ChannelAccount::STATUS_ACTIVE || ! $registry->has((string) $account->provider)) {
            return;
        }
        $tenant = Tenant::query()->find($account->tenant_id);
        if (! $tenant) {
            return;
        }
        $cfg = (array) config('integrations.order_refresh', []);
        $stuckHours = (int) ($cfg['stuck_hours'] ?? 2);
        $maxAgeDays = (int) ($cfg['max_age_days'] ?? 30);
        $batch = (int) ($cfg['batch'] ?? 200);
        $sleepMs = (int) ($cfg['sleep_ms'] ?? 300);

        $connector = $registry->for((string) $account->provider);

        $orders = Order::withoutGlobalScope(TenantScope::class)
            ->where('channel_account_id', $this->channelAccountId)
            ->whereIn('status', [S::Pending->value, S::Processing->value, S::ReadyToShip->value])
            ->where(function ($q) {
                $q->where('has_issue', true)
                    ->orWhereHas('shipments', function ($s) {
                        $s->whereIn('status', Shipment::OPEN_STATUSES)
                            ->whereNull('label_path')
                            ->where(fn ($w) => $w->whereNull('label_fetch_next_retry_at')->orWhere('label_fetch_next_retry_at', '<=', now()));
                    });
            })
            ->where(fn ($q) => $q->whereNull('last_synced_at')->orWhere('last_synced_at', '<', now()->subHours($stuckHours)))
            ->where('placed_at', '>=', now()->subDays($maxAgeDays))
            ->orderBy('id')
            ->limit($batch)
            ->get();

        foreach ($orders as $order) {
            try {
                $tenant->runAs($account, function () use ($connector, $account, $order, $upsert) {
                    $dto = $connector->fetchOrderDetail($account->authContext(), (string) $order->external_order_id);
                    $status = $connector->mapStatus($dto->rawStatus, $dto->raw);
                    $upsert->upsertWithStatus($dto, (int) $account->tenant_id, (int) $account->getKey(), 'refresh', $status, true);
                    $this->clearStaleIssue($order->getKey());
                });
            } catch (Throwable $e) {
                Log::warning('order.refresh_stuck_failed', ['order' => $order->getKey(), 'provider' => $account->provider, 'error' => $e->getMessage()]);
            }
            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }
    }

    /**
     * Clear has_issue/issue_reason CŨ loại tem/tracking khi đơn đã tiến lên (đã shipped+/terminal-completed
     * HOẶC đã có tem). KHÔNG động 'SKU chưa ghép' / âm tồn.
     */
    private function clearStaleIssue(int $orderId): void
    {
        $o = Order::withoutGlobalScope(TenantScope::class)->with('shipments')->find($orderId);
        if (! $o || ! $o->has_issue) {
            return;
        }
        $advanced = in_array($o->status, [S::Shipped, S::Delivered, S::Completed, S::Returning, S::ReturnedRefunded, S::Cancelled], true)
            || $o->shipments->first(fn ($s) => filled($s->label_path)) !== null;
        if (! $advanced) {
            return;
        }
        $reason = (string) $o->issue_reason;
        $labelKeywords = ['phiếu giao hàng', 'mã vận đơn', 'sắp xếp vận chuyển', 'in đơn', 'Advance Fulfilment', 'COD đang chờ', 'chờ Shopee'];
        $isLabelIssue = false;
        foreach ($labelKeywords as $kw) {
            if (mb_stripos($reason, $kw) !== false) {
                $isLabelIssue = true;
                break;
            }
        }
        if ($isLabelIssue) {
            $o->forceFill(['has_issue' => false, 'issue_reason' => null])->save();
        }
    }
}
```
> Lưu ý: `runAs($account, ...)` — kiểm chữ ký `Tenant::runAs()` thực tế (xem RenderPrintJob/PrepareShipment dùng `$tenant->runAs($shop, fn)` với `$shop` là gì). Dùng ĐÚNG như các job đó. `Shipment::OPEN_STATUSES` đã tồn tại (hằng public). `meta.tracking_stopped` không cần lọc ở query (upsert đã skip), nhưng có thể thêm `->where(fn($q)=>$q->whereNull('meta->tracking_stopped')->orWhere('meta->tracking_stopped',false))` nếu muốn giảm call (tùy chọn, đảm bảo chạy trên cả sqlite test).

- [ ] **Step 3c: Scheduler.** Trong `app/routes/console.php`, thêm sau block `sync-unprocessed-orders` (dòng ~82):
```php
// Mỗi 2h: làm mới trạng thái đơn SÀN đang treo (refetch) để không kẹt vĩnh viễn. Tách biệt sync thường.
Schedule::call(function () {
    \CMBcoreSeller\Modules\Channels\Models\ChannelAccount::withoutGlobalScope(\CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope::class)
        ->where('status', \CMBcoreSeller\Modules\Channels\Models\ChannelAccount::STATUS_ACTIVE)
        ->orderBy('id')
        ->each(fn ($a) => \CMBcoreSeller\Modules\Channels\Jobs\RefreshStuckOrders::dispatch((int) $a->getKey()));
})->cron('15 */2 * * *')->name('refresh-stuck-orders')->onOneServer()->withoutOverlapping();
```
(`cron('15 */2 * * *')` = phút 15 mỗi 2 giờ — lệch các job khác để tránh dồn.)

- [ ] **Step 4: Run → PASS** `php artisan test --filter=RefreshStuckOrdersTest`.
- [ ] **Step 5: Gate + commit** `vendor/bin/pint --test <files>` + `vendor/bin/phpstan analyse <files>`
```bash
git add app/app/Modules/Channels/Jobs/RefreshStuckOrders.php app/config/integrations.php app/routes/console.php app/tests/Feature/Channels/RefreshStuckOrdersTest.php
git commit -m "feat(orders): job RefreshStuckOrders — refresh trạng thái đơn treo định kỳ (3 sàn), force + clear issue lỗi thời"
```

---

## Self-Review
- Spec coverage: phạm vi đơn treo (Task 2 query) ✓; fetchOrderDetail→mapStatus→upsert force (Task 2 handle + Task 1 force) ✓; clear has_issue tem/tracking khi tiến lên, chừa SKU (Task 2 clearStaleIssue) ✓; mỗi 2h + onOneServer + ShouldBeUnique + cap + sleep (Task 2 scheduler/handle) ✓; tôn trọng tracking_stopped (Task 1 giữ guard) + sticky-forward (không đụng) ✓; không migration/FE/connector ✓.
- Placeholder: test helpers (`seedStuckOrder`, `registerFakeShopeeReturning`, `OrderDTOFactoryHelper`) là fixture người triển khai tự viết theo mẫu repo — đã chỉ rõ nguồn tham khảo; KHÔNG để logic feature dở.
- Type consistency: `force` cuối chữ ký ở cả `upsertWithStatus`/`doUpsert`; `RefreshStuckOrders(int $channelAccountId)`; enum `S::*` đồng nhất.
- Rủi ro: dựng OrderDTO/fake connector trong test hơi nặng → cho phép DONE_WITH_CONCERNS với test focus + giải thích, miễn không test rỗng.
