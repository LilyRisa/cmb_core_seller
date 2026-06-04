<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Integrations\Messaging\Lazada\LazadaChatConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Lazada IM uses its OWN "IM ERP" app, so the chat connector must build its own
 * authorize URL and exchange the code for a token using `messaging_lazada_im`
 * config (NOT the shared orders app `integrations.lazada`).
 */
class LazadaChatOAuthMethodsTest extends TestCase
{
    public function test_build_authorization_url_uses_im_app_config(): void
    {
        config([
            'integrations.messaging_lazada_im.app_key' => 'LK_IM',
            'integrations.messaging_lazada_im.authorize_url' => 'https://auth.lazada.com/oauth/authorize',
            'integrations.messaging_lazada_im.redirect_uri' => 'https://app.cmbcore.com/oauth/lazada_im/callback',
        ]);

        $url = (new LazadaChatConnector)->buildAuthorizationUrl('STATE123');

        $this->assertStringContainsString('https://auth.lazada.com/oauth/authorize', $url);
        $this->assertStringContainsString('client_id=LK_IM', $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('state=STATE123', $url);
        $this->assertStringContainsString('oauth%2Flazada_im%2Fcallback', $url);
    }

    public function test_exchange_code_for_token_parses_token_and_seller(): void
    {
        config([
            'integrations.messaging_lazada_im.app_key' => 'LK_IM',
            'integrations.messaging_lazada_im.app_secret' => 'SEC_IM',
            'integrations.messaging_lazada_im.auth_base_url' => 'https://auth.lazada.com/rest',
        ]);
        Http::fake(['*/auth/token/create*' => Http::response([
            'code' => '0',
            'access_token' => 'IM_ACCESS',
            'refresh_token' => 'IM_REFRESH',
            'expires_in' => 2592000,
            'country_user_info_list' => [['country' => 'vn', 'seller_id' => '200758896491']],
        ], 200)]);

        $token = (new LazadaChatConnector)->exchangeCodeForToken('CODE_X');

        $this->assertSame('IM_ACCESS', $token->accessToken);
        $this->assertSame('IM_REFRESH', $token->refreshToken);
        $this->assertSame('200758896491', (string) ($token->raw['country_user_info_list'][0]['seller_id'] ?? ''));
        Http::assertSent(fn ($r) => str_contains($r->url(), '/auth/token/create') && str_contains($r->url(), 'app_key=LK_IM'));
    }
}
