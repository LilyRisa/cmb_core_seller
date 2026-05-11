<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * sync_runs — one record per order-sync run (poll / backfill / webhook),
 * with cursor + stats so a failed run resumes and the "sync log" UI can show
 * what happened. See docs/03-domain/order-sync-pipeline.md §3, §5.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('channel_account_id')->index();
            $table->string('type');                           // poll | backfill | webhook
            $table->string('status')->default('running');     // running | done | failed
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('finished_at')->nullable();
            $table->string('cursor')->nullable();
            $table->json('stats')->nullable();                // { fetched, created, updated, skipped, errors }
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['channel_account_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_runs');
    }
};
