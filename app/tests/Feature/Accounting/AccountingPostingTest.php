<?php

namespace Tests\Feature\Accounting;

use CMBcoreSeller\Modules\Accounting\Models\JournalEntry;
use CMBcoreSeller\Modules\Accounting\Models\JournalLine;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;
use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * Phase 7.1 — SPEC 0019: 3 listener nền tự ghi sổ kép từ event Inventory.
 *
 *  - GoodsReceiptConfirmed → Dr 156 / Cr 331
 *  - StockTransferConfirmed → Dr 156(to) / Cr 156(from), cùng TK nhưng khác `dim_warehouse_id`.
 *  - StocktakeConfirmed → diff>0: Dr 156/Cr 711; diff<0: Dr 811/Cr 156.
 *
 * QUEUE_CONNECTION=sync trong phpunit.xml ⇒ listener (ShouldQueue) chạy đồng bộ trong test.
 */
class AccountingPostingTest extends TestCase
{
    use AccountingTestHelpers, RefreshDatabase;

    private Sku $sku;

    private Warehouse $wh1;

    private Warehouse $wh2;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([PushStockForSku::class]); // chặn job push tồn lên sàn — không liên quan test
        $this->setUpAccountingTenant();
        // Onboard Accounting cho tenant.
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/accounting/setup', ['year' => 2026])->assertOk();
        // Tạo dữ liệu cơ bản: 2 kho + 1 SKU + tồn ban đầu 10@40k tại kho 1.
        $this->wh1 = Warehouse::defaultFor((int) $this->tenant->getKey());
        $this->wh2 = Warehouse::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'name' => 'Kho 2', 'code' => 'WH2',
        ]);
        $this->sku = Sku::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'sku_code' => 'A-1', 'name' => 'Áo', 'cost_price' => 40000,
        ]);
        app(InventoryLedgerService::class)->receipt((int) $this->tenant->getKey(), (int) $this->sku->getKey(), (int) $this->wh1->getKey(), 10);
        InventoryLevel::withoutGlobalScope(TenantScope::class)
            ->where('warehouse_id', $this->wh1->getKey())->where('sku_id', $this->sku->getKey())
            ->update(['cost_price' => 40000]);
    }

    /** Helper lấy entry kế toán theo source. */
    private function entryFor(string $sourceType, int $sourceId): ?JournalEntry
    {
        return JournalEntry::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->with('lines')->first();
    }

    public function test_confirm_goods_receipt_posts_dr156_cr331(): void
    {
        // Tạo phiếu nhập 30 cái @ 50k qua API.
        $grId = (int) $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/warehouse-docs/goods-receipts', [
                'warehouse_id' => $this->wh1->getKey(),
                'items' => [['sku_id' => $this->sku->getKey(), 'qty' => 30, 'unit_cost' => 50000]],
            ])->assertCreated()->json('data.id');
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/warehouse-docs/goods-receipts/{$grId}/confirm")->assertOk();

        $entry = $this->entryFor('goods_receipt', $grId);
        $this->assertNotNull($entry, 'Listener phải tạo bút toán.');
        $this->assertSame(30 * 50000, (int) $entry->total_debit);
        $this->assertSame(30 * 50000, (int) $entry->total_credit);

        $debit = $entry->lines->firstWhere('account_code', '1561');
        $credit = $entry->lines->firstWhere('account_code', '331');
        $this->assertNotNull($debit, 'Phải có Dr 1561 (HTK lá).');
        $this->assertNotNull($credit, 'Phải có Cr 331 (Phải trả NCC).');
        $this->assertSame(30 * 50000, (int) $debit->dr_amount);
        $this->assertSame(30 * 50000, (int) $credit->cr_amount);
        $this->assertSame((int) $this->wh1->getKey(), (int) $debit->dim_warehouse_id);
        $this->assertSame((int) $this->sku->getKey(), (int) $debit->dim_sku_id);
    }

    public function test_confirm_goods_receipt_is_idempotent(): void
    {
        $grId = (int) $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/warehouse-docs/goods-receipts', [
                'warehouse_id' => $this->wh1->getKey(),
                'items' => [['sku_id' => $this->sku->getKey(), 'qty' => 5, 'unit_cost' => 10000]],
            ])->assertCreated()->json('data.id');
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/warehouse-docs/goods-receipts/{$grId}/confirm")->assertOk();

        // Listener chạy 2 lần tay (replay) phải = no-op vì idempotency_key unique.
        $listener = app(\CMBcoreSeller\Modules\Accounting\Listeners\PostOnGoodsReceiptConfirmed::class);
        $event = new \CMBcoreSeller\Modules\Inventory\Events\GoodsReceiptConfirmed(
            \CMBcoreSeller\Modules\Inventory\Models\GoodsReceipt::withoutGlobalScope(TenantScope::class)->find($grId)
        );
        $listener->handle($event);
        $listener->handle($event);

        $this->assertSame(1, JournalEntry::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())
            ->where('source_type', 'goods_receipt')
            ->where('source_id', $grId)->count());
    }

    public function test_confirm_stock_transfer_posts_dr_cr_156_with_warehouse_dim(): void
    {
        $id = (int) $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/warehouse-docs/stock-transfers', [
                'from_warehouse_id' => $this->wh1->getKey(),
                'to_warehouse_id' => $this->wh2->getKey(),
                'items' => [['sku_id' => $this->sku->getKey(), 'qty' => 4]],
            ])->assertCreated()->json('data.id');
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/warehouse-docs/stock-transfers/{$id}/confirm")->assertOk();

        $entry = $this->entryFor('stock_transfer', $id);
        $this->assertNotNull($entry);
        // 4 × 40000 (giá vốn kho 1) = 160000.
        $this->assertSame(160000, (int) $entry->total_debit);
        $this->assertSame(160000, (int) $entry->total_credit);

        // 2 dòng đều TK 1561, phân biệt qua warehouse_id.
        $debit = $entry->lines->firstWhere('dr_amount', 160000);
        $credit = $entry->lines->firstWhere('cr_amount', 160000);
        $this->assertSame('1561', $debit->account_code);
        $this->assertSame('1561', $credit->account_code);
        $this->assertSame((int) $this->wh2->getKey(), (int) $debit->dim_warehouse_id, 'Dr 156 cho kho đến.');
        $this->assertSame((int) $this->wh1->getKey(), (int) $credit->dim_warehouse_id, 'Cr 156 cho kho đi.');
    }

    public function test_confirm_stocktake_surplus_posts_dr156_cr711(): void
    {
        // Hiện có 10, kiểm thấy 13 → diff = +3.
        $id = (int) $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/warehouse-docs/stocktakes', [
                'warehouse_id' => $this->wh1->getKey(),
                'items' => [['sku_id' => $this->sku->getKey(), 'counted_qty' => 13]],
            ])->assertCreated()->json('data.id');
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/warehouse-docs/stocktakes/{$id}/confirm")->assertOk();

        $entries = JournalEntry::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())
            ->where('source_type', 'stocktake')
            ->where('source_id', $id)->with('lines')->get();
        $this->assertCount(1, $entries, 'Thừa kiểm kê ⇒ 1 entry phía in.');
        $entry = $entries->first();
        $this->assertSame(3 * 40000, (int) $entry->total_debit);
        $this->assertSame('1561', $entry->lines->firstWhere('dr_amount', '>', 0)->account_code);
        $this->assertSame('711', $entry->lines->firstWhere('cr_amount', '>', 0)->account_code);
    }

    public function test_confirm_stocktake_shortage_posts_dr811_cr156(): void
    {
        // 10 → kiểm 7 ⇒ diff = -3 (thiếu).
        $id = (int) $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/warehouse-docs/stocktakes', [
                'warehouse_id' => $this->wh1->getKey(),
                'items' => [['sku_id' => $this->sku->getKey(), 'counted_qty' => 7]],
            ])->assertCreated()->json('data.id');
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/warehouse-docs/stocktakes/{$id}/confirm")->assertOk();

        $entry = JournalEntry::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())
            ->where('source_type', 'stocktake')
            ->where('source_id', $id)->with('lines')->first();
        $this->assertNotNull($entry);
        $this->assertSame(3 * 40000, (int) $entry->total_debit);
        $this->assertSame('811', $entry->lines->firstWhere('dr_amount', '>', 0)->account_code);
        $this->assertSame('1561', $entry->lines->firstWhere('cr_amount', '>', 0)->account_code);
    }

    public function test_tenant_isolation_for_posted_entries(): void
    {
        // Confirm 1 GR tenant A.
        $grId = (int) $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/warehouse-docs/goods-receipts', [
                'warehouse_id' => $this->wh1->getKey(),
                'items' => [['sku_id' => $this->sku->getKey(), 'qty' => 1, 'unit_cost' => 1000]],
            ])->assertCreated()->json('data.id');
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/warehouse-docs/goods-receipts/{$grId}/confirm")->assertOk();

        // Tenant B (chưa nâng gói) ⇒ vẫn vào được listener flow nhưng entry không tạo (skipped).
        // Tổng entries của tenant A = 1.
        $countA = JournalEntry::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())->count();
        $this->assertSame(1, $countA);

        // Bất kỳ JournalLine của tenant khác = 0.
        $this->assertSame(0, JournalLine::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', '!=', $this->tenant->getKey())->count());
    }
}
