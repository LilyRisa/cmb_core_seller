<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 0021 (mở rộng) — danh mục địa chỉ hành chính VN, hai hệ song song:
 *
 *  - **NEW (2 cấp)** từ `addresskit.cas.so` — sau cải cách 2025 (chỉ còn Tỉnh + Phường/Xã).
 *  - **OLD (3 cấp)** từ `provinces.open-api.vn` — Tỉnh / Quận-Huyện / Phường-Xã (chuẩn cũ).
 *
 * Cả hai dùng `format` enum để chia chung 1 schema (giảm số bảng). `admin_districts` chỉ phục
 * vụ format = 'old' (NEW không còn cấp quận). `admin_wards` có `district_code` nullable để chứa
 * cả 2 cấp dữ liệu (NEW → null, OLD → district code).
 *
 * Lookup theo (format, code) để API `/master-data/*` đọc trực tiếp DB, không gọi external API
 * mỗi request. Idempotent upsert qua command `addresses:sync`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_provinces', function (Blueprint $t) {
            $t->id();
            $t->string('format', 8)->index();   // 'new' | 'old'
            $t->string('code', 16);
            $t->string('name', 160);
            $t->string('english_name', 255)->nullable();
            $t->string('division_type', 64)->nullable();   // 'thành phố trung ương' / 'tỉnh' / 'Thành phố Trung ương' / 'Tỉnh'
            $t->string('codename', 160)->nullable();        // OLD-only — snake_case slug
            $t->integer('phone_code')->nullable();          // OLD-only
            $t->string('decree', 255)->nullable();          // NEW-only — nghị quyết hợp nhất
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->unique(['format', 'code']);
            $t->index('name');
        });

        // OLD only.
        Schema::create('admin_districts', function (Blueprint $t) {
            $t->id();
            $t->string('province_code', 16)->index();
            $t->string('code', 16);
            $t->string('name', 200);
            $t->string('codename', 200)->nullable();
            $t->string('division_type', 64)->nullable();   // 'quận' | 'huyện' | 'thị xã' | 'thành phố' (thuộc tỉnh)
            $t->timestamps();
            $t->unique('code');   // mã quận VN unique global
            $t->index('name');
        });

        Schema::create('admin_wards', function (Blueprint $t) {
            $t->id();
            $t->string('format', 8)->index();
            $t->string('code', 16);
            $t->string('province_code', 16)->index();        // NEW + OLD đều có
            $t->string('district_code', 16)->nullable()->index();   // OLD only; NEW = null
            $t->string('name', 200);
            $t->string('english_name', 255)->nullable();
            $t->string('codename', 200)->nullable();
            $t->string('division_type', 64)->nullable();   // 'phường' | 'xã' | 'thị trấn' | 'đặc khu' …
            $t->string('decree', 255)->nullable();          // NEW-only
            $t->timestamps();
            $t->unique(['format', 'code']);
            $t->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_wards');
        Schema::dropIfExists('admin_districts');
        Schema::dropIfExists('admin_provinces');
    }
};
