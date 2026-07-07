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
        // Đang trên đường trả về người gửi (chưa tới nơi) ⇒ RETURNING, tách khỏi "đã về kho" (RETURNED).
        'return' => Shipment::STATUS_RETURNING,
        'returning' => Shipment::STATUS_RETURNING,
        'return_transporting' => Shipment::STATUS_RETURNING,
        'return_sorting' => Shipment::STATUS_RETURNING,
        // GHN "List Of Shipping Status" (api.ghn.vn id=48): hoàn hàng THẤT BẠI — hàng không giao
        // được lẫn không trả được về người gửi ⇒ cần xử lý (đánh "thất bại", KHÔNG phải đã hoàn).
        'return_fail' => Shipment::STATUS_FAILED,
        // Đã về tới kho người gửi — hoàn tất hoàn hàng.
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
