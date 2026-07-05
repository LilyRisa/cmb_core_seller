<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ai_usage_counters — đếm số LƯỢT gọi AI theo (tenant, user, tháng, tính năng).
 *  - user_id = 0 ⇒ hệ thống / auto (không có user request, vd auto-reply hàng đợi).
 *  - period_ym = YYYYMM (vd 202607). feature = messaging|marketing|products|visual|transcription|intent|other.
 *  - Đếm tiến (không backfill); dùng cho màn admin thống kê lượt AI mỗi user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->default(0); // 0 = hệ thống/auto (KHÔNG FK — cho phép giá trị 0)
            $table->unsignedInteger('period_ym');
            $table->string('feature', 24);
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'period_ym', 'feature'], 'ai_usage_counters_unique');
            $table->index(['tenant_id', 'period_ym']);
            $table->index(['user_id', 'period_ym']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_counters');
    }
};
