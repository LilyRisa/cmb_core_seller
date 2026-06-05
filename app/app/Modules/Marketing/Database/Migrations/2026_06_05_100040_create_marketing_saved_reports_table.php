<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * marketing_saved_reports — a snapshot of one report run (level + date range +
 * filters + the rows captured at that moment), tenant-scoped, reviewable over time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_saved_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('ad_account_id')->index();
            $table->foreignId('created_by')->nullable();
            $table->string('name');
            $table->string('level');                 // campaign|adset|ad
            $table->date('since');
            $table->date('until');
            $table->string('currency')->nullable();
            $table->json('filters');                 // the filters used
            $table->json('snapshot');                // rows captured at save time
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_saved_reports');
    }
};
