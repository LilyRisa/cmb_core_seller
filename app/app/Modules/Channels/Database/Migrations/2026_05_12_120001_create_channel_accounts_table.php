<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * channel_accounts — one connected shop of a tenant on a marketplace (TikTok
 * Shop in Phase 1), with OAuth tokens (encrypted at the app layer) and sync
 * bookkeeping. See docs/01-architecture/multi-tenancy-and-rbac.md, SPEC 0001.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('provider');                       // tiktok | shopee | lazada
            $table->string('external_shop_id');
            $table->string('shop_name')->nullable();
            $table->string('shop_region', 8)->default('VN');
            $table->string('seller_type')->nullable();
            $table->string('status')->default('active');      // active | expired | revoked | disabled
            $table->text('access_token')->nullable();         // encrypted cast
            $table->text('refresh_token')->nullable();        // encrypted cast
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('refresh_token_expires_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_webhook_at')->nullable();
            $table->json('meta')->nullable();                 // { shop_cipher, open_id, scope, ... }  (shop_cipher not a secret but kept here)
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'provider', 'external_shop_id']);
            $table->index(['tenant_id', 'provider', 'status']);
            $table->index(['provider', 'external_shop_id']);  // webhook resolution (no tenant context yet)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_accounts');
    }
};
