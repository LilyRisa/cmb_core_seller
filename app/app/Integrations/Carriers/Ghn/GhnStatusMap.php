<?php

namespace CMBcoreSeller\Integrations\Carriers\Ghn;

use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;

/** Maps GHN order statuses onto our canonical shipment statuses. */
final class GhnStatusMap
{
    /** @var array<string, string> */
    private const MAP = [
        // GHN system mới tiếp nhận đơn / shipper đã được phân — bên ta hiển thị "Chờ lấy hàng" (SPEC 0021).
        'ready_to_pick' => Shipment::STATUS_AWAITING_PICKUP,
        'picking' => Shipment::STATUS_AWAITING_PICKUP,
        'money_collect_picking' => Shipment::STATUS_AWAITING_PICKUP,
        // Shipper đã lấy hàng thành công ⇒ chuyển sang "Đã giao ĐVVC" + order → shipped.
        'picked' => Shipment::STATUS_PICKED_UP,
        'storing' => Shipment::STATUS_IN_TRANSIT,
        'transporting' => Shipment::STATUS_IN_TRANSIT,
        'sorting' => Shipment::STATUS_IN_TRANSIT,
        'delivering' => Shipment::STATUS_IN_TRANSIT,
        'money_collect_delivering' => Shipment::STATUS_IN_TRANSIT,
        'delivered' => Shipment::STATUS_DELIVERED,
        'delivery_fail' => Shipment::STATUS_FAILED,
        'waiting_to_return' => Shipment::STATUS_FAILED,
        'return' => Shipment::STATUS_RETURNED,
        'returning' => Shipment::STATUS_RETURNED,
        'return_transporting' => Shipment::STATUS_RETURNED,
        'return_sorting' => Shipment::STATUS_RETURNED,
        'returned' => Shipment::STATUS_RETURNED,
        'cancel' => Shipment::STATUS_CANCELLED,
        'exception' => Shipment::STATUS_FAILED,
        'lost' => Shipment::STATUS_FAILED,
        'damage' => Shipment::STATUS_FAILED,
    ];

    public static function toShipmentStatus(?string $ghn): ?string
    {
        return self::MAP[strtolower(trim((string) $ghn))] ?? null;
    }
}
