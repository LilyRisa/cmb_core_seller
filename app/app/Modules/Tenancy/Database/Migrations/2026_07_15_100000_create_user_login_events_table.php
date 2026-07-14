<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lịch sử đăng nhập nhân viên tenant (guard `web`, KHÔNG bao gồm admin_web — admin có audit riêng).
 * Design 2026-07-15. Không gắn tenant_id trực tiếp — user có thể thuộc nhiều tenant qua tenant_users,
 * trang admin join qua đó để lọc theo tenant đang xem.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_login_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('logged_in_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'logged_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_login_events');
    }
};
