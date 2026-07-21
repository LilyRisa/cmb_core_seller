<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Billing\Models\AiUsageCounter;
use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Models\Voucher;
use CMBcoreSeller\Modules\Support\Models\SupportConversation;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_aggregates_tenants_revenue_support_and_ai_usage(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');

        $starter = Plan::query()->create([
            'code' => 'starter', 'name' => 'Starter', 'is_active' => true, 'sort_order' => 1,
            'price_monthly' => 190_000, 'price_yearly' => 1_900_000, 'currency' => 'VND',
            'trial_days' => 14, 'limits' => [], 'features' => [],
        ]);
        $pro = Plan::query()->create([
            'code' => 'pro', 'name' => 'Pro', 'is_active' => true, 'sort_order' => 2,
            'price_monthly' => 270_000, 'price_yearly' => 2_700_000, 'currency' => 'VND',
            'trial_days' => 14, 'limits' => [], 'features' => [],
        ]);

        $t1 = Tenant::factory()->create(['status' => 'active']);
        $t2 = Tenant::factory()->create(['status' => 'active']);
        $t3 = Tenant::factory()->create(['status' => 'active']);

        // t1: active Starter (monthly) ⇒ MRR += 190_000.
        $sub1 = Subscription::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t1->id, 'plan_id' => $starter->id, 'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);
        // t2: active Pro (yearly) ⇒ MRR += 2_700_000/12 = 225_000.
        $sub2 = Subscription::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t2->id, 'plan_id' => $pro->id, 'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_YEARLY,
            'current_period_start' => now(), 'current_period_end' => now()->addYear(),
        ]);
        // t3: trialing Starter, trial ends in 3 days ⇒ trial_ending_soon. Per spec 2026-07-21
        // §6.2: by_plan (phân bố gói hiện tại) dùng Subscription::ALIVE_STATUSES nên t3 CÓ cộng
        // vào by_plan.starter.count (tổng 2, không phải 1) — tenant dùng thử vẫn "đang ở" gói đó.
        // Nhưng MRR (Monthly Recurring *Revenue*) dùng riêng self::MRR_STATUSES = [active,
        // past_due], KHÔNG gồm 'trialing' — trial chưa phát sinh doanh thu. Vì vậy t3 KHÔNG cộng
        // vào mrr_estimate. Đừng "sửa" giá trị mrr_estimate kỳ vọng bên dưới để gồm cả t3 — đã
        // có lần cố tình làm vậy rồi bị coordinator sửa lại đúng theo spec.
        Subscription::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t3->id, 'plan_id' => $starter->id, 'status' => Subscription::STATUS_TRIALING,
            'billing_cycle' => Subscription::CYCLE_TRIAL, 'trial_ends_at' => now()->addDays(3),
            'current_period_start' => now(), 'current_period_end' => now()->addDays(14),
        ]);

        // Hoá đơn tháng này: 1 paid 190_000, 1 pending 270_000.
        Invoice::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t1->id, 'subscription_id' => $sub1->id, 'code' => 'INV-TEST-0001',
            'status' => Invoice::STATUS_PAID, 'period_start' => now(), 'period_end' => now()->addMonth(),
            'subtotal' => 190_000, 'tax' => 0, 'total' => 190_000, 'currency' => 'VND',
            'due_at' => now(), 'paid_at' => now(),
        ]);
        Invoice::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t2->id, 'subscription_id' => $sub2->id, 'code' => 'INV-TEST-0002',
            'status' => Invoice::STATUS_PENDING, 'period_start' => now(), 'period_end' => now()->addMonth(),
            'subtotal' => 270_000, 'tax' => 0, 'total' => 270_000, 'currency' => 'VND',
            'due_at' => now()->addDays(3),
        ]);

        Voucher::query()->create([
            'code' => 'TEST10', 'name' => 'Test 10%', 'kind' => Voucher::KIND_PERCENT, 'value' => 10,
            'max_redemptions' => -1, 'redemption_count' => 0, 'is_active' => true,
        ]);

        SupportConversation::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t1->id, 'status' => SupportConversation::STATUS_OPEN,
        ]);
        $closedConv = SupportConversation::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t2->id, 'status' => SupportConversation::STATUS_CLOSED, 'closed_at' => now(),
        ]);
        // created_at không nằm trong $fillable của SupportConversation ⇒ set qua forceFill.
        $closedConv->forceFill(['created_at' => now()->subHours(2)])->save();

        $ym = (int) now()->format('Ym');
        AiUsageCounter::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t1->id, 'user_id' => 0, 'period_ym' => $ym, 'feature' => 'messaging', 'count' => 3,
        ]);
        AiUsageCounter::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t2->id, 'user_id' => 0, 'period_ym' => $ym, 'feature' => 'messaging', 'count' => 5,
        ]);

        $resp = $this->getJson('/api/v1/admin/dashboard/overview')->assertOk();

        $resp->assertJsonPath('data.tenants.active_total', 3);
        // 190_000 (t1 active) + 225_000 (t2 active yearly/12). t3 là 'trialing' nên KHÔNG cộng
        // vào MRR (self::MRR_STATUSES chỉ gồm active/past_due — spec §6.2) dù nó VẪN cộng vào
        // by_plan.starter.count phía dưới (ALIVE_STATUSES, khác status set với MRR).
        $resp->assertJsonPath('data.revenue.mrr_estimate', 190_000 + 225_000);
        $resp->assertJsonPath('data.revenue.invoices_this_month.paid_count', 1);
        $resp->assertJsonPath('data.revenue.invoices_this_month.paid_total', 190_000);
        $resp->assertJsonPath('data.revenue.invoices_this_month.pending_count', 1);
        $resp->assertJsonPath('data.revenue.invoices_this_month.pending_total', 270_000);
        $resp->assertJsonPath('data.revenue.active_vouchers', 1);
        $resp->assertJsonPath('data.support.open_count', 1);
        $resp->assertJsonPath('data.support.avg_resolution_hours', 2.0);
        $resp->assertJsonPath('data.ai_usage.calls_this_month', 8);
        $resp->assertJsonPath('data.ai_usage.top_tenants.0.tenant_id', $t2->id);
        $resp->assertJsonPath('data.ai_usage.top_tenants.0.calls_this_month', 5);

        $byPlan = collect($resp->json('data.tenants.by_plan'));
        // starter = 2: t1 (active) + t3 (trialing, cũng thuộc ALIVE_STATUSES — spec §6.2).
        $this->assertSame(2, $byPlan->firstWhere('plan_code', 'starter')['count']);
        $this->assertSame(1, $byPlan->firstWhere('plan_code', 'pro')['count']);

        $trialSoon = collect($resp->json('data.tenants.trial_ending_soon'));
        $this->assertTrue($trialSoon->contains('tenant_id', $t3->id));
    }
}
