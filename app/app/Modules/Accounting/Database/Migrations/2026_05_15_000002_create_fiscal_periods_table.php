<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7.1 — SPEC 0019: kỳ kế toán (`open|closed|locked`).
 *
 *  - `open`     : post tự do
 *  - `closed`   : kế toán đã đóng tháng; entry mới ⇒ 422; có thể reopen nếu kỳ kế tiếp chưa đóng.
 *  - `locked`   : đã nộp tờ khai / khoá vĩnh viễn; không reopen được. Sai ⇒ điều chỉnh ở kỳ mới.
 *
 * Năm tài chính = năm dương lịch (1/1–31/12 — quyết định 2026-05-15 chốt với chủ dự án).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            // 'YYYY-MM' | 'YYYY-Qn' | 'YYYY'
            $table->string('code', 16);
            // 'month' | 'quarter' | 'year'
            $table->string('kind', 8);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 8)->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->string('close_note', 500)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'kind', 'start_date']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_periods');
    }
};
