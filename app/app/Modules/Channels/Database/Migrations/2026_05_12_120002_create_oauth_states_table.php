<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * oauth_states — short-lived CSRF state for the OAuth connect flow. The
 * callback has no trusted session, so the tenant is resolved from this row
 * by `state`. Pruned by age. See docs/05-api/webhooks-and-oauth.md §2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_states', function (Blueprint $table) {
            $table->id();
            $table->string('state', 64)->unique();
            $table->string('provider');
            $table->foreignId('tenant_id')->index();
            $table->foreignId('created_by')->nullable();
            $table->string('redirect_after')->nullable();     // SPA path to land on after callback
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_states');
    }
};
