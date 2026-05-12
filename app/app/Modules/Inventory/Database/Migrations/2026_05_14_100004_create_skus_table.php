<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** skus — master SKU: the single source of truth for stock (ADR-0008). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('product_id')->nullable();
            $table->string('sku_code');
            $table->string('barcode')->nullable();
            $table->string('name');
            $table->bigInteger('cost_price')->default(0);   // VND đồng
            $table->json('attributes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'sku_code']);
            $table->index(['tenant_id', 'barcode']);
            $table->index(['tenant_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skus');
    }
};
