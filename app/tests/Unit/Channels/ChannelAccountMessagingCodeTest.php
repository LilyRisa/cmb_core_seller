<?php

namespace Tests\Unit\Channels;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use PHPUnit\Framework\TestCase;

/**
 * messagingConnectorCode() maps a channel account's provider to its messaging
 * connector code. Lazada IM uses a SEPARATE app (provider `lazada_im`); the
 * orders provider `lazada` no longer does chat (shared-app path removed).
 */
class ChannelAccountMessagingCodeTest extends TestCase
{
    private function account(string $provider): ChannelAccount
    {
        $a = new ChannelAccount;
        $a->provider = $provider;

        return $a;
    }

    public function test_lazada_im_maps_to_lazada_chat_connector(): void
    {
        $this->assertSame('lazada_chat', $this->account('lazada_im')->messagingConnectorCode());
    }

    public function test_orders_lazada_provider_has_no_messaging(): void
    {
        $this->assertNull($this->account('lazada')->messagingConnectorCode());
    }

    public function test_other_providers_unchanged(): void
    {
        $this->assertSame('tiktok_chat', $this->account('tiktok')->messagingConnectorCode());
        $this->assertSame('shopee_chat', $this->account('shopee')->messagingConnectorCode());
        $this->assertSame('facebook_page', $this->account('facebook_page')->messagingConnectorCode());
        $this->assertNull($this->account('unknown')->messagingConnectorCode());
    }
}
