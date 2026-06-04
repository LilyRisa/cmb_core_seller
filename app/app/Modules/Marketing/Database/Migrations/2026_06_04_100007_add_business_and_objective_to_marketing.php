<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Business Manager grouping on ad accounts + campaign objective on entities
 * (Ads-Manager-style report). SPEC 2026-06-04.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->string('business_id')->nullable()->after('provider');
            $table->string('business_name')->nullable()->after('business_id');
        });
        Schema::table('ad_entities', function (Blueprint $table) {
            $table->string('objective')->nullable()->after('effective_status');
        });
    }

    public function down(): void
    {
        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->dropColumn(['business_id', 'business_name']);
        });
        Schema::table('ad_entities', function (Blueprint $table) {
            $table->dropColumn('objective');
        });
    }
};
