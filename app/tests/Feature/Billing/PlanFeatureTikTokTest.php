<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature `marketing_tiktok` (Quảng cáo TikTok — ADR-0025) đã vào catalog gói:
 * Pro bật, trial/starter tắt — giống marketing_facebook.
 */
class PlanFeatureTikTokTest extends TestCase
{
    use RefreshDatabase;

    public function test_pro_has_tiktok_ads_trial_and_starter_do_not(): void
    {
        (new BillingPlanSeeder)->run();

        $pro = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        $trial = Plan::query()->where('code', Plan::CODE_TRIAL)->firstOrFail();
        $starter = Plan::query()->where('code', Plan::CODE_STARTER)->firstOrFail();

        $this->assertTrue($pro->hasFeature('marketing_tiktok'));
        $this->assertFalse($trial->hasFeature('marketing_tiktok'));
        $this->assertFalse($starter->hasFeature('marketing_tiktok'));
        // Đồng bộ với marketing_facebook (cùng chính sách gating).
        $this->assertSame($pro->hasFeature('marketing_facebook'), $pro->hasFeature('marketing_tiktok'));
    }
}
