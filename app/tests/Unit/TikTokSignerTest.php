<?php

namespace Tests\Unit;

use CMBcoreSeller\Integrations\Channels\TikTok\TikTokSigner;
use PHPUnit\Framework\TestCase;

/**
 * Pins the TikTok Shop request-signing algorithm (see TikTokSigner / sdk_tiktok_seller/utils/generate-sign.ts).
 * If TikTok ever changes the algorithm, these vectors break loudly.
 */
class TikTokSignerTest extends TestCase
{
    public function test_sign_for_a_get_request_without_body(): void
    {
        // secret + path + sorted({k}{v}...) + secret  (access_token & sign excluded)
        $secret = 'app_secret_value';
        $path = '/order/202309/orders';
        $query = ['app_key' => 'AK', 'timestamp' => '1700000000', 'ids' => '123', 'shop_cipher' => 'CIPHER', 'access_token' => 'TOKEN', 'sign' => 'ignored'];

        // Build the expectation independently so the test verifies the *algorithm*, not just the impl.
        $params = ['app_key' => 'AK', 'ids' => '123', 'shop_cipher' => 'CIPHER', 'timestamp' => '1700000000']; // sorted, sans sign/access_token
        $str = $path.'app_keyAK'.'ids123'.'shop_cipherCIPHER'.'timestamp1700000000';
        $expected = hash_hmac('sha256', $secret.$str.$secret, $secret);

        $this->assertSame($expected, TikTokSigner::sign($secret, $path, $query));
    }

    public function test_sign_includes_json_body_for_post(): void
    {
        $secret = 's3cr3t';
        $path = '/order/202309/orders/search';
        $query = ['app_key' => 'AK', 'timestamp' => '1700000000', 'shop_cipher' => 'C', 'page_size' => '50'];
        $body = '{"update_time_ge":1699000000}';

        $str = $path.'app_keyAK'.'page_size50'.'shop_cipherC'.'timestamp1700000000'.$body;
        $expected = hash_hmac('sha256', $secret.$str.$secret, $secret);

        $this->assertSame($expected, TikTokSigner::sign($secret, $path, $query, $body));
    }

    public function test_empty_body_is_not_appended_and_multipart_body_is_skipped(): void
    {
        $secret = 'x';
        $path = '/p';
        $query = ['app_key' => 'A', 'timestamp' => '1'];
        $base = TikTokSigner::sign($secret, $path, $query, '');

        $this->assertSame($base, TikTokSigner::sign($secret, $path, $query, '{}'));
        $this->assertSame($base, TikTokSigner::sign($secret, $path, $query, '{"a":1}', multipart: true));
        $this->assertNotSame($base, TikTokSigner::sign($secret, $path, $query, '{"a":1}'));
    }

    public function test_sign_is_deterministic_and_secret_sensitive(): void
    {
        $path = '/order/202309/orders';
        $query = ['app_key' => 'A', 'timestamp' => '1700000000'];

        $this->assertSame(TikTokSigner::sign('k1', $path, $query), TikTokSigner::sign('k1', $path, $query));
        $this->assertNotSame(TikTokSigner::sign('k1', $path, $query), TikTokSigner::sign('k2', $path, $query));
        $this->assertSame(64, strlen(TikTokSigner::sign('k1', $path, $query))); // hex sha256
    }
}
