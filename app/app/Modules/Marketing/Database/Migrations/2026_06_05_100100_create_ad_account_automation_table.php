<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ad_account_automation — which tenant/connection "owns" automation + writes for a
 * Facebook ad account that is connected by several tenants. One row per FB account
 * (provider + external_account_id). Lets ownership be transferred (take-over).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_account_automation', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('external_account_id');
            $table->foreignId('owner_ad_account_id');
            $table->foreignId('owner_tenant_id');
            $table->timestamps();
            $table->unique(['provider', 'external_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_account_automation');
    }
};
