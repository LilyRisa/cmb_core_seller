<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `flow_runs` — state máy thực thi flow THEO TỪNG hội thoại.
 * Unique partial (flow_id, conversation_id) WHERE status IN ('active','waiting')
 * ⇒ một hội thoại chỉ có 1 run đang chạy / flow (chống double-enter, idempotent).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('flow_id');
            $table->foreignId('conversation_id');
            $table->string('current_node_id')->nullable();
            $table->string('status', 16)->default('active'); // active|waiting|completed|ended|failed
            $table->json('context')->nullable();
            $table->string('error')->nullable();
            $table->timestamp('entered_at')->nullable();
            $table->timestamp('last_advanced_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'conversation_id', 'status']);
        });

        DB::statement(
            "CREATE UNIQUE INDEX flow_runs_one_active_per_conv
             ON flow_runs (flow_id, conversation_id)
             WHERE status IN ('active','waiting')"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_runs');
    }
};
