<?php

namespace Tests\Unit;

use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use PHPUnit\Framework\TestCase;

class StandardOrderStatusTest extends TestCase
{
    public function test_every_status_has_a_label(): void
    {
        foreach (StandardOrderStatus::cases() as $status) {
            $this->assertNotEmpty($status->label());
        }
    }

    public function test_pre_shipment_statuses(): void
    {
        $this->assertTrue(StandardOrderStatus::Unpaid->isPreShipment());
        $this->assertTrue(StandardOrderStatus::Pending->isPreShipment());
        $this->assertTrue(StandardOrderStatus::Processing->isPreShipment());
        $this->assertTrue(StandardOrderStatus::ReadyToShip->isPreShipment());

        $this->assertFalse(StandardOrderStatus::Shipped->isPreShipment());
        $this->assertFalse(StandardOrderStatus::Delivered->isPreShipment());
        $this->assertFalse(StandardOrderStatus::Cancelled->isPreShipment());
    }

    public function test_terminal_statuses(): void
    {
        $this->assertTrue(StandardOrderStatus::Completed->isTerminal());
        $this->assertTrue(StandardOrderStatus::Cancelled->isTerminal());
        $this->assertTrue(StandardOrderStatus::ReturnedRefunded->isTerminal());

        $this->assertFalse(StandardOrderStatus::Pending->isTerminal());
        $this->assertFalse(StandardOrderStatus::Shipped->isTerminal());
        $this->assertFalse(StandardOrderStatus::Returning->isTerminal());
    }

    public function test_values_are_stable_canonical_strings(): void
    {
        $this->assertSame('ready_to_ship', StandardOrderStatus::ReadyToShip->value);
        $this->assertSame('returned_refunded', StandardOrderStatus::ReturnedRefunded->value);
        $this->assertSame(StandardOrderStatus::Processing, StandardOrderStatus::from('processing'));
    }
}
