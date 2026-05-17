<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec 2026-05-17 — bảng token reset password cho `admin_users`. Tách bảng riêng
 * khỏi `password_reset_tokens` của user (để Laravel password broker `admin_users`
 * trỏ vào đây — xem `config/auth.php`). Primary key = email vì admin có thể không
 * có email (chỉ admin có email mới reset qua email; broker bỏ qua admin không
 * có email).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_password_reset_tokens');
    }
};
