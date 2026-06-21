<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Events\MessageReceived;
use CMBcoreSeller\Modules\Messaging\Listeners\PushWebOnNewMessage;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\PushSubscription;
use CMBcoreSeller\Modules\Messaging\Services\WebPushSender;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * PushWebOnNewMessage — push web NGAY khi có tin nhắn inbound (event-driven), chỉ cho sub không hoạt động.
 * WebPushSender được mock (không gửi push thật).
 */
class InstantWebPushTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Conversation $conv;

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
        $this->conv = Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $account->getKey(),
            'provider' => 'facebook_page', 'external_conversation_id' => 'PSID_P', 'buyer_external_id' => 'PSID_P',
            'buyer_name' => 'Chị Lan', 'status' => Conversation::STATUS_OPEN, 'last_message_at' => now(), 'last_inbound_at' => now(),
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

    /** @return list<string> endpoint đã được push */
    private function fire(): array
    {
        $sent = [];
        $sender = Mockery::mock(WebPushSender::class);
        $sender->shouldReceive('isConfigured')->andReturnTrue();
        $sender->shouldReceive('send')->andReturnUsing(function (PushSubscription $s) use (&$sent) {
            $sent[] = $s->endpoint;

            return true;
        });
        (new PushWebOnNewMessage($sender))->handle(new MessageReceived(1, $this->conv->getKey()));

        return $sent;
    }

    public function test_pushes_instantly_to_inactive_only_and_skips_active_and_throttled(): void
    {
        $this->sub('https://push/INACTIVE', ['last_seen_at' => now()->subMinutes(10)]);     // tab đóng → push
        $this->sub('https://push/ACTIVE', ['last_seen_at' => now()]);                       // tab mở (heartbeat mới) → bỏ
        $this->sub('https://push/THROTTLED', ['last_seen_at' => now()->subMinutes(10), 'last_notified_at' => now()]); // vừa push → bỏ

        $sent = $this->fire();

        $this->assertSame(['https://push/INACTIVE'], $sent);
        $inactive = PushSubscription::query()->where('endpoint', 'https://push/INACTIVE')->first();
        $this->assertTrue($inactive->last_notified_at->gt(now()->subMinute()), 'last_notified_at cập nhật sau khi push');
    }

    public function test_does_not_push_for_spam_or_blocked_conversation(): void
    {
        $this->sub('https://push/X', ['last_seen_at' => now()->subMinutes(10)]);
        $this->conv->forceFill(['status' => Conversation::STATUS_SPAM])->save();

        $this->assertSame([], $this->fire(), 'hội thoại spam không push');
    }

    public function test_no_op_when_vapid_not_configured(): void
    {
        $sub = $this->sub('https://push/Y', ['last_seen_at' => now()->subMinutes(10), 'last_notified_at' => now()->subHour()]);
        $sender = Mockery::mock(WebPushSender::class);
        $sender->shouldReceive('isConfigured')->andReturnFalse();
        $sender->shouldReceive('send')->never();

        (new PushWebOnNewMessage($sender))->handle(new MessageReceived(1, $this->conv->getKey()));

        // không gửi ⇒ last_notified_at giữ nguyên (không cập nhật).
        $this->assertTrue($sub->fresh()->last_notified_at->lt(now()->subMinutes(30)));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
