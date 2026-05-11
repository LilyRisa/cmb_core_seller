<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * orders — every order, from any source (TikTok in Phase 1; manual & other
 * channels later). Money is bigint VND đồng. Status is the canonical code +
 * raw_status from the channel. See docs/03-domain/order-status-state-machine.md,
 * docs/02-data-model/overview.md (module Orders). 02-data-model rule 9 lists
 * this as a partition target — plain table in Phase 1, see SPEC 0001 §5.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('source');                          // tiktok | shopee | lazada | manual
            $table->foreignId('channel_account_id')->nullable()->index();
            $table->string('external_order_id')->nullable();
            $table->string('order_number')->nullable();
            $table->string('status');                          // StandardOrderStatus
            $table->string('raw_status')->nullable();
            $table->string('payment_status')->nullable();      // unpaid | paid | refunded | partial_refund
            $table->string('buyer_name')->nullable();
            $table->text('buyer_phone')->nullable();           // encrypted cast
            $table->json('shipping_address')->nullable();
            $table->string('currency', 8)->default('VND');
            $table->bigInteger('item_total')->default(0);
            $table->bigInteger('shipping_fee')->default(0);
            $table->bigInteger('platform_discount')->default(0);
            $table->bigInteger('seller_discount')->default(0);
            $table->bigInteger('tax')->default(0);
            $table->bigInteger('cod_amount')->default(0);
            $table->bigInteger('grand_total')->default(0);
            $table->boolean('is_cod')->default(false);
            $table->string('fulfillment_type')->nullable();    // e.g. TIKTOK / SELLER
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->text('note')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('has_issue')->default(false);
            $table->string('issue_reason')->nullable();
            $table->json('packages')->nullable();              // [{ externalPackageId, trackingNo, carrier, status }]
            $table->json('raw_payload')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['source', 'channel_account_id', 'external_order_id'], 'orders_source_account_external_unique');
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'source', 'placed_at']);
            $table->index(['tenant_id', 'has_issue']);
            $table->index(['tenant_id', 'placed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
