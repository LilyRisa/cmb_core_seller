<?php

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Database\Seeders\TestUnlimitedPlanSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\App;

/**
 * Re-apply catalog gói khi deploy để các gói HIỆN CÓ nhận feature mới `marketing_tiktok`
 * (Quảng cáo TikTok — ADR-0025): Pro + gói TEST (`test_unlimited`) full quyền;
 * trial/starter tắt (giống `marketing_facebook`). Idempotent (updateOrCreate theo
 * `code`). Bỏ qua khi chạy test (RefreshDatabase). SPEC 2026-06-09.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (App::runningUnitTests()) {
            return;
        }

        (new BillingPlanSeeder)->run();
        (new TestUnlimitedPlanSeeder)->run();
    }

    public function down(): void
    {
        // Catalog data — không revert.
    }
};
