<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * orders.carrier — denormalized primary shipping carrier (from packages[0].carrier),
 * so the orders list can filter by carrier and show per-carrier counts cheaply.
 * Set by OrderUpsertService; null until a shipment/package carries one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('carrier')->nullable()->after('fulfillment_type');
            $table->index(['tenant_id', 'carrier']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'carrier']);
            $table->dropColumn('carrier');
        });
    }
};
