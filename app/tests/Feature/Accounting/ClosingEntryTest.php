<?php

namespace Tests\Feature\Accounting;

use CMBcoreSeller\Modules\Accounting\Models\FiscalPeriod;
use CMBcoreSeller\Modules\Accounting\Models\JournalEntry;
use CMBcoreSeller\Modules\Accounting\Models\JournalLine;
use CMBcoreSeller\Modules\Accounting\Services\AccountingSetupService;
use CMBcoreSeller\Modules\Accounting\Services\ClosingEntryService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Phase 7.6 — Bút toán kết chuyển cuối kỳ (xác định KQKD). */
class ClosingEntryTest extends TestCase
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

        // Doanh thu 1.000.000, giá vốn 600.000, chi phí 200.000 ⇒ lãi 200.000.
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
        $this->actingAs($this->accountant)->withHeaders($this->h() + ['Idempotency-Key' => 'cf-'.uniqid()])
            ->postJson('/api/v1/accounting/journals', ['posted_at' => $date, 'lines' => $lines])
            ->assertCreated();
    }

    private function lineSum(string $code, string $col): int
    {
        return (int) JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())
            ->where('account_code', $code)
            ->sum($col);
    }

    public function test_carry_forward_creates_balanced_entries(): void
    {
        $result = app(ClosingEntryService::class)->carryForward((int) $this->tenant->getKey(), $this->period);

        $this->assertFalse($result['already']);
        $this->assertSame(200_000, $result['net_income']);
        $this->assertCount(2, $result['entries']);

        // Mỗi bút toán cân Nợ = Có.
        foreach ($result['entries'] as $e) {
            $this->assertSame((int) $e->total_debit, (int) $e->total_credit);
        }

        // TK kết quả (5111, 632, 6422) tất toán về 0 trong kỳ (PS Nợ = PS Có).
        $this->assertSame($this->lineSum('5111', 'dr_amount'), $this->lineSum('5111', 'cr_amount'));
        $this->assertSame($this->lineSum('632', 'dr_amount'), $this->lineSum('632', 'cr_amount'));
        $this->assertSame($this->lineSum('6422', 'dr_amount'), $this->lineSum('6422', 'cr_amount'));

        // TK 911 net = 0; lãi 200.000 chuyển sang 4211 (dư Có).
        $this->assertSame(0, $this->lineSum('911', 'cr_amount') - $this->lineSum('911', 'dr_amount'));
        $this->assertSame(200_000, $this->lineSum('4211', 'cr_amount') - $this->lineSum('4211', 'dr_amount'));
    }

    public function test_carry_forward_is_idempotent(): void
    {
        $svc = app(ClosingEntryService::class);
        $svc->carryForward((int) $this->tenant->getKey(), $this->period);
        $second = $svc->carryForward((int) $this->tenant->getKey(), $this->period);

        $this->assertTrue($second['already']);
        $carryCount = JournalEntry::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())
            ->where('source_type', 'period_carry')->count();
        $this->assertSame(2, $carryCount);
    }

    public function test_carry_forward_api_rbac(): void
    {
        $this->actingAs($this->staffOrder)->withHeaders($this->h())
            ->postJson('/api/v1/accounting/periods/2026-05/carry-forward')
            ->assertForbidden();

        $this->actingAs($this->accountant)->withHeaders($this->h())
            ->postJson('/api/v1/accounting/periods/2026-05/carry-forward')
            ->assertOk()
            ->assertJsonPath('data.net_income', 200_000);
    }
}
