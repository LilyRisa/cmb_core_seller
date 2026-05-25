<?php

namespace CMBcoreSeller\Modules\Fulfillment\Jobs;

use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Fulfillment\Services\ShipmentService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Async retry để kéo **mã vận đơn (tracking)** của sàn khi sàn cấp TRỄ — đặc biệt TikTok: sau
 * `POST /packages/{id}/ship`, `tracking_number` thường chưa có ngay (TikTok gán async). Khi đó
 * `ShipmentService::prepareChannelOrder` tạo vận đơn với tracking rỗng + enqueue job này. Job gọi
 * `backfillChannelTracking` (arrange idempotent — chỉ GET package detail, KHÔNG ship lại) đến khi có
 * tracking ⇒ cập nhật shipment + lấy tem ⇒ list FE tự thấy mã vận đơn, không cần user bấm tay. SPEC 0014.
 */
class BackfillChannelTracking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Retry sau 30s, 60s, 120s, 300s, 600s ⇒ tổng ~18' chờ sàn cấp tracking. */
    public int $tries = 5;

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 60, 120, 300, 600];
    }

    public function __construct(public readonly int $shipmentId) {}

    public function handle(ShipmentService $service): void
    {
        $shipment = Shipment::withoutGlobalScope(TenantScope::class)->find($this->shipmentId);
        if (! $shipment) {
            return;
        }
        if (filled($shipment->tracking_no)) {
            return;   // tracking đã có (lần trước hoặc luồng khác)
        }
        $order = Order::withoutGlobalScope(TenantScope::class)->find($shipment->order_id);
        if (! $order) {
            return;
        }
        if ($service->backfillChannelTracking($order, $shipment)) {
            Log::info('shipment.backfill_tracking_ok', ['shipment' => $shipment->getKey(), 'attempt' => $this->attempts()]);

            return;
        }
        // Chưa có tracking ⇒ để Laravel queue retry theo backoff.
        throw new \RuntimeException('Sàn chưa cấp mã vận đơn — sẽ retry theo backoff.');
    }
}
