<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Fixtures\Channels\tiktok\TikTokFixtures as F;
use Tests\TestCase;

/**
 * Giai đoạn 1 (design 2026-07-14 §2) — dedupe_status_key phải được ghi đúng giá trị mỗi lần tạo
 * webhook event mới: bằng order_raw_status khi có, hoặc chuỗi rỗng khi payload không kèm trạng thái
 * (KHÔNG null — NULL không so bằng NULL trong unique index chuẩn SQL, cần cho giai đoạn 2/Task 6).
 *
 * Dùng đúng scaffolding thật của TikTokSyncTest (setUp + request ký HMAC qua /webhook/tiktok) thay vì
 * request giả /webhook/channels/tiktok trong task-4-brief.md — endpoint đó không tồn tại và request
 * không ký sẽ bị 401 trước khi chạm tới code cần test.
 */
class WebhookDedupeStatusKeyTest extends TestCase
{
    use RefreshDatabase;

    private ChannelAccount $account;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        F::configure();

        $this->tenant = Tenant::create(['name' => 'Shop dedupe test']);
        app(CurrentTenant::class)->set($this->tenant);

        $this->account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'tiktok',
            'external_shop_id' => F::SHOP_ID,
            'shop_name' => 'Cửa hàng test',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'access_token' => 'tk_access',
            'refresh_token' => 'tk_refresh',
            'token_expires_at' => now()->addDays(7),
            'meta' => ['shop_cipher' => F::SHOP_CIPHER],
        ]);
    }

    /** Build a signed TikTok webhook body for a given order status (mirrors TikTokSyncTest::signedWebhook). */
    private function signedWebhook(string $status, string $secret = F::APP_SECRET, string $orderId = F::ORDER_ID): array
    {
        $body = [
            'type' => 1, 'tts_notification_id' => 'ntf-'.uniqid(), 'shop_id' => F::SHOP_ID, 'timestamp' => now()->timestamp,
            'data' => ['order_id' => $orderId, 'order_status' => $status, 'update_time' => now()->timestamp],
        ];
        $raw = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return ['raw' => $raw, 'sig' => hash_hmac('sha256', F::APP_KEY.$raw, $secret)];
    }

    /** Same as signedWebhook() but `data` carries NO `order_status` key at all. */
    private function signedWebhookWithoutStatus(string $orderId = F::ORDER_ID): array
    {
        $body = [
            'type' => 1, 'tts_notification_id' => 'ntf-'.uniqid(), 'shop_id' => F::SHOP_ID, 'timestamp' => now()->timestamp,
            'data' => ['order_id' => $orderId, 'update_time' => now()->timestamp],
        ];
        $raw = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return ['raw' => $raw, 'sig' => hash_hmac('sha256', F::APP_KEY.$raw, F::APP_SECRET)];
    }

    public function test_sets_dedupe_status_key_from_order_raw_status(): void
    {
        Queue::fake();

        $wh = $this->signedWebhook('AWAITING_SHIPMENT');
        $this->call('POST', '/webhook/tiktok', server: ['HTTP_AUTHORIZATION' => $wh['sig'], 'CONTENT_TYPE' => 'application/json'], content: $wh['raw'])
            ->assertOk();

        $row = WebhookEvent::query()->where('provider', 'tiktok')->where('external_id', F::ORDER_ID)->first();
        $this->assertNotNull($row);
        $this->assertSame('AWAITING_SHIPMENT', $row->order_raw_status);
        $this->assertSame('AWAITING_SHIPMENT', $row->dedupe_status_key);
    }

    public function test_sets_empty_string_dedupe_status_key_when_status_absent(): void
    {
        Queue::fake();

        $wh = $this->signedWebhookWithoutStatus();
        $this->call('POST', '/webhook/tiktok', server: ['HTTP_AUTHORIZATION' => $wh['sig'], 'CONTENT_TYPE' => 'application/json'], content: $wh['raw'])
            ->assertOk();

        $row = WebhookEvent::query()->where('provider', 'tiktok')->where('external_id', F::ORDER_ID)->first();
        $this->assertNotNull($row);
        $this->assertNull($row->order_raw_status);
        $this->assertSame('', $row->dedupe_status_key);
    }
}
