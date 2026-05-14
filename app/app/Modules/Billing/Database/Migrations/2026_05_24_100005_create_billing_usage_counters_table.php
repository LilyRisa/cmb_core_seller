<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6.4 — `usage_counters` — đếm hạn mức theo tenant.
 *
 * V1 chỉ 1 metric `channel_accounts` (denormalized cho hiển thị "đã dùng N/M").
 * Period = 'current' (tổng tức thời) cho v1. Nếu v2 cần metric tháng (orders/tháng) thì
 * thêm metric mới với `period='YYYY-MM'` — unique constraint đã chừa khả năng đó.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('metric', 32);
            $table->string('period', 8)->default('current');  // 'current' | 'YYYY-MM'
            $table->bigInteger('value')->default(0);
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'metric', 'period'], 'usage_counters_uniq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_counters');
    }
};
