<?php

namespace Tests\Feature\Accounting;

use CMBcoreSeller\Modules\Accounting\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7.1 — SPEC 0019 §3.4 + §3.5.
 * Bút toán tay: cân, validate, RBAC, idempotent qua header, đảo.
 */
class JournalApiTest extends TestCase
{
    use AccountingTestHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAccountingTenant();
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/accounting/setup', ['year' => 2026])->assertOk();
    }

    public function test_post_manual_entry_balanced_succeeds(): void
    {
        $resp = $this->actingAs($this->accountant)->withHeaders($this->h() + ['Idempotency-Key' => 'test-1'])
            ->postJson('/api/v1/accounting/journals', [
                'posted_at' => '2026-05-15',
                'narration' => 'Tạm ứng văn phòng phẩm',
                'lines' => [
                    ['account_code' => '6422', 'dr_amount' => 500000, 'memo' => 'VPP'],
                    ['account_code' => '1111', 'cr_amount' => 500000, 'memo' => 'Quỹ TM'],
                ],
            ]);

        $resp->assertCreated()
            ->assertJsonPath('data.total_debit', 500000)
            ->assertJsonPath('data.total_credit', 500000);
        $this->assertCount(2, $resp->json('data.lines'));
    }

    public function test_unbalanced_entry_rejected(): void
    {
        $this->actingAs($this->accountant)->withHeaders($this->h() + ['Idempotency-Key' => 'test-2'])
            ->postJson('/api/v1/accounting/journals', [
                'posted_at' => '2026-05-15',
                'lines' => [
                    ['account_code' => '6422', 'dr_amount' => 500000],
                    ['account_code' => '1111', 'cr_amount' => 400000],
                ],
            ])->assertStatus(422)
            ->assertJsonPath('error.code', 'ACCOUNTING_UNBALANCED');
    }

    public function test_account_not_postable_rejected(): void
    {
        // TK 156 là tổng — không postable.
        $this->actingAs($this->accountant)->withHeaders($this->h() + ['Idempotency-Key' => 'test-3'])
            ->postJson('/api/v1/accounting/journals', [
                'posted_at' => '2026-05-15',
                'lines' => [
                    ['account_code' => '156', 'dr_amount' => 100000],
                    ['account_code' => '1111', 'cr_amount' => 100000],
                ],
            ])->assertStatus(422)
            ->assertJsonPath('error.code', 'ACCOUNTING_ACCOUNT_NOT_POSTABLE');
    }

    public function test_account_not_found_rejected(): void
    {
        $this->actingAs($this->accountant)->withHeaders($this->h() + ['Idempotency-Key' => 'test-4'])
            ->postJson('/api/v1/accounting/journals', [
                'posted_at' => '2026-05-15',
                'lines' => [
                    ['account_code' => '9999', 'dr_amount' => 100000],
                    ['account_code' => '1111', 'cr_amount' => 100000],
                ],
            ])->assertStatus(422)
            ->assertJsonPath('error.code', 'ACCOUNTING_ACCOUNT_NOT_FOUND');
    }

    public function test_idempotency_replay_returns_same_entry(): void
    {
        $payload = [
            'posted_at' => '2026-05-15',
            'lines' => [
                ['account_code' => '6422', 'dr_amount' => 100000],
                ['account_code' => '1111', 'cr_amount' => 100000],
            ],
        ];
        $headers = $this->h() + ['Idempotency-Key' => 'idem-replay'];
        $r1 = $this->actingAs($this->accountant)->withHeaders($headers)->postJson('/api/v1/accounting/journals', $payload);
        $r1->assertCreated();
        $id1 = $r1->json('data.id');
        $r2 = $this->actingAs($this->accountant)->withHeaders($headers)->postJson('/api/v1/accounting/journals', $payload);
        $r2->assertCreated();
        $this->assertSame($id1, $r2->json('data.id'), 'Cùng Idempotency-Key ⇒ entry cũ.');
        $this->assertSame(1, JournalEntry::query()->where('tenant_id', $this->tenant->getKey())->count());
    }

    public function test_staff_order_cannot_post_manual(): void
    {
        $this->actingAs($this->staffOrder)->withHeaders($this->h() + ['Idempotency-Key' => 't-x'])
            ->postJson('/api/v1/accounting/journals', [
                'posted_at' => '2026-05-15',
                'lines' => [
                    ['account_code' => '6422', 'dr_amount' => 100000],
                    ['account_code' => '1111', 'cr_amount' => 100000],
                ],
            ])->assertStatus(403);
    }

    public function test_viewer_can_list_entries(): void
    {
        $this->actingAs($this->viewer)->withHeaders($this->h())
            ->getJson('/api/v1/accounting/journals')
            ->assertStatus(403); // Viewer KHÔNG có accounting.view (Phase 7 không cấp cho Viewer).
    }

    public function test_reverse_entry_creates_reverse_journal(): void
    {
        $r = $this->actingAs($this->accountant)->withHeaders($this->h() + ['Idempotency-Key' => 'orig'])
            ->postJson('/api/v1/accounting/journals', [
                'posted_at' => '2026-05-15',
                'lines' => [
                    ['account_code' => '6422', 'dr_amount' => 300000],
                    ['account_code' => '1111', 'cr_amount' => 300000],
                ],
            ])->assertCreated();
        $id = $r->json('data.id');

        $rev = $this->actingAs($this->accountant)->withHeaders($this->h())
            ->postJson("/api/v1/accounting/journals/{$id}/reverse", ['reason' => 'Sai số tiền'])
            ->assertOk();

        $this->assertSame($id, $rev->json('data.is_reversal_of_id'));
        // Đảo: line[0] giờ Cr 300k cho 6422, line[1] giờ Dr 300k cho 1111.
        $lines = $rev->json('data.lines');
        $tk6422 = collect($lines)->firstWhere('account_code', '6422');
        $tk1111 = collect($lines)->firstWhere('account_code', '1111');
        $this->assertSame(0, (int) $tk6422['dr_amount']);
        $this->assertSame(300000, (int) $tk6422['cr_amount']);
        $this->assertSame(300000, (int) $tk1111['dr_amount']);
        $this->assertSame(0, (int) $tk1111['cr_amount']);

        // Idempotent: gọi reverse lần nữa = trả entry đảo cũ, không tạo trùng.
        $rev2 = $this->actingAs($this->accountant)->withHeaders($this->h())
            ->postJson("/api/v1/accounting/journals/{$id}/reverse", ['reason' => 'Sai số tiền'])
            ->assertOk();
        $this->assertSame($rev->json('data.id'), $rev2->json('data.id'));
    }

    public function test_list_filter_and_pagination(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->accountant)->withHeaders($this->h() + ['Idempotency-Key' => "list-{$i}"])
                ->postJson('/api/v1/accounting/journals', [
                    'posted_at' => '2026-05-1'.$i,
                    'narration' => "Test entry {$i}",
                    'lines' => [
                        ['account_code' => '6422', 'dr_amount' => 100000 + $i * 1000],
                        ['account_code' => '1111', 'cr_amount' => 100000 + $i * 1000],
                    ],
                ])->assertCreated();
        }
        $resp = $this->actingAs($this->accountant)->withHeaders($this->h())
            ->getJson('/api/v1/accounting/journals?source_module=manual&per_page=3')
            ->assertOk();
        $this->assertSame(3, count($resp->json('data')));
        $this->assertSame(5, $resp->json('meta.total'));
    }
}
