<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 0023 — `voucher_redemptions` ghi lại mỗi lần voucher được dùng:
 *   - user redeem ở checkout ⇒ invoice_id != null, discount_amount > 0
 *   - admin grant ⇒ invoice_id null, granted_days > 0 hoặc swap plan (subscription_id != null)
 *
 * Unique `(voucher_id, tenant_id, invoice_id)` (partial, where invoice_id IS NOT NULL)
 * chống double-redeem cùng voucher cho cùng invoice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->index();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('user_id')->nullable();           // user redeem ở checkout (null nếu admin grant)
            $table->foreignId('invoice_id')->nullable();        // null nếu admin grant ngoài checkout
            $table->foreignId('subscription_id')->nullable();
            $table->bigInteger('discount_amount')->default(0);  // VND, > 0 cho checkout
            $table->integer('granted_days')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['voucher_id', 'tenant_id']);
        });

        // Partial unique chống double-redeem voucher cho cùng invoice. PG + SQLite OK.
        if (in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
            DB::statement(
                'CREATE UNIQUE INDEX voucher_redemptions_unique_per_invoice '.
                'ON voucher_redemptions (voucher_id, tenant_id, invoice_id) '.
                'WHERE invoice_id IS NOT NULL'
            );
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
            DB::statement('DROP INDEX IF EXISTS voucher_redemptions_unique_per_invoice');
        }
        Schema::dropIfExists('voucher_redemptions');
    }
};
