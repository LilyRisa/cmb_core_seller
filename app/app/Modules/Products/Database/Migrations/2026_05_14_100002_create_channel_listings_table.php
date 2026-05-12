<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * channel_listings — a product/variant as it appears on a marketplace shop.
 * `channel_stock` is what's currently shown on the channel. See SPEC 0003 §5.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('channel_account_id');
            $table->string('external_product_id')->nullable();
            $table->string('external_sku_id');
            $table->string('seller_sku')->nullable();
            $table->string('title')->nullable();
            $table->string('variation')->nullable();
            $table->bigInteger('price')->nullable();
            $table->integer('channel_stock')->nullable();
            $table->string('currency', 8)->default('VND');
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_stock_locked')->default(false);     // user "pinned" — don't auto-push
            $table->string('sync_status', 16)->default('ok');       // ok | error | pending
            $table->string('sync_error')->nullable();
            $table->timestamp('last_pushed_at')->nullable();
            $table->timestamp('last_fetched_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['channel_account_id', 'external_sku_id']);
            $table->index(['tenant_id', 'channel_account_id']);
            $table->index(['tenant_id', 'seller_sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_listings');
    }
};
