<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\ProTrialGrant;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Services\SubscriptionExpiryService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProTrialRevertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
    }

    public function test_expired_pro_trial_reverts_to_previous_plan(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);
        $trial = Plan::query()->where('code', Plan::CODE_TRIAL)->first();
        $pro = Plan::query()->where('code', Plan::CODE_PRO)->first();

        ProTrialGrant::query()->create([
            'tenant_id' => $tenant->getKey(), 'granted_at' => now()->subMonth(),
            'expires_at' => now()->subDay(), 'previous_plan_id' => $trial->getKey(),
            'previous_cycle' => 'trial', 'previous_period_end' => now()->addYears(50),
            'terms_accepted_at' => now()->subMonth(), 'terms_version' => 'refund-v1',
        ]);
        Subscription::query()->create([
            'tenant_id' => $tenant->getKey(), 'plan_id' => $pro->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now()->subMonth(), 'current_period_end' => now()->subDay(),
            'meta' => ['pro_trial' => true, 'revert_plan_id' => $trial->getKey(), 'revert_cycle' => 'trial', 'revert_period_end' => now()->addYears(50)->toIso8601String()],
        ]);

        app(SubscriptionExpiryService::class)->run();

        $alive = Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())->whereIn('status', Subscription::ALIVE_STATUSES)
            ->with('plan')->first();
        $this->assertSame('trial', $alive->plan->code);
        $this->assertNotNull(ProTrialGrant::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())->value('reverted_at'));
    }
}
