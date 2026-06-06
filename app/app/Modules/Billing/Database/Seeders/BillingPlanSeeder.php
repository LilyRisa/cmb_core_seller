<?php

namespace CMBcoreSeller\Modules\Billing\Database\Seeders;

use CMBcoreSeller\Modules\Billing\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Catalog 3 gói (SPEC 0032 — upsert theo `code`):
 *  - trial (miễn phí): chỉ xử lý đơn + SKU/tồn kho + tạo đơn thủ công. 3 gian hàng, 1/nền tảng.
 *  - starter (90k/tháng): + nhắn tin Facebook Page (KHÔNG AI). 2 gian hàng/nền tảng. Không kế toán/marketing.
 *  - pro (170k/tháng): full tính năng, không giới hạn gian hàng, tặng 500 lượt AI/kỳ.
 *
 * `business` (gói cũ) bị tắt (is_active=false) — giữ row cho subscription cũ, không chào bán nữa.
 */
class BillingPlanSeeder extends Seeder
{
    public function run(): void
    {
        $allOff = [
            'procurement' => false,
            'fifo_cogs' => false,
            'profit_reports' => false,
            'finance_settlements' => false,
            'demand_planning' => false,
            'mass_listing' => false,
            'automation_rules' => false,
            'priority_support' => false,
            'accounting_basic' => false,
            'accounting_advanced' => false,
            'messaging_inbox' => false,
            'messaging_ai' => false,
            'marketing_facebook' => false,
            'shop_health_reports' => false,
            'ai' => false,
        ];

        // starter chỉ mở thêm hộp thư (nhắn tin Page Facebook) — KHÔNG AI, kế toán, marketing.
        $starter = array_merge($allOff, [
            'messaging_inbox' => true,
        ]);

        // pro = full: bật mọi feature.
        $pro = array_map(static fn () => true, $allOff);

        $plans = [
            [
                'code' => Plan::CODE_TRIAL,
                'name' => 'Dùng thử',
                'description' => 'Miễn phí 14 ngày — xử lý đơn + tồn kho SKU + tạo đơn thủ công. Tối đa 3 gian hàng (mỗi nền tảng 1).',
                'sort_order' => 0,
                'price_monthly' => 0,
                'price_yearly' => 0,
                'trial_days' => 14,
                'is_active' => true,
                'limits' => [
                    'max_channel_accounts' => 3,
                    'max_channel_accounts_per_platform' => 1,
                    'ai_credits_monthly' => 0,
                ],
                'features' => $allOff,
            ],
            [
                'code' => Plan::CODE_STARTER,
                'name' => 'Cơ bản',
                'description' => 'Xử lý đơn + nhắn tin Facebook Page (chưa có AI). Mỗi nền tảng tối đa 2 gian hàng. Chưa gồm kế toán & quảng cáo Facebook.',
                'sort_order' => 1,
                'price_monthly' => 90_000,
                'price_yearly' => 900_000,
                'trial_days' => 0,
                'is_active' => true,
                'limits' => [
                    'max_channel_accounts' => -1,
                    'max_channel_accounts_per_platform' => 2,
                    'ai_credits_monthly' => 0,
                ],
                'features' => $starter,
            ],
            [
                'code' => Plan::CODE_PRO,
                'name' => 'Chuyên nghiệp',
                'description' => 'Full tính năng (kế toán, quảng cáo Facebook, AI chat, đối soát, báo cáo lợi nhuận…), không giới hạn gian hàng, tặng 500 lượt AI mỗi kỳ.',
                'sort_order' => 2,
                'price_monthly' => 170_000,
                'price_yearly' => 1_700_000,
                'trial_days' => 0,
                'is_active' => true,
                'limits' => [
                    'max_channel_accounts' => -1,
                    'max_channel_accounts_per_platform' => -1,
                    'ai_credits_monthly' => 500,
                ],
                'features' => $pro,
            ],
            // Gói cũ — tắt, không chào bán (giữ row cho subscription đang dùng).
            [
                'code' => Plan::CODE_BUSINESS,
                'name' => 'Business (ngừng bán)',
                'description' => 'Gói cũ — không còn chào bán.',
                'sort_order' => 9,
                'price_monthly' => 399_000,
                'price_yearly' => 3_990_000,
                'trial_days' => 0,
                'is_active' => false,
                'limits' => [
                    'max_channel_accounts' => -1,
                    'max_channel_accounts_per_platform' => -1,
                    'ai_credits_monthly' => 500,
                ],
                'features' => $pro,
            ],
        ];

        foreach ($plans as $p) {
            Plan::query()->updateOrCreate(['code' => $p['code']], array_merge($p, [
                'currency' => 'VND',
            ]));
        }
    }
}
