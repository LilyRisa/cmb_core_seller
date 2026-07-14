<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\AiCreditWallet;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTenantAiCreditAdjustTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->admin = AdminUser::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        $now = now();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => $now, 'current_period_end' => $now->copy()->addMonth(),
        ]);
    }

    public function test_admin_can_grant_ai_credit(): void
    {
        $this->actingAs($this->admin, 'admin_web')
            ->postJson("/api/v1/admin/tenants/{$this->tenant->getKey()}/ai-credit/adjust", [
                'amount' => 300, 'reason' => 'Tặng thêm do lỗi hệ thống tuần trước',
            ])->assertOk()
            ->assertJsonPath('data.purchased_balance', 300);

        $w = AiCreditWallet::withoutGlobalScope(TenantScope::class)->where('tenant_id', $this->tenant->getKey())->first();
        $this->assertSame(300, $w->purchased_balance);

        $audit = AuditLog::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())->where('action', 'admin.ai_credit.adjust')->first();
        $this->assertNotNull($audit);
        $this->assertSame(300, $audit->changes['amount']);
    }

    public function test_admin_can_deduct_ai_credit_floored_at_zero(): void
    {
        AiCreditWallet::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'purchased_balance' => 100, 'period_used' => 0,
        ]);

        $this->actingAs($this->admin, 'admin_web')
            ->postJson("/api/v1/admin/tenants/{$this->tenant->getKey()}/ai-credit/adjust", [
                'amount' => -500, 'reason' => 'Thu hồi do tặng nhầm tuần trước',
            ])->assertOk()
            ->assertJsonPath('data.purchased_balance', 0);
    }

    public function test_requires_reason_at_least_10_chars(): void
    {
        $this->actingAs($this->admin, 'admin_web')
            ->postJson("/api/v1/admin/tenants/{$this->tenant->getKey()}/ai-credit/adjust", [
                'amount' => 100, 'reason' => 'ngắn',
            ])->assertStatus(422);
    }
}
