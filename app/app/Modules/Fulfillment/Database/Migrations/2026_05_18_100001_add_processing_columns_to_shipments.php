<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Order-processing columns on `shipments` (SPEC 0009): how many times the shipping
 * label was printed (`print_count` + `last_printed_at` — UI shows "đã in N lần" and
 * pops a confirm from the 2nd print) and when the parcel was packed (`packed_at` — the
 * new `packed` status sits between `created` and `picked_up`/handover).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->unsignedInteger('print_count')->default(0)->after('label_path');
            $table->timestamp('last_printed_at')->nullable()->after('print_count');
            $table->timestamp('packed_at')->nullable()->after('picked_up_at');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['print_count', 'last_printed_at', 'packed_at']);
        });
    }
};
