<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Fulfillment\Jobs\BackfillChannelLabels;
use CMBcoreSeller\Modules\Fulfillment\Jobs\BackfillChannelTracking;
use CMBcoreSeller\Modules\Fulfillment\Jobs\FetchChannelLabel;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * BackfillChannelLabels: kéo lại tem sàn cho đơn còn xử lý mà AWB/tem về muộn (sau khi FetchChannelLabel
 * hết lượt retry). Chỉ re-dispatch cho vận đơn ĐỦ điều kiện, bỏ qua đơn không cần / đang retry / quá cũ.
 */
class BackfillChannelLabelsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'shopee',
            'external_shop_id' => 'sp-1', 'shop_name' => 'Shopee', 'status' => ChannelAccount::STATUS_ACTIVE,
        ]);
    }

    /** @param array<string,mixed> $orderOver @param array<string,mixed> $shipOver */
    private function makeOrderShipment(string $ext, array $orderOver = [], array $shipOver = []): Shipment
    {
        $order = Order::withoutGlobalScope(TenantScope::class)->create(array_merge([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'shopee',
            'channel_account_id' => $this->account->getKey(),
            'external_order_id' => $ext, 'order_number' => $ext,
            'status' => StandardOrderStatus::Processing, 'raw_status' => 'PROCESSED',
            'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000,
            'placed_at' => now()->subHours(2), 'tags' => [], 'carrier' => 'SPX Express',
            'source_updated_at' => now()->subHours(2),
        ], $orderOver));

        return Shipment::withoutGlobalScope(TenantScope::class)->create(array_merge([
            'tenant_id' => $this->tenant->getKey(), 'order_id' => $order->getKey(),
            'carrier' => 'SPX Express', 'tracking_no' => null, 'status' => Shipment::STATUS_CREATED,
            'cod_amount' => 0, 'label_path' => null, 'label_fetch_next_retry_at' => null, 'raw' => [],
        ], $shipOver));
    }

    public function test_redispatches_only_eligible_shipments(): void
    {
        Queue::fake();

        // CHƯA có tracking ⇒ có thể sàn chưa arrange (ship_order chưa chạy) ⇒ BackfillChannelTracking re-arrange.
        $needArrange = $this->makeOrderShipment('SP-ELIGIBLE');                                          // ✓ re-arrange
        // Đã có tracking nhưng tem về muộn ⇒ chỉ cần kéo tem (FetchChannelLabel).
        $needLabel = $this->makeOrderShipment('SP-HAS-TRACKING', [], ['tracking_no' => 'SPXVN123']);      // ✓ kéo tem
        $this->makeOrderShipment('SP-HAS-LABEL', [], ['label_path' => 'tenants/1/labels/x.pdf']);        // đã có tem
        $this->makeOrderShipment('SP-IN-RETRY', [], ['label_fetch_next_retry_at' => now()->addMinutes(5)]); // đang retry
        $this->makeOrderShipment('SP-UNAVAILABLE', [], ['raw' => ['label_unavailable' => ['message' => 'DBS']]]); // sàn không cấp
        $this->makeOrderShipment('SP-OLD', ['placed_at' => now()->subDays(30)]);                         // quá cũ
        $this->makeOrderShipment('SP-SHIPPED', ['status' => StandardOrderStatus::Shipped]);              // không còn ở processing
        $manualReady = $this->makeOrderShipment('SP-RTS-READY', ['status' => StandardOrderStatus::ReadyToShip]); // ✓ chưa tracking ⇒ re-arrange

        // đơn manual (không có channel_account_id) — không phải đơn sàn
        $manualOrder = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'manual', 'channel_account_id' => null,
            'external_order_id' => 'M-1', 'order_number' => 'M-1', 'status' => StandardOrderStatus::Processing,
            'raw_status' => 'processing', 'currency' => 'VND', 'grand_total' => 1, 'item_total' => 1,
            'placed_at' => now()->subHour(), 'tags' => [], 'carrier' => 'manual', 'source_updated_at' => now()->subHour(),
        ]);
        Shipment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'order_id' => $manualOrder->getKey(), 'carrier' => 'manual',
            'status' => Shipment::STATUS_CREATED, 'cod_amount' => 0, 'label_path' => null, 'raw' => [],
        ]);

        (new BackfillChannelLabels)->handle();

        // Vận đơn chưa có tracking ⇒ re-arrange (ship_order idempotent), KHÔNG chỉ kéo tem.
        $arrangeIds = [$needArrange->getKey(), $manualReady->getKey()];
        Queue::assertPushed(BackfillChannelTracking::class, 2);
        Queue::assertPushed(BackfillChannelTracking::class, fn (BackfillChannelTracking $j) => in_array($j->shipmentId, $arrangeIds, true));

        // Vận đơn đã có tracking, thiếu tem ⇒ chỉ kéo tem.
        Queue::assertPushed(FetchChannelLabel::class, 1);
        Queue::assertPushed(FetchChannelLabel::class, fn (FetchChannelLabel $j) => $j->shipmentId === $needLabel->getKey());
    }
}
