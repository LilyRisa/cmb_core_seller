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
 * Async retry để kéo AWB/tem PDF từ sàn (Lazada 3PL render PDF *async* 5–30s+ sau /order/rts). Khi sync
 * retry trong `ShipmentService::fetchAndStoreChannelLabel` exhausted mà PDF chưa sẵn ⇒ controller gọi
 * `ShipmentService::queueChannelLabelFetch()` ⇒ job này chạy ở queue `labels` với backoff 15s/30s/60s/
 * 120s/300s. Khi sàn trả PDF ⇒ `media->storeBytes` đẩy R2 + lưu `label_path` ⇒ list refresh thấy
 * `has_label=true`, render in về sau đọc R2, không gọi lại sàn. SPEC 0013 §6 / 0008b.
 */
class FetchChannelLabel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Kiên nhẫn ~50': tem/AWB render async sau khi sàn gán mã vận đơn — Shopee có cửa sổ propagation (vừa gán
     * tracking thì create_shipping_document báo `tracking_number_invalid`/`package_can_not_print` vài phút).
     * Dùng release() (không ném lỗi) nên KHÔNG đếm là job failed & không log ERROR khi đang chờ render.
     */
    public int $tries = 10;

    /** @return list<int> Delay giữa các lượt (giây) — tổng ~50'. */
    public function backoff(): array
    {
        return [15, 30, 60, 120, 300, 300, 600, 600, 900, 900];
    }

    public function __construct(public readonly int $shipmentId) {}

    public function handle(ShipmentService $service): void
    {
        $shipment = Shipment::withoutGlobalScope(TenantScope::class)->find($this->shipmentId);
        if (! $shipment) {
            return;
        }
        if (filled($shipment->label_path)) {
            // Đã có tem (lần retry trước đã lấy được, hoặc luồng khác) ⇒ clear retry marker.
            $shipment->forceFill(['label_fetch_next_retry_at' => null])->save();

            return;
        }
        if (data_get($shipment->raw, 'label_unavailable')) {
            // Terminal: sàn không bao giờ cấp tem cho đơn này (vd Lazada DBS/SOF) ⇒ ngừng retry vĩnh viễn.
            $shipment->forceFill(['label_fetch_next_retry_at' => null])->save();

            return;
        }
        $order = Order::withoutGlobalScope(TenantScope::class)->find($shipment->order_id);
        if (! $order) {
            return;
        }
        // Chưa có tracking: hầu hết sàn (TikTok/Lazada) chưa thể lấy tem ⇒ dừng (giữ NGUYÊN luồng cũ). RIÊNG
        // sàn cấp AWB ĐỘC LẬP với mã vận đơn (Shopee — capability shipping.document_before_tracking) thì vẫn
        // lấy được tem khi đã arrange ⇒ tiếp tục, không để đơn kẹt thiếu tem chờ tracking async.
        if (! $shipment->tracking_no && ! $service->channelLabelBeforeTracking($order)) {
            return;
        }
        $service->retryChannelLabelFetch($order, $shipment);
        $shipment->refresh();
        if (blank($shipment->label_path)) {
            // Vẫn rỗng ⇒ release retry theo backoff (KHÔNG ném lỗi → không log ERROR giả; tem render async là
            // bình thường). `label_fetch_next_retry_at` để UI xếp vào "Đang tải lại". Hết lượt ⇒ clear marker ⇒
            // vận đơn rơi sang "Nhận phiếu giao hàng" cho user retry tay.
            $backoff = $this->backoff();
            $attempt = $this->attempts();
            if ($attempt < $this->tries) {
                $delay = (int) ($backoff[$attempt - 1] ?? 900);
                $shipment->forceFill(['label_fetch_next_retry_at' => now()->addSeconds($delay)])->save();
                Log::info('shipment.fetch_channel_label_async_pending', ['shipment' => $shipment->getKey(), 'attempt' => $attempt]);
                $this->release($delay);

                return;
            }
            $shipment->forceFill(['label_fetch_next_retry_at' => null])->save();
            Log::warning('shipment.fetch_channel_label_async_exhausted', ['shipment' => $shipment->getKey(), 'attempts' => $attempt]);

            return;
        }
        // Lấy thành công ⇒ retryChannelLabelFetch đã clear `label_fetch_next_retry_at` qua
        // `ShipmentService::fetchAndStoreChannelLabel` (forceFill khi save label_path).
        Log::info('shipment.fetch_channel_label_async_ok', ['shipment' => $shipment->getKey(), 'attempt' => $this->attempts()]);
    }

    /**
     * Job exhausted tất cả tries (Laravel gọi sau khi `throw` ở lần cuối). Clear marker ⇒ vận đơn rời
     * sub-tab "Đang tải lại" sang "Nhận phiếu giao hàng" để user retry thủ công.
     */
    public function failed(\Throwable $e): void
    {
        $shipment = Shipment::withoutGlobalScope(TenantScope::class)->find($this->shipmentId);
        if ($shipment && blank($shipment->label_path)) {
            $shipment->forceFill(['label_fetch_next_retry_at' => null])->save();
        }
        Log::warning('shipment.fetch_channel_label_async_failed', ['shipment' => $this->shipmentId, 'error' => $e->getMessage()]);
    }
}
