<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Web Push subscription cho thông báo tin nhắn mới khi tab đóng/ẩn.
 * `last_seen_at`: heartbeat khi tab visible ⇒ digest chỉ push cho sub KHÔNG hoạt động.
 * `last_notified_at`: mốc lần push gần nhất ⇒ digest đếm inbound mới sau mốc này.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messaging_push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->text('endpoint');
            $table->string('p256dh', 255);
            $table->string('auth', 255);
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->unique('endpoint');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messaging_push_subscriptions');
    }
};
