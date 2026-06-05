<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ai_credit_wallets — ví lượt gọi AI mỗi tenant (SPEC 0032).
 *  - purchased_balance: credit MUA thêm — vĩnh viễn, cộng dồn tối đa 5000.
 *  - period_used: số lượt đã dùng trong kỳ hiện tại (trừ hạn mức tặng kèm gói trước).
 *  - period_anchor: mốc bắt đầu kỳ hiện tại (reset hạn mức tặng mỗi tháng).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_credit_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('purchased_balance')->default(0);
            $table->unsignedInteger('period_used')->default(0);
            $table->date('period_anchor')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_credit_wallets');
    }
};
