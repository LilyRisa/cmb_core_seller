<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('type', 24);              // topup|order_payment|refund|adjustment
            $table->bigInteger('amount');            // signed: + nạp/hoàn, − trừ đơn
            $table->bigInteger('balance_after');
            $table->string('payment_method', 16)->nullable(); // cash|bank|ewallet (topup)
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->string('note', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['tenant_id', 'customer_id', 'id']);
            // Idempotency: tối đa 1 order_payment + 1 refund mỗi đơn. (order_id NULL cho topup ⇒ Postgres coi distinct.)
            $table->unique(['order_id', 'type'], 'cwt_order_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_wallet_transactions');
    }
};
