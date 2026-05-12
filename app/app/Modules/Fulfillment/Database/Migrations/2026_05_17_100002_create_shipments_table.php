<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** shipments — one parcel for an order via a carrier. SPEC 0006 §5; domain doc §1. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('order_id');
            $table->string('carrier');
            $table->foreignId('carrier_account_id')->nullable();
            $table->string('package_no')->nullable();
            $table->string('tracking_no')->nullable();
            $table->string('status')->default('pending');   // pending|created|picked_up|in_transit|delivered|failed|returned|cancelled
            $table->string('service')->nullable();
            $table->unsignedInteger('weight_grams')->nullable();
            $table->json('dims')->nullable();
            $table->bigInteger('cod_amount')->default(0);
            $table->bigInteger('fee')->default(0);          // estimated shipping fee (VND đồng)
            $table->string('label_url')->nullable();        // public URL on the media disk
            $table->string('label_path')->nullable();       // object key on the media disk
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'order_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'tracking_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
