<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * order_status_history — append-only; one row per status change. See
 * docs/03-domain/order-status-state-machine.md §3 rule 4. SPEC 0001 §5.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('order_id')->index();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('raw_status')->nullable();
            $table->string('source');                          // channel | polling | webhook | user | system | carrier
            $table->timestamp('changed_at')->useCurrent();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['order_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
    }
};
