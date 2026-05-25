<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * order_returns — after-sales records (cancel / return / refund) from any channel, separate from the
 * order status. Money is bigint VND đồng. Unique per (source, channel_account_id, external_return_id).
 * SoftDeletes (mirror orders — disconnect cleanup). See SPEC 0025.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('channel_account_id')->nullable()->index();
            $table->foreignId('order_id')->nullable()->index();   // đơn gốc nếu khớp; null nếu chưa sync
            $table->string('source');                              // tiktok | shopee | lazada | manual
            $table->string('external_return_id');                  // id return/cancel của sàn
            $table->string('external_order_id')->nullable();       // để resolve order_id về sau
            $table->string('kind')->default('return');             // cancel | return | refund
            $table->string('status');                              // AfterSalesStatus (canonical)
            $table->string('raw_status')->nullable();              // trạng thái gốc sàn
            $table->text('reason')->nullable();
            $table->bigInteger('refund_amount')->default(0);
            $table->string('currency', 8)->default('VND');
            $table->json('items')->nullable();                     // line items hoàn (sku, qty)
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('source_updated_at')->nullable();    // out-of-order guard
            $table->json('raw')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['source', 'channel_account_id', 'external_return_id'], 'order_returns_source_account_external_unique');
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'kind', 'status']);
        });

        // Postgres: partial unique chỉ khi chưa soft-delete (mirror orders) — defense-in-depth.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE order_returns DROP CONSTRAINT IF EXISTS order_returns_source_account_external_unique');
            DB::statement('CREATE UNIQUE INDEX order_returns_source_account_external_unique ON order_returns (source, channel_account_id, external_return_id) WHERE deleted_at IS NULL');
        }

        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'has_return')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->boolean('has_return')->default(false)->index();   // có ≥1 đơn hoàn/hủy đang mở
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_returns');
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'has_return')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('has_return');
            });
        }
    }
};
