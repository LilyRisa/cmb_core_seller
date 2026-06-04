<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ad_insight_snapshots — latest insight metrics per (entity, window, date range).
 * Upserted in place by SyncAdInsights (idempotent); `is_finalizing` marks rows
 * still inside Facebook's 28-day re-attribution window (numbers may fluctuate).
 * Money fields are minor units (Phase 1 targets VND / zero-decimal).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_insight_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('ad_account_id')->index();
            $table->foreignId('ad_entity_id')->nullable()->index();
            $table->string('level');                 // account | campaign | adset | ad
            $table->string('external_id');           // entity id this row is for
            $table->date('date_start');
            $table->date('date_stop');
            $table->string('window')->default('today'); // today | last_7d | ...
            $table->boolean('is_finalizing')->default(false);
            $table->unsignedBigInteger('spend')->default(0);
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('reach')->default(0);
            $table->decimal('ctr', 8, 4)->nullable();
            $table->unsignedBigInteger('cpc')->nullable();
            $table->unsignedBigInteger('cpm')->nullable();
            $table->decimal('frequency', 8, 4)->nullable();
            $table->decimal('purchase_roas', 10, 4)->nullable();
            $table->json('metrics')->nullable();
            $table->timestamp('fetched_at')->index();
            $table->timestamps();

            $table->unique(['ad_account_id', 'level', 'external_id', 'window', 'date_start', 'date_stop'], 'ad_insight_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_insight_snapshots');
    }
};
