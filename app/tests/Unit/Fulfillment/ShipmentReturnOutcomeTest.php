<?php

namespace Tests\Unit\Fulfillment;

use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use PHPUnit\Framework\TestCase;

class ShipmentReturnOutcomeTest extends TestCase
{
    public function test_returning_status_constant_exists(): void
    {
        $this->assertSame('returning', Shipment::STATUS_RETURNING);
        $this->assertNotSame(Shipment::STATUS_RETURNED, Shipment::STATUS_RETURNING);
    }
}
