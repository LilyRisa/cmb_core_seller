<?php

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Database\Seeders\TestUnlimitedPlanSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\App;

/**
 * Re-apply catalog gói khi deploy để các gói HIỆN CÓ nhận feature mới `shop_health_reports`
 * (Báo cáo sàn): Pro/business + gói TEST (`test_unlimited`) full quyền. Idempotent
 * (updateOrCreate theo `code`). Bỏ qua khi chạy test (RefreshDatabase). SPEC 2026-06-06.
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
