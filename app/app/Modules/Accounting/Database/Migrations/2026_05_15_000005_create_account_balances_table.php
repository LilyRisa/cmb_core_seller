<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7.1 — SPEC 0019: số dư tài khoản theo kỳ (materialized aggregate).
 *
 * Rebuild được — chạy `accounting:recompute-balances` idempotent. Báo cáo BCTC ở Phase 7.5 sẽ
 * đọc đây thay vì aggregate trên `journal_lines` mỗi lần (∀ tenant ~5k entries/tháng × 4 lines).
 *
 *  - Số dư = signed VND theo `normal_balance` của TK (asset/expense + debit; liability/revenue + credit).
 *  - Đa chiều: vẫn có row tổng (party/dim = NULL) + row chi tiết theo party/warehouse/shop cho sổ chi tiết.
 *  - `opening` của kỳ kế tiếp = `closing` của kỳ trước (tính trong PeriodService::close).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('account_id');
            $table->foreignId('period_id');

            $table->string('party_type', 16)->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->unsignedBigInteger('dim_warehouse_id')->nullable();
            $table->unsignedBigInteger('dim_shop_id')->nullable();

            $table->bigInteger('opening')->default(0);
            $table->bigInteger('debit')->default(0);
            $table->bigInteger('credit')->default(0);
            $table->bigInteger('closing')->default(0);

            $table->timestamp('recomputed_at')->nullable();
            $table->timestamps();

            // Unique theo (account, period, slice dimension) — dùng COALESCE trick: lưu 0 / chuỗi rỗng cho null.
            // Trên Postgres dùng partial index; ở đây giữ index thường + ràng buộc app-level.
            $table->index(['tenant_id', 'account_id', 'period_id'], 'bal_acc_period_idx');
            $table->index(['tenant_id', 'party_type', 'party_id', 'period_id'], 'bal_party_idx');
            $table->unique(['tenant_id', 'account_id', 'period_id', 'party_type', 'party_id', 'dim_warehouse_id', 'dim_shop_id'], 'bal_slice_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_balances');
    }
};
