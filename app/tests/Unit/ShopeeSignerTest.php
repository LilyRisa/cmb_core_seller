<?php

namespace Tests\Unit;

use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeSigner;
use PHPUnit\Framework\TestCase;

/** Pins the Shopee Open Platform v2 sign algorithm (HMAC-SHA256 hex of concatenated base). */
class ShopeeSignerTest extends TestCase
{
    public function test_public_sign_is_partner_path_timestamp(): void
    {
        $key = 'PARTNER_KEY';
        $base = '1001'.'/api/v2/auth/token/get'.'1700000000';
        $expected = hash_hmac('sha256', $base, $key);

        $this->assertSame($expected, ShopeeSigner::signPublic($key, 1001, '/api/v2/auth/token/get', 1700000000));
    }

    public function test_shop_sign_appends_token_and_shop(): void
    {
        $key = 'PARTNER_KEY';
        $base = '1001'.'/api/v2/order/get_order_list'.'1700000000'.'ACCESS'.'55';
        $expected = hash_hmac('sha256', $base, $key);

        $this->assertSame($expected, ShopeeSigner::signShop($key, 1001, '/api/v2/order/get_order_list', 1700000000, 'ACCESS', '55'));
    }

    public function test_deterministic_and_key_sensitive(): void
    {
        $this->assertSame(ShopeeSigner::signPublic('k1', 1, '/p', 1), ShopeeSigner::signPublic('k1', 1, '/p', 1));
        $this->assertNotSame(ShopeeSigner::signPublic('k1', 1, '/p', 1), ShopeeSigner::signPublic('k2', 1, '/p', 1));
        $this->assertSame(64, strlen(ShopeeSigner::signPublic('k1', 1, '/p', 1)));
    }
}
