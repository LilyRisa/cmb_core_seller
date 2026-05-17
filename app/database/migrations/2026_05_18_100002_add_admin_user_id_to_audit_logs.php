<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec 2026-05-17 — admin tách lập. Action do super-admin thực hiện không có
 * `users.id` (admin ở bảng `admin_users` riêng) ⇒ cần cột thứ hai `admin_user_id`
 * để audit phân biệt rõ actor. Không FK cứng để xoá admin không kéo audit history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_user_id')->nullable()->after('user_id');
            $table->index('admin_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['admin_user_id']);
            $table->dropColumn('admin_user_id');
        });
    }
};
