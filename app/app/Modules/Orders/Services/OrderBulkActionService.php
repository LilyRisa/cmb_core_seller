<?php

namespace CMBcoreSeller\Modules\Orders\Services;

use CMBcoreSeller\Modules\Orders\Events\OrderUpserted;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderStatusHistory;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;

/**
 * Thao tác HÀNG LOẠT đơn ở danh sách: huỷ (local "ngừng theo dõi") + xoá mềm.
 *
 * Huỷ ở đây KHÁC `ManualOrderService::cancel` (đơn lẻ, manual-only, chặn nếu đã đẩy
 * ĐVVC). Đây là "đẩy về Đã huỷ trong app + ngừng theo dõi": áp cho MỌI nguồn (sàn /
 * thủ công có-mã-vận-đơn) và TUYỆT ĐỐI KHÔNG đẩy thao tác huỷ lên sàn/ĐVVC. Đánh cờ
 * `meta.tracking_stopped` ⇒ `OrderUpsertService` không hồi sinh đơn khi sync ngược.
 */
class OrderBulkActionService
{
    /** Huỷ local + ngừng theo dõi. Trả false nếu đơn đã huỷ sẵn (chỉ bảo đảm cờ). */
    public function cancelLocally(Order $order, ?int $userId, ?string $reason): bool
    {
        $meta = (array) ($order->meta ?? []);

        if ($order->status === StandardOrderStatus::Cancelled) {
            if (empty($meta['tracking_stopped'])) {
                $meta['tracking_stopped'] = true;
                $order->forceFill(['meta' => $meta])->save();
            }

            return false;
        }

        $from = $order->status;
        $now = now();
        $meta['tracking_stopped'] = true;
        $order->forceFill([
            'status' => StandardOrderStatus::Cancelled,
            'raw_status' => 'cancelled',
            'cancel_reason' => $reason,
            'cancelled_at' => $now,
            'source_updated_at' => $now,
            'meta' => $meta,
        ])->save();

        OrderStatusHistory::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->getKey(),
            'from_status' => $from->value,
            'to_status' => 'cancelled',
            'raw_status' => 'cancelled',
            'source' => OrderStatusHistory::SOURCE_USER,
            'changed_at' => $now,
            'payload' => ['cancelled_by' => $userId, 'reason' => $reason, 'local_stop_tracking' => true],
            'created_at' => $now,
        ]);

        // Recompute tồn kho (giải phóng giữ chỗ) như huỷ thường — KHÔNG gọi API sàn/ĐVVC.
        OrderUpserted::dispatch($order, false);

        return true;
    }

    /** Xoá mềm — CHỈ đơn đã huỷ (luồng: huỷ rồi mới xoá). Trả false nếu chưa huỷ. */
    public function softDelete(Order $order): bool
    {
        if ($order->status !== StandardOrderStatus::Cancelled) {
            return false;
        }
        $order->delete();

        return true;
    }
}
