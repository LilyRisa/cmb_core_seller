<?php

use CMBcoreSeller\Support\Database\PartitionRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `messages` — bảng lớn nhất. ADR-0020 dự kiến PARTITION RANGE theo
 * `created_at` tháng khi volume cần. S1: giữ bảng phẳng + index — pattern
 * giống `orders` Phase 1 (xem note migration `create_orders_table.php`).
 * Khi nâng partition: tạo migration tách + register vào PartitionRegistry +
 * scheduler tự roll forward; KHÔNG cần đổi model.
 *
 * Unique `(conversation_id, external_message_id)` chống dedupe webhook
 * (SPEC-0024 §4.1). External null cho outbound chưa nhận echo-back từ sàn —
 * unique partial WHERE NOT NULL trên Postgres, ở MySQL/SQLite vẫn an toàn vì
 * outbound dùng `(conversation_id, NULL)` không trùng nhau.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('conversation_id');
            $table->string('external_message_id')->nullable();
            $table->string('direction', 16);                            // inbound|outbound
            $table->string('kind', 16);                                 // text|image|video|file|template|system
            $table->text('body')->nullable();
            $table->unsignedSmallInteger('attachments_count')->default(0);
            $table->foreignId('sent_by_user_id')->nullable();
            $table->boolean('sent_by_ai')->default(false);
            $table->string('delivery_status', 16)->default('pending');  // pending|sent|delivered|read|failed
            $table->string('failure_code', 64)->nullable();
            $table->foreignId('reply_to_message_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->json('raw_payload')->nullable();                    // purge sau 30d (PruneMessagingPayloads)
            $table->json('meta')->nullable();                           // {auto_rule_id?, template_id?, ai_run_id?}
            $table->timestamps();

            $table->unique(['conversation_id', 'external_message_id'], 'messages_conv_external_unique');
            $table->index(['conversation_id', 'created_at']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['delivery_status', 'created_at']);
        });

        // Pre-register placeholder để khi nâng partition sau, code path đã có.
        // Không gọi `MonthlyPartition::createTable` ở S1 vì bảng phẳng.
        PartitionRegistry::register('messages', 'created_at');
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
