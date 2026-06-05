<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Database\Seeders\TestUnlimitedPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Services\SubscriptionExpiryService;
use CMBcoreSeller\Modules\Billing\Services\SubscriptionService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionBetaModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->seed(TestUnlimitedPlanSeeder::class);
    }

    private function setBeta(bool $on): void
    {
        // Công tắc beta = is_active của gói test_unlimited.
        Plan::query()->where('code', 'test_unlimited')->update(['is_active' => $on]);
    }

    private function sub(int $tenantId): ?Subscription
    {
        return Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->latest('id')->first();
    }

    public function test_beta_on_assigns_unlimited_test_plan_to_new_tenant(): void
    {
        $this->setBeta(true);
        $tenant = Tenant::create(['name' => 'Beta Shop']);

        app(SubscriptionService::class)->startTrial((int) $tenant->getKey());

        $sub = $this->sub((int) $tenant->getKey());
        $this->assertNotNull($sub);
        $this->assertSame('test_unlimited', $sub->plan->code);
        $this->assertSame(Subscription::STATUS_ACTIVE, $sub->status);
        $this->assertTrue($sub->current_period_end->isFuture());
        $this->assertSame(-1, $sub->plan->aiCreditsMonthly());
    }

    public function test_beta_off_assigns_trial(): void
    {
        $this->setBeta(false);
        $tenant = Tenant::create(['name' => 'Trial Shop']);

        app(SubscriptionService::class)->startTrial((int) $tenant->getKey());

        $sub = $this->sub((int) $tenant->getKey());
        $this->assertSame('trial', $sub->plan->code);
        $this->assertSame(Subscription::STATUS_TRIALING, $sub->status);
    }

    public function test_disabling_beta_downgrades_test_users_to_trial(): void
    {
        $this->setBeta(true);
        $tenant = Tenant::create(['name' => 'Beta Shop']);
        app(SubscriptionService::class)->startTrial((int) $tenant->getKey());
        $this->assertSame('test_unlimited', $this->sub((int) $tenant->getKey())->plan->code);

        // Admin tắt beta ⇒ scheduler/command hạ về trial.
        $this->setBeta(false);
        app(SubscriptionExpiryService::class)->run();

        $subs = Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())->orderBy('id')->get();
        $this->assertSame(Subscription::STATUS_EXPIRED, $subs[0]->status);
        $this->assertSame(Subscription::STATUS_ACTIVE, $subs->last()->status);
        $this->assertSame('trial', $subs->last()->plan->code);
    }

    public function test_billing_beta_command_toggles_and_migrates(): void
    {
        $this->setBeta(true);
        $tenant = Tenant::create(['name' => 'Beta Shop']);
        app(SubscriptionService::class)->startTrial((int) $tenant->getKey());

        $this->artisan('billing:beta off')->assertSuccessful();

        $this->assertSame('trial', $this->sub((int) $tenant->getKey())->plan->code);
    }
}
