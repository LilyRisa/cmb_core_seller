<?php

namespace Tests\Feature\EInvoice;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EInvoicePlanFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_pro_plan_has_einvoice_feature(): void
    {
        $this->seed(BillingPlanSeeder::class);
        $pro = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        $this->assertTrue($pro->hasFeature('einvoice'));
    }
}
