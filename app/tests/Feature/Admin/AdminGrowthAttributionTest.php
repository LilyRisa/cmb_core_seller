<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGrowthAttributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_groups_signups_and_paid_conversions_by_utm_source(): void
    {
        $admin = AdminUser::factory()->create();
        $plan = Plan::query()->create([
            'code' => 'starter', 'name' => 'Starter', 'is_active' => true, 'sort_order' => 1,
            'price_monthly' => 190_000, 'price_yearly' => 1_900_000, 'currency' => 'VND',
            'trial_days' => 14, 'limits' => [], 'features' => [],
        ]);

        // Nguồn "facebook": 1 đăng ký, đã lên gói trả phí (subscription active non-trial).
        $fbTenant = Tenant::create(['name' => 'FB Shop']);
        $fbTenant->forceFill(['acquisition' => ['utm_source' => 'facebook']])->save();
        Subscription::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $fbTenant->id, 'plan_id' => $plan->id, 'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);
        Invoice::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $fbTenant->id, 'subscription_id' => 0, 'code' => 'INV-FB-1',
            'status' => Invoice::STATUS_PAID, 'period_start' => now()->format('Y-m-d'),
            'period_end' => now()->addMonth()->format('Y-m-d'),
            'subtotal' => 190_000, 'tax' => 0, 'total' => 190_000, 'currency' => 'VND',
            'due_at' => now(), 'paid_at' => now(),
        ]);

        // Không có utm — nhóm "Không xác định", chưa lên gói.
        Tenant::create(['name' => 'Direct Shop']);

        $response = $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/growth/attribution');

        $response->assertOk();
        $rows = collect($response->json('data'))->keyBy('source');
        $this->assertSame(1, $rows['facebook']['signups']);
        $this->assertSame(1, $rows['facebook']['paid']);
        $this->assertSame(100.0, $rows['facebook']['conversion_rate']);
        $this->assertSame(190_000, $rows['facebook']['revenue_vnd']);
        $this->assertSame(1, $rows['Không xác định']['signups']);
        $this->assertSame(0, $rows['Không xác định']['paid']);
    }
}
