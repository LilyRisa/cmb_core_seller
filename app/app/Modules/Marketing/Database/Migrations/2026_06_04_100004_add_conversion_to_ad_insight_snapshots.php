<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conversion metrics from Insights `actions` — click-to-Messenger conversations
 * and lead-ads leads — reconciled daily against manual orders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_insight_snapshots', function (Blueprint $table) {
            $table->unsignedBigInteger('messaging_conversations')->default(0)->after('purchase_roas');
            $table->unsignedBigInteger('leads')->default(0)->after('messaging_conversations');
        });
    }

    public function down(): void
    {
        Schema::table('ad_insight_snapshots', function (Blueprint $table) {
            $table->dropColumn(['messaging_conversations', 'leads']);
        });
    }
};
