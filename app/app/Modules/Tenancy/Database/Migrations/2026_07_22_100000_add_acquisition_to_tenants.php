<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tenants.acquisition — dữ liệu UTM/fbclid/fbp/fbc/landing_page bắt được lúc đăng ký
 * (first-touch, ghi 1 lần, bất biến). Tách khỏi `settings` (settings = hành vi tenant
 * tự chỉnh). Dùng cho báo cáo Growth attribution + báo cáo Conversions API Meta
 * (SPEC 2026-07-22-facebook-pixel-capi-growth-attribution-design.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->json('acquisition')->nullable()->after('settings');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('acquisition');
        });
    }
};
