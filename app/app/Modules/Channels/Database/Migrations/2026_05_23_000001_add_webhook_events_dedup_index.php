<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit fix: thêm external_shop_id vào dedup index của webhook_events để dedup
 * đúng scope per shop (tránh cross-tenant false duplicate). Xem WebhookIngestService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropIndex(['provider', 'event_type', 'external_id']);
            $table->index(['provider', 'event_type', 'external_id', 'external_shop_id'], 'webhook_events_dedup_idx');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropIndex('webhook_events_dedup_idx');
            $table->index(['provider', 'event_type', 'external_id']);
        });
    }
};
