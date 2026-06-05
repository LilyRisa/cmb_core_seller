<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ad_monitor_actions — history of what the auto-monitors did (pause / raise budget),
 * so it can be reviewed and deleted even though it was also emailed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_monitor_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('ad_account_id')->index();
            $table->foreignId('ad_monitor_id')->nullable()->index();
            $table->string('target_level');
            $table->string('target_external_id')->index();
            $table->string('target_name')->nullable();
            $table->string('type');                  // pause | increase
            $table->unsignedBigInteger('cpr')->nullable();
            $table->unsignedBigInteger('spend')->nullable();
            $table->unsignedBigInteger('results')->nullable();
            $table->unsignedBigInteger('from_budget')->nullable();
            $table->unsignedBigInteger('to_budget')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_monitor_actions');
    }
};
