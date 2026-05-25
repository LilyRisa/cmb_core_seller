<?php

namespace Tests\Feature\Orders;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\ReturnDTO;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Orders\Contracts\ReturnUpsertContract;
use CMBcoreSeller\Modules\Orders\Events\ReturnStatusChanged;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderReturn;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\AfterSalesStatus;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ReturnUpsertTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Shop returns']);
        app(CurrentTenant::class)->set($this->tenant);
        $this->account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'tiktok', 'external_shop_id' => 'SHOP1',
            'shop_name' => 'X', 'status' => ChannelAccount::STATUS_ACTIVE, 'access_token' => 'tk',
        ]);
    }

    private function order(string $extId = 'ORD-1'): Order
    {
        return Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'tiktok', 'channel_account_id' => $this->account->getKey(),
            'external_order_id' => $extId, 'order_number' => $extId, 'status' => StandardOrderStatus::Delivered,
            'currency' => 'VND', 'grand_total' => 200000, 'item_total' => 200000, 'tags' => [], 'has_issue' => false,
            'source_updated_at' => now()->subDay(),
        ]);
    }

    private function dto(array $o = []): ReturnDTO
    {
        return new ReturnDTO(
            externalReturnId: $o['id'] ?? 'RET-1',
            source: 'tiktok',
            kind: $o['kind'] ?? ReturnDTO::KIND_RETURN,
            status: $o['status'] ?? AfterSalesStatus::Requested,
            rawStatus: $o['raw'] ?? 'RETURN_OR_REFUND_REQUEST_PENDING',
            externalOrderId: $o['order'] ?? 'ORD-1',
            reason: 'Hàng lỗi',
            refundAmount: $o['amount'] ?? 50000,
            currency: 'VND',
            items: [['seller_sku' => 'SKU-A', 'quantity' => 1]],
            requestedAt: CarbonImmutable::now()->subHours(2),
            sourceUpdatedAt: $o['updated'] ?? CarbonImmutable::now()->subHour(),
        );
    }

    public function test_upsert_creates_record_resolves_order_and_flags_has_return(): void
    {
        Event::fake([ReturnStatusChanged::class]);
        $order = $this->order();

        $ret = app(ReturnUpsertContract::class)->upsert($this->dto(), (int) $this->tenant->getKey(), (int) $this->account->getKey(), 'polling');

        $this->assertSame((int) $order->getKey(), (int) $ret->order_id);
        $this->assertSame(AfterSalesStatus::Requested, $ret->status);
        $this->assertSame(50000, $ret->refund_amount);
        $this->assertSame('return', $ret->kind);
        $this->assertTrue((bool) $order->fresh()->has_return);
        Event::assertDispatched(ReturnStatusChanged::class);
    }

    public function test_upsert_is_idempotent_and_skips_out_of_order(): void
    {
        $this->order();
        $svc = app(ReturnUpsertContract::class);
        $svc->upsert($this->dto(['updated' => CarbonImmutable::now()->subHour()]), (int) $this->tenant->getKey(), (int) $this->account->getKey(), 'polling');

        // same/older snapshot → no new row, status unchanged
        $svc->upsert($this->dto(['updated' => CarbonImmutable::now()->subHours(3), 'status' => AfterSalesStatus::Completed]), (int) $this->tenant->getKey(), (int) $this->account->getKey(), 'polling');
        $this->assertSame(1, OrderReturn::withoutGlobalScope(TenantScope::class)->count());
        $this->assertSame(AfterSalesStatus::Requested, OrderReturn::withoutGlobalScope(TenantScope::class)->first()->status);
    }

    public function test_closing_return_clears_has_return_flag(): void
    {
        $order = $this->order();
        $svc = app(ReturnUpsertContract::class);
        $svc->upsert($this->dto(['updated' => CarbonImmutable::now()->subHours(2)]), (int) $this->tenant->getKey(), (int) $this->account->getKey(), 'polling');
        $this->assertTrue((bool) $order->fresh()->has_return);

        // newer snapshot → Completed (terminal) ⇒ has_return false
        $svc->upsert($this->dto(['updated' => CarbonImmutable::now(), 'status' => AfterSalesStatus::Completed]), (int) $this->tenant->getKey(), (int) $this->account->getKey(), 'webhook');
        $this->assertSame(AfterSalesStatus::Completed, OrderReturn::withoutGlobalScope(TenantScope::class)->first()->status);
        $this->assertFalse((bool) $order->fresh()->has_return);
    }

    public function test_upsert_without_matching_order_keeps_null_order_id(): void
    {
        // đơn gốc chưa sync ⇒ order_id null, vẫn lưu
        $ret = app(ReturnUpsertContract::class)->upsert($this->dto(['order' => 'NOT-SYNCED']), (int) $this->tenant->getKey(), (int) $this->account->getKey(), 'polling');
        $this->assertNull($ret->order_id);
        $this->assertSame('NOT-SYNCED', $ret->external_order_id);
    }
}
