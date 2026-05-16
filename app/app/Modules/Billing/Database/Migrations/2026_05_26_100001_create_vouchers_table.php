<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 0023 — Bảng `vouchers` (catalog mã ưu đãi của admin).
 * KHÔNG tenant-scoped (admin tạo, áp dụng xuyên tenant).
 *
 * kind:
 *   - 'percent'      → value = % giảm giá ở invoice checkout (0-100)
 *   - 'fixed'        → value = số VND giảm trừ ở invoice checkout
 *   - 'free_days'    → value = N ngày extend current_period_end (admin grant)
 *   - 'plan_upgrade' → value = plan_id mục tiêu (admin grant — swap plan tới X ngày)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name', 255);
            $table->string('description', 500)->nullable();
            $table->string('kind', 16);                      // percent|fixed|free_days|plan_upgrade
            $table->bigInteger('value');                     // ý nghĩa theo kind
            $table->json('valid_plans')->nullable();         // [plan_code, ...]; null = mọi plan
            $table->integer('max_redemptions')->default(-1); // -1 = unlimited
            $table->integer('redemption_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_user_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('is_active');
            $table->index('expires_at');
            $table->index('kind');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
