<?php

namespace Tests\Unit\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\JtExpress\JtExpressStatusMap;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use Tests\TestCase;

class JtExpressStatusMapTest extends TestCase
{
    public function test_maps_documented_status_table(): void
    {
        // Bảng công bố ở trang Webhook/Tracking Query (open.jtexpress.vn/apiDoc/logistics/statusFeedback).
        $cases = [
            103 => Shipment::STATUS_CREATED,       // Order Placed
            104 => Shipment::STATUS_FAILED,        // Pickup Failure
            105 => Shipment::STATUS_CANCELLED,     // Cancel Order
            106 => Shipment::STATUS_PICKED_UP,     // Picked Up
            109 => Shipment::STATUS_IN_TRANSIT,    // Departure
            110 => Shipment::STATUS_IN_TRANSIT,    // Arrival
            112 => Shipment::STATUS_IN_TRANSIT,    // On Delivery
            113 => Shipment::STATUS_DELIVERED,     // Delivered
            116 => Shipment::STATUS_RETURNING,     // Returning
            117 => Shipment::STATUS_RETURNED,      // Returned Sign
            118 => Shipment::STATUS_FAILED,        // Delivery Problem
        ];
        foreach ($cases as $code => $expected) {
            $this->assertSame($expected, JtExpressStatusMap::toShipmentStatus($code), "code {$code}");
        }
    }

    public function test_maps_real_world_codes_not_in_the_published_table(): void
    {
        // Ví dụ response THẬT trong tài liệu J&T (Tracking Query) dùng bộ scanTypeCode khác hẳn bảng công
        // bố 103-121 — cùng ý nghĩa vĩ mô (nhận/vận chuyển/giao) nhưng mã số khác. Xem file tham khảo
        // docs/superpowers/research/2026-07-17-jt-express-api-reference.md §5.3.
        $cases = [
            10 => Shipment::STATUS_PICKED_UP,   // "Nhận hàng" / 快件揽收
            50 => Shipment::STATUS_IN_TRANSIT,  // "Gửi hàng"
            92 => Shipment::STATUS_IN_TRANSIT,  // "Hàng đến"
            94 => Shipment::STATUS_IN_TRANSIT,  // "Quét phát hàng"
            100 => Shipment::STATUS_DELIVERED,  // "Ký nhận"
        ];
        foreach ($cases as $code => $expected) {
            $this->assertSame($expected, JtExpressStatusMap::toShipmentStatus($code), "code {$code}");
        }
    }

    public function test_unmapped_codes_return_null_not_a_guess(): void
    {
        // 120 (Return Problem) và 121 (FINISH, chỉ là marker kết thúc — trạng thái thật đã set qua 113/117
        // trước đó) cố ý KHÔNG map — an toàn hơn đoán sai. Mã lạ hoàn toàn cũng phải trả null.
        $this->assertNull(JtExpressStatusMap::toShipmentStatus(120));
        $this->assertNull(JtExpressStatusMap::toShipmentStatus(121));
        $this->assertNull(JtExpressStatusMap::toShipmentStatus(999999));
    }

    public function test_null_or_empty_input_returns_null(): void
    {
        $this->assertNull(JtExpressStatusMap::toShipmentStatus(null));
        $this->assertNull(JtExpressStatusMap::toShipmentStatus(''));
    }

    public function test_accepts_numeric_string_code(): void
    {
        $this->assertSame(Shipment::STATUS_DELIVERED, JtExpressStatusMap::toShipmentStatus('113'));
    }
}
