<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Plan C (2026-07-23) — lượt xem trang "Chung" theo user (1 dòng/user, idempotent qua unique). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('general_notification_page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('general_notification_pages')->cascadeOnDelete();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('user_id')->index();
            $table->timestamp('viewed_at');
            $table->timestamps();

            $table->unique(['page_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_notification_page_views');
    }
};
