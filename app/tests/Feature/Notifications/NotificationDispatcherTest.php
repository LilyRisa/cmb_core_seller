<?php

namespace Tests\Feature\Notifications;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Notifications\Events\NotificationCreated;
use CMBcoreSeller\Modules\Notifications\Models\Notification;
use CMBcoreSeller\Modules\Notifications\Services\NotificationDispatcher;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC 0036 — NotificationDispatcher: fan-out tới mọi thành viên tenant + dedup theo
 * dedup_key (chỉ chặn khi user còn bản CHƯA đọc).
 */
class NotificationDispatcherTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenantWithUsers(int $count): Tenant
    {
        $tenant = Tenant::create(['name' => 'NShop']);
        for ($i = 0; $i < $count; $i++) {
            $tenant->users()->attach(User::factory()->create()->getKey(), ['role' => Role::Owner->value]);
        }

        return $tenant;
    }

    private function countFor(int $tenantId): int
    {
        return Notification::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId)->count();
    }

    public function test_dispatch_fans_out_one_row_per_tenant_user(): void
    {
        $tenant = $this->makeTenantWithUsers(3);

        $created = app(NotificationDispatcher::class)->dispatch((int) $tenant->getKey(), [
            'type' => 'order.negative_total',
            'title' => 'Đơn X âm tiền',
            'dedup_key' => 'order.negative:1',
        ]);

        $this->assertSame(3, $created);
        $this->assertSame(3, $this->countFor((int) $tenant->getKey()));
    }

    public function test_dedup_skips_when_unread_duplicate_exists_but_recreates_after_read(): void
    {
        $tenant = $this->makeTenantWithUsers(2);
        $dispatcher = app(NotificationDispatcher::class);
        $payload = ['type' => 'channel.reconnect_needed', 'title' => 'Liên kết hết hạn', 'dedup_key' => 'channel.reconnect:9'];

        $this->assertSame(2, $dispatcher->dispatch((int) $tenant->getKey(), $payload));
        // Lần 2: cả 2 user còn bản chưa đọc cùng key ⇒ bỏ qua hết.
        $this->assertSame(0, $dispatcher->dispatch((int) $tenant->getKey(), $payload));
        $this->assertSame(2, $this->countFor((int) $tenant->getKey()));

        // Đọc hết ⇒ event mới được tạo lại.
        Notification::withoutGlobalScope(TenantScope::class)->update(['read_at' => now()]);
        $this->assertSame(2, $dispatcher->dispatch((int) $tenant->getKey(), $payload));
        $this->assertSame(4, $this->countFor((int) $tenant->getKey()));
    }

    public function test_dispatch_without_dedup_key_always_creates(): void
    {
        $tenant = $this->makeTenantWithUsers(1);
        $dispatcher = app(NotificationDispatcher::class);
        $payload = ['type' => 'order.cancelled', 'title' => 'Đơn đã hủy'];

        $dispatcher->dispatch((int) $tenant->getKey(), $payload);
        $dispatcher->dispatch((int) $tenant->getKey(), $payload);

        $this->assertSame(2, $this->countFor((int) $tenant->getKey()));
    }

    public function test_dispatch_to_tenant_without_users_is_noop(): void
    {
        $tenant = Tenant::create(['name' => 'EmptyShop']);

        $created = app(NotificationDispatcher::class)->dispatch((int) $tenant->getKey(), [
            'type' => 'order.cancelled', 'title' => 'X',
        ]);

        $this->assertSame(0, $created);
    }

    public function test_notification_created_broadcasts_on_per_user_private_channel(): void
    {
        $channels = (new NotificationCreated(notificationId: 5, tenantId: 7, userId: 42))->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-tenant.7.notifications.42', $channels[0]->name);
    }

    public function test_dispatch_auto_assigns_category_from_type(): void
    {
        $tenant = $this->makeTenantWithUsers(1);

        app(NotificationDispatcher::class)->dispatch((int) $tenant->getKey(), [
            'type' => 'order.cancelled',
            'title' => 'Đơn đã hủy',
        ]);

        $n = Notification::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())->first();
        $this->assertSame('order', $n->category);
    }

    public function test_dispatch_ignores_category_in_payload_and_derives_from_type(): void
    {
        $tenant = $this->makeTenantWithUsers(1);

        // Dù payload cố tình truyền category sai, dispatcher vẫn tự suy ra đúng từ type
        // (category không phải input tin cậy từ listener — tránh 6 listener hiện có phải
        // sửa để truyền đúng field này).
        app(NotificationDispatcher::class)->dispatch((int) $tenant->getKey(), [
            'type' => 'channel.reconnect_needed',
            'title' => 'X',
            'category' => 'order',
        ]);

        $n = Notification::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())->first();
        $this->assertSame('system', $n->category);
    }
}
