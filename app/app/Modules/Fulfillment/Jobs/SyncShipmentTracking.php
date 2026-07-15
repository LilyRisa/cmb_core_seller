<?php

namespace CMBcoreSeller\Modules\Fulfillment\Jobs;

use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Fulfillment\Services\ShipmentService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Polls carriers for tracking updates on still-in-flight shipments (created/picked_up/
 * in_transit/failed) and syncs shipment + order status. Scheduled ~every 30'. With no
 * id it sweeps all such shipments (chunked); with an id it refreshes just that one.
 * See SPEC 0006 §3.5.
 */
class SyncShipmentTracking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly ?int $shipmentId = null) {}

    public function handle(ShipmentService $service, CurrentTenant $tenant): void
    {
        $live = [Shipment::STATUS_CREATED, Shipment::STATUS_PICKED_UP, Shipment::STATUS_IN_TRANSIT, Shipment::STATUS_FAILED];
        if ($this->shipmentId) {
            $s = Shipment::withoutGlobalScope(TenantScope::class)->find($this->shipmentId);
            if ($s && in_array($s->status, $live, true)) {
                $this->syncOne($service, $tenant, $s);
            }

            return;
        }
        Shipment::withoutGlobalScope(TenantScope::class)->whereIn('status', $live)->whereNotNull('tracking_no')
            ->where('carrier', '!=', 'manual')->orderBy('id')
            ->chunkById(200, fn ($chunk) => $chunk->each(fn (Shipment $s) => $this->syncOne($service, $tenant, $s)));
    }

    /**
     * Chạy `syncTracking` DƯỚI tenant của chính shipment đó. Job này quét CROSS-TENANT
     * (withoutGlobalScope ở trên) nhưng ShipmentService::syncTracking() bên trong lại đọc
     * $shipment->carrierAccount (model tenant-scoped qua BelongsToTenant) — không set tenant
     * hiện tại thì TenantScope ép tenant_id=0, quan hệ luôn ra null, connector luôn "chưa có
     * token" dù carrier_account thật sự có token hợp lệ. Cùng lớp lỗi thiếu runAs đã gặp ở
     * FetchChannelLabel — xem FetchChannelLabelTenantContextTest.
     */
    private function syncOne(ShipmentService $service, CurrentTenant $tenant, Shipment $shipment): void
    {
        $shop = Tenant::query()->find($shipment->tenant_id);
        if ($shop === null) {
            return;
        }
        $tenant->runAs($shop, fn () => $service->syncTracking($shipment));
    }
}
