<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature `messaging_zalo` (Nhắn tin Zalo OA — SPEC 0039) đã vào catalog gói:
 * Cơ bản (starter) + Chuyên nghiệp (pro) bật, Miễn phí (trial) tắt — giống `messaging_inbox`.
 */
class PlanFeatureZaloTest extends TestCase
{
    use RefreshDatabase;

    public function test_starter_and_pro_have_zalo_messaging_trial_does_not(): void
    {
        (new BillingPlanSeeder)->run();

        $pro = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        $trial = Plan::query()->where('code', Plan::CODE_TRIAL)->firstOrFail();
        $starter = Plan::query()->where('code', Plan::CODE_STARTER)->firstOrFail();

        $this->assertTrue($pro->hasFeature('messaging_zalo'));
        $this->assertTrue($starter->hasFeature('messaging_zalo'));
        $this->assertFalse($trial->hasFeature('messaging_zalo'));
        // Đồng bộ chính sách với messaging_inbox (Free off, Basic+Pro on).
        $this->assertSame($starter->hasFeature('messaging_inbox'), $starter->hasFeature('messaging_zalo'));
        $this->assertSame($trial->hasFeature('messaging_inbox'), $trial->hasFeature('messaging_zalo'));
    }
}
