<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\PushSubscription;
use CMBcoreSeller\Modules\Messaging\Services\WebPushSender;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * `messaging:push-digest` — gom tin nhắn mới → Web Push cho user KHÔNG hoạt động.
 * WebPushSender được mock (không gửi push thật).
 */
class PushNotificationDigestTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'PushTenant']);
        app(CurrentTenant::class)->set($this->tenant);

        $account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_P', 'shop_name' => 'Trang', 'status' => ChannelAccount::STATUS_ACTIVE,
            'messaging_enabled' => true,
        ]);
        // 1 hội thoại có inbound MỚI (sau baseline last_notified_at).
        Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $account->getKey(),
            'provider' => 'facebook_page', 'external_conversation_id' => 'PSID_P', 'buyer_external_id' => 'PSID_P',
            'status' => Conversation::STATUS_OPEN, 'last_message_at' => now(), 'last_inbound_at' => now(),
        ]);
    }

    private function sub(string $endpoint, array $overrides = []): PushSubscription
    {
        return PushSubscription::query()->create(array_merge([
            'tenant_id' => $this->tenant->getKey(), 'user_id' => 1,
            'endpoint' => $endpoint, 'p256dh' => 'p', 'auth' => 'a',
            'last_seen_at' => now()->subHour(), 'last_notified_at' => now()->subHour(),
        ], $overrides));
    }

    public function test_pushes_to_inactive_subscription_and_skips_active(): void
    {
        $this->sub('https://push/INACTIVE', ['last_seen_at' => now()->subHour()]);
        $this->sub('https://push/ACTIVE', ['last_seen_at' => now()]); // heartbeat mới → bỏ qua

        $sent = [];
        $this->mock(WebPushSender::class, function ($m) use (&$sent) {
            $m->shouldReceive('isConfigured')->andReturn(true);
            $m->shouldReceive('send')->andReturnUsing(function (PushSubscription $s) use (&$sent) {
                $sent[] = $s->endpoint;

                return true;
            });
        });

        $this->artisan('messaging:push-digest')->assertSuccessful();

        $this->assertContains('https://push/INACTIVE', $sent);
        $this->assertNotContains('https://push/ACTIVE', $sent, 'sub đang hoạt động không bị push');

        $inactive = PushSubscription::query()->where('endpoint', 'https://push/INACTIVE')->first();
        $this->assertTrue($inactive->last_notified_at->gt(now()->subMinute()), 'last_notified_at được cập nhật');
    }

    public function test_does_not_push_when_no_new_messages(): void
    {
        // Baseline = now ⇒ inbound (now) KHÔNG mới hơn ⇒ không push.
        $this->sub('https://push/X', ['last_notified_at' => now()->addSecond()]);

        $this->mock(WebPushSender::class, function ($m) {
            $m->shouldReceive('isConfigured')->andReturn(true);
            $m->shouldReceive('send')->never();
        });

        $this->artisan('messaging:push-digest')->assertSuccessful();
    }

    public function test_no_op_when_vapid_not_configured(): void
    {
        $this->sub('https://push/Y');

        $this->mock(WebPushSender::class, function ($m) {
            $m->shouldReceive('isConfigured')->andReturn(false);
            $m->shouldReceive('send')->never();
        });

        $this->artisan('messaging:push-digest')->assertSuccessful();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
