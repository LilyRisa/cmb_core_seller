<?php

namespace Tests\Feature\Accounting;

use CMBcoreSeller\Modules\Accounting\Models\FiscalPeriod;
use CMBcoreSeller\Modules\Accounting\Services\BalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7.1 — SPEC 0019 §3.3 + §4.3.
 *
 * Đóng/mở/khoá kỳ, recompute balance, reverse entry kỳ closed → entry đảo sang kỳ kế tiếp.
 */
class AccountingPeriodTest extends TestCase
{
    use AccountingTestHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAccountingTenant();
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/accounting/setup', ['year' => 2026])->assertOk();
    }

    public function test_close_period_blocks_post_into_it(): void
    {
        // Post 1 entry vào kỳ 2026-05.
        $this->actingAs($this->accountant)->withHeaders($this->h() + ['Idempotency-Key' => 'a1'])
            ->postJson('/api/v1/accounting/journals', [
                'posted_at' => '2026-05-10',
                'lines' => [
                    ['account_code' => '6422', 'dr_amount' => 100000],
                    ['account_code' => '1111', 'cr_amount' => 100000],
                ],
            ])->assertCreated();

        // Đóng kỳ.
        $this->actingAs($this->accountant)->withHeaders($this->h())
            ->postJson('/api/v1/accounting/periods/2026-05/close', ['note' => 'Đóng tháng 5'])
            ->assertOk()
            ->assertJsonPath('data.status', 'closed');

        // Post entry mới vào kỳ closed → 422.
        $this->actingAs($this->accountant)->withHeaders($this->h() + ['Idempotency-Key' => 'a2'])
            ->postJson('/api/v1/accounting/journals', [
                'posted_at' => '2026-05-20',
                'lines' => [
                    ['account_code' => '6422', 'dr_amount' => 50000],
                    ['account_code' => '1111', 'cr_amount' => 50000],
                ],
            ])->assertStatus(422)
            ->assertJsonPath('error.code', 'ACCOUNTING_PERIOD_CLOSED');
    }

    public function test_reopen_period_blocked_when_next_period_closed(): void
    {
        $this->actingAs($this->accountant)->withHeaders($this->h())
            ->postJson('/api/v1/accounting/periods/2026-05/close')->assertOk();
        $this->actingAs($this->accountant)->withHeaders($this->h())
            ->postJson('/api/v1/accounting/periods/2026-06/close')->assertOk();

        // Reopen 2026-05 ⇒ chặn vì 2026-06 đã closed.
        $this->actingAs($this->accountant)->withHeaders($this->h())
            ->postJson('/api/v1/accounting/periods/2026-05/reopen')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'ACCOUNTING_REOPEN_BLOCKED');

        // Reopen 2026-06 OK (không có kỳ kế tiếp closed).
        $this->actingAs($this->accountant)->withHeaders($this->h())
            ->postJson('/api/v1/accounting/periods/2026-06/reopen')
            ->assertOk()->assertJsonPath('data.status', 'open');
    }

    public function test_lock_period_is_permanent(): void
    {
        $this->actingAs($this->accountant)->withHeaders($this->h())
            ->postJson('/api/v1/accounting/periods/2026-05/close')->assertOk();
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/accounting/periods/2026-05/lock')
            ->assertOk()->assertJsonPath('data.status', 'locked');

        // Reopen sau lock → 422.
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/accounting/periods/2026-05/reopen')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'ACCOUNTING_PERIOD_LOCKED');
    }

    public function test_reverse_entry_in_closed_period_lands_on_next_open_period(): void
    {
        $orig = $this->actingAs($this->accountant)->withHeaders($this->h() + ['Idempotency-Key' => 'r1'])
            ->postJson('/api/v1/accounting/journals', [
                'posted_at' => '2026-05-15',
                'lines' => [
                    ['account_code' => '6422', 'dr_amount' => 200000],
                    ['account_code' => '1111', 'cr_amount' => 200000],
                ],
            ])->assertCreated();
        $origId = $orig->json('data.id');

        // Close 2026-05.
        $this->actingAs($this->accountant)->withHeaders($this->h())
            ->postJson('/api/v1/accounting/periods/2026-05/close')->assertOk();

        // Reverse — entry đảo phải sang 2026-06.
        $rev = $this->actingAs($this->accountant)->withHeaders($this->h())
            ->postJson("/api/v1/accounting/journals/{$origId}/reverse", ['reason' => 'Sai TK'])
            ->assertOk();
        $period06 = FiscalPeriod::query()
            ->where('tenant_id', $this->tenant->getKey())
            ->where('code', '2026-06')->first();
        $this->assertSame($period06->id, (int) $rev->json('data.period_id'));
        $this->assertTrue((bool) $rev->json('data.is_adjustment'));
    }

    public function test_balance_recompute_deterministic(): void
    {
        // Post 3 entry vào 2026-05.
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->accountant)->withHeaders($this->h() + ['Idempotency-Key' => "b{$i}"])
                ->postJson('/api/v1/accounting/journals', [
                    'posted_at' => '2026-05-1'.$i,
                    'lines' => [
                        ['account_code' => '6422', 'dr_amount' => 100000],
                        ['account_code' => '1111', 'cr_amount' => 100000],
                    ],
                ])->assertCreated();
        }

        $period = FiscalPeriod::query()->where('tenant_id', $this->tenant->getKey())->where('code', '2026-05')->first();
        $service = app(BalanceService::class);
        $n1 = $service->recomputeForPeriod((int) $this->tenant->getKey(), $period);
        $n2 = $service->recomputeForPeriod((int) $this->tenant->getKey(), $period);
        $this->assertSame($n1, $n2, 'Recompute deterministic: row count không đổi.');

        // API lấy balances.
        $resp = $this->actingAs($this->accountant)->withHeaders($this->h())
            ->getJson('/api/v1/accounting/balances?period=2026-05')->assertOk();
        $tk6422 = collect($resp->json('data'))->firstWhere('account_code', '6422');
        $tk1111 = collect($resp->json('data'))->firstWhere('account_code', '1111');
        $this->assertSame(300000, (int) $tk6422['debit']);
        $this->assertSame(300000, (int) $tk6422['closing']); // debit-normal: +debit
        $this->assertSame(300000, (int) $tk1111['credit']);
        $this->assertSame(-300000, (int) $tk1111['closing']); // 1111 debit-normal: + dr - cr = 0-300000=-300000
    }
}
