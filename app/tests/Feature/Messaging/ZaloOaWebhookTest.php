<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZaloOaWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('integrations.messaging', ['zalo_oa']);
        config()->set('integrations.messaging_zalo_oa', [
            'app_id' => 'app_123', 'app_secret' => 'sec', 'oa_secret' => 'oa_secret_xyz', 'redirect_uri' => 'https://x.test/cb',
        ]);
        // Clear singleton so registry picks up new config (mirrors MessagingFacebookWebhookTest).
        $this->app->forgetInstance(MessagingRegistry::class);
    }

    public function test_zalo_webhook_ingests_text_message(): void
    {
        $tenant = Tenant::factory()->create();
        ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->id, 'provider' => 'zalo_oa', 'external_shop_id' => 'OA_9',
            'shop_name' => 'Shop Zalo', 'access_token' => 'TKN', 'status' => ChannelAccount::STATUS_ACTIVE,
            'messaging_enabled' => true,
        ]);

        $payload = ['app_id' => 'app_123', 'event_name' => 'user_send_text', 'timestamp' => (string) now()->getTimestampMs(),
            'sender' => ['id' => 'USER_1'], 'recipient' => ['id' => 'OA_9'], 'message' => ['msg_id' => 'MID_1', 'text' => 'Xin chào shop']];
        $body = json_encode($payload);
        $mac = 'mac='.hash('sha256', 'app_123'.$body.$payload['timestamp'].'oa_secret_xyz');

        $this->call('POST', '/webhook/messaging/zalo_oa', [], [], [], ['HTTP_X_ZEVENT_SIGNATURE' => $mac, 'CONTENT_TYPE' => 'application/json'], $body)
            ->assertOk();

        // Webhook ingest dispatches ProcessMessagingWebhook (sync queue in tests) → conversation created.
        $this->assertDatabaseHas('conversations', ['provider' => 'zalo_oa', 'buyer_external_id' => 'USER_1']);
    }

    public function test_zalo_webhook_acks_200_when_signature_invalid_but_does_not_ingest(): void
    {
        // Zalo yêu cầu phản hồi 200 cho MỌI webhook (doc Webhook > Tổng quan §3); nếu trả ≠200
        // Zalo vô hiệu hóa webhook → không thể hoàn tất cấu hình để lấy OA Secret Key (deadlock).
        // Khi chữ ký không hợp lệ (vd request xác minh lúc chưa có secret) → vẫn 200, KHÔNG ingest.
        $payload = ['app_id' => 'app_123', 'event_name' => 'user_send_text', 'timestamp' => (string) now()->getTimestampMs(),
            'sender' => ['id' => 'USER_X'], 'recipient' => ['id' => 'OA_9'], 'message' => ['msg_id' => 'MID_X', 'text' => 'hi']];
        $body = json_encode($payload);

        $this->call('POST', '/webhook/messaging/zalo_oa', [], [], [], ['HTTP_X_ZEVENT_SIGNATURE' => 'mac=deadbeef', 'CONTENT_TYPE' => 'application/json'], $body)
            ->assertOk();

        $this->assertDatabaseMissing('conversations', ['provider' => 'zalo_oa', 'buyer_external_id' => 'USER_X']);
        $this->assertDatabaseMissing('webhook_events', ['provider' => 'messaging.zalo_oa', 'external_id' => 'MID_X']);
    }
}
