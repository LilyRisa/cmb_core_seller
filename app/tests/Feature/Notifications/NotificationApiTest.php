<?php

namespace Tests\Feature\Notifications;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Notifications\Models\Notification;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC 0036 — API chuông: list (kèm meta.unread_count), mark-read, read-all; scope đúng
 * user/tenant (không thấy notif của user khác).
 */
class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $other;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->other = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'ApiShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->tenant->users()->attach($this->other->getKey(), ['role' => Role::Owner->value]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function makeNotif(int $userId, bool $read = false, string $type = 'order.cancelled', string $category = 'order'): Notification
    {
        return Notification::create([
            'tenant_id' => $this->tenant->getKey(),
            'user_id' => $userId,
            'type' => $type,
            'category' => $category,
            'level' => 'info',
            'title' => 'Đơn đã hủy',
            'read_at' => $read ? now() : null,
        ]);
    }

    public function test_index_returns_own_notifications_and_unread_count(): void
    {
        $this->makeNotif($this->owner->getKey());
        $this->makeNotif($this->owner->getKey(), read: true);
        $this->makeNotif($this->other->getKey()); // của user khác — không được thấy

        $res = $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/notifications');

        $res->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.unread_count', 1);
    }

    public function test_mark_read_decrements_unread(): void
    {
        $n = $this->makeNotif($this->owner->getKey());
        $this->makeNotif($this->owner->getKey());

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/notifications/{$n->getKey()}/read");

        $res->assertOk()->assertJsonPath('data.unread_count', 1);
    }

    public function test_read_all_zeroes_unread(): void
    {
        $this->makeNotif($this->owner->getKey());
        $this->makeNotif($this->owner->getKey());

        $res = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/notifications/read-all');

        $res->assertOk()->assertJsonPath('data.unread_count', 0);
    }

    public function test_cannot_mark_read_another_users_notification(): void
    {
        $foreign = $this->makeNotif($this->other->getKey());

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/notifications/{$foreign->getKey()}/read")
            ->assertNotFound();
    }

    public function test_index_filters_by_category(): void
    {
        $this->makeNotif($this->owner->getKey(), category: 'order');
        $this->makeNotif($this->owner->getKey(), type: 'channel.reconnect_needed', category: 'system');

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/notifications?category=system');

        $res->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category', 'system');
    }

    public function test_index_returns_unread_count_by_category(): void
    {
        $this->makeNotif($this->owner->getKey(), category: 'order');
        $this->makeNotif($this->owner->getKey(), category: 'order', read: true);
        $this->makeNotif($this->owner->getKey(), type: 'channel.reconnect_needed', category: 'system');

        $res = $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/notifications');

        $res->assertOk()
            ->assertJsonPath('meta.unread_count_by_category.order', 1)
            ->assertJsonPath('meta.unread_count_by_category.system', 1)
            ->assertJsonPath('meta.unread_count_by_category.general', 0)
            ->assertJsonPath('meta.unread_count', 2);
    }
}
