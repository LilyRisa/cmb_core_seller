<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ad_accounts — one connected Facebook ad account (act_<id>) of a tenant, with its
 * OWN OAuth token (ads_read; separate from page/messaging tokens). See
 * docs/superpowers/specs/2026-06-04-facebook-ads-realtime-ai-design.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('provider')->default('facebook');   // ads provider code
            $table->string('external_account_id');             // act_<id>
            $table->string('name')->nullable();
            $table->string('currency', 8)->nullable();
            $table->string('status')->default('active');        // active | expired | revoked | disabled
            $table->text('access_token')->nullable();           // encrypted cast
            $table->text('refresh_token')->nullable();          // encrypted cast (long-lived/system-user)
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('insights_synced_at')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'provider', 'external_account_id']);
            $table->index(['provider', 'external_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_accounts');
    }
};
