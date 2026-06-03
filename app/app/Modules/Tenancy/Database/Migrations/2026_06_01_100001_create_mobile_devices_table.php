<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mobile device registry (Expo Push tokens) — SPEC 0029.
 *
 * Tách hoàn toàn khỏi Web Push (`messaging_push_subscriptions`). KHÔNG dùng
 * BelongsToTenant: `messaging:push-digest` quét cross-tenant trong scheduler
 * (không có request tenant context) — giống `PushSubscription`. tenant_id set
 * tường minh ở controller. `expo_push_token` UNIQUE toàn bảng: 1 thiết bị đổi
 * user ⇒ upsert cập nhật user_id/tenant_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('expo_push_token', 255)->unique();
            $table->string('platform', 20); // ios | android
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_notified_at')->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_devices');
    }
};
