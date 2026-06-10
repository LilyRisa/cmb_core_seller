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

    /**
     * Kiên nhẫn ~90': sàn gán mã vận đơn ASYNC, có thể TRỄ 10–30'+ sau arrange (Shopee SPX live cấp muộn hơn
     * cửa sổ 18' cũ ⇒ đơn kẹt không có tem). Dùng release() (không ném lỗi) nên KHÔNG đếm là job failed.
     */
    public int $tries = 12;

    /** @return list<int> Delay giữa các lượt (giây) — tổng ~90'. */
    public function backoff(): array
    {
        return [30, 60, 120, 300, 600, 600, 900, 900, 900, 1200, 1800];
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
        // Còn lượt ⇒ release (KHÔNG ném lỗi → không log ERROR giả mỗi lần chờ; chờ mã vận đơn là bình thường,
        // sàn cấp ASYNC phụ thuộc 3PL). backoff theo attempt hiện tại; rỗng thì dùng 1800s.
        $backoff = $this->backoff();
        if ($this->attempts() < $this->tries) {
            $this->release($backoff[$this->attempts() - 1] ?? 1800);

            return;
        }
        // Hết lượt mà vẫn rỗng: KHÔNG phải lỗi hệ thống. Kênh non_integrated seller tự nhập mã (API không trả),
        // hoặc 3PL cấp rất trễ. Chỉ cảnh báo — mã còn về qua order_trackingno_push (code 4) / đồng bộ đơn / "Nhận phiếu".
        Log::warning('shipment.backfill_tracking_pending', ['shipment' => $shipment->getKey(), 'order' => $order->getKey(), 'attempts' => $this->attempts()]);
    }
}
