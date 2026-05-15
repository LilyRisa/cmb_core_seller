<?php

namespace Tests\Feature\Accounting;

use CMBcoreSeller\Modules\Accounting\Models\JournalEntry;
use CMBcoreSeller\Modules\Accounting\Models\VendorBill;
use CMBcoreSeller\Modules\Accounting\Models\VendorPayment;
use CMBcoreSeller\Modules\Accounting\Services\AccountingSetupService;
use CMBcoreSeller\Modules\Accounting\Services\ApService;
use CMBcoreSeller\Modules\Procurement\Models\Supplier;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Phase 7.3 — AP: vendor bills + vendor payments. */
class ApPostingTest extends TestCase
{
    use AccountingTestHelpers, RefreshDatabase;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAccountingTenant();
        app(AccountingSetupService::class)->run((int) $this->tenant->getKey(), 2026);

        $this->supplier = Supplier::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'code' => 'NCC-001',
            'name' => 'NCC A',
            'is_active' => true,
        ]);
    }

    public function test_create_and_record_bill_posts_dr1561_drvat_cr331(): void
    {
        $ap = app(ApService::class);
        $bill = $ap->createBill((int) $this->tenant->getKey(), [
            'supplier_id' => (int) $this->supplier->id,
            'bill_date' => now()->toDateTimeString(),
            'subtotal' => 1_000_000,
            'tax' => 100_000,
            'bill_no' => 'BILL-001',
            'memo' => 'Hàng tháng 5',
        ], 1);
        $this->assertSame('draft', $bill->status);
        $this->assertSame(1_100_000, (int) $bill->total);

        $ap->recordBill($bill, 1);
        $bill->refresh();
        $this->assertSame('recorded', $bill->status);
        $entry = JournalEntry::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())
            ->where('source_type', 'vendor_bill')
            ->where('source_id', $bill->id)->first();
        $this->assertNotNull($entry);
        $this->assertSame(1_100_000, (int) $entry->total_debit);
        $lines = \CMBcoreSeller\Modules\Accounting\Models\JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('entry_id', $entry->id)->get();
        $this->assertSame(1_000_000, (int) $lines->firstWhere('account_code', '1561')->dr_amount);
        $this->assertSame(100_000, (int) $lines->firstWhere('account_code', '1331')->dr_amount);
        $this->assertSame(1_100_000, (int) $lines->firstWhere('account_code', '331')->cr_amount);
    }

    public function test_payment_confirm_clears_ap(): void
    {
        $ap = app(ApService::class);
        $bill = $ap->createBill((int) $this->tenant->getKey(), [
            'supplier_id' => (int) $this->supplier->id,
            'bill_date' => now()->toDateTimeString(),
            'subtotal' => 1_000_000,
            'tax' => 0,
            'bill_no' => 'BILL-002',
        ], 1);
        $ap->recordBill($bill, 1);
        $this->assertSame(1_000_000, $ap->balanceBySupplier((int) $this->tenant->getKey(), (int) $this->supplier->id));

        $payment = $ap->createPayment((int) $this->tenant->getKey(), [
            'supplier_id' => (int) $this->supplier->id,
            'paid_at' => now()->toDateTimeString(),
            'amount' => 1_000_000,
            'payment_method' => 'bank',
        ], 1);
        $ap->confirmPayment($payment, 1);
        $this->assertSame('confirmed', $payment->refresh()->status);
        $this->assertSame(0, $ap->balanceBySupplier((int) $this->tenant->getKey(), (int) $this->supplier->id));
    }

    public function test_ap_aging_api(): void
    {
        $ap = app(ApService::class);
        $bill = $ap->createBill((int) $this->tenant->getKey(), [
            'supplier_id' => (int) $this->supplier->id,
            'bill_date' => now()->toDateTimeString(),
            'subtotal' => 500_000, 'tax' => 0,
        ], 1);
        $ap->recordBill($bill, 1);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/accounting/ap/aging')
            ->assertOk()
            ->assertJsonPath('data.0.supplier_id', (int) $this->supplier->id)
            ->assertJsonPath('data.0.total', 500_000);
    }
}
