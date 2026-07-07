<?php

namespace CMBcoreSeller\Integrations\Carriers\ViettelPost;

use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;

/**
 * Map ORDER_STATUS (mã trạng thái nội bộ VTP — số nguyên) → trạng thái shipment chuẩn của app.
 * Nguồn: tài liệu Webhook partner2.viettelpost.vn/document/webhook ("Bảng danh sách trạng thái").
 *
 * Một số code là "trạng thái cuối" (đơn dừng cập nhật): 101/104/107/201/501/503/504.
 */
final class ViettelPostStatusMap
{
    /** @var array<int, string> */
    private const MAP = [
        // Lấy hàng / điều phối — bên ta vẫn coi là "chờ lấy hàng".
        103 => Shipment::STATUS_AWAITING_PICKUP,   // Điều phối bưu cục lấy hàng
        104 => Shipment::STATUS_AWAITING_PICKUP,    // Lấy hàng điều phối bưu tá (cuối ở nhánh lấy)
        // Lấy hàng thành công ⇒ đã giao cho ĐVVC.
        200 => Shipment::STATUS_PICKED_UP,
        202 => Shipment::STATUS_PICKED_UP,          // Sửa phiếu gửi — vẫn đang trong tuyến
        // Trung chuyển / đang phát.
        300 => Shipment::STATUS_IN_TRANSIT,         // Khai thác đi
        400 => Shipment::STATUS_IN_TRANSIT,         // Khai thác đến
        500 => Shipment::STATUS_IN_TRANSIT,         // Giao bưu tá đi phát
        508 => Shipment::STATUS_IN_TRANSIT,         // Phát tiếp (đơn vị yêu cầu)
        509 => Shipment::STATUS_IN_TRANSIT,         // Chuyển tiếp bưu cục khác
        550 => Shipment::STATUS_IN_TRANSIT,         // Phát tiếp (khách yêu cầu)
        // Giao thành công.
        501 => Shipment::STATUS_DELIVERED,
        // Thất bại / phát lại.
        102 => Shipment::STATUS_FAILED,             // Lấy hàng thất bại
        506 => Shipment::STATUS_FAILED,             // Phát thất bại
        507 => Shipment::STATUS_FAILED,             // KH đến bưu cục nhận (phát thất bại)
        // Hoàn hàng — đang xử lý/trên đường hoàn (chưa về tới kho) ⇒ RETURNING.
        505 => Shipment::STATUS_RETURNING,          // Yêu cầu chuyển hoàn
        515 => Shipment::STATUS_RETURNING,          // Duyệt hoàn
        504 => Shipment::STATUS_RETURNED,           // Thành công - chuyển trả người gửi (cuối)
        // Hủy / tiêu hủy.
        101 => Shipment::STATUS_CANCELLED,          // VTP hủy lấy hàng (cuối)
        107 => Shipment::STATUS_CANCELLED,          // Đối tác yêu cầu hủy (cuối)
        201 => Shipment::STATUS_CANCELLED,          // VTP hủy đơn hàng (cuối)
        503 => Shipment::STATUS_CANCELLED,          // Tiêu hủy theo yêu cầu KH (cuối)
    ];

    /** Trạng thái cuối — đơn dừng cập nhật hành trình (theo tài liệu Webhook). */
    private const FINAL = [101, 104, 107, 201, 501, 503, 504];

    public static function toShipmentStatus(int|string|null $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }

        return self::MAP[(int) $code] ?? null;
    }

    public static function isFinal(int|string|null $code): bool
    {
        return $code !== null && $code !== '' && in_array((int) $code, self::FINAL, true);
    }
}
