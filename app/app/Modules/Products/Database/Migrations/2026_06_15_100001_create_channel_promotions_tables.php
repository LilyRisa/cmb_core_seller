<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chiến dịch giảm giá nhiều SKU trên sàn (Shopee/TikTok có đối tượng chương trình;
 * Lazada chỉ rải SalePrice — external_promotion_id null). Tách bảng riêng, KHÔNG đụng
 * channel_listings/listing_drafts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('channel_account_id')->index();
            $table->string('provider', 32);
            $table->string('external_promotion_id')->nullable()->index();
            $table->string('title');
            $table->string('discount_type', 16)->default('fixed'); // percent | fixed
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            // draft | pushing | live | ended | failed
            $table->string('status', 16)->default('draft')->index();
            // Nguồn: 'app' (tạo trong app) | 'sync' (đồng bộ từ sàn).
            $table->string('source', 8)->default('app');
            $table->json('last_error')->nullable();
            $table->timestamp('pushed_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['channel_account_id', 'external_promotion_id'], 'uq_promo_account_ext');
        });

        Schema::create('channel_promotion_skus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('promotion_id')->index();
            $table->foreignId('channel_listing_id')->nullable()->index();
            $table->string('external_product_id')->nullable();
            $table->string('external_sku_id')->nullable();
            $table->string('seller_sku')->nullable();
            $table->unsignedBigInteger('base_price')->default(0);
            $table->string('discount_type', 16)->default('fixed');
            $table->unsignedBigInteger('discount_value')->default(0); // percent: 1-99 ; fixed: giá sale
            $table->unsignedBigInteger('sale_price')->default(0);
            $table->string('push_status', 12)->default('pending'); // pending | ok | failed
            $table->string('error')->nullable();
            $table->timestamps();

            $table->index(['promotion_id', 'external_sku_id'], 'idx_promo_sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_promotion_skus');
        Schema::dropIfExists('channel_promotions');
    }
};
