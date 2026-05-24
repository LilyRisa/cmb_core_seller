<?php

namespace Tests\Unit\Fulfillment;

use CMBcoreSeller\Modules\Fulfillment\Support\FulfillmentErrorMapper;
use RuntimeException;
use Tests\TestCase;

class FulfillmentErrorMapperTest extends TestCase
{
    public function test_classifies_cancelled_as_skipped(): void
    {
        $r = FulfillmentErrorMapper::classify(new RuntimeException('Vận đơn đã huỷ.'));
        $this->assertSame('skipped', $r['status']);
        $this->assertSame('Đơn đã huỷ — bỏ qua.', $r['reason']);
    }

    public function test_classifies_generic_as_error_with_technical(): void
    {
        $r = FulfillmentErrorMapper::classify(new RuntimeException('cURL timeout #28'));
        $this->assertSame('error', $r['status']);
        $this->assertNotSame('', $r['reason']);
        $this->assertStringContainsString('cURL timeout #28', $r['technical']);
    }
}
