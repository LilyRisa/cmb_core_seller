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

    /** Retry sau 15s, 30s, 60s, 120s, 300s ⇒ tổng ~8' đợi 3PL render. */
    public int $tries = 5;

    /** @return list<int> */
    public function backoff(): array
    {
        return [15, 30, 60, 120, 300];
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
        if (! $shipment->tracking_no) {
            return;   // chưa có tracking ⇒ chưa thể fetch tem
        }
        $order = Order::withoutGlobalScope(TenantScope::class)->find($shipment->order_id);
        if (! $order) {
            return;
        }
        $service->retryChannelLabelFetch($order, $shipment);
        $shipment->refresh();
        if (blank($shipment->label_path)) {
            // Vẫn rỗng ⇒ để Laravel queue retry theo backoff. Cập nhật `label_fetch_next_retry_at` về thời
            // điểm retry kế (nếu còn tries) HOẶC clear (nếu hết tries ⇒ vận đơn rơi vào "Nhận phiếu giao
            // hàng" để user retry thủ công). Backoff: [15, 30, 60, 120, 300] ⇒ retry kế = backoff[$attempt].
            $backoff = $this->backoff();
            $attempt = $this->attempts();
            $nextRetry = $attempt < $this->tries && isset($backoff[$attempt])
                ? now()->addSeconds((int) $backoff[$attempt])
                : null;
            $shipment->forceFill(['label_fetch_next_retry_at' => $nextRetry])->save();
            Log::info('shipment.fetch_channel_label_async_pending', ['shipment' => $shipment->getKey(), 'attempt' => $attempt, 'next_retry_at' => $nextRetry?->toIso8601String()]);
            throw new \RuntimeException('Sàn chưa render xong tem — sẽ retry theo backoff.');
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
