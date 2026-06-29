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
 * TDD: cờ $force trên upsertWithStatus — bỏ qua stale-guard source_updated_at, nhưng
 * vẫn tôn trọng cờ tracking_stopped và mặc định false (callers cũ không bị ảnh hưởng).
 */
class OrderUpsertForceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $account;

    private OrderUpsertService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Force test shop']);
        app(CurrentTenant::class)->set($this->tenant);
        $this->account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'shopee',
            'external_shop_id' => 'SH-FT', 'shop_name' => 'Shopee Force Test',
            'status' => ChannelAccount::STATUS_ACTIVE,
        ]);
        $this->svc = app(OrderUpsertService::class);
    }

    /** Dựng OrderDTO tối thiểu cho 1 đơn shopee. */
    private function dto(string $extId, string $rawStatus, CarbonImmutable $updatedAt): OrderDTO
    {
        return new OrderDTO(
            externalOrderId: $extId, source: 'shopee', rawStatus: $rawStatus, sourceUpdatedAt: $updatedAt,
            orderNumber: $extId, paymentStatus: null, placedAt: $updatedAt->subHours(2), paidAt: null,
            shippedAt: null, deliveredAt: null, completedAt: null, cancelledAt: null, cancelReason: null,
            buyer: ['name' => 'Buyer'], shippingAddress: [], currency: 'VND', itemTotal: 100000, shippingFee: 0,
            platformDiscount: 0, sellerDiscount: 0, tax: 0, codAmount: 0, grandTotal: 100000, isCod: false,
            fulfillmentType: null, items: [], packages: [], raw: [],
        );
    }

    /** force=true bỏ qua stale-guard; force=false (default) vẫn giữ guard. */
    public function test_force_applies_status_even_when_stale_guard_would_skip(): void
    {
        $now = CarbonImmutable::now();
        $tenantId = (int) $this->tenant->getKey();
        $accountId = (int) $this->account->getKey();

        // Seed: đơn shopee, source_updated_at = now, status Unpaid.
        $this->svc->upsertWithStatus($this->dto('SP1', 'UNPAID', $now), $tenantId, $accountId, 'sync', StandardOrderStatus::Unpaid);

        $older = $now->subHour();

        // force=false (default): stale-guard bật → không đổi, vẫn Unpaid.
        $this->svc->upsertWithStatus($this->dto('SP1', 'SHIPPED', $older), $tenantId, $accountId, 'sync', StandardOrderStatus::Shipped, false);
        $o = Order::withoutGlobalScope(TenantScope::class)->where('external_order_id', 'SP1')->first();
        $this->assertSame(StandardOrderStatus::Unpaid, $o->status, 'force=false phải giữ stale-guard (không đổi)');

        // force=true: bỏ qua stale-guard → áp trạng thái mới dù timestamp cũ hơn.
        $this->svc->upsertWithStatus($this->dto('SP1', 'SHIPPED', $older), $tenantId, $accountId, 'sync', StandardOrderStatus::Shipped, true);
        $o->refresh();
        $this->assertSame(StandardOrderStatus::Shipped, $o->status, 'force=true phải áp trạng thái mới');
    }

    /** force=true KHÔNG bỏ qua tracking_stopped — cờ đó vẫn được tôn trọng. */
    public function test_force_still_respects_tracking_stopped(): void
    {
        $now = CarbonImmutable::now();
        $tenantId = (int) $this->tenant->getKey();
        $accountId = (int) $this->account->getKey();

        // Seed đơn rồi gắn cờ tracking_stopped.
        $this->svc->upsertWithStatus($this->dto('SP2', 'UNPAID', $now), $tenantId, $accountId, 'sync', StandardOrderStatus::Unpaid);
        $o = Order::withoutGlobalScope(TenantScope::class)->where('external_order_id', 'SP2')->first();
        $o->forceFill(['meta' => ['tracking_stopped' => true]])->save();

        // force=true + timestamp cũ hơn → tracking_stopped vẫn chặn.
        $this->svc->upsertWithStatus($this->dto('SP2', 'SHIPPED', $now->subHour()), $tenantId, $accountId, 'sync', StandardOrderStatus::Shipped, true);
        $o->refresh();
        $this->assertSame(StandardOrderStatus::Unpaid, $o->status, 'tracking_stopped phải được tôn trọng kể cả force');
    }
}
