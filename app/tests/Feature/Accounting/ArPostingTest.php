<?php

namespace Tests\Feature\Accounting;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Accounting\Models\CustomerReceipt;
use CMBcoreSeller\Modules\Accounting\Models\JournalEntry;
use CMBcoreSeller\Modules\Accounting\Models\JournalLine;
use CMBcoreSeller\Modules\Accounting\Services\AccountingSetupService;
use CMBcoreSeller\Modules\Accounting\Services\ArService;
use CMBcoreSeller\Modules\Accounting\Services\CustomerReceiptService;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Inventory\Models\OrderCost;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Orders\Events\OrderStatusChanged;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7.2 — SPEC 0019: AR listener + AR service + Customer Receipts.
 *
 * Test luồng đầu-cuối:
 *   1. Phát `OrderStatusChanged → shipped` ⇒ post revenue Dr 131/Cr 5111 (+ 33311 nếu có VAT) + COGS Dr 632/Cr 1561.
 *   2. AR balance per customer.
 *   3. Phiếu thu confirm ⇒ Dr 1111/Cr 131.
 *   4. Reverse khi cancelled.
 */
class ArPostingTest extends TestCase
{
    use AccountingTestHelpers, RefreshDatabase;

    private Order $order;

    private Customer $customer;

    private Sku $sku;

    private Warehouse $wh;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAccountingTenant();
        app(AccountingSetupService::class)->run((int) $this->tenant->getKey(), 2026);

        // Tạo dữ liệu cơ bản — customer, order, COGS data.
        $this->customer = Customer::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'phone_hash' => str_repeat('a', 64),
            'phone' => '0900000000',
            'name' => 'Nguyễn A',
            'lifetime_stats' => ['orders_total' => 0],
            'tags' => [],
            'addresses_meta' => [],
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);
        $this->wh = Warehouse::defaultFor((int) $this->tenant->getKey());
        $this->sku = Sku::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'sku_code' => 'A-1', 'name' => 'Áo', 'cost_price' => 40000,
        ]);
        app(InventoryLedgerService::class)->receipt((int) $this->tenant->getKey(), (int) $this->sku->getKey(), (int) $this->wh->getKey(), 10);
        InventoryLevel::withoutGlobalScope(TenantScope::class)
            ->where('warehouse_id', $this->wh->getKey())->where('sku_id', $this->sku->getKey())
            ->update(['cost_price' => 40000]);

        $this->order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'source' => 'manual',
            'external_order_id' => 'O-1',
            'order_number' => 'ORD-001',
            'status' => StandardOrderStatus::Processing->value,
            'customer_id' => $this->customer->getKey(),
            'grand_total' => 110000,
            'tax' => 10000,
            'currency' => 'VND',
            'placed_at' => now(),
        ]);
        // Tạo 1 OrderCost dummy (Phase 6.1 FIFO).
        OrderCost::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'order_id' => $this->order->id,
            'order_item_id' => 1,
            'sku_id' => $this->sku->id,
            'qty' => 1,
            'cogs_unit_avg' => 40000,
            'cogs_total' => 40000,
            'cost_method' => 'average',
            'layers_used' => [],
            'shipped_at' => now(),
            'created_at' => now(),
        ]);
    }

    private function fireShipped(): void
    {
        OrderStatusChanged::dispatch(
            $this->order,
            StandardOrderStatus::Processing,
            StandardOrderStatus::Shipped,
            'test'
        );
    }

    public function test_order_shipped_posts_revenue_and_cogs(): void
    {
        $this->fireShipped();

        $entries = JournalEntry::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())->get();
        $this->assertCount(2, $entries, 'Phải có 2 entry: revenue + cogs.');

        // Revenue entry: Dr 131 110k / Cr 5111 100k + Cr 33311 10k.
        $rev = $entries->firstWhere('source_type', 'order');
        $this->assertSame(110000, (int) $rev->total_debit);
        $lines = JournalLine::query()->withoutGlobalScope(TenantScope::class)->where('entry_id', $rev->id)->get();
        $this->assertSame(110000, (int) $lines->where('account_code', '131')->first()->dr_amount);
        $this->assertSame(100000, (int) $lines->where('account_code', '5111')->first()->cr_amount);
        $this->assertSame(10000, (int) $lines->where('account_code', '33311')->first()->cr_amount);
        $this->assertSame((int) $this->customer->id, (int) $lines->where('account_code', '131')->first()->party_id);

        // COGS entry: Dr 632 40k / Cr 1561 40k.
        $cogs = $entries->firstWhere('source_type', 'order_cogs');
        $this->assertSame(40000, (int) $cogs->total_debit);
    }

    public function test_order_shipped_is_idempotent(): void
    {
        $this->fireShipped();
        $this->fireShipped();

        $this->assertSame(2, JournalEntry::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())->count(), 'Replay không tạo entry mới.');
    }

    public function test_order_cancelled_reverses_revenue_and_cogs(): void
    {
        $this->fireShipped();

        OrderStatusChanged::dispatch(
            $this->order,
            StandardOrderStatus::Shipped,
            StandardOrderStatus::Cancelled,
            'test'
        );

        // 2 entries gốc + 2 entries đảo = 4.
        $entries = JournalEntry::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())->get();
        $this->assertCount(4, $entries);
        $reversals = $entries->whereNotNull('is_reversal_of_id');
        $this->assertCount(2, $reversals);
    }

    public function test_ar_aging_returns_correct_balance(): void
    {
        $this->fireShipped();

        $ar = app(ArService::class);
        $aging = $ar->agingByCustomer((int) $this->tenant->getKey());
        $this->assertCount(1, $aging);
        $row = $aging[0];
        $this->assertSame((int) $this->customer->id, $row['customer_id']);
        $this->assertSame(110000, $row['total']); // toàn bộ Dr 131 ở b0_30 (post hôm nay)
    }

    public function test_customer_receipt_confirm_clears_ar(): void
    {
        $this->fireShipped();
        $ar = app(ArService::class);
        $this->assertSame(110000, $ar->balancesByCustomer((int) $this->tenant->getKey(), (int) $this->customer->id)[$this->customer->id]['balance']);

        $service = app(CustomerReceiptService::class);
        $receipt = $service->create((int) $this->tenant->getKey(), [
            'customer_id' => (int) $this->customer->id,
            'received_at' => now()->toDateTimeString(),
            'amount' => 110000,
            'payment_method' => 'cash',
        ], 1);
        $service->confirm($receipt, 1);

        $balanceAfter = $ar->balancesByCustomer((int) $this->tenant->getKey(), (int) $this->customer->id);
        $this->assertSame(0, $balanceAfter[$this->customer->id]['balance']);
    }

    public function test_ar_aging_api(): void
    {
        $this->fireShipped();
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/accounting/ar/aging')
            ->assertOk()
            ->assertJsonPath('data.0.customer_id', (int) $this->customer->id)
            ->assertJsonPath('data.0.total', 110000)
            ->assertJsonPath('meta.total_balance', 110000);
    }

    public function test_receipt_create_and_confirm_api(): void
    {
        $this->fireShipped();
        $r = $this->actingAs($this->accountant)->withHeaders($this->h())
            ->postJson('/api/v1/accounting/customer-receipts', [
                'customer_id' => $this->customer->id,
                'received_at' => now()->toDateTimeString(),
                'amount' => 50000,
                'payment_method' => 'cash',
                'memo' => 'Thu một phần',
            ])->assertCreated();
        $id = $r->json('data.id');
        $this->assertSame('draft', $r->json('data.status'));

        $this->actingAs($this->accountant)->withHeaders($this->h())
            ->postJson("/api/v1/accounting/customer-receipts/{$id}/confirm")
            ->assertOk()->assertJsonPath('data.status', 'confirmed');

        $this->assertSame(60000, app(ArService::class)
            ->balancesByCustomer((int) $this->tenant->getKey(), (int) $this->customer->id)[$this->customer->id]['balance']);
    }
}
