<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `messaging_account_meta` — 1-1 với `channel_accounts`. Lưu metadata
 * messaging-specific tách khỏi bảng Channels (Channels không sở hữu — đặt ở
 * Messaging module). SPEC-0024 §5.1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messaging_account_meta', function (Blueprint $table) {
            $table->foreignId('channel_account_id')->primary();
            $table->foreignId('tenant_id')->index();
            $table->boolean('messaging_enabled')->default(false);
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamp('last_outbound_at')->nullable();
            $table->json('outbound_window_meta')->nullable();        // snapshot từ connector.outboundWindow()
            $table->boolean('ai_enabled')->default(false);
            $table->text('settings')->nullable();                    // encrypted:array — per-shop overrides
            $table->timestamps();

            $table->index(['tenant_id', 'last_inbound_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messaging_account_meta');
    }
};
