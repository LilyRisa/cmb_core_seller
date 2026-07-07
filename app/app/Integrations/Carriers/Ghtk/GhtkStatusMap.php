<?php

namespace CMBcoreSeller\Integrations\Carriers\Ghtk;

use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;

/**
 * Maps GHTK numeric `status_id` onto our canonical shipment statuses.
 * Tham khảo bảng trạng thái GHTK (api.ghtk.vn/docs/submit-order/tracking-status).
 */
final class GhtkStatusMap
{
    /** @var array<int, string> */
    private const MAP = [
        -1 => Shipment::STATUS_CANCELLED,        // Hủy đơn
        1 => Shipment::STATUS_AWAITING_PICKUP,   // Chưa tiếp nhận
        2 => Shipment::STATUS_AWAITING_PICKUP,   // Đã tiếp nhận
        12 => Shipment::STATUS_AWAITING_PICKUP,  // Đang lấy hàng
        123 => Shipment::STATUS_AWAITING_PICKUP, // Shipper báo đã/đang lấy
        3 => Shipment::STATUS_PICKED_UP,         // Đã lấy hàng / nhập kho
        4 => Shipment::STATUS_IN_TRANSIT,        // Đang giao hàng
        45 => Shipment::STATUS_IN_TRANSIT,       // Shipper báo đang giao
        5 => Shipment::STATUS_DELIVERED,         // Đã giao hàng (chưa đối soát)
        6 => Shipment::STATUS_DELIVERED,         // Đã đối soát
        7 => Shipment::STATUS_FAILED,            // Không lấy được hàng
        8 => Shipment::STATUS_FAILED,            // Hoãn lấy hàng
        9 => Shipment::STATUS_FAILED,            // Không giao được hàng
        10 => Shipment::STATUS_FAILED,           // Delay giao hàng
        49 => Shipment::STATUS_FAILED,           // Shipper báo không giao được
        127 => Shipment::STATUS_FAILED,          // Shipper báo không lấy được
        128 => Shipment::STATUS_FAILED,          // Shipper báo delay lấy
        11 => Shipment::STATUS_RETURNED,         // Đã đối soát công nợ trả hàng
        13 => Shipment::STATUS_RETURNED,         // Đơn bồi hoàn
        20 => Shipment::STATUS_RETURNING,        // Đang trả hàng (chưa về tới kho)
        21 => Shipment::STATUS_RETURNED,         // Đã trả hàng
    ];

    public static function toShipmentStatus(int|string|null $statusId): ?string
    {
        if ($statusId === null || $statusId === '') {
            return null;
        }

        return self::MAP[(int) $statusId] ?? null;
    }
}
