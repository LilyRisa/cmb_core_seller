<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 0023 — thêm `payments.meta` (JSON) + `payments.refunded_at`.
 *
 * `meta` lưu method (`bank_transfer`/`cash`...) + reference (mã chuyển khoản) +
 * `marked_by_admin` / `refunded_by_admin` / `refund_reason` (PII-free).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'meta')) {
                $table->json('meta')->nullable()->after('raw_payload');
            }
            if (! Schema::hasColumn('payments', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('occurred_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'meta')) {
                $table->dropColumn('meta');
            }
            if (Schema::hasColumn('payments', 'refunded_at')) {
                $table->dropColumn('refunded_at');
            }
        });
    }
};
