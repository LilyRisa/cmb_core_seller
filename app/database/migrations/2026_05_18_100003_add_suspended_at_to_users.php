<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec 2026-05-17 — super-admin có thể tạm khoá tenant user qua endpoint
 * `/api/v1/admin/users/{id}/suspend`. EnsureTenant middleware kiểm cột này:
 * suspended_at != null ⇒ 403 USER_SUSPENDED. Reactivate = clear cột.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->after('email_verified_at');
            $table->index('suspended_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['suspended_at']);
            $table->dropColumn('suspended_at');
        });
    }
};
