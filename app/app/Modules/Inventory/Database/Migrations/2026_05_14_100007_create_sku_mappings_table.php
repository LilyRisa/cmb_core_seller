<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * sku_mappings — links a channel_listing to one or more master SKUs (× quantity).
 * `type=single`: 1 line. `type=bundle`: many lines (combo). See docs/03-domain/inventory-and-sku-mapping.md §1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sku_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('channel_listing_id');
            $table->foreignId('sku_id');
            $table->integer('quantity')->default(1);
            $table->string('type', 16)->default('single');   // single | bundle
            $table->foreignId('created_by')->nullable();
            $table->timestamps();

            $table->unique(['channel_listing_id', 'sku_id']);
            $table->index(['tenant_id', 'channel_listing_id']);
            $table->index(['tenant_id', 'sku_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sku_mappings');
    }
};
