<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - channel_accounts.display_name: an alias the seller sets at connect time, since
 *   two shops can share the same shop_name. UI shows display_name ?? shop_name.
 * - webhook_events.order_raw_status: the order status carried by the push (if any),
 *   so we can update an existing order even when re-fetching the detail fails.
 * See the Phase-2 follow-up changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channel_accounts', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('shop_name');
        });
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->string('order_raw_status')->nullable()->after('external_shop_id');
        });
    }

    public function down(): void
    {
        Schema::table('channel_accounts', function (Blueprint $table) {
            $table->dropColumn('display_name');
        });
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropColumn('order_raw_status');
        });
    }
};
