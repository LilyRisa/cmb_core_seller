<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sự kiện điểm phạt/vi phạm real-time từ webhook sàn (Shopee penalty/violation push).
 * Lưu lại để cảnh báo + hiển thị "Cảnh báo gần đây" trong Báo cáo sàn. SPEC 2026-06-06.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_penalty_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('channel_account_id')->index();
            $table->string('provider', 32);
            $table->string('kind', 32);                 // penalty_issued | penalty_removed | tier_update | listing_violation
            $table->integer('points')->default(0);
            $table->unsignedInteger('violation_type')->nullable();
            $table->string('violation_label')->nullable();
            $table->unsignedInteger('tier')->nullable();
            $table->string('item_id')->nullable();
            $table->string('item_name')->nullable();
            $table->unsignedBigInteger('webhook_event_id')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'channel_account_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_penalty_events');
    }
};
