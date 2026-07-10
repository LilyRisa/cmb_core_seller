<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPlansTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_plans_no_auth(): void
    {
        $this->seed(BillingPlanSeeder::class);
        $this->getJson('/api/v1/public/plans')
            ->assertOk()
            ->assertJsonStructure(['data' => [['code', 'name', 'price_monthly', 'price_yearly', 'currency', 'features', 'limits']]]);
    }

    public function test_public_plans_never_exposes_internal_codes(): void
    {
        Plan::query()->create([
            'code' => 'test_unlimited', 'name' => 'Test', 'description' => '',
            'is_active' => true, 'sort_order' => 99,
            'price_monthly' => 0, 'price_yearly' => 0, 'currency' => 'VND', 'trial_days' => 0,
            'limits' => [], 'features' => [],
        ]);

        $codes = collect($this->getJson('/api/v1/public/plans')->assertOk()->json('data'))
            ->pluck('code')->all();
        $this->assertNotContains('test_unlimited', $codes);
    }
}
