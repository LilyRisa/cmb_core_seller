<?php

namespace Tests\Feature;

use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Contracts\ChannelConnector;
use CMBcoreSeller\Integrations\Channels\Manual\ManualConnector;
use InvalidArgumentException;
use Tests\TestCase;

class IntegrationsRegistryTest extends TestCase
{
    public function test_manual_channel_is_always_registered(): void
    {
        $registry = app(ChannelRegistry::class);

        $this->assertTrue($registry->has('manual'));
        $this->assertContains('manual', $registry->providers());
        $this->assertInstanceOf(ManualConnector::class, $registry->for('manual'));
        $this->assertInstanceOf(ChannelConnector::class, $registry->for('manual'));
    }

    public function test_unknown_channel_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(ChannelRegistry::class)->for('does-not-exist');
    }

    public function test_disabled_channels_are_not_registered(): void
    {
        $registry = app(ChannelRegistry::class);

        // tiktok/shopee/lazada connectors land in later phases and are config-gated.
        $this->assertFalse($registry->has('tiktok'));
        $this->assertFalse($registry->has('shopee'));
        $this->assertFalse($registry->has('lazada'));
    }

    public function test_manual_connector_reports_no_capabilities_and_canonical_status(): void
    {
        $manual = app(ChannelRegistry::class)->for('manual');

        $this->assertSame('manual', $manual->code());
        $this->assertFalse($manual->supports('orders.fetch'));
        $this->assertSame('pending', $manual->mapStatus('pending')->value);
    }

    public function test_carrier_registry_starts_empty(): void
    {
        $this->assertSame([], app(CarrierRegistry::class)->carriers());
        $this->assertFalse(app(CarrierRegistry::class)->has('ghn'));
    }
}
