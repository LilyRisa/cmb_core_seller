# Phiếu xuất kho (goods-issue) + cách ly SKU theo tenant — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a standalone goods-issue (phiếu xuất kho, PXK) WMS document that decreases stock — usable by both web UI and third-party API keys — with negative-stock blocking and hardened cross-tenant SKU rejection.

**Architecture:** Mirror the existing goods-receipts WMS document as a fourth polymorphic `{type}` on `WarehouseDocumentController`/`WarehouseDocumentService`. New tables/models `goods_issues`/`goods_issue_items`, a new `goods_issue` ledger movement + `InventoryLedgerService::issue()`, and `confirmGoodsIssue()` that blocks issuing more than on_hand. Harden the shared store()-time SKU/warehouse validation to name offending ids (proving cross-tenant isolation). Add a web tab and update both the repo API docs and the public in-app API docs page.

**Tech Stack:** PHP 8, Laravel 11, PHPUnit; React 18 + TypeScript + Ant Design.

## Global Constraints

- All PHP/Node commands run from `app/`. PSR-4 `CMBcoreSeller\` → `app/app/`.
- `InventoryLedgerService` is the ONLY writer of stock; every mutation locks the level, appends an immutable `inventory_movements` row with `balance_after`, fires `InventoryChanged`.
- Every business table carries `tenant_id` + `BelongsToTenant`. `Sku`/`Warehouse` are tenant-scoped (global `TenantScope`) — a tenant-scoped `whereIn(id)` naturally excludes other tenants' rows.
- API key = owner Sanctum PAT with `tenant_id` bound by `EnsureTenant` (ignores `X-Tenant-Id` for bearer tokens); reaches the `auth:sanctum → verified → tenant → plan.over_quota_lock` group where warehouse-docs live.
- Envelope `{data}/{data,meta}` success, `{error:{code,message,trace_id,details}}` error (422 for validation). Money = integer VND. Vietnamese user-facing strings, English identifiers.
- Permission for goods-issues reuses `inventory.adjust` (no new permission seeded).
- Negative-stock policy: a goods-issue confirm that would drive `on_hand` below 0 for any SKU is rejected (422), no mutation.
- Confirmed WMS docs are immutable (draft→confirmed / draft→cancelled one-way).
- Prod does NOT auto-migrate (`RUN_MIGRATIONS=false`) — run `php artisan migrate` after deploy. No JS test runner — FE gates on `npm run typecheck`/`build`. ~pre-existing test failures on main unrelated to inventory — only new/related tests must pass.
- Commit Vietnamese messages via a BOM-less UTF-8 file + `git commit -F`. `git` runs via PowerShell (not on Bash-tool PATH). Do NOT push.

---

## Task 1: Tables, models, movement constant

**Files:**
- Create: `app/app/Modules/Inventory/Database/Migrations/2026_07_05_100003_create_goods_issues_tables.php`
- Create: `app/app/Modules/Inventory/Models/GoodsIssue.php`
- Create: `app/app/Modules/Inventory/Models/GoodsIssueItem.php`
- Modify: `app/app/Modules/Inventory/Models/InventoryMovement.php` (add `GOODS_ISSUE` const)
- Test: `app/tests/Feature/Inventory/GoodsIssueModelTest.php` (create)

**Interfaces:**
- Produces: `GoodsIssue` (columns `tenant_id, code, warehouse_id, reason, note, status, confirmed_at, confirmed_by, created_by`; consts `STATUS_DRAFT/CONFIRMED/CANCELLED`; `items()` hasMany, `warehouse()` belongsTo). `GoodsIssueItem` (`tenant_id, goods_issue_id, sku_id, qty`; `$timestamps=false`). `InventoryMovement::GOODS_ISSUE = 'goods_issue'`.

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/Inventory/GoodsIssueModelTest.php`:

```php
<?php

namespace Tests\Feature\Inventory;

use CMBcoreSeller\Modules\Inventory\Models\GoodsIssue;
use CMBcoreSeller\Modules\Inventory\Models\GoodsIssueItem;
use CMBcoreSeller\Modules\Inventory\Models\InventoryMovement;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoodsIssueModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_goods_issue_persists_with_items(): void
    {
        $doc = GoodsIssue::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'code' => 'PXK-260705-ABCDE', 'warehouse_id' => 1,
            'reason' => 'Hàng hỏng', 'status' => GoodsIssue::STATUS_DRAFT, 'created_by' => 1,
        ]);
        GoodsIssueItem::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'goods_issue_id' => $doc->id, 'sku_id' => 7, 'qty' => 3,
        ]);

        $this->assertSame('draft', $doc->fresh()->status);
        $this->assertSame(3, (int) $doc->items()->first()->qty);
        $this->assertSame('goods_issue', InventoryMovement::GOODS_ISSUE);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=GoodsIssueModelTest`
Expected: FAIL — tables/models/const missing.

- [ ] **Step 3: Create the migration**

Create `app/app/Modules/Inventory/Database/Migrations/2026_07_05_100003_create_goods_issues_tables.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phiếu XUẤT kho (goods_issues) — đối xứng phiếu nhập (goods_receipts). draft → confirmed
 * (áp sổ cái: on_hand -= qty, movement `goods_issue`) → cancelled. `reason` = lý do xuất
 * (hủy/hỏng/biếu tặng...). Xem docs/superpowers/specs/2026-07-05-warehouse-goods-issue-and-tenant-safe-sku-design.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('code');                    // PXK-...
            $table->foreignId('warehouse_id');
            $table->string('reason')->nullable();      // lý do xuất
            $table->string('note')->nullable();
            $table->string('status')->default('draft'); // draft | confirmed | cancelled
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
        });
        Schema::create('goods_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('goods_issue_id');
            $table->foreignId('sku_id');
            $table->integer('qty');
            $table->index(['tenant_id', 'goods_issue_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_issue_items');
        Schema::dropIfExists('goods_issues');
    }
};
```

- [ ] **Step 4: Create the models**

Create `app/app/Modules/Inventory/Models/GoodsIssue.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Inventory\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Phiếu xuất kho (Phase 5 WMS). draft → confirmed (áp sổ cái: on_hand -= qty) → cancelled.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $code
 * @property int $warehouse_id
 * @property string|null $reason
 * @property string|null $note
 * @property string $status
 * @property Carbon|null $confirmed_at
 * @property int|null $confirmed_by
 * @property int|null $created_by
 * @property-read Warehouse|null $warehouse
 * @property-read Collection<int, GoodsIssueItem> $items
 */
class GoodsIssue extends Model
{
    use BelongsToTenant;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = ['tenant_id', 'code', 'warehouse_id', 'reason', 'note', 'status', 'confirmed_at', 'confirmed_by', 'created_by'];

    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime'];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsIssueItem::class);
    }
}
```

Create `app/app/Modules/Inventory/Models/GoodsIssueItem.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Inventory\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $goods_issue_id
 * @property int $sku_id
 * @property int $qty
 * @property-read Sku|null $sku
 * @property-read GoodsIssue|null $goodsIssue
 */
class GoodsIssueItem extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'goods_issue_id', 'sku_id', 'qty'];

    protected function casts(): array
    {
        return ['qty' => 'integer'];
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function goodsIssue(): BelongsTo
    {
        return $this->belongsTo(GoodsIssue::class);
    }
}
```

- [ ] **Step 5: Add the movement constant**

In `app/app/Modules/Inventory/Models/InventoryMovement.php`, after the `GOODS_RECEIPT` const (line 36) add:

```php
    public const GOODS_ISSUE = 'goods_issue';
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=GoodsIssueModelTest`
Expected: PASS.

- [ ] **Step 7: Commit**

Message (BOM-less UTF-8 + `-F`): `feat(inventory): bảng + model phiếu xuất kho (goods_issues) + movement goods_issue`
`git add` the migration, both models, InventoryMovement.php, the test.

---

## Task 2: `InventoryLedgerService::issue()`

**Files:**
- Modify: `app/app/Modules/Inventory/Services/InventoryLedgerService.php` (add `issue()`)
- Test: `app/tests/Feature/Inventory/LedgerIssueTest.php` (create)

**Interfaces:**
- Consumes: `InventoryMovement::GOODS_ISSUE` (Task 1); the private `apply(...)` (existing).
- Produces: `InventoryLedgerService::issue(int $tenantId, int $skuId, ?int $warehouseId, int $qty, ?string $note = null, ?string $refType = null, ?int $refId = null, ?int $userId = null): InventoryMovement` — decreases on_hand by `$qty` (positive magnitude), movement type `goods_issue`.

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/Inventory/LedgerIssueTest.php`:

```php
<?php

namespace Tests\Feature\Inventory;

use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Inventory\Models\InventoryMovement;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerIssueTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_decreases_on_hand_and_writes_movement(): void
    {
        InventoryLevel::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'sku_id' => 7, 'warehouse_id' => 2,
            'on_hand' => 10, 'reserved' => 0, 'safety_stock' => 0, 'available_cached' => 10,
        ]);

        $mv = app(InventoryLedgerService::class)->issue(1, 7, 2, 4, 'Xuất kho PXK-x', 'goods_issue', 99, 1);

        $this->assertSame(InventoryMovement::GOODS_ISSUE, $mv->type);
        $this->assertSame(-4, (int) $mv->qty_change);
        $this->assertSame(6, (int) $mv->balance_after);

        $level = InventoryLevel::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', 1)->where('sku_id', 7)->where('warehouse_id', 2)->first();
        $this->assertSame(6, (int) $level->on_hand);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LedgerIssueTest`
Expected: FAIL — `issue()` missing.

- [ ] **Step 3: Add the method**

In `InventoryLedgerService.php`, after `receipt()` (ends line 35) add:

```php
    /** Goods issue (xuất kho: hủy/hỏng/biếu tặng): on_hand -= qty (type=goods_issue). Phase 5 WMS. */
    public function issue(int $tenantId, int $skuId, ?int $warehouseId, int $qty, ?string $note = null, ?string $refType = null, ?int $refId = null, ?int $userId = null): InventoryMovement
    {
        return $this->apply($tenantId, $skuId, $warehouseId, onHandDelta: -$qty, reservedDelta: 0,
            type: InventoryMovement::GOODS_ISSUE, qtyChange: -$qty, refType: $refType, refId: $refId, note: $note, userId: $userId, reason: 'goods_issue');
    }
```

Note: `apply()`'s return type is `?InventoryMovement`, but non-idempotent callers (`receipt`/`transferOut`) declare `: InventoryMovement`. Match that: `issue()` returns `InventoryMovement` (apply never returns null for non-idempotent calls). If phpstan complains about the nullable-vs-non-nullable like it (does not for the siblings), keep the sibling pattern.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=LedgerIssueTest`
Expected: PASS.

- [ ] **Step 5: Commit**

Message: `feat(inventory): InventoryLedgerService::issue (xuất kho giảm on_hand)`
`git add` the service + test.

---

## Task 3: `confirmGoodsIssue()` service + event + negative-stock guard

**Files:**
- Create: `app/app/Modules/Inventory/Events/GoodsIssueConfirmed.php`
- Modify: `app/app/Modules/Inventory/Services/WarehouseDocumentService.php` (add `confirmGoodsIssue`)
- Test: `app/tests/Feature/Inventory/ConfirmGoodsIssueTest.php` (create)

**Interfaces:**
- Consumes: `GoodsIssue`/`GoodsIssueItem` (Task 1), `InventoryLedgerService::issue()` + `onHand()` (Task 2 / existing).
- Produces: `WarehouseDocumentService::confirmGoodsIssue(GoodsIssue $doc, int $userId): GoodsIssue` — blocks if any SKU's total issue qty > current on_hand (throws `RuntimeException` → 422), else applies `issue()` per line, sets status confirmed, dispatches `GoodsIssueConfirmed`. `GoodsIssueConfirmed` event carrying the doc.

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/Inventory/ConfirmGoodsIssueTest.php`:

```php
<?php

namespace Tests\Feature\Inventory;

use CMBcoreSeller\Modules\Inventory\Events\GoodsIssueConfirmed;
use CMBcoreSeller\Modules\Inventory\Models\GoodsIssue;
use CMBcoreSeller\Modules\Inventory\Models\GoodsIssueItem;
use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Inventory\Models\InventoryMovement;
use CMBcoreSeller\Modules\Inventory\Services\WarehouseDocumentService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ConfirmGoodsIssueTest extends TestCase
{
    use RefreshDatabase;

    private function seedLevel(int $onHand): void
    {
        InventoryLevel::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'sku_id' => 7, 'warehouse_id' => 2,
            'on_hand' => $onHand, 'reserved' => 0, 'safety_stock' => 0, 'available_cached' => $onHand,
        ]);
    }

    private function draft(int $qty): GoodsIssue
    {
        $doc = GoodsIssue::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'code' => 'PXK-260705-AAAAA', 'warehouse_id' => 2,
            'status' => GoodsIssue::STATUS_DRAFT, 'created_by' => 1,
        ]);
        GoodsIssueItem::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'goods_issue_id' => $doc->id, 'sku_id' => 7, 'qty' => $qty,
        ]);

        return $doc;
    }

    public function test_confirm_decreases_stock_and_dispatches_event(): void
    {
        Event::fake([GoodsIssueConfirmed::class]);
        $this->seedLevel(10);
        $doc = $this->draft(4);

        $out = app(WarehouseDocumentService::class)->confirmGoodsIssue($doc, 1);

        $this->assertSame('confirmed', $out->status);
        $level = InventoryLevel::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', 1)->where('sku_id', 7)->where('warehouse_id', 2)->first();
        $this->assertSame(6, (int) $level->on_hand);
        $this->assertSame(1, InventoryMovement::withoutGlobalScope(TenantScope::class)
            ->where('type', 'goods_issue')->where('ref_id', $doc->id)->count());
        Event::assertDispatched(GoodsIssueConfirmed::class);
    }

    public function test_confirm_blocks_when_exceeds_on_hand(): void
    {
        $this->seedLevel(3);
        $doc = $this->draft(5);

        $this->expectException(\RuntimeException::class);
        try {
            app(WarehouseDocumentService::class)->confirmGoodsIssue($doc, 1);
        } finally {
            // no mutation
            $level = InventoryLevel::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', 1)->where('sku_id', 7)->where('warehouse_id', 2)->first();
            $this->assertSame(3, (int) $level->on_hand);
            $this->assertSame('draft', $doc->fresh()->status);
        }
    }

    public function test_confirm_twice_is_rejected(): void
    {
        $this->seedLevel(10);
        $doc = $this->draft(2);
        app(WarehouseDocumentService::class)->confirmGoodsIssue($doc, 1);

        $this->expectException(\RuntimeException::class);
        app(WarehouseDocumentService::class)->confirmGoodsIssue($doc->fresh(), 1);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ConfirmGoodsIssueTest`
Expected: FAIL — method/event missing.

- [ ] **Step 3: Create the event**

Create `app/app/Modules/Inventory/Events/GoodsIssueConfirmed.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Inventory\Events;

use CMBcoreSeller\Modules\Inventory\Models\GoodsIssue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired sau khi WarehouseDocumentService::confirmGoodsIssue đã áp tồn (on_hand -= qty).
 * Các module khác (vd Accounting) có thể listen để hạch toán xuất kho.
 */
class GoodsIssueConfirmed
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly GoodsIssue $issue) {}
}
```

- [ ] **Step 4: Add the confirm method**

In `WarehouseDocumentService.php`, add imports (with the other model/event imports at the top):

```php
use CMBcoreSeller\Modules\Inventory\Events\GoodsIssueConfirmed;
use CMBcoreSeller\Modules\Inventory\Models\GoodsIssue;
```

Add the method after `confirmGoodsReceipt` (ends line 58):

```php
    public function confirmGoodsIssue(GoodsIssue $doc, int $userId): GoodsIssue
    {
        $this->assertDraft($doc->status);
        $tenantId = (int) $doc->tenant_id;
        $whId = (int) $doc->warehouse_id;
        $items = $doc->items;
        if ($items->isEmpty()) {
            throw new RuntimeException('Phiếu chưa có dòng hàng nào.');
        }

        // Chặn âm tồn: tổng qty cần xuất mỗi SKU không được vượt on_hand hiện tại của kho.
        $required = [];
        foreach ($items as $it) {
            $required[(int) $it->sku_id] = ($required[(int) $it->sku_id] ?? 0) + max(0, (int) $it->qty);
        }
        foreach ($required as $skuId => $need) {
            $have = $this->ledger->onHand($tenantId, (int) $skuId, $whId);
            if ($need > $have) {
                throw new RuntimeException("Không đủ tồn để xuất SKU #{$skuId} (cần {$need}, còn {$have}).");
            }
        }

        DB::transaction(function () use ($doc, $items, $tenantId, $whId, $userId) {
            foreach ($items as $it) {
                if ((int) $it->qty <= 0) {
                    continue;
                }
                $this->ledger->issue($tenantId, (int) $it->sku_id, $whId, (int) $it->qty, 'Xuất kho '.$doc->code, 'goods_issue', (int) $doc->getKey(), $userId);
            }
            $doc->forceFill(['status' => GoodsIssue::STATUS_CONFIRMED, 'confirmed_at' => now(), 'confirmed_by' => $userId])->save();
        });
        GoodsIssueConfirmed::dispatch($doc->refresh()->load('items'));

        return $doc;
    }
```

Also extend the `cancel()` signature union to accept `GoodsIssue` — change `public function cancel(GoodsReceipt|StockTransfer|Stocktake $doc): void` to `public function cancel(GoodsReceipt|StockTransfer|Stocktake|GoodsIssue $doc): void` (needed for Task 4's cancel path).

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=ConfirmGoodsIssueTest`
Expected: PASS.

- [ ] **Step 6: Commit**

Message: `feat(inventory): confirmGoodsIssue + event + chặn âm tồn khi xuất`
`git add` the event, service, test.

---

## Task 4: Controller + route + cross-tenant SKU hardening

**Files:**
- Modify: `app/app/Modules/Inventory/Http/Controllers/WarehouseDocumentController.php`
- Modify: `app/routes/api.php:237` (add `goods-issues` to `whereIn`)
- Test: `app/tests/Feature/Inventory/GoodsIssueApiTest.php` (create)

**Interfaces:**
- Consumes: everything from Tasks 1-3.
- Produces: `POST/GET /api/v1/warehouse-docs/goods-issues[...]` fully working (create draft, confirm, cancel, show, list). Store-time validation returns 422 naming any `sku_id`/`warehouse_id` not owned by the token's tenant (applies to ALL types).

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/Inventory/GoodsIssueApiTest.php`. Reuse the auth/tenant/warehouse/sku setup from the existing warehouse-doc test (grep `app/tests/Feature/Inventory` for the test hitting `warehouse-docs/goods-receipts` — copy how it creates a tenant user with `inventory.adjust`, a Warehouse, a Sku, and seeds on_hand; and how it authenticates with `X-Tenant-Id`). Skeleton:

```php
<?php

namespace Tests\Feature\Inventory;

use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoodsIssueApiTest extends TestCase
{
    use RefreshDatabase;

    // helper: [$user, $tenant, $warehouseId, $skuId] with on_hand seeded — mirror the existing goods-receipts API test setup.

    public function test_create_and_confirm_goods_issue_decreases_stock(): void
    {
        [$user, $tenant, $whId, $skuId] = $this->seedInventory(onHand: 10); // reuse/copy existing helper

        $create = $this->actingAs($user)->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->postJson('/api/v1/warehouse-docs/goods-issues', [
                'warehouse_id' => $whId, 'reason' => 'Hàng hỏng',
                'items' => [['sku_id' => $skuId, 'qty' => 4]],
            ])->assertCreated();
        $id = $create->json('data.id');
        $this->assertStringStartsWith('PXK-', $create->json('data.code'));

        $this->actingAs($user)->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->postJson("/api/v1/warehouse-docs/goods-issues/{$id}/confirm")->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $level = InventoryLevel::withoutGlobalScope(TenantScope::class)
            ->where('sku_id', $skuId)->where('warehouse_id', $whId)->first();
        $this->assertSame(6, (int) $level->on_hand);
    }

    public function test_confirm_blocks_negative_stock_with_422(): void
    {
        [$user, $tenant, $whId, $skuId] = $this->seedInventory(onHand: 3);
        $id = $this->actingAs($user)->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->postJson('/api/v1/warehouse-docs/goods-issues', ['warehouse_id' => $whId, 'items' => [['sku_id' => $skuId, 'qty' => 5]]])
            ->json('data.id');

        $this->actingAs($user)->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->postJson("/api/v1/warehouse-docs/goods-issues/{$id}/confirm")
            ->assertStatus(422);

        $level = InventoryLevel::withoutGlobalScope(TenantScope::class)->where('sku_id', $skuId)->first();
        $this->assertSame(3, (int) $level->on_hand);
    }

    public function test_cannot_use_another_tenants_sku(): void
    {
        [$userA, $tenantA, $whA, $skuA] = $this->seedInventory(onHand: 10);
        [$userB, $tenantB, $whB, $skuB] = $this->seedInventory(onHand: 10); // second tenant

        // tenant A submits tenant B's sku_id
        $this->actingAs($userA)->withHeader('X-Tenant-Id', (string) $tenantA->id)
            ->postJson('/api/v1/warehouse-docs/goods-issues', ['warehouse_id' => $whA, 'items' => [['sku_id' => $skuB, 'qty' => 1]]])
            ->assertStatus(422);

        // no stock changed for tenant B
        $level = InventoryLevel::withoutGlobalScope(TenantScope::class)->where('sku_id', $skuB)->first();
        $this->assertSame(10, (int) $level->on_hand);
    }
}
```

Replace `seedInventory(...)` with the real helper pattern from the existing goods-receipts API test (tenant + user with role granting `inventory.adjust`, a Warehouse, a Sku, an InventoryLevel). If the existing test uses a trait/helper, reuse it.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=GoodsIssueApiTest`
Expected: FAIL — `goods-issues` type unknown (404) / route not whitelisted.

- [ ] **Step 3: Register the route type**

In `app/routes/api.php` line 237, add `'goods-issues'` to the `whereIn`:

```php
            Route::prefix('warehouse-docs/{type}')->whereIn('type', ['goods-receipts', 'goods-issues', 'stock-transfers', 'stocktakes'])->group(function () {
```

- [ ] **Step 4: Register goods-issues in the controller**

In `WarehouseDocumentController.php`:

Add imports (with the other model imports):
```php
use CMBcoreSeller\Modules\Inventory\Models\GoodsIssue;
use CMBcoreSeller\Modules\Inventory\Models\GoodsIssueItem;
```

Extend `PERM` (line 32) and `PREFIX` (line 34):
```php
    private const PERM = ['goods-receipts' => 'inventory.adjust', 'goods-issues' => 'inventory.adjust', 'stock-transfers' => 'inventory.transfer', 'stocktakes' => 'inventory.stocktake'];

    private const PREFIX = ['goods-receipts' => 'PNK', 'goods-issues' => 'PXK', 'stock-transfers' => 'PCK', 'stocktakes' => 'PKK'];
```

Extend the return-type docblock + `query()` (line 44-51) and `find()` (line 53-60) match arms to include goods-issues (add a `'goods-issues' => GoodsIssue::query()` arm to each; update the union return type hints to add `GoodsIssue`):
```php
    /** @return Builder<GoodsReceipt>|Builder<GoodsIssue>|Builder<StockTransfer>|Builder<Stocktake> */
    private function query(string $type): Builder
    {
        return match ($type) {
            'goods-receipts' => GoodsReceipt::query(),
            'goods-issues' => GoodsIssue::query(),
            'stock-transfers' => StockTransfer::query(),
            default => Stocktake::query(),
        };
    }

    private function find(string $type, int $id): GoodsReceipt|GoodsIssue|StockTransfer|Stocktake
    {
        return match ($type) {
            'goods-receipts' => GoodsReceipt::query()->findOrFail($id),
            'goods-issues' => GoodsIssue::query()->findOrFail($id),
            'stock-transfers' => StockTransfer::query()->findOrFail($id),
            default => Stocktake::query()->findOrFail($id),
        };
    }
```
(Also widen the `present()` param type and the `confirm()`/`cancel()` `$doc` types to include `GoodsIssue` wherever a union `GoodsReceipt|StockTransfer|Stocktake` appears.)

In `store()`, add a validation branch. The existing structure is `if ($type === 'stock-transfers') {...} else { $rules['warehouse_id']=...; if ($type==='goods-receipts'){...} else { /*stocktakes*/ } }`. Change the inner else-if chain to also handle goods-issues:
```php
            $rules['warehouse_id'] = ['required', 'integer'];
            if ($type === 'goods-receipts') {
                $rules['supplier'] = ['sometimes', 'nullable', 'string', 'max:191'];
                $rules['items.*.qty'] = ['required', 'integer', 'min:1'];
                $rules['items.*.unit_cost'] = ['sometimes', 'integer', 'min:0'];
            } elseif ($type === 'goods-issues') {
                $rules['reason'] = ['sometimes', 'nullable', 'string', 'max:255'];
                $rules['items.*.qty'] = ['required', 'integer', 'min:1'];
            } else { // stocktakes
                $rules['items.*.counted_qty'] = ['required', 'integer', 'min:0'];
            }
```

Add the create branch inside the `DB::transaction` (after the goods-receipts branch, before stock-transfers or anywhere among them):
```php
            if ($type === 'goods-issues') {
                $doc = GoodsIssue::query()->create(['tenant_id' => $tenantId, 'code' => $code, 'status' => 'draft', 'note' => $data['note'] ?? null,
                    'warehouse_id' => (int) $data['warehouse_id'], 'reason' => $data['reason'] ?? null, 'created_by' => $userId]);
                foreach ($data['items'] as $i) {
                    GoodsIssueItem::query()->create(['tenant_id' => $tenantId, 'goods_issue_id' => $doc->getKey(), 'sku_id' => (int) $i['sku_id'], 'qty' => (int) $i['qty']]);
                }

                return $doc;
            }
```

In `confirm()` (line 166-170), add the goods-issue arm:
```php
            $doc = match (true) {
                $doc instanceof GoodsReceipt => $service->confirmGoodsReceipt($doc, $userId),
                $doc instanceof GoodsIssue => $service->confirmGoodsIssue($doc, $userId),
                $doc instanceof StockTransfer => $service->confirmTransfer($doc, $userId),
                default => $service->confirmStocktake($doc, $userId),
            };
```

In `present()` (line 194-232), add a GoodsIssue branch (mirror the GoodsReceipt block) — for the header:
```php
        } elseif ($doc instanceof GoodsIssue) {
            $base['type'] = 'goods-issues';
            $base += ['warehouse_id' => $doc->warehouse_id, 'reason' => $doc->reason];
        } elseif ($doc instanceof GoodsReceipt) {
```
and for the items block, add a `GoodsIssueItem` arm:
```php
                if ($it instanceof GoodsReceiptItem) {
                    $row += ['qty' => (int) $it->qty, 'unit_cost' => (int) $it->unit_cost];
                } elseif ($it instanceof GoodsIssueItem) {
                    $row += ['qty' => (int) $it->qty];
                } elseif ($it instanceof StockTransferItem) {
```

- [ ] **Step 5: Harden cross-tenant SKU/warehouse rejection (applies to all types)**

In `store()`, replace the warehouse check (lines 118-121) and SKU check (lines 122-125) with explicit id-naming versions:

```php
        $whIds = array_values(array_unique(array_map('intval', array_filter([$data['warehouse_id'] ?? null, $data['from_warehouse_id'] ?? null, $data['to_warehouse_id'] ?? null]))));
        if ($whIds !== []) {
            $ownedWh = Warehouse::query()->whereIn('id', $whIds)->pluck('id')->map(fn ($v) => (int) $v)->all();
            $foreignWh = array_values(array_diff($whIds, $ownedWh));
            if ($foreignWh !== []) {
                throw ValidationException::withMessages(['warehouse_id' => 'Kho không thuộc gian hàng: '.implode(', ', $foreignWh)]);
            }
        }
        $skuIds = array_values(array_unique(array_map(fn ($i) => (int) $i['sku_id'], $data['items'])));
        $ownedSku = Sku::query()->whereIn('id', $skuIds)->whereNull('deleted_at')->pluck('id')->map(fn ($v) => (int) $v)->all();
        $foreignSku = array_values(array_diff($skuIds, $ownedSku));
        if ($foreignSku !== []) {
            throw ValidationException::withMessages(['items' => 'SKU không thuộc gian hàng: '.implode(', ', $foreignSku)]);
        }
```

(`Sku::query()`/`Warehouse::query()` are tenant-scoped, so any id belonging to another tenant lands in `$foreign*` → 422 naming it. This is the cross-tenant isolation guarantee, now explicit and testable.)

- [ ] **Step 6: Run test to verify it passes**

Run: `cd app; php artisan test --filter=GoodsIssueApiTest; php artisan test --filter=WarehouseDocument; vendor/bin/phpstan analyse`
Expected: PASS (existing warehouse-doc tests still pass; phpstan no new errors on touched files — verify the widened union types).

- [ ] **Step 7: Commit**

Message: `feat(inventory): API phiếu xuất kho goods-issues + chặn dùng SKU khác gian hàng (422 nêu id)`
`git add` the controller, routes, test.

---

## Task 5: Web UI tab (phiếu xuất kho)

**Files:**
- Modify: `app/resources/js/lib/inventory.tsx` (extend the warehouse-doc type union + hooks/types for `goods-issues`)
- Modify: `app/resources/js/components/WarehouseDocsTab.tsx` (add the phiếu-xuất tab/form)
- Test: none (no JS runner) — gate on `npm run typecheck` + `npm run build`.

**Interfaces:**
- Consumes: `POST/GET /api/v1/warehouse-docs/goods-issues` (Task 4).

- [ ] **Step 1: Extend the data layer**

READ `app/resources/js/lib/inventory.tsx` first. Find the warehouse-doc type (a union/string type that currently includes `'goods-receipts' | 'stock-transfers' | 'stocktakes'`) and the hooks (list/create/confirm/cancel keyed by type). Add `'goods-issues'` to the type union, and ensure the create payload type allows `{ warehouse_id, reason?, note?, items: {sku_id, qty}[] }` for goods-issues (mirror the goods-receipts shape minus `unit_cost`, plus `reason`). If hooks are generic over `type`, no new hook is needed — only the type union + payload variant.

- [ ] **Step 2: Add the tab/form**

READ `app/resources/js/components/WarehouseDocsTab.tsx`. It renders a tab per doc type with a create form + list. Add a "Phiếu xuất kho" tab mirroring the goods-receipts tab: pick warehouse, optional reason (`Input`) + note, add line items (SKU picker + qty), save draft, then confirm/cancel actions. Reuse the existing SKU picker, warehouse select, item-line editor, and status tags. Labels Vietnamese; icons from `@ant-design/icons` (no emoji). Reuse the existing table columns/action toolbar. For goods-issues, show `reason` where goods-receipts shows `supplier`, and DON'T show `unit_cost`/`total_cost`.

- [ ] **Step 3: Typecheck + build**

Run: `cd app; npm run typecheck; npm run build`
Expected: PASS. Also `npm run lint` clean on changed files.

- [ ] **Step 4: Commit**

Message: `feat(inventory-ui): tab phiếu xuất kho trên màn tồn kho`
`git add` the 2 files.

---

## Task 6: Docs — repo endpoints + public API docs page

**Files:**
- Modify: `docs/05-api/endpoints.md`
- Modify: `app/resources/js/pages/public/ApiDocsPage.tsx`

**Interfaces:** documentation only.

- [ ] **Step 1: Repo endpoint catalog**

In `docs/05-api/endpoints.md`, in the warehouse-docs section (near the existing goods-receipts rows), add `goods-issues`: `POST /api/v1/warehouse-docs/goods-issues` (create draft), `POST .../{id}/confirm` (giảm tồn, movement `goods_issue`, chặn âm tồn → 422), `POST .../{id}/cancel`. Note the cross-tenant SKU rejection: submitting a `sku_id`/`warehouse_id` not in the caller's tenant → 422 naming the id.

- [ ] **Step 2: Public in-app API docs page**

READ `app/resources/js/pages/public/ApiDocsPage.tsx`. In section 6 ("Sản phẩm & tồn kho"), add two `<Endpoint>` blocks (mirror the existing `<Endpoint method path desc request response />` usage), documenting phiếu xuất kho for third-party integrators:

```tsx
                        <Endpoint
                            method="POST"
                            path="/warehouse-docs/goods-issues"
                            desc="Tạo phiếu xuất kho (nháp) — giảm tồn theo sku_id. Xác nhận ở bước confirm."
                            request={`curl -X POST -H "Authorization: Bearer <API_KEY>" \\
  -H "Content-Type: application/json" \\
  -d '{
    "warehouse_id": 1,
    "reason": "Hàng hỏng",
    "items": [ { "sku_id": 1, "qty": 5 } ]
  }' \\
  "${BASE}/warehouse-docs/goods-issues"`}
                            response={`{
  "data": {
    "id": 321,
    "code": "PXK-260705-AB12C",
    "type": "goods-issues",
    "status": "draft",
    "warehouse_id": 1,
    "reason": "Hàng hỏng",
    "items": [ { "sku_id": 1, "qty": 5 } ]
  }
}`}
                        />
                        <Endpoint
                            method="POST"
                            path="/warehouse-docs/goods-issues/{id}/confirm"
                            desc="Xác nhận phiếu xuất → trừ tồn (on_hand). Xuất quá tồn sẽ trả 422 (chặn âm tồn); SKU không thuộc gian hàng cũng bị từ chối 422."
                            request={`curl -X POST -H "Authorization: Bearer <API_KEY>" \\
  "${BASE}/warehouse-docs/goods-issues/321/confirm"`}
                            response={`{ "data": { "id": 321, "status": "confirmed" } }`}
                        />
```

(Optionally also add a `goods-receipts` create endpoint block in the same style so nhập kho is documented too — mirror the above with `qty`+`unit_cost` and path `/warehouse-docs/goods-receipts`.)

- [ ] **Step 3: Typecheck + build (page compiles)**

Run: `cd app; npm run typecheck; npm run build`
Expected: PASS.

- [ ] **Step 4: Commit**

Message: `docs: phiếu xuất kho ở endpoints.md + trang tài liệu API public`
`git add docs app/resources/js/pages/public/ApiDocsPage.tsx`

---

## Post-implementation (manual — not code steps)

- **Prod migrate** (`RUN_MIGRATIONS=false`): run `php artisan migrate` after deploy → creates `goods_issues` + `goods_issue_items`.
- **Verify end-to-end** (verify skill / real app): create a phiếu xuất kho via API key (curl) → confirm → on_hand decreases + a `goods_issue` movement appears; confirm over-issue → 422; a key for tenant A cannot reference tenant B's sku_id (422); the web tab creates/confirms a phiếu xuất.
- No backfill; no new permission to seed.
