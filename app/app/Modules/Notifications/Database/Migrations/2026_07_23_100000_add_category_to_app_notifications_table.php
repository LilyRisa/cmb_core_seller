<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plan A (2026-07-23) — thêm `category` (order|system|general) để panel thông báo lọc
 * theo tab. Mặc định 'system' (an toàn cho loại chưa phân loại); giá trị THẬT được
 * NotificationDispatcher tự gán qua NotificationType::categoryFor() khi tạo mới — cột
 * default chỉ là fallback cho backfill (xem notifications:backfill-category, Task 2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_notifications', function (Blueprint $table) {
            $table->string('category', 16)->default('system')->after('type');
        });

        Schema::table('app_notifications', function (Blueprint $table) {
            $table->index(['tenant_id', 'user_id', 'category', 'id']);
            $table->index(['tenant_id', 'user_id', 'category', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::table('app_notifications', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'user_id', 'category', 'id']);
            $table->dropIndex(['tenant_id', 'user_id', 'category', 'read_at']);
            $table->dropColumn('category');
        });
    }
};
