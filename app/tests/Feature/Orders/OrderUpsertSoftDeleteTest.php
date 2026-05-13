<?php

namespace Tests\Feature\Orders;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\OrderDTO;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Services\OrderUpsertService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression for `sync.upsert_failed | SQLSTATE[23505] Unique violation: duplicate key value violates
 * unique constraint "orders_source_account_external_unique"`. Reproduces the exact scenario from the user's
 * docker worker log: an order existed, was soft-deleted (via channel deletion), sàn re-pushes ⇒ sync must
 * restore the row instead of throwing.
 */
class OrderUpsertSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $account;

    private OrderUpsertService $upsert;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Shop sync']);
        app(CurrentTenant::class)->set($this->tenant);
        $this->account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'tiktok',
            'external_shop_id' => 'SH-1', 'shop_name' => 'TikTok',
            'status' => ChannelAccount::STATUS_ACTIVE,
        ]);
        $this->upsert = app(OrderUpsertService::class);
    }

    private function dto(string $extId, string $rawStatus = 'AWAITING_SHIPMENT'): OrderDTO
    {
        return new OrderDTO(
            externalOrderId: $extId,
            source: 'tiktok',
            rawStatus: $rawStatus,
            sourceUpdatedAt: CarbonImmutable::now(),
            orderNumber: $extId,
            paymentStatus: 'paid',
            placedAt: CarbonImmutable::now()->subHours(2),
            paidAt: CarbonImmutable::now()->subHours(2),
            shippedAt: null, deliveredAt: null, completedAt: null,
            cancelledAt: null, cancelReason: null,
            buyer: ['name' => 'Test buyer'], shippingAddress: [],
            currency: 'VND', itemTotal: 100000, shippingFee: 0,
            platformDiscount: 0, sellerDiscount: 0, tax: 0, codAmount: 0, grandTotal: 100000, isCod: false,
            fulfillmentType: null, items: [], packages: [], raw: [],
        );
    }

    public function test_resyncing_a_soft_deleted_order_restores_it_instead_of_throwing_unique_violation(): void
    {
        // 1. Order arrives & is created.
        $order = $this->upsert->upsertWithStatus($this->dto('583997102811087882'), (int) $this->tenant->getKey(), (int) $this->account->getKey(), 'polling', StandardOrderStatus::Pending);
        $orderId = $order->getKey();
        $this->assertNotNull($orderId);

        // 2. User deletes the channel ⇒ orders soft-deleted (as ChannelConnectionService::deleteWithOrders does).
        Order::withoutGlobalScope(TenantScope::class)->whereKey($orderId)->delete();
        $this->assertTrue(Order::withoutGlobalScope(TenantScope::class)->withTrashed()->find($orderId)->trashed());

        // 3. Sàn re-pushes the same order (webhook / next poll). Sync must NOT throw the DB unique
        // constraint — it must restore the row and apply updates. Before this fix the second upsert
        // raised SQLSTATE[23505] because the soft-deleted row still holds (source, channel_account_id,
        // external_order_id) and the default Eloquent query (SoftDeletes scope) hid it from the lookup.
        $restored = $this->upsert->upsertWithStatus($this->dto('583997102811087882', 'IN_TRANSIT'), (int) $this->tenant->getKey(), (int) $this->account->getKey(), 'polling', StandardOrderStatus::Shipped);
        $this->assertSame($orderId, $restored->getKey(), 'Same row must be reused — no new row inserted, no exception.');
        $this->assertNull($restored->deleted_at);
        $this->assertSame(StandardOrderStatus::Shipped, $restored->status);
    }

    public function test_apply_status_from_webhook_restores_soft_deleted_order(): void
    {
        // The webhook fast-path (apply only the status) must also restore — otherwise webhooks would
        // silently drop on soft-deleted orders even though the full re-fetch later would restore them.
        $order = $this->upsert->upsertWithStatus($this->dto('999000111'), (int) $this->tenant->getKey(), (int) $this->account->getKey(), 'polling', StandardOrderStatus::Pending);
        Order::withoutGlobalScope(TenantScope::class)->whereKey($order->getKey())->delete();

        $result = $this->upsert->applyStatusFromWebhook((int) $this->tenant->getKey(), (int) $this->account->getKey(), 'tiktok', '999000111', StandardOrderStatus::Shipped, 'IN_TRANSIT');
        $this->assertNotNull($result);
        $this->assertSame($order->getKey(), $result->getKey());
        $this->assertNull($result->deleted_at);
        $this->assertSame(StandardOrderStatus::Shipped, $result->status);
    }
}
