<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lịch sử mỗi lần đẩy tồn lên sàn (1 dòng / listing / lần đẩy) — phục vụ xem lại,
 * đặc biệt các lần THẤT BẠI để thử lại. Ghi bởi RecordStockPushLog khi StockPushed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_push_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('channel_listing_id')->nullable()->index();
            $table->unsignedBigInteger('channel_account_id')->nullable();
            $table->string('seller_sku')->nullable();
            $table->string('external_sku_id')->nullable();
            $table->integer('desired_qty')->default(0);
            $table->string('status', 16)->default('ok');   // ok | failed
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'id']);
            $table->index(['tenant_id', 'channel_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_push_logs');
    }
};
