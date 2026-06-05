<?php

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Database\Seeders\TestUnlimitedPlanSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\App;

/**
 * Áp catalog gói SPEC 0032 vào DB khi deploy (`php artisan migrate`) — vì seeder KHÔNG tự chạy
 * trên production. Idempotent (updateOrCreate theo `code`): cập nhật trial/starter/pro sang giá +
 * hạn mức + tính năng mới, tắt `business`, thêm `test_unlimited`.
 *
 * Bỏ qua khi chạy test: test dùng RefreshDatabase nên migration này sẽ chạy trước mỗi test, làm
 * thay đổi baseline (mọi tenant không subscription bị coi là trial → khoá feature). Các test cần
 * gói sẽ tự `$this->seed(BillingPlanSeeder::class)` riêng.
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
        // Catalog data — không revert (giữ nguyên gói để không phá subscription đang tham chiếu).
    }
};
