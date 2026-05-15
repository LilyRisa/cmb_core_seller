<?php

namespace CMBcoreSeller\Modules\Billing\Database\Seeders;

use CMBcoreSeller\Modules\Billing\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Seed 4 gói chuẩn (idempotent — upsert theo `code`). SPEC 0018 §4.1.
 *
 *  trial    | 0 / 0           | 2 gian hàng  | features = starter
 *  starter  | 99.000 / 990.000| 2 gian hàng  | features cơ bản
 *  pro      | 199.000 / 1.990.000 | 5 gian hàng | + procurement/fifo/profit/finance/demand
 *  business | 399.000 / 3.990.000 | 10 gian hàng| + mass_listing/automation/priority
 *
 * Giá năm = giá 10 tháng (giảm 2 tháng). Tiền: bigint VND đồng.
 */
class BillingPlanSeeder extends Seeder
{
    public function run(): void
    {
        $featuresBasic = [
            'procurement' => false,
            'fifo_cogs' => false,
            'profit_reports' => false,
            'finance_settlements' => false,
            'demand_planning' => false,
            'mass_listing' => false,
            'automation_rules' => false,
            'priority_support' => false,
            // Phase 7 — Kế toán đầy đủ (SPEC 0019).
            'accounting_basic' => false,
            'accounting_advanced' => false,
        ];

        $featuresPro = array_merge($featuresBasic, [
            'procurement' => true,
            'fifo_cogs' => true,
            'profit_reports' => true,
            'finance_settlements' => true,
            'demand_planning' => true,
            // Pro: bật Accounting nền (CoA, journal, AR/AP, sổ NK, BS/P&L cơ bản).
            'accounting_basic' => true,
        ]);

        $featuresBusiness = array_merge($featuresPro, [
            'mass_listing' => true,
            'automation_rules' => true,
            'priority_support' => true,
            // Business: thêm Accounting nâng cao (VAT + tờ khai + bank reconcile + export MISA).
            'accounting_advanced' => true,
        ]);

        $plans = [
            [
                'code' => Plan::CODE_TRIAL,
                'name' => 'Dùng thử',
                'description' => 'Trải nghiệm miễn phí 14 ngày — đủ tính năng cơ bản, 2 gian hàng.',
                'sort_order' => 0,
                'price_monthly' => 0,
                'price_yearly' => 0,
                'trial_days' => 14,
                'limits' => ['max_channel_accounts' => 2],
                'features' => $featuresBasic,
            ],
            [
                'code' => Plan::CODE_STARTER,
                'name' => 'Starter',
                'description' => 'Cho shop nhỏ bán 2 sàn — đủ luồng cơ bản (đồng bộ đơn, tồn, in tem, sổ khách).',
                'sort_order' => 1,
                'price_monthly' => 99_000,
                'price_yearly' => 990_000,
                'trial_days' => 0,
                'limits' => ['max_channel_accounts' => 2],
                'features' => $featuresBasic,
            ],
            [
                'code' => Plan::CODE_PRO,
                'name' => 'Pro',
                'description' => 'Tính năng nâng cao đầy đủ — quản lý mua hàng, FIFO, đối soát, báo cáo lợi nhuận thật, đề xuất nhập hàng.',
                'sort_order' => 2,
                'price_monthly' => 199_000,
                'price_yearly' => 1_990_000,
                'trial_days' => 0,
                'limits' => ['max_channel_accounts' => 5],
                'features' => $featuresPro,
            ],
            [
                'code' => Plan::CODE_BUSINESS,
                'name' => 'Business',
                'description' => 'Shop đa sàn quy mô lớn — đăng bán đa sàn, tự động hoá, hỗ trợ SLA.',
                'sort_order' => 3,
                'price_monthly' => 399_000,
                'price_yearly' => 3_990_000,
                'trial_days' => 0,
                'limits' => ['max_channel_accounts' => 10],
                'features' => $featuresBusiness,
            ],
        ];

        foreach ($plans as $p) {
            Plan::query()->updateOrCreate(['code' => $p['code']], array_merge($p, [
                'is_active' => true,
                'currency' => 'VND',
            ]));
        }
    }
}
