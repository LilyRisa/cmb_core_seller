<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mở rộng `channel_accounts.shop_region` từ `varchar(8)` → `varchar(32)` để chứa được mã không phải ISO
 * 2 ký tự (vd Lazada `/seller/get` từng trả `location` free-form như "Hà Nội (Mới)"). LazadaMappers nay
 * đã chuẩn hoá về 2 ký tự ISO trước khi lưu, nhưng widen cột để vĩnh viễn không gặp lại SQLSTATE 22001.
 *
 * Kèm: `seller_type` mặc định là `varchar(255)` (Laravel `string()`); một số sàn (Lazada `seller_type`)
 * cũng có thể trả tên dài tiếng Việt — giữ nguyên `string()` (varchar(255)) là an toàn rồi, không cần đổi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channel_accounts', function (Blueprint $table) {
            $table->string('shop_region', 32)->default('VN')->change();
        });
    }

    public function down(): void
    {
        Schema::table('channel_accounts', function (Blueprint $table) {
            $table->string('shop_region', 8)->default('VN')->change();
        });
    }
};
