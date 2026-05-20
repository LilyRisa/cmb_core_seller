<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `conversations` — header của 1 cuộc hội thoại buyer↔shop trên 1 nền tảng.
 *
 * KHÔNG partition (ADR-0020): cột `last_message_at` UPDATE thường xuyên — partition
 * theo cột này gây di chuyển row giữa partitions (không khả thi). Index
 * `(tenant_id, status, last_message_at DESC)` đủ trong 2–3 năm với scale dự kiến.
 *
 * Unique `(channel_account_id, external_conversation_id)` — chống duplicate khi
 * webhook + polling cùng về 1 lúc.
 *
 * SPEC-0024 §5.2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('channel_account_id');
            $table->string('provider', 32);                                  // denorm cho query
            $table->string('external_conversation_id');
            $table->string('buyer_external_id');
            $table->string('buyer_name')->nullable();
            $table->string('buyer_avatar_url', 512)->nullable();
            $table->foreignId('customer_id')->nullable();                    // FK soft — Customers module
            $table->foreignId('order_id')->nullable();                       // FK soft — Orders module
            $table->string('status', 16)->default('open');                   // open|snoozed|resolved|spam
            $table->timestamp('snoozed_until')->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->unsignedInteger('message_count')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->string('last_message_preview', 200)->nullable();
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamp('last_outbound_at')->nullable();
            $table->foreignId('assigned_user_id')->nullable();
            $table->json('tags')->nullable();                                // string[]
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['channel_account_id', 'external_conversation_id']);
            $table->index(['tenant_id', 'status', 'last_message_at']);
            $table->index(['tenant_id', 'customer_id', 'last_message_at']);
            $table->index(['tenant_id', 'assigned_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
