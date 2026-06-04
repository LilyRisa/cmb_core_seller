<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ad_entities — campaign / ad set / ad tree for an ad account (self-nested via
 * parent_id). Budgets stored as minor units (integer).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_entities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('ad_account_id')->index();
            $table->string('level');                              // campaign | adset | ad
            $table->string('external_id');                        // FB id
            $table->string('parent_external_id')->nullable();     // FB parent id (campaign/adset)
            $table->unsignedBigInteger('parent_id')->nullable();  // local ad_entities.id
            $table->string('name')->nullable();
            $table->string('status')->nullable();                 // ACTIVE | PAUSED | ...
            $table->string('effective_status')->nullable();
            $table->unsignedBigInteger('daily_budget')->nullable();    // minor units
            $table->unsignedBigInteger('lifetime_budget')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['ad_account_id', 'level', 'external_id']);
            $table->index(['ad_account_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_entities');
    }
};
