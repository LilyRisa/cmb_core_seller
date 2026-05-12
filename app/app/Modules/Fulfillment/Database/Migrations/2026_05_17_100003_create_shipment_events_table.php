<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** shipment_events — tracking timeline per shipment (carrier scans + system actions). SPEC 0006 §5. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('shipment_id');
            $table->string('code');                       // carrier status code or internal action (e.g. 'created', 'packed_scanned')
            $table->string('description')->nullable();
            $table->string('status')->nullable();         // derived shipment status at this event
            $table->timestamp('occurred_at');
            $table->string('source')->default('carrier'); // carrier|system|user
            $table->json('raw')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['shipment_id', 'code', 'occurred_at']);
            $table->index(['tenant_id', 'shipment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_events');
    }
};
