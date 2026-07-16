<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Giới hạn voucher chỉ áp dụng cho vài tenant cụ thể — null/[] = mọi tenant. Design 2026-07-16. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->json('valid_tenant_ids')->nullable()->after('valid_plans');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn('valid_tenant_ids');
        });
    }
};
