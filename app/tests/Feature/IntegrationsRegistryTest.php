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

    public function test_enabled_and_disabled_channels(): void
    {
        $registry = app(ChannelRegistry::class);

        // Phase 1: TikTok is wired & enabled (config('integrations.channels') includes 'tiktok').
        $this->assertTrue($registry->has('tiktok'));
        $this->assertSame('TikTok Shop', $registry->for('tiktok')->displayName());
        $this->assertTrue($registry->for('tiktok')->supports('orders.fetch'));
        $this->assertTrue($registry->for('tiktok')->supports('orders.webhook'));
        $this->assertTrue($registry->for('tiktok')->supports('listings.updateStock'));   // Phase 2 (SPEC 0003)
        $this->assertFalse($registry->for('tiktok')->supports('listings.publish'));       // Phase 5

        // Shopee/Lazada land in Phase 4 — not registered yet.
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

    public function test_carrier_registry_has_manual_and_loads_others_per_config(): void
    {
        $registry = app(CarrierRegistry::class);
        // 'manual' is always available (built-in self-managed carrier). 'ghn' only when enabled in env.
        $this->assertContains('manual', $registry->carriers());
        $this->assertTrue($registry->has('manual'));
        $this->assertSame('manual', $registry->for('manual')->code());
        $this->assertFalse($registry->has('ghn'));   // INTEGRATIONS_CARRIERS is empty in tests
    }
}
