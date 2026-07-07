<?php

namespace Tests\Unit\Carriers;

use CMBcoreSeller\Integrations\Carriers\Ghn\GhnStatusMap;
use CMBcoreSeller\Integrations\Carriers\Ghtk\GhtkStatusMap;
use CMBcoreSeller\Integrations\Carriers\ViettelPost\ViettelPostStatusMap;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use PHPUnit\Framework\TestCase;

class ReturnStatusMapTest extends TestCase
{
    public function test_ghn_returning_vs_returned(): void
    {
        $this->assertSame(Shipment::STATUS_RETURNING, GhnStatusMap::toShipmentStatus('return'));
        $this->assertSame(Shipment::STATUS_RETURNING, GhnStatusMap::toShipmentStatus('returning'));
        $this->assertSame(Shipment::STATUS_RETURNING, GhnStatusMap::toShipmentStatus('return_transporting'));
        $this->assertSame(Shipment::STATUS_RETURNING, GhnStatusMap::toShipmentStatus('return_sorting'));
        $this->assertSame(Shipment::STATUS_RETURNED, GhnStatusMap::toShipmentStatus('returned'));
        $this->assertSame(Shipment::STATUS_FAILED, GhnStatusMap::toShipmentStatus('delivery_fail'));
        $this->assertSame(Shipment::STATUS_FAILED, GhnStatusMap::toShipmentStatus('waiting_to_return'));
        $this->assertSame(Shipment::STATUS_FAILED, GhnStatusMap::toShipmentStatus('return_fail'));
    }

    public function test_ghtk_returning_vs_returned(): void
    {
        $this->assertSame(Shipment::STATUS_RETURNING, GhtkStatusMap::toShipmentStatus(20)); // Đang trả hàng
        $this->assertSame(Shipment::STATUS_RETURNED, GhtkStatusMap::toShipmentStatus(11)); // Đã đối soát công nợ trả hàng
        $this->assertSame(Shipment::STATUS_RETURNED, GhtkStatusMap::toShipmentStatus(13)); // Đơn bồi hoàn
        $this->assertSame(Shipment::STATUS_RETURNED, GhtkStatusMap::toShipmentStatus(21)); // Đã trả hàng
    }

    public function test_vtp_returning_vs_returned(): void
    {
        $this->assertSame(Shipment::STATUS_RETURNING, ViettelPostStatusMap::toShipmentStatus(505)); // Yêu cầu chuyển hoàn
        $this->assertSame(Shipment::STATUS_RETURNING, ViettelPostStatusMap::toShipmentStatus(515)); // Duyệt hoàn
        $this->assertSame(Shipment::STATUS_RETURNED, ViettelPostStatusMap::toShipmentStatus(504)); // Thành công - chuyển trả người gửi
        $this->assertTrue(ViettelPostStatusMap::isFinal(504));
    }
}
