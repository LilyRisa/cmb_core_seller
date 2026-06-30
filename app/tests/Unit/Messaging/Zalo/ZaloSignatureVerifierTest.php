<?php

namespace Tests\Unit\Messaging\Zalo;

use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloSignatureVerifier;
use Symfony\Component\HttpFoundation\Request;
use Tests\TestCase;

class ZaloSignatureVerifierTest extends TestCase
{
    private const APP_ID = 'app_123';
    private const OA_SECRET = 'oa_secret_xyz';

    private function request(string $body, ?string $signature): Request
    {
        $server = $signature !== null ? ['HTTP_X_ZEVENT_SIGNATURE' => $signature] : [];

        return Request::create('/webhook/messaging/zalo_oa', 'POST', [], [], [], $server, $body);
    }

    public function test_verifies_valid_mac(): void
    {
        $body = '{"app_id":"app_123","event_name":"user_send_text","timestamp":"1700000000"}';
        $mac = 'mac='.hash('sha256', self::APP_ID.$body.'1700000000'.self::OA_SECRET);

        $this->assertTrue((new ZaloSignatureVerifier)->verify($this->request($body, $mac), self::APP_ID, self::OA_SECRET));
    }

    public function test_rejects_wrong_mac(): void
    {
        $body = '{"app_id":"app_123","timestamp":"1700000000"}';
        $this->assertFalse((new ZaloSignatureVerifier)->verify($this->request($body, 'mac=deadbeef'), self::APP_ID, self::OA_SECRET));
    }

    public function test_rejects_missing_header_or_secret(): void
    {
        $body = '{"timestamp":"1"}';
        $this->assertFalse((new ZaloSignatureVerifier)->verify($this->request($body, null), self::APP_ID, self::OA_SECRET));
        $this->assertFalse((new ZaloSignatureVerifier)->verify($this->request($body, 'mac=x'), self::APP_ID, ''));
    }
}
