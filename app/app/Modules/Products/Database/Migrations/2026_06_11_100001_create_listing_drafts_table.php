<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('product_id')->index();
            $table->foreignId('channel_account_id')->index();
            $table->string('provider', 32);
            $table->string('external_item_id')->nullable()->index();
            $table->string('category_id')->nullable();
            $table->string('brand_id')->nullable();
            $table->json('attributes')->nullable();
            $table->json('media_refs')->nullable();
            $table->json('logistics')->nullable();
            $table->string('status', 16)->default('draft');
            $table->json('validation_errors')->nullable();
            $table->string('raw_qc_status')->nullable();
            $table->json('last_error')->nullable();
            $table->timestamp('pushed_at')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'product_id', 'channel_account_id'], 'uq_draft_product_shop');
            $table->index(['tenant_id', 'provider', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_drafts');
    }
};
