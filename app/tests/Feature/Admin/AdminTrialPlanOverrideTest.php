<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC 0023 — custom trial extension + plan editor + feature override.
 */
class AdminTrialPlanOverrideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
    }

    public function test_extend_trial_sets_subscription_for_custom_days(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $tenant = Tenant::create(['name' => 'X']);

        $this->actingAs($admin)->postJson("/api/v1/admin/tenants/{$tenant->id}/extend-trial", [
            'days' => 45, 'plan_code' => 'pro', 'reason' => 'Khách doanh nghiệp xin trial 45 ngày',
        ])->assertOk()
            ->assertJsonPath('data.status', Subscription::STATUS_TRIALING)
            ->assertJsonPath('data.plan_code', 'pro');

        $alive = Subscription::query()->withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->id)
            ->whereIn('status', Subscription::ALIVE_STATUSES)->first();
        $this->assertNotNull($alive);
        $this->assertEqualsWithDelta(45, now()->diffInDays($alive->current_period_end, false), 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.trial.extend', 'tenant_id' => $tenant->id]);
    }

    public function test_extend_trial_rejects_out_of_range_days(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $tenant = Tenant::create(['name' => 'Y']);

        $this->actingAs($admin)->postJson("/api/v1/admin/tenants/{$tenant->id}/extend-trial", [
            'days' => 0, 'reason' => 'Quá ngắn — bị reject',
        ])->assertStatus(422);

        $this->actingAs($admin)->postJson("/api/v1/admin/tenants/{$tenant->id}/extend-trial", [
            'days' => 999, 'reason' => 'Quá dài — bị reject',
        ])->assertStatus(422);
    }

    public function test_extend_trial_rejects_short_reason(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $tenant = Tenant::create(['name' => 'Z']);

        $this->actingAs($admin)->postJson("/api/v1/admin/tenants/{$tenant->id}/extend-trial", [
            'days' => 14, 'reason' => 'short',
        ])->assertStatus(422);
    }

    public function test_plan_update_changes_limits_and_features(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $plan = Plan::query()->where('code', 'starter')->first();

        $this->actingAs($admin)->patchJson("/api/v1/admin/plans/{$plan->id}", [
            'limits' => ['max_channel_accounts' => 3],
            'features' => ['mass_listing' => true],
        ])->assertOk()
            ->assertJsonPath('data.limits.max_channel_accounts', 3)
            ->assertJsonPath('data.features.mass_listing', true);

        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.plan.update']);
    }

    public function test_plan_update_rejects_code_change(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $plan = Plan::query()->where('code', 'starter')->first();

        $this->actingAs($admin)->patchJson("/api/v1/admin/plans/{$plan->id}", [
            'code' => 'newcode',
        ])->assertStatus(422)->assertJsonPath('error.code', 'PLAN_IMMUTABLE_FIELD');
    }

    public function test_feature_override_grants_feature_to_tenant(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'T1']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        // Set up starter sub (no mass_listing by default)
        $starter = Plan::query()->where('code', 'starter')->first();
        Subscription::query()->create([
            'tenant_id' => $tenant->id, 'plan_id' => $starter->id,
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => 'monthly',
            'current_period_start' => now(), 'current_period_end' => now()->addDays(30),
        ]);

        $this->actingAs($admin)->postJson("/api/v1/admin/tenants/{$tenant->id}/feature-overrides", [
            'features' => ['mass_listing' => true],
            'reason' => 'Khách VIP — mở mass_listing cho gói Starter',
        ])->assertOk()->assertJsonPath('data.feature_overrides.mass_listing', true);

        // Verify the subscription meta was updated
        $sub = Subscription::query()->withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->id)->latest('id')->first();
        $this->assertTrue($sub->meta['feature_overrides']['mass_listing'] ?? false);

        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.feature_override.set', 'tenant_id' => $tenant->id]);
    }
}
