<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * webhook_events — raw inbound marketplace/carrier webhooks. Stored verbatim
 * (payload jsonb) so they can be re-driven after a mapping fix; deduped by
 * (provider, event_type, external_id). `tenant_id`/`channel_account_id` are
 * resolved during processing. See docs/03-domain/order-sync-pipeline.md §2.
 *
 * 02-data-model rule 9 lists this as a monthly-partition target — kept a plain
 * table in Phase 1; convert when volume warrants (helper MonthlyPartition + the
 * db:partitions:ensure command are in place). See SPEC 0001 §5.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('event_type');                     // normalized WebhookEventDTO type
            $table->string('external_id')->nullable();        // order id / notification id / ...
            $table->string('external_shop_id')->nullable();
            $table->unsignedSmallInteger('raw_type')->nullable(); // provider's numeric type, if any
            $table->foreignId('tenant_id')->nullable()->index();
            $table->foreignId('channel_account_id')->nullable()->index();
            $table->boolean('signature_ok')->default(false);
            $table->json('headers')->nullable();
            $table->json('payload');
            $table->string('status')->default('pending');     // pending | processed | ignored | failed
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'event_type', 'external_id']); // dedupe lookup
            $table->index(['status', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
