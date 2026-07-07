<?php

namespace Tests\Unit\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\Ghtk\GhtkStatusMap;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use Tests\TestCase;

class GhtkStatusMapTest extends TestCase
{
    public function test_maps_ghtk_status_ids_to_canonical_shipment_statuses(): void
    {
        $cases = [
            -1 => Shipment::STATUS_CANCELLED,
            1 => Shipment::STATUS_AWAITING_PICKUP,   // chưa tiếp nhận
            2 => Shipment::STATUS_AWAITING_PICKUP,   // đã tiếp nhận
            12 => Shipment::STATUS_AWAITING_PICKUP,  // đang lấy hàng
            123 => Shipment::STATUS_AWAITING_PICKUP, // shipper báo đang lấy
            3 => Shipment::STATUS_PICKED_UP,         // đã lấy/nhập kho
            4 => Shipment::STATUS_IN_TRANSIT,        // đang giao
            45 => Shipment::STATUS_IN_TRANSIT,       // shipper báo đang giao
            5 => Shipment::STATUS_DELIVERED,         // đã giao
            6 => Shipment::STATUS_DELIVERED,         // đã đối soát
            7 => Shipment::STATUS_FAILED,            // không lấy được hàng
            9 => Shipment::STATUS_FAILED,            // không giao được hàng
            20 => Shipment::STATUS_RETURNING,        // đang trả hàng (chưa về tới kho)
            21 => Shipment::STATUS_RETURNED,         // đã trả hàng
        ];
        foreach ($cases as $statusId => $expected) {
            $this->assertSame($expected, GhtkStatusMap::toShipmentStatus($statusId), "status_id {$statusId}");
        }
    }

    public function test_accepts_string_status_id_from_webhook(): void
    {
        $this->assertSame(Shipment::STATUS_DELIVERED, GhtkStatusMap::toShipmentStatus('5'));
    }

    public function test_unknown_status_id_returns_null(): void
    {
        $this->assertNull(GhtkStatusMap::toShipmentStatus(999));
        $this->assertNull(GhtkStatusMap::toShipmentStatus(null));
    }
}
