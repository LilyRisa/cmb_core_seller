<?php

namespace Tests\Feature\Accounting;

use CMBcoreSeller\Modules\Accounting\Models\BankStatement;
use CMBcoreSeller\Modules\Accounting\Models\BankStatementLine;
use CMBcoreSeller\Modules\Accounting\Models\CashAccount;
use CMBcoreSeller\Modules\Accounting\Services\AccountingSetupService;
use CMBcoreSeller\Modules\Accounting\Services\BankStatementService;
use CMBcoreSeller\Modules\Accounting\Services\CashService;
use CMBcoreSeller\Modules\Accounting\Services\CustomerReceiptService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Phase 7.4 — Cash & Bank reconcile. */
class CashTest extends TestCase
{
    use AccountingTestHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAccountingTenant();
        app(AccountingSetupService::class)->run((int) $this->tenant->getKey(), 2026);
    }

    public function test_create_cash_account_and_balance(): void
    {
        $cs = app(CashService::class);
        $cash = $cs->create((int) $this->tenant->getKey(), [
            'code' => 'BANK-VCB',
            'name' => 'Vietcombank — TK chính',
            'kind' => 'bank',
            'gl_account_code' => '1121',
            'bank_name' => 'Vietcombank',
            'account_no' => '0123456789',
        ]);
        $gl = \CMBcoreSeller\Modules\Accounting\Models\ChartAccount::query()
            ->withoutGlobalScope(TenantScope::class)
            ->whereKey($cash->gl_account_id)->first();
        $this->assertSame('1121', $gl->code);
        $this->assertSame(0, $cs->balance((int) $this->tenant->getKey(), $cash));
    }

    public function test_balance_reflects_customer_receipt(): void
    {
        $cs = app(CashService::class);
        $cash = $cs->create((int) $this->tenant->getKey(), [
            'code' => 'QUY', 'name' => 'Quỹ tiền mặt', 'kind' => 'cash', 'gl_account_code' => '1111',
        ]);
        // Tạo phiếu thu khách → confirm → Dr 1111 / Cr 131 (giả sử không có customer cụ thể).
        $r = app(CustomerReceiptService::class)->create((int) $this->tenant->getKey(), [
            'received_at' => now()->toDateTimeString(),
            'amount' => 500_000,
            'payment_method' => 'cash',
        ], 1);
        app(CustomerReceiptService::class)->confirm($r, 1);
        $this->assertSame(500_000, $cs->balance((int) $this->tenant->getKey(), $cash));
    }

    public function test_import_and_match_bank_statement(): void
    {
        $cs = app(CashService::class);
        $cash = $cs->create((int) $this->tenant->getKey(), [
            'code' => 'BANK', 'name' => 'Bank', 'kind' => 'bank', 'gl_account_code' => '1121',
        ]);
        $bs = app(BankStatementService::class);
        $stmt = $bs->import((int) $this->tenant->getKey(), (int) $cash->id, '2026-05-01', '2026-05-31', 'csv', [
            ['txn_date' => '2026-05-15 10:00:00', 'amount' => 1_000_000, 'counter_party' => 'Khách A', 'memo' => 'CK ORD-001'],
            ['txn_date' => '2026-05-16 11:00:00', 'amount' => -500_000, 'counter_party' => 'NCC A', 'memo' => 'Chi tiền hàng'],
        ], 1);
        $this->assertSame(2, $stmt->lines_count);
        $this->assertSame(1_000_000, $stmt->total_in);
        $this->assertSame(500_000, $stmt->total_out);

        $line1 = BankStatementLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('bank_statement_id', $stmt->id)
            ->orderBy('id')->first();
        $bs->matchLine($line1, 'customer_receipt', 999, null, 1);
        $this->assertSame('matched', $line1->refresh()->status);
        $this->assertSame('customer_receipt', $line1->matched_ref_type);
        $this->assertSame(999, (int) $line1->matched_ref_id);
    }

    public function test_cash_accounts_api(): void
    {
        $cs = app(CashService::class);
        $cs->create((int) $this->tenant->getKey(), [
            'code' => 'QUY', 'name' => 'Quỹ TM', 'kind' => 'cash', 'gl_account_code' => '1111',
        ]);
        $r = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/accounting/cash-accounts')->assertOk();
        $this->assertCount(1, $r->json('data'));
        $this->assertSame('QUY', $r->json('data.0.code'));
        $this->assertSame(0, $r->json('data.0.balance'));
    }
}
