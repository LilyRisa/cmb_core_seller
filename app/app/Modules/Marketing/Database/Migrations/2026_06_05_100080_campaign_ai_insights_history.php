<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Keep a HISTORY of per-campaign AI analyses (was one-latest-row). Drop the unique
 * so each generation inserts a new row; an index keeps "latest per campaign" fast.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_ai_insights', function (Blueprint $table) {
            $table->dropUnique(['ad_account_id', 'campaign_external_id']);
            $table->index(['ad_account_id', 'campaign_external_id'], 'cai_account_campaign_idx');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_ai_insights', function (Blueprint $table) {
            $table->dropIndex('cai_account_campaign_idx');
            $table->unique(['ad_account_id', 'campaign_external_id']);
        });
    }
};
