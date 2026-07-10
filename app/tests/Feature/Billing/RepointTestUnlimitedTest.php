<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RepointTestUnlimitedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
    }

    private function makeTestUnlimitedPlan(): Plan
    {
        return Plan::query()->create([
            'code' => 'test_unlimited', 'name' => 'Test', 'description' => '',
            'is_active' => true, 'sort_order' => 99,
            'price_monthly' => 0, 'price_yearly' => 0, 'currency' => 'VND', 'trial_days' => 0,
            'limits' => ['max_channel_accounts' => -1], 'features' => ['ai' => true],
        ]);
    }

    public function test_migration_repoints_alive_test_unlimited_subs_to_starter_and_deactivates_plan(): void
    {
        $plan = $this->makeTestUnlimitedPlan();
        $tenant = Tenant::create(['name' => 'Test Shop']);
        Subscription::query()->create([
            'tenant_id' => $tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->addYears(50),
        ]);

        // RefreshDatabase chạy migrate:fresh (bao gồm migration này) trong setUp *trước khi* seed
        // dữ liệu test — ở thời điểm đó plan test_unlimited/starter chưa tồn tại nên up() no-op và
        // bị coi là "đã chạy". Rollback (down() rỗng, an toàn) để buộc migrate chạy lại thật với
        // dữ liệu vừa tạo ở trên.
        Artisan::call('migrate:rollback', ['--path' => 'app/Modules/Billing/Database/Migrations/2026_07_10_100001_repoint_test_unlimited_to_starter.php', '--force' => true]);
        Artisan::call('migrate', ['--path' => 'app/Modules/Billing/Database/Migrations/2026_07_10_100001_repoint_test_unlimited_to_starter.php', '--force' => true]);

        $alive = Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())->whereIn('status', Subscription::ALIVE_STATUSES)
            ->with('plan')->first();
        $this->assertNotNull($alive);
        $this->assertSame('starter', $alive->plan->code);
        $this->assertFalse((bool) Plan::query()->where('code', 'test_unlimited')->value('is_active'));
    }
}
