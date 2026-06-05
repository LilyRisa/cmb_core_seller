<?php

namespace CMBcoreSeller\Modules\Billing\Database\Seeders;

use CMBcoreSeller\Modules\Billing\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Gói TEST "Không giới hạn" — MỌI feature bật + MỌI hạn mức = -1 (unlimited).
 *
 * Mục đích: super-admin gán cho từng shop để kiểm thử (mở hết tính năng, bỏ mọi hạn mức)
 * mà không cần thanh toán. Gán qua /admin (Tenant → "Đổi gói") hoặc
 * POST /api/v1/admin/tenants/{tid}/subscription { plan_code: "test_unlimited", cycle: "monthly", reason }.
 *
 * KHÔNG thuộc Plan::CODES ⇒ KHÔNG hiện ở trang gói công khai (BillingController::plans lọc CODES) và
 * tenant KHÔNG tự checkout được (checkout validate `in:CODES`). Chỉ super-admin changePlan gán được.
 *
 * Idempotent (updateOrCreate theo `code`). Chạy: php artisan db:seed --class=TestUnlimitedPlanSeeder
 */
class TestUnlimitedPlanSeeder extends Seeder
{
    public const CODE = 'test_unlimited';

    public function run(): void
    {
        // Bật toàn bộ feature flags app kiểm tra (đồng bộ với BillingPlanSeeder).
        $features = [
            'procurement' => true,
            'fifo_cogs' => true,
            'profit_reports' => true,
            'finance_settlements' => true,
            'demand_planning' => true,
            'mass_listing' => true,
            'automation_rules' => true,
            'priority_support' => true,
            'accounting_basic' => true,
            'accounting_advanced' => true,
            'messaging_inbox' => true,
            'messaging_ai' => true,
            'marketing_facebook' => true,
            'ai' => true,
        ];

        // -1 = không giới hạn. ai_credits_monthly=-1 ⇒ gọi AI không giới hạn (SPEC 0032).
        $limits = [
            'max_channel_accounts' => -1,
            'max_channel_accounts_per_platform' => -1,
            'ai_credits_monthly' => -1,
            'messaging_ai_replies_monthly' => -1,
            'messaging_media_mb_daily' => -1,
        ];

        Plan::query()->updateOrCreate(['code' => self::CODE], [
            'name' => 'Test — Không giới hạn',
            'description' => 'Gói TEST nội bộ: mở mọi tính năng + mọi hạn mức không giới hạn. Super-admin gán cho shop để kiểm thử.',
            'is_active' => true,
            'sort_order' => 99,
            'price_monthly' => 0,
            'price_yearly' => 0,
            'currency' => 'VND',
            'trial_days' => 0,
            'limits' => $limits,
            'features' => $features,
        ]);
    }
}
