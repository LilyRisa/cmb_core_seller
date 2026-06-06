<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaConnector;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Fulfillment\Models\PrintJob;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Fulfillment\Services\ShipmentService;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * `auto_rts_after_print` (gian hàng Lazada): sau khi IN tem, tự đẩy `/order/rts` lên SÀN để Lazada
 * chuyển `packed → ready_to_ship`. NHƯNG phía APP phải GIỮ NGUYÊN: đơn `processing`, vận đơn `created`
 * — để kho vẫn quét xác nhận đóng hàng (markPacked thủ công). Cài đặt này chỉ cập nhật phía sàn, KHÔNG
 * được tự đẩy trạng thái nội bộ sang "Chờ bàn giao".
 */
class AutoRtsAfterPrintTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([PushStockForSku::class]);
        app(ChannelRegistry::class)->register('lazada', LazadaConnector::class);
        config([
            'integrations.lazada.app_key' => 'k',
            'integrations.lazada.app_secret' => 's',
            'integrations.lazada.fulfillment_enabled' => true,
        ]);
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    /** Tạo: gian hàng Lazada (autoRts), đơn channel `processing`, vận đơn `created` (có tracking + item ids), print job `label`. Trả [order, shipment, jobId]. */
    private function scenario(bool $autoRts): array
    {
        $shop = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 'LZ-1', 'shop_name' => 'LZ', 'status' => 'active',
            'access_token' => 'tk', 'refresh_token' => 'rk', 'token_expires_at' => now()->addDays(7),
            'auto_rts_after_print' => $autoRts,
        ]);
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $shop->getKey(),
            'source' => 'lazada', 'external_order_id' => 'EO-1', 'status' => 'processing',
        ]);
        $shipment = Shipment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'order_id' => $order->getKey(),
            'carrier' => 'GHN', 'tracking_no' => 'LZTN-1', 'status' => Shipment::STATUS_CREATED,
            'raw' => ['external_item_ids' => [9001]],
        ]);
        $job = PrintJob::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'type' => PrintJob::TYPE_LABEL,
            'scope' => ['shipment_ids' => [$shipment->getKey()]],
            'status' => PrintJob::STATUS_DONE, 'created_by' => $this->owner->getKey(),
        ]);

        return [$order, $shipment, $job->getKey()];
    }

    private function lazadaOk(): array
    {
        return ['code' => '0', 'type' => '', 'request_id' => 'rq', 'data' => []];
    }

    /** Flag ON: in xong → đẩy /order/rts lên SÀN, nhưng APP giữ `processing` + vận đơn `created` (kho còn quét đóng hàng). */
    public function test_auto_rts_pushes_to_marketplace_but_keeps_app_processing(): void
    {
        Http::fake(['*/order/rts*' => Http::response($this->lazadaOk())]);
        [$order, $shipment, $jobId] = $this->scenario(autoRts: true);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/print-jobs/{$jobId}/mark-printed", ['copies' => 1])->assertOk();

        // SÀN: /order/rts đã được gọi với tracking của vận đơn.
        Http::assertSent(fn ($req) => str_contains($req->url(), '/order/rts') && str_contains((string) $req->body(), 'LZTN-1'));
        // APP: KHÔNG đổi trạng thái — vận đơn vẫn `created`, đơn vẫn `processing`.
        $this->assertSame(Shipment::STATUS_CREATED, Shipment::withoutGlobalScope(TenantScope::class)->find($shipment->getKey())->status);
        $this->assertSame(StandardOrderStatus::Processing, Order::withoutGlobalScope(TenantScope::class)->find($order->getKey())->status);
    }

    /** Flag OFF: không gọi sàn, app giữ nguyên (vận đơn `created`, đơn `processing`). */
    public function test_no_auto_rts_when_flag_off(): void
    {
        Http::fake();
        [$order, $shipment, $jobId] = $this->scenario(autoRts: false);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/print-jobs/{$jobId}/mark-printed", ['copies' => 1])->assertOk();

        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/order/rts'));
        $this->assertSame(Shipment::STATUS_CREATED, Shipment::withoutGlobalScope(TenantScope::class)->find($shipment->getKey())->status);
        $this->assertSame(StandardOrderStatus::Processing, Order::withoutGlobalScope(TenantScope::class)->find($order->getKey())->status);
    }

    /** Kho quét đóng hàng SAU auto-RTS: markPacked KHÔNG đẩy /order/rts lần 2 (idempotent), app mới tiến tới `ready_to_ship`. */
    public function test_manual_pack_after_auto_rts_does_not_double_push_rts(): void
    {
        Http::fake(['*/order/rts*' => Http::response($this->lazadaOk())]);
        [$order, $shipment, $jobId] = $this->scenario(autoRts: true);

        // 1) In xong → auto RTS đẩy lên sàn 1 lần; app vẫn processing.
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/print-jobs/{$jobId}/mark-printed", ['copies' => 1])->assertOk();

        // 2) Kho quét xác nhận đóng hàng → markPacked: bỏ qua đẩy RTS (đã đẩy), nhưng tiến app sang Chờ bàn giao.
        app(ShipmentService::class)->markPacked(
            Shipment::withoutGlobalScope(TenantScope::class)->find($shipment->getKey()), 'user', $this->owner->getKey()
        );

        Http::assertSentCount(1); // chỉ 1 lần /order/rts, KHÔNG đẩy lại khi quét
        $this->assertSame(StandardOrderStatus::ReadyToShip, Order::withoutGlobalScope(TenantScope::class)->find($order->getKey())->status);
        $this->assertContains(
            Shipment::withoutGlobalScope(TenantScope::class)->find($shipment->getKey())->status,
            [Shipment::STATUS_PACKED, Shipment::STATUS_AWAITING_PICKUP]
        );
    }
}
