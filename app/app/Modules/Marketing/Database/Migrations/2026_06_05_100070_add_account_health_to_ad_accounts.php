<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cache the Facebook ad-account health (account_status + disable_reason) so the
 * dashboard can surface disabled / payment / policy-violation accounts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->unsignedSmallInteger('fb_account_status')->nullable()->after('status');
            $table->unsignedSmallInteger('disable_reason')->nullable()->after('fb_account_status');
            $table->timestamp('health_checked_at')->nullable()->after('disable_reason');
        });
    }

    public function down(): void
    {
        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->dropColumn(['fb_account_status', 'disable_reason', 'health_checked_at']);
        });
    }
};
