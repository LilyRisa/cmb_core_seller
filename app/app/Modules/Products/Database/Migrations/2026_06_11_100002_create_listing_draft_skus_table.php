<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_draft_skus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('listing_draft_id')->index();
            $table->foreignId('master_variant_id')->nullable();
            $table->string('seller_sku');
            $table->json('sale_props')->nullable();
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('stock')->default(0);
            $table->decimal('package_weight', 8, 2)->nullable();
            $table->json('package_dims')->nullable();
            $table->string('external_sku_id')->nullable();
            $table->string('image_ref')->nullable();
            $table->timestamps();

            $table->unique(['listing_draft_id', 'seller_sku'], 'uq_draft_seller_sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_draft_skus');
    }
};
