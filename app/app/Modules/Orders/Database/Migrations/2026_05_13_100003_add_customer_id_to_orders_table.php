<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * orders.customer_id — soft link to the internal customer registry (Phase 2 / SPEC 0002).
 * Nullable: an order may not match any customer (masked phone). No DB-level FK
 * (cross-module soft reference, like channel_account_id); merge/anonymize keep it
 * consistent in code. See docs/02-data-model/overview.md, docs/01-architecture/modules.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->after('channel_account_id');
            $table->index(['tenant_id', 'customer_id', 'placed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'customer_id', 'placed_at']);
            $table->dropColumn('customer_id');
        });
    }
};
