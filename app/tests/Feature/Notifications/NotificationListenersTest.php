<?php

namespace Tests\Feature\Notifications;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Events\ChannelAccountNeedsReconnect;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Marketing\Events\AdMonitorThresholdApproaching;
use CMBcoreSeller\Modules\Notifications\Listeners\NotifyOnAdMonitorApproaching;
use CMBcoreSeller\Modules\Notifications\Listeners\NotifyOnChannelReconnect;
use CMBcoreSeller\Modules\Notifications\Listeners\NotifyOnNegativeOrder;
use CMBcoreSeller\Modules\Notifications\Models\Notification;
use CMBcoreSeller\Modules\Notifications\Services\NotificationDispatcher;
use CMBcoreSeller\Modules\Notifications\Support\NotificationType;
use CMBcoreSeller\Modules\Orders\Events\OrderUpserted;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * SPEC 0036 — listener dịch domain event → thông báo in-app đúng loại. Gọi handle() trực
 * tiếp (không phụ thuộc queue) với model attribute giả lập.
 */
class NotificationListenersTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'ListenShop']);
        $this->tenant->users()->attach(User::factory()->create()->getKey(), ['role' => Role::Owner->value]);
    }

    private function notifications(): Collection
    {
        return Notification::withoutGlobalScope(TenantScope::class)->where('tenant_id', $this->tenant->getKey())->get();
    }

    public function test_negative_order_creates_warning_notification(): void
    {
        $order = (new Order)->forceFill([
            'id' => 123, 'tenant_id' => $this->tenant->getKey(), 'grand_total' => -5000, 'order_number' => 'DH1',
        ]);

        (new NotifyOnNegativeOrder(app(NotificationDispatcher::class)))->handle(new OrderUpserted($order, true));

        $n = $this->notifications();
        $this->assertCount(1, $n);
        $this->assertSame(NotificationType::ORDER_NEGATIVE_TOTAL, $n->first()->type);
        $this->assertSame('warning', $n->first()->level);
        $this->assertSame('order.negative:123', $n->first()->dedup_key);
    }

    public function test_non_negative_order_creates_nothing(): void
    {
        $order = (new Order)->forceFill([
            'id' => 124, 'tenant_id' => $this->tenant->getKey(), 'grand_total' => 10000, 'order_number' => 'DH2',
        ]);

        (new NotifyOnNegativeOrder(app(NotificationDispatcher::class)))->handle(new OrderUpserted($order, true));

        $this->assertCount(0, $this->notifications());
    }

    public function test_channel_reconnect_creates_notification_with_provider_label(): void
    {
        $account = (new ChannelAccount)->forceFill([
            'id' => 9, 'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'display_name' => 'Shop FB', 'shop_name' => 'Shop FB', 'external_shop_id' => 'fb1',
        ]);

        (new NotifyOnChannelReconnect(app(NotificationDispatcher::class)))
            ->handle(new ChannelAccountNeedsReconnect($account, 'token_expired'));

        $n = $this->notifications()->first();
        $this->assertSame(NotificationType::CHANNEL_RECONNECT_NEEDED, $n->type);
        $this->assertStringContainsString('Facebook', $n->title);
        $this->assertSame('channel.reconnect:9', $n->dedup_key);
    }

    public function test_ad_monitor_approaching_creates_warning(): void
    {
        (new NotifyOnAdMonitorApproaching(app(NotificationDispatcher::class)))
            ->handle(new AdMonitorThresholdApproaching((int) $this->tenant->getKey(), 1, 'Camp Tết', 'campaign', 50000, 60000));

        $n = $this->notifications()->first();
        $this->assertSame(NotificationType::ADS_MONITOR_APPROACHING, $n->type);
        $this->assertStringContainsString('Camp Tết', $n->title);
        $this->assertSame('ads.approaching:1', $n->dedup_key);
    }
}
