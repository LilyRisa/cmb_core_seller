<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * inventory_levels — stock of one SKU in one warehouse. `available_cached` =
 * max(0, on_hand - reserved - safety_stock). See docs/03-domain/inventory-and-sku-mapping.md §1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('sku_id');
            $table->foreignId('warehouse_id');
            $table->integer('on_hand')->default(0);
            $table->integer('reserved')->default(0);
            $table->integer('safety_stock')->default(0);
            $table->integer('available_cached')->default(0);
            $table->boolean('is_negative')->default(false);   // sold beyond stock — push 0 + warn
            $table->timestamps();

            $table->unique(['sku_id', 'warehouse_id']);
            $table->index(['tenant_id', 'sku_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_levels');
    }
};
