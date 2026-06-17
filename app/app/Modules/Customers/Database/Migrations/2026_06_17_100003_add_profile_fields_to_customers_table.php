<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm thông tin hồ sơ khách nhập tay từ màn tạo đơn thủ công (modal "Thông tin
 * khách hàng" — SPEC 0038 v2): avatar, nguồn khách, ngày sinh, địa chỉ. Persist
 * bền vững vào hồ sơ khách (không phải PII nhạy cảm như phone ⇒ không mã hoá).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('avatar_url')->nullable()->after('name');
            $table->string('source', 64)->nullable()->after('avatar_url');   // nguồn khách: facebook/zalo/website/...
            $table->date('dob')->nullable()->after('source');
            $table->string('address')->nullable()->after('dob');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['avatar_url', 'source', 'dob', 'address']);
        });
    }
};
