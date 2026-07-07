<?php

namespace Tests\Unit\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\Ghn\GhnStatusMap;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use Tests\TestCase;

class GhnStatusMapTest extends TestCase
{
    /**
     * Toàn bộ 22 trạng thái trong "List Of Shipping Status" của GHN
     * (https://api.ghn.vn/home/docs/detail?id=48) — verify khớp API THẬT, không đoán.
     */
    public function test_maps_every_official_ghn_status(): void
    {
        $cases = [
            'ready_to_pick' => Shipment::STATUS_AWAITING_PICKUP,
            'picking' => Shipment::STATUS_AWAITING_PICKUP,
            'money_collect_picking' => Shipment::STATUS_AWAITING_PICKUP,
            'picked' => Shipment::STATUS_PICKED_UP,
            'storing' => Shipment::STATUS_IN_TRANSIT,
            'transporting' => Shipment::STATUS_IN_TRANSIT,
            'sorting' => Shipment::STATUS_IN_TRANSIT,
            'delivering' => Shipment::STATUS_IN_TRANSIT,
            'money_collect_delivering' => Shipment::STATUS_IN_TRANSIT,
            'delivered' => Shipment::STATUS_DELIVERED,
            'delivery_fail' => Shipment::STATUS_FAILED,
            'waiting_to_return' => Shipment::STATUS_FAILED,
            'return' => Shipment::STATUS_RETURNING,
            'return_transporting' => Shipment::STATUS_RETURNING,
            'return_sorting' => Shipment::STATUS_RETURNING,
            'returning' => Shipment::STATUS_RETURNING,
            'return_fail' => Shipment::STATUS_FAILED,   // hoàn hàng thất bại — trước đây bị thiếu
            'returned' => Shipment::STATUS_RETURNED,
            'cancel' => Shipment::STATUS_CANCELLED,
            'exception' => Shipment::STATUS_FAILED,
            'damage' => Shipment::STATUS_FAILED,
            'lost' => Shipment::STATUS_FAILED,
        ];
        foreach ($cases as $ghn => $expected) {
            $this->assertSame($expected, GhnStatusMap::toShipmentStatus($ghn), "GHN status '{$ghn}'");
        }
        // Mọi trạng thái GHN chính thức PHẢI có ánh xạ (không để rơi xuống null ⇒ sync bỏ qua).
        $this->assertCount(22, $cases);
    }

    public function test_is_case_insensitive_and_unknown_returns_null(): void
    {
        $this->assertSame(Shipment::STATUS_DELIVERED, GhnStatusMap::toShipmentStatus('DELIVERED'));
        $this->assertNull(GhnStatusMap::toShipmentStatus('khong_ton_tai'));
        $this->assertNull(GhnStatusMap::toShipmentStatus(null));
    }
}
