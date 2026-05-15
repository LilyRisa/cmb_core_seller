<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7.1 — SPEC 0019: hệ thống tài khoản kế toán (Chart of Accounts).
 *
 * Mỗi tenant clone template TT133 (Thông tư 133/2016/TT-BTC, DN nhỏ & vừa) lúc onboard.
 * Tenant có thể thêm TK con, đổi tên / `is_active`; KHÔNG xoá TK đang có phát sinh.
 *
 *  - `code` là chuỗi (vd '111', '1111', '156', '33311') — KHÔNG dùng số để khỏi cắt zero đầu.
 *  - `type` quyết định normal_balance + thuộc nhóm nào trên BCTC (B01/B02/B03 ở Phase 7.5).
 *  - `is_postable=false` cho TK tổng (vd '111' tổng, '1111'/'1112' mới postable).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('code', 16);
            $table->string('name', 255);
            // asset | liability | equity | revenue | expense | cogs | contra_revenue | contra_asset | clearing
            $table->string('type', 16);
            $table->foreignId('parent_id')->nullable();
            // 'debit' | 'credit' — quyết định opening/closing được tính dương theo chiều nào.
            $table->string('normal_balance', 8);
            $table->boolean('is_postable')->default(true);
            $table->boolean('is_active')->default(true);
            // 'tt133' | 'tt200' | 'custom' — chừa đường mở rộng sang TT200.
            $table->string('vas_template', 8)->default('tt133');
            $table->integer('sort_order')->default(0);
            $table->string('description', 500)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'type', 'is_active']);
            $table->index(['tenant_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_accounts');
    }
};
