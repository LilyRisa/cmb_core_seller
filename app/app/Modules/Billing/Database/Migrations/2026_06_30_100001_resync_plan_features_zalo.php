<?php

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Database\Seeders\TestUnlimitedPlanSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\App;

/**
 * Re-apply catalog gói khi deploy để các gói HIỆN CÓ nhận feature mới `messaging_zalo`
 * (Nhắn tin Zalo OA — SPEC 0039): Cơ bản (starter) + Chuyên nghiệp (pro) + gói TEST
 * (`test_unlimited`) bật; gói Miễn phí (trial) tắt (giống `messaging_inbox`). Idempotent
 * (updateOrCreate theo `code`). Bỏ qua khi chạy test (RefreshDatabase). SPEC 2026-06-30.
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
