<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec 2026-05-17 — bảng super-admin tách lập.
 *
 * Không belongsToMany Tenant; không trộn vào `users`. Mọi route /api/v1/admin/*
 * dùng guard `admin` (Sanctum) hoặc `admin_web` (session) trỏ vào provider
 * `admin_users`. Promote/demote qua `php artisan admin:create|reset-password|
 * promote|demote` (xem Console/Commands).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 32)->unique();
            $table->string('email')->nullable()->unique();
            $table->string('name');
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestamps();
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_users');
    }
};
