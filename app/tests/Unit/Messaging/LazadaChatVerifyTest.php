<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Integrations\Messaging\Lazada\LazadaChatConnector;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Lazada push signature verify must accept UPPERCASE hex (Lazada signs UPPERCASE)
 * as well as lowercase, using the dedicated IM app secret.
 */
class LazadaChatVerifyTest extends TestCase
{
    private function req(string $body, array $server): Request
    {
        return Request::create('/webhook/messaging/lazada_chat', 'POST', [], [], [], $server, $body);
    }

    public function test_accepts_uppercase_rawbody_hmac_header(): void
    {
        config(['integrations.messaging_lazada_im.app_secret' => 'SEC']);
        $body = '{"data":{"session_id":"S1","message_id":"M1"}}';
        $sig = strtoupper(hash_hmac('sha256', $body, 'SEC'));

        $this->assertTrue((new LazadaChatConnector)->verifyWebhookSignature(
            $this->req($body, ['HTTP_X_LAZOP_SIGN' => $sig]),
        ));
    }

    public function test_rejects_wrong_signature(): void
    {
        config(['integrations.messaging_lazada_im.app_secret' => 'SEC']);
        $this->assertFalse((new LazadaChatConnector)->verifyWebhookSignature(
            $this->req('{"data":{}}', ['HTTP_X_LAZOP_SIGN' => 'deadbeef']),
        ));
    }
}
