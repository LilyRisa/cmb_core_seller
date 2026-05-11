<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * order_items — line items of an order. `sku_id` stays null until SKU mapping
 * lands (Phase 2). Money is bigint VND đồng. See SPEC 0001 §5.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('order_id')->index();
            $table->string('external_item_id');
            $table->string('external_product_id')->nullable();
            $table->string('external_sku_id')->nullable();
            $table->string('seller_sku')->nullable();
            $table->foreignId('sku_id')->nullable()->index();  // resolved by SKU mapping (Phase 2)
            $table->string('name');
            $table->string('variation')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->bigInteger('unit_price')->default(0);
            $table->bigInteger('discount')->default(0);
            $table->bigInteger('subtotal')->default(0);
            $table->string('image')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'external_item_id']);
            $table->index(['tenant_id', 'seller_sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
