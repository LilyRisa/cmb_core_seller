<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ad_drafts — wizard work-in-progress for ONE ad (1 draft = 1 campaign/adset/ad in v1).
 * Loosely validated (payload JSON holds the step state); strict validation + the
 * Facebook external ids are filled at publish time (PublishAdDraft, Plan 4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('ad_account_id')->index();
            $table->foreignId('created_by')->nullable();
            $table->string('name')->nullable();
            $table->string('status')->default('draft');
            $table->string('objective')->nullable();
            $table->json('payload')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->string('campaign_external_id')->nullable();
            $table->string('adset_external_id')->nullable();
            $table->string('ad_external_id')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'ad_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_drafts');
    }
};
