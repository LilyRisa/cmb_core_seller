<?php

namespace CMBcoreSeller\Integrations\Carriers\JtExpress;

use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;

/**
 * Map `scanTypeCode` (Tracking Query + Webhook) sang trạng thái shipment chuẩn. Nguồn:
 * docs/superpowers/research/2026-07-17-jt-express-api-reference.md §5.3.
 *
 * ⚠️ J&T công bố bảng 103-121 nhưng ví dụ response THẬT của chính họ dùng bộ mã khác hẳn (10/50/92/94/100)
 * cho cùng ý nghĩa — 2 bộ mã không phải tập con của nhau. Map CẢ HAI. Mã lạ (kể cả 120/121, xem test) →
 * null thay vì đoán — CarrierWebhookController coi null là "không đổi status", chỉ ghi event. Bổ sung dần
 * khi gặp mã mới qua log thật (xem JtExpressConnector::getTracking/parseWebhook).
 */
final class JtExpressStatusMap
{
    /** @var array<int, string> */
    private const MAP = [
        // Bảng công bố.
        103 => Shipment::STATUS_CREATED,
        104 => Shipment::STATUS_FAILED,
        105 => Shipment::STATUS_CANCELLED,
        106 => Shipment::STATUS_PICKED_UP,
        109 => Shipment::STATUS_IN_TRANSIT,
        110 => Shipment::STATUS_IN_TRANSIT,
        112 => Shipment::STATUS_IN_TRANSIT,
        113 => Shipment::STATUS_DELIVERED,
        116 => Shipment::STATUS_RETURNING,
        117 => Shipment::STATUS_RETURNED,
        118 => Shipment::STATUS_FAILED,
        // 120 (Return Problem), 121 (FINISH — chỉ là marker) cố ý KHÔNG map, xem docblock lớp.
        // Mã thực tế quan sát trong ví dụ response thật (khác bảng công bố).
        10 => Shipment::STATUS_PICKED_UP,
        50 => Shipment::STATUS_IN_TRANSIT,
        92 => Shipment::STATUS_IN_TRANSIT,
        94 => Shipment::STATUS_IN_TRANSIT,
        100 => Shipment::STATUS_DELIVERED,
    ];

    public static function toShipmentStatus(int|string|null $scanTypeCode): ?string
    {
        if ($scanTypeCode === null || $scanTypeCode === '') {
            return null;
        }

        return self::MAP[(int) $scanTypeCode] ?? null;
    }
}
