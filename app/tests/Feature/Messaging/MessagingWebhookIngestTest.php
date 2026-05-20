<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Messaging\Jobs\ProcessMessagingWebhook;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Test luồng webhook → ingest qua `manual` MessagingConnector — verify pipeline
 * E2E mà không cần API ngoài.
 *
 * Scenarios:
 *   - Webhook → 200 OK + dispatch ProcessMessagingWebhook + ghi webhook_events
 *   - 2 webhook trùng (cùng external_message_id) ⇒ 200 'duplicate', không tạo job 2
 *   - Job xử lý → tạo conversation + message; chạy lại idempotent (không double)
 *   - Webhook không có chữ ký hợp lệ ⇒ 401 (manual chỉ active trong non-prod ⇒ pass)
 *   - Provider không tồn tại ⇒ 404
 */
class MessagingWebhookIngestTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        // Manual messaging connector luôn nạp (kể cả khi env trống) — pattern
        // tương tự ManualConnector cho Channels.
        $this->tenant = Tenant::create(['name' => 'MsgTest']);
        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'manual',
            'external_shop_id' => 'shop_manual_1',
            'shop_name' => 'Manual Shop',
            'status' => 'active',
            'messaging_enabled' => true,
        ]);
    }

    public function test_webhook_post_returns_200_and_stores_event(): void
    {
        Queue::fake();

        $res = $this->postJson('/webhook/messaging/manual', [
            'event_type' => 'message_received',
            'external_shop_id' => 'shop_manual_1',
            'external_conversation_id' => 'conv_1',
            'external_message_id' => 'msg_1',
            'buyer_external_id' => 'buyer_xx',
            'body' => 'Hello from buyer',
            'kind' => 'text',
        ]);

        $res->assertOk()->assertJsonPath('ok', true);
        $this->assertSame(1, WebhookEvent::query()->where('provider', 'messaging.manual')->count());
        Queue::assertPushed(ProcessMessagingWebhook::class, 1);
    }

    public function test_duplicate_webhook_returns_200_without_dispatching_job_again(): void
    {
        Queue::fake();

        $payload = [
            'event_type' => 'message_received',
            'external_shop_id' => 'shop_manual_1',
            'external_conversation_id' => 'conv_2',
            'external_message_id' => 'msg_dup',
            'buyer_external_id' => 'b',
            'body' => 'Hi',
        ];

        $this->postJson('/webhook/messaging/manual', $payload)->assertOk();
        $res2 = $this->postJson('/webhook/messaging/manual', $payload)->assertOk()->assertJsonPath('note', 'duplicate');

        $this->assertSame(1, WebhookEvent::query()->where('external_id', 'msg_dup')->count());
        Queue::assertPushed(ProcessMessagingWebhook::class, 1);
    }

    public function test_unknown_provider_returns_404(): void
    {
        // Provider không trong whitelist `whereIn` ⇒ route trả 404 từ Laravel
        $this->postJson('/webhook/messaging/bogus_provider', [])->assertNotFound();
    }

    public function test_processing_webhook_creates_conversation_and_message(): void
    {
        // Không fake queue — chạy luôn (job)
        $this->postJson('/webhook/messaging/manual', [
            'event_type' => 'message_received',
            'external_shop_id' => 'shop_manual_1',
            'external_conversation_id' => 'conv_X',
            'external_message_id' => 'msg_X_1',
            'buyer_external_id' => 'buyer_X',
            'body' => 'Cảm ơn shop',
            'kind' => 'text',
        ])->assertOk();

        // Process pending webhook event (queue:sync trong test ⇒ chạy ngay; nhưng dispatch
        // không tự run ⇒ test manually)
        $event = WebhookEvent::query()->where('provider', 'messaging.manual')->first();
        $this->assertNotNull($event);
        (new ProcessMessagingWebhook($event->id))->handle(
            app(\CMBcoreSeller\Integrations\Messaging\MessagingRegistry::class),
            app(\CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService::class),
        );

        // Webhook chạy cross-tenant (không có tenant context) ⇒ verify queries
        // phải bỏ TenantScope.
        $conv = Conversation::withoutGlobalScopes()->where('external_conversation_id', 'conv_X')->first();
        $this->assertNotNull($conv);
        $this->assertSame($this->tenant->getKey(), $conv->tenant_id);
        $this->assertSame(1, $conv->message_count);
        $this->assertSame(1, $conv->unread_count);
        $this->assertSame('Cảm ơn shop', $conv->last_message_preview);

        $msg = Message::withoutGlobalScopes()->where('conversation_id', $conv->id)->first();
        $this->assertNotNull($msg);
        $this->assertSame('msg_X_1', $msg->external_message_id);
        $this->assertSame(Message::DIRECTION_INBOUND, $msg->direction);
        $this->assertSame(Message::KIND_TEXT, $msg->kind);
    }

    public function test_ingestion_is_idempotent_on_replay(): void
    {
        $this->postJson('/webhook/messaging/manual', [
            'event_type' => 'message_received',
            'external_shop_id' => 'shop_manual_1',
            'external_conversation_id' => 'conv_Y',
            'external_message_id' => 'msg_Y_1',
            'buyer_external_id' => 'buyer_Y',
            'body' => 'A',
        ])->assertOk();

        $event = WebhookEvent::query()->where('external_id', 'msg_Y_1')->first();

        // Chạy job 2 lần — kết quả: 1 conversation, 1 message
        (new ProcessMessagingWebhook($event->id))->handle(
            app(\CMBcoreSeller\Integrations\Messaging\MessagingRegistry::class),
            app(\CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService::class),
        );

        // Reset status để re-run (job đã markProcessed sau call đầu)
        $event->refresh();
        $event->forceFill(['status' => WebhookEvent::STATUS_PENDING])->save();

        (new ProcessMessagingWebhook($event->id))->handle(
            app(\CMBcoreSeller\Integrations\Messaging\MessagingRegistry::class),
            app(\CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService::class),
        );

        $this->assertSame(1, Conversation::withoutGlobalScopes()->where('external_conversation_id', 'conv_Y')->count());
        $this->assertSame(1, Message::withoutGlobalScopes()->where('external_message_id', 'msg_Y_1')->count());
    }
}
