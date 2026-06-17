<?php

namespace Tests\Feature\Orders;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\OrderDTO;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Services\OrderBulkActionService;
use CMBcoreSeller\Modules\Orders\Services\OrderUpsertService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus as S;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Thao tác hàng loạt đơn: huỷ (local "ngừng theo dõi", không đẩy lên sàn/ĐVVC) +
 * xoá mềm đơn đã huỷ; sync KHÔNG hồi sinh đơn đã ngừng theo dõi.
 */
class OrderBulkActionTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'BulkShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        app(CurrentTenant::class)->set($this->tenant);
        $this->account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 'LZ', 'shop_name' => 'Lazada', 'status' => ChannelAccount::STATUS_ACTIVE,
        ]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function makeOrder(string $source, S $status, string $extId): Order
    {
        return Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => $source,
            'channel_account_id' => $source === 'manual' ? null : $this->account->getKey(),
            'external_order_id' => $source === 'manual' ? null : $extId, 'order_number' => $extId,
            'status' => $status, 'raw_status' => 'x', 'currency' => 'VND', 'grand_total' => 100000,
            'item_total' => 100000, 'placed_at' => now(), 'has_issue' => false, 'tags' => [], 'source_updated_at' => now(),
        ]);
    }

    public function test_bulk_cancel_sets_cancelled_local_and_stop_tracking_flag(): void
    {
        $o = $this->makeOrder('lazada', S::Pending, 'LZ1');

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/orders/bulk-cancel', ['ids' => [$o->id], 'reason' => 'Khách bom'])
            ->assertOk()->assertJsonPath('data.cancelled', 1)->assertJsonPath('data.skipped', 0);

        $o->refresh();
        $this->assertSame(S::Cancelled, $o->status);
        $this->assertTrue((bool) ($o->meta['tracking_stopped'] ?? false));
        $this->assertNotNull($o->cancelled_at);
        $this->assertDatabaseHas('order_status_history', ['order_id' => $o->id, 'to_status' => 'cancelled']);
    }

    public function test_bulk_cancel_skips_already_cancelled(): void
    {
        $o = $this->makeOrder('manual', S::Cancelled, 'M1');

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/orders/bulk-cancel', ['ids' => [$o->id]])
            ->assertOk()->assertJsonPath('data.cancelled', 0)->assertJsonPath('data.skipped', 1);
    }

    public function test_bulk_delete_only_removes_cancelled(): void
    {
        $cancelled = $this->makeOrder('lazada', S::Cancelled, 'LZ2');
        $pending = $this->makeOrder('lazada', S::Pending, 'LZ3');

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/orders/bulk-delete', ['ids' => [$cancelled->id, $pending->id]])
            ->assertOk()->assertJsonPath('data.deleted', 1)->assertJsonPath('data.skipped', 1);

        $this->assertSoftDeleted('orders', ['id' => $cancelled->id]);
        $this->assertNotSoftDeleted('orders', ['id' => $pending->id]);
    }

    public function test_bulk_delete_forbidden_for_staff_without_delete_permission(): void
    {
        $staff = User::factory()->create();
        $this->tenant->users()->attach($staff->getKey(), ['role' => Role::StaffOrder->value]);
        $o = $this->makeOrder('manual', S::Cancelled, 'M2');

        $this->actingAs($staff)->withHeaders($this->h())
            ->postJson('/api/v1/orders/bulk-delete', ['ids' => [$o->id]])
            ->assertForbidden();
    }

    public function test_sync_does_not_resurrect_stop_tracked_order(): void
    {
        $upsert = app(OrderUpsertService::class);
        $o = $upsert->upsertWithStatus($this->dto('LZ9', 'pending', CarbonImmutable::now()->subMinutes(10)), (int) $this->tenant->getKey(), (int) $this->account->getKey(), 'polling', S::Pending);

        app(OrderBulkActionService::class)->cancelLocally($o, $this->owner->getKey(), null);

        // Sàn đẩy trạng thái MỚI hơn (đã ship) ⇒ đơn đã ngừng theo dõi KHÔNG bị hồi sinh.
        $after = $upsert->upsertWithStatus($this->dto('LZ9', 'shipped', CarbonImmutable::now()), (int) $this->tenant->getKey(), (int) $this->account->getKey(), 'polling', S::Shipped);

        $this->assertSame(S::Cancelled, $after->status);
    }

    private function dto(string $extId, string $rawStatus, CarbonImmutable $updatedAt): OrderDTO
    {
        return new OrderDTO(
            externalOrderId: $extId, source: 'lazada', rawStatus: $rawStatus, sourceUpdatedAt: $updatedAt,
            orderNumber: $extId, paymentStatus: 'paid', placedAt: $updatedAt->subHours(2), paidAt: $updatedAt->subHours(2),
            shippedAt: null, deliveredAt: null, completedAt: null, cancelledAt: null, cancelReason: null,
            buyer: ['name' => 'B'], shippingAddress: [], currency: 'VND', itemTotal: 100000, shippingFee: 0,
            platformDiscount: 0, sellerDiscount: 0, tax: 0, codAmount: 0, grandTotal: 100000, isCod: false,
            fulfillmentType: null, items: [], packages: [], raw: [],
        );
    }
}
