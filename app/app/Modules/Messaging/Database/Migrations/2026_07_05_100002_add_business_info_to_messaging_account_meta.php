<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * business_info (json, nullable) trên messaging_account_meta — thông tin cửa hàng theo PAGE
 * để AI trả lời khi khách hỏi SĐT/địa chỉ/bảo hành... Không mã hoá (thông tin công khai của shop),
 * khác cột `settings` (encrypted) chứa secret.
 * Khoá: shop_name, phone, address, email, warranty_policy, working_hours, website, extra_note.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messaging_account_meta', function (Blueprint $table) {
            $table->json('business_info')->nullable()->after('settings');
        });
    }

    public function down(): void
    {
        Schema::table('messaging_account_meta', function (Blueprint $table) {
            $table->dropColumn('business_info');
        });
    }
};
