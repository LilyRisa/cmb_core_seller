<?php

namespace Tests\Unit\Integrations\Channels;

use CMBcoreSeller\Integrations\Channels\Lazada\LazadaConnector;
use CMBcoreSeller\Integrations\Channels\Manual\ManualConnector;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeConnector;
use CMBcoreSeller\Integrations\Channels\TikTok\TikTokConnector;
use CMBcoreSeller\Support\Enums\PrepareBlockReason;
use Tests\TestCase;

class PrepareBlockReasonMapTest extends TestCase
{
    public function test_tiktok_mapping(): void
    {
        $c = app(TikTokConnector::class);
        $this->assertSame(PrepareBlockReason::AwaitingPayment, $c->prepareBlockReason('UNPAID'));
        $this->assertSame(PrepareBlockReason::PlatformHold, $c->prepareBlockReason('ON_HOLD'));
        $this->assertSame(PrepareBlockReason::PlatformFulfilled, $c->prepareBlockReason('AWAITING_SHIPMENT', ['fulfillment_type' => 'FULFILLMENT_BY_TIKTOK']));
        $this->assertNull($c->prepareBlockReason('AWAITING_SHIPMENT'));
        $this->assertNull($c->prepareBlockReason('AWAITING_COLLECTION'));
    }

    public function test_lazada_mapping(): void
    {
        $c = app(LazadaConnector::class);
        $this->assertSame(PrepareBlockReason::AwaitingPayment, $c->prepareBlockReason('unpaid'));
        $this->assertSame(PrepareBlockReason::PlatformProcessing, $c->prepareBlockReason('topack'));
        $this->assertNull($c->prepareBlockReason('pending'));
    }

    public function test_shopee_mapping(): void
    {
        $c = app(ShopeeConnector::class);
        $this->assertSame(PrepareBlockReason::AwaitingPayment, $c->prepareBlockReason('UNPAID'));
        $this->assertSame(PrepareBlockReason::CancelInProgress, $c->prepareBlockReason('IN_CANCEL'));
        $this->assertNull($c->prepareBlockReason('READY_TO_SHIP'));
        $this->assertNull($c->prepareBlockReason('RETRY_SHIP'));
    }

    public function test_manual_never_blocks(): void
    {
        $this->assertNull(app(ManualConnector::class)->prepareBlockReason('anything'));
    }
}
