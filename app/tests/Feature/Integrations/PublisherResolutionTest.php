<?php

declare(strict_types=1);

namespace Tests\Feature\Integrations;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaConnector;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaPublisher;
use CMBcoreSeller\Integrations\Channels\PublisherRegistry;
use Tests\TestCase;

class PublisherResolutionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // lazada is env-gated in ChannelRegistry (INTEGRATIONS_CHANNELS); register for test.
        config([
            'integrations.lazada.app_key' => 'test-key',
            'integrations.lazada.app_secret' => 'test-secret',
        ]);
        app(ChannelRegistry::class)->register('lazada', LazadaConnector::class);
    }

    public function test_resolves_a_lazada_publisher_and_reports_capability(): void
    {
        // ChannelRegistry resolves LazadaConnector and the capability flag is true.
        $connector = app(ChannelRegistry::class)->for('lazada');
        $this->assertTrue($connector->supports('listings.publish'));

        // PublisherRegistry resolves LazadaPublisher by provider code.
        $publisher = app(PublisherRegistry::class)->for('lazada');
        $this->assertInstanceOf(LazadaPublisher::class, $publisher);
    }

    public function test_throws_unsupported_operation_resolving_tiktok_publisher(): void
    {
        $this->expectException(UnsupportedOperation::class);

        app(PublisherRegistry::class)->for('tiktok');
    }
}
