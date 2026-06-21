<?php

namespace CMBcoreSeller\Modules\Fulfillment\Jobs;

use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Reconciliation định kỳ "kéo lại tem sàn" — bù cho trường hợp AWB/tem của sàn về MUỘN.
 *
 * Bối cảnh (bằng chứng prod, đơn Shopee GHN COD): lúc "Chuẩn bị hàng", sàn chưa cấp mã vận đơn
 * (`LOGISTICS_REQUEST_CREATED`, chưa pickup). `FetchChannelLabel` retry ~50' rồi HẾT LƯỢT
 * (`label_fetch_next_retry_at` = null ⇒ vận đơn rơi sang "Nhận phiếu giao hàng"). Vài giờ sau sàn MỚI
 * cấp AWB + render tem — nhưng KHÔNG còn gì tự kéo lại ⇒ đơn kẹt "chưa có tem" dù tem đã sẵn sàng, phải
 * bấm tay (user không biết). `SyncShipmentTracking` chỉ poll ĐVVC, không kéo tem từ SÀN.
 *
 * Job này quét vận đơn open của đơn SÀN còn đang xử lý, chưa có tem, KHÔNG đang trong vòng retry và đơn
 * chưa quá cũ ⇒ dispatch lại `FetchChannelLabel`. Khi AWB đã có, lần này lấy được tem & lưu R2 ⇒ list FE
 * tự thấy "Có thể in" mà không cần thao tác. Idempotent (đơn `label_unavailable` / đã có tem bị bỏ qua;
 * vận đơn đang retry — `label_fetch_next_retry_at` còn hạn — không bị enqueue đè). Scheduled ~mỗi 15'.
 */
class BackfillChannelLabels implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Bỏ qua đơn quá cũ — bound công việc + tránh kéo tem cho đơn đã quên (đơn thật xong/huỷ rời filter). */
    public function __construct(public readonly int $maxAgeDays = 14) {}

    public function handle(): void
    {
        Shipment::withoutGlobalScope(TenantScope::class)
            ->whereIn('status', [Shipment::STATUS_PENDING, Shipment::STATUS_CREATED])
            ->whereNull('label_path')
            ->whereNull('label_fetch_next_retry_at')   // KHÔNG đang trong vòng retry (đã exhaust / chưa từng queue)
            ->whereExists(fn (Builder $q) => $q->selectRaw('1')->from('orders')
                ->whereColumn('orders.id', 'shipments.order_id')
                ->whereNotNull('orders.channel_account_id')                                   // chỉ đơn sàn (manual tự có tem/không cần)
                ->whereIn('orders.status', ['processing', 'ready_to_ship'])                   // còn cần tem
                ->whereNull('orders.deleted_at')
                ->where('orders.placed_at', '>=', now()->subDays($this->maxAgeDays)))
            ->orderBy('id')
            ->chunkById(200, function ($chunk) {
                foreach ($chunk as $shipment) {
                    if (data_get($shipment->raw, 'label_unavailable')) {
                        continue; // sàn không bao giờ cấp tem (vd Lazada DBS/SOF) ⇒ retry vô ích
                    }
                    FetchChannelLabel::dispatch((int) $shipment->getKey())->onQueue('labels');
                }
            });
    }
}
