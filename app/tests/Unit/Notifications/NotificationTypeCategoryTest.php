<?php

namespace Tests\Unit\Notifications;

use CMBcoreSeller\Modules\Notifications\Support\NotificationType;
use Tests\TestCase;

class NotificationTypeCategoryTest extends TestCase
{
    public function test_order_types_map_to_order_category(): void
    {
        $this->assertSame('order', NotificationType::categoryFor(NotificationType::ORDER_NEGATIVE_TOTAL));
        $this->assertSame('order', NotificationType::categoryFor(NotificationType::ORDER_CANCELLED));
        $this->assertSame('order', NotificationType::categoryFor(NotificationType::ORDER_RETURN_NEW));
    }

    public function test_system_types_map_to_system_category(): void
    {
        $this->assertSame('system', NotificationType::categoryFor(NotificationType::CHANNEL_RECONNECT_NEEDED));
        $this->assertSame('system', NotificationType::categoryFor(NotificationType::ADS_MONITOR_APPROACHING));
        $this->assertSame('system', NotificationType::categoryFor(NotificationType::ADS_MONITOR_ACTION));
        $this->assertSame('system', NotificationType::categoryFor(NotificationType::INVENTORY_STOCK_PUSH_FAILED));
    }

    public function test_unknown_type_falls_back_to_system(): void
    {
        $this->assertSame('system', NotificationType::categoryFor('some.unmapped.type'));
    }
}
