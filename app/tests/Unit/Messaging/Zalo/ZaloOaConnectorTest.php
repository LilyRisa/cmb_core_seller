<?php

namespace Tests\Unit\Messaging\Zalo;

use CMBcoreSeller\Integrations\Messaging\Contracts\InteractiveMessagingConnector;
use CMBcoreSeller\Integrations\Messaging\Contracts\MessagingConnector;
use CMBcoreSeller\Integrations\Messaging\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloClient;
use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloOaConnector;
use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloSignatureVerifier;
use Tests\TestCase;

class ZaloOaConnectorTest extends TestCase
{
    private function connector(): ZaloOaConnector
    {
        return new ZaloOaConnector(
            ['app_id' => 'app_123', 'app_secret' => 'sec', 'oa_secret' => 'oa_secret_xyz', 'redirect_uri' => 'https://x.test/oauth/zalo_oa/callback'],
            new ZaloSignatureVerifier,
            new ZaloClient,
        );
    }

    public function test_identity_and_interfaces(): void
    {
        $c = $this->connector();
        $this->assertSame('zalo_oa', $c->code());
        $this->assertInstanceOf(MessagingConnector::class, $c);
        $this->assertInstanceOf(InteractiveMessagingConnector::class, $c);
    }

    public function test_capability_map(): void
    {
        $c = $this->connector();
        $this->assertTrue($c->supports('inbound.webhook'));
        $this->assertTrue($c->supports('inbound.postback'));
        $this->assertTrue($c->supports('outbound.text'));
        $this->assertTrue($c->supports('outbound.image'));
        $this->assertTrue($c->supports('outbound.file'));
        $this->assertTrue($c->supports('outbound.interactive'));
        $this->assertTrue($c->supports('read_receipt'));
        $this->assertFalse($c->supports('outbound.video'));
        $this->assertFalse($c->supports('outbound.utility_template'));
    }

    public function test_comment_ops_unsupported(): void
    {
        $this->expectException(UnsupportedOperation::class);
        $this->connector()->hideComment(
            new \CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext(1, 'zalo_oa', 'oa1', 'TKN'),
            'c1', true,
        );
    }
}
