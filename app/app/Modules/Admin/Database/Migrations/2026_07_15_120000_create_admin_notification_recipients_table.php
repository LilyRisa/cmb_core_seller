<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 2026-07-15 — email nhận thông báo cấp nền tảng. KHÔNG tenant-scoped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('label')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notification_recipients');
    }
};
