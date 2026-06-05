<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * campaign_ai_insights — cached AI analysis per single campaign (one latest row).
 * Generated on-demand (cooldown-guarded; re-runs when analysis params change).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_ai_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('ad_account_id')->index();
            $table->string('campaign_external_id')->index();
            $table->json('payload');                 // AI result
            $table->json('params');                  // { days, metrics:[...], include_engagement }
            $table->string('provider_code')->nullable();
            $table->string('model')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();
            $table->unique(['ad_account_id', 'campaign_external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_ai_insights');
    }
};
