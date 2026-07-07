<?php

namespace Tests\Unit\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\ViettelPost\ViettelPostStatusMap;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use Tests\TestCase;

class ViettelPostStatusMapTest extends TestCase
{
    public function test_maps_vtp_order_status_codes_to_canonical_shipment_statuses(): void
    {
        $cases = [
            103 => Shipment::STATUS_AWAITING_PICKUP,  // điều phối bưu cục lấy hàng
            104 => Shipment::STATUS_AWAITING_PICKUP,  // điều phối bưu tá
            200 => Shipment::STATUS_PICKED_UP,        // lấy hàng thành công
            300 => Shipment::STATUS_IN_TRANSIT,       // khai thác đi
            400 => Shipment::STATUS_IN_TRANSIT,       // khai thác đến
            500 => Shipment::STATUS_IN_TRANSIT,       // giao bưu tá đi phát
            501 => Shipment::STATUS_DELIVERED,        // phát thành công
            506 => Shipment::STATUS_FAILED,           // phát thất bại
            505 => Shipment::STATUS_RETURNING,        // yêu cầu chuyển hoàn (chưa về tới kho)
            504 => Shipment::STATUS_RETURNED,         // chuyển trả người gửi
            101 => Shipment::STATUS_CANCELLED,        // VTP hủy lấy hàng
            107 => Shipment::STATUS_CANCELLED,        // đối tác hủy
            201 => Shipment::STATUS_CANCELLED,        // VTP hủy đơn
            503 => Shipment::STATUS_CANCELLED,        // tiêu hủy
        ];
        foreach ($cases as $code => $expected) {
            $this->assertSame($expected, ViettelPostStatusMap::toShipmentStatus($code), "ORDER_STATUS {$code}");
        }
    }

    public function test_accepts_string_status_code_from_webhook(): void
    {
        $this->assertSame(Shipment::STATUS_DELIVERED, ViettelPostStatusMap::toShipmentStatus('501'));
    }

    public function test_unknown_or_null_status_returns_null(): void
    {
        $this->assertNull(ViettelPostStatusMap::toShipmentStatus(999));
        $this->assertNull(ViettelPostStatusMap::toShipmentStatus(null));
        $this->assertNull(ViettelPostStatusMap::toShipmentStatus(''));
    }

    public function test_final_statuses(): void
    {
        foreach ([101, 104, 107, 201, 501, 503, 504] as $final) {
            $this->assertTrue(ViettelPostStatusMap::isFinal($final), "code {$final} phải là trạng thái cuối");
        }
        $this->assertFalse(ViettelPostStatusMap::isFinal(300));
        $this->assertFalse(ViettelPostStatusMap::isFinal(null));
    }
}
