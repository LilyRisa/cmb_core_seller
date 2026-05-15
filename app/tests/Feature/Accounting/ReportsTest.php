<?php

namespace Tests\Feature\Accounting;

use CMBcoreSeller\Modules\Accounting\Models\FiscalPeriod;
use CMBcoreSeller\Modules\Accounting\Services\AccountingSetupService;
use CMBcoreSeller\Modules\Accounting\Services\Reports\BalanceSheetService;
use CMBcoreSeller\Modules\Accounting\Services\Reports\ProfitLossService;
use CMBcoreSeller\Modules\Accounting\Services\Reports\TrialBalanceService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Phase 7.5 — Báo cáo TC + Export MISA. */
class ReportsTest extends TestCase
{
    use AccountingTestHelpers, RefreshDatabase;

    private FiscalPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAccountingTenant();
        app(AccountingSetupService::class)->run((int) $this->tenant->getKey(), 2026);
        $this->period = FiscalPeriod::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())->where('code', '2026-05')->firstOrFail();

        // Tạo 3 bút toán tay để có data: bán hàng + chi phí + nhập kho.
        $this->postManualEntry('2026-05-10', [
            ['account_code' => '131', 'dr_amount' => 1_100_000],
            ['account_code' => '5111', 'cr_amount' => 1_000_000],
            ['account_code' => '33311', 'cr_amount' => 100_000],
        ]);
        $this->postManualEntry('2026-05-12', [
            ['account_code' => '632', 'dr_amount' => 600_000],
            ['account_code' => '1561', 'cr_amount' => 600_000],
        ]);
        $this->postManualEntry('2026-05-15', [
            ['account_code' => '6422', 'dr_amount' => 200_000],
            ['account_code' => '1111', 'cr_amount' => 200_000],
        ]);
    }

    private function postManualEntry(string $date, array $lines): void
    {
        $this->actingAs($this->accountant)->withHeaders($this->h() + ['Idempotency-Key' => 'rpt-'.uniqid()])
            ->postJson('/api/v1/accounting/journals', [
                'posted_at' => $date,
                'lines' => $lines,
            ])->assertCreated();
    }

    public function test_trial_balance_is_balanced(): void
    {
        $tb = app(TrialBalanceService::class);
        $rows = $tb->generate((int) $this->tenant->getKey(), $this->period);
        $totals = $tb->totals($rows);
        $this->assertSame($totals['total_debit'], $totals['total_credit']);
        // Tổng = 1.1M + 600k + 200k = 1.9M (cả 2 phía).
        $this->assertSame(1_900_000, $totals['total_debit']);

        $rev = collect($rows)->firstWhere('account_code', '5111');
        $this->assertSame(1_000_000, $rev['credit']);
    }

    public function test_profit_loss_computes_net_income(): void
    {
        $pnl = app(ProfitLossService::class);
        $r = $pnl->generate((int) $this->tenant->getKey(), $this->period);
        $this->assertSame(1_000_000, $r['revenue']);
        $this->assertSame(600_000, $r['cogs']);
        $this->assertSame(400_000, $r['gross_profit']);
        $this->assertSame(200_000, $r['opex']);
        $this->assertSame(200_000, $r['net_income']); // 400k - 200k
    }

    public function test_balance_sheet_is_balanced(): void
    {
        $bs = app(BalanceSheetService::class);
        $r = $bs->generate((int) $this->tenant->getKey(), $this->period);
        // 131 = 1.1M; 1111 = -200k (chi); 1561 = -600k (xuất);
        // 33311 = 100k (liab); 5111-632-6422 ⇒ retained_earnings_net = 200k.
        // Assets = 1.1M - 200k - 600k = 300k. Liab + Equity = 100k + 200k = 300k. Balanced.
        $this->assertSame(300_000, $r['assets']);
        $this->assertSame(100_000, $r['liabilities']);
        $this->assertSame(200_000, $r['equity']);
        $this->assertTrue($r['balanced']);
    }

    public function test_trial_balance_api(): void
    {
        $r = $this->actingAs($this->accountant)->withHeaders($this->h())
            ->getJson('/api/v1/accounting/reports/trial-balance?period=2026-05')
            ->assertOk();
        $this->assertSame(1_900_000, $r->json('meta.total_debit'));
        $this->assertTrue($r->json('meta.balanced'));
    }

    public function test_misa_export_returns_zip(): void
    {
        // Phase 7.5 advanced ⇒ cần plan Business.
        $this->activatePlan(\CMBcoreSeller\Modules\Billing\Models\Plan::CODE_BUSINESS);
        $r = $this->actingAs($this->owner)->withHeaders($this->h())
            ->get('/api/v1/accounting/reports/export-misa?period=2026-05');
        $r->assertOk();
        $this->assertStringContainsString('application/zip', $r->headers->get('Content-Type'));
    }
}
