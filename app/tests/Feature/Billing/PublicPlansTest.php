<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
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
}
