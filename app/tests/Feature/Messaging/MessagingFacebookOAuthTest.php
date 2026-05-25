<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\OAuthState;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Test Facebook Page OAuth callback (SPEC-0024 S2 / ADR-0019). Graph API faked
 * — verify state → exchange code → /me/accounts → upsert channel_account.
 */
class MessagingFacebookOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Bật facebook_page trong registry (prod set INTEGRATIONS_MESSAGING).
        config(['integrations.messaging' => ['facebook_page']]);
        $this->app->forgetInstance(MessagingRegistry::class);
    }

    public function test_callback_connects_pages(): void
    {
        Http::fake([
            'graph.facebook.com/*oauth/access_token*' => Http::response(['access_token' => 'USER_TOKEN'], 200),
            'graph.facebook.com/*me/accounts*' => Http::response([
                'data' => [['id' => 'PAGE_1', 'name' => 'Shop FB', 'access_token' => 'PAGE_TOKEN']],
            ], 200),
            'graph.facebook.com/*me/businesses*' => Http::response(['data' => []], 200),
            'graph.facebook.com/*subscribed_apps*' => Http::response(['success' => true], 200),
        ]);

        $tenant = Tenant::create(['name' => 'FbShop']);
        OAuthState::create([
            'state' => 'st_valid_123', 'provider' => 'facebook_page', 'tenant_id' => $tenant->getKey(),
            'created_by' => null, 'redirect_after' => '/messaging/channels?connected=facebook_page',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->get('/oauth/facebook_page/callback?code=CODE&state=st_valid_123')
            ->assertOk()
            ->assertViewIs('oauth-callback')
            ->assertViewHas('redirect', '/messaging/channels?connected=facebook_page');

        $this->assertDatabaseHas('channel_accounts', [
            'tenant_id' => $tenant->getKey(),
            'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_1',
            'messaging_enabled' => true,
        ]);

        // state one-time-use → đã xoá
        $this->assertDatabaseMissing('oauth_states', ['state' => 'st_valid_123']);
    }

    public function test_callback_connects_business_manager_pages(): void
    {
        // Regression: page thuộc Business Manager KHÔNG hiện ở /me/accounts. Phải duyệt
        // /me/businesses → owned_pages/client_pages; edge business thiếu access_token ⇒
        // backfill /{page-id}?fields=access_token.
        Http::fake([
            'graph.facebook.com/*oauth/access_token*' => Http::response(['access_token' => 'USER_TOKEN'], 200),
            'graph.facebook.com/*/me/accounts*' => Http::response(['data' => []], 200),
            'graph.facebook.com/*/me/businesses*' => Http::response(['data' => [['id' => 'BIZ_1', 'name' => 'Cty A']]], 200),
            'graph.facebook.com/*/owned_pages*' => Http::response(['data' => [['id' => 'PAGE_BIZ', 'name' => 'Page DN']]], 200),
            'graph.facebook.com/*/client_pages*' => Http::response(['data' => []], 200),
            'graph.facebook.com/*subscribed_apps*' => Http::response(['success' => true], 200),
            // Backfill token (page edge không trả access_token) + bất kỳ GET page nào khác.
            'graph.facebook.com/*' => Http::response(['access_token' => 'PAGE_BIZ_TOKEN'], 200),
        ]);

        $tenant = Tenant::create(['name' => 'BizShop']);
        OAuthState::create([
            'state' => 'st_biz_1', 'provider' => 'facebook_page', 'tenant_id' => $tenant->getKey(),
            'created_by' => null, 'redirect_after' => '/messaging/channels?connected=facebook_page',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->get('/oauth/facebook_page/callback?code=CODE&state=st_biz_1')
            ->assertOk()
            ->assertViewHas('redirect', '/messaging/channels?connected=facebook_page');

        $this->assertDatabaseHas('channel_accounts', [
            'tenant_id' => $tenant->getKey(),
            'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_BIZ',
            'messaging_enabled' => true,
        ]);

        // Token được backfill (access_token cast `encrypted` ⇒ kiểm qua model đã giải mã).
        $account = ChannelAccount::withoutGlobalScopes()
            ->where('provider', 'facebook_page')->where('external_shop_id', 'PAGE_BIZ')->firstOrFail();
        $this->assertSame('PAGE_BIZ_TOKEN', $account->access_token);
    }

    public function test_callback_rejects_invalid_state(): void
    {
        $this->get('/oauth/facebook_page/callback?code=CODE&state=bogus')
            ->assertOk()
            ->assertViewIs('oauth-callback')
            ->assertViewHas('redirect', '/messaging/channels?error=facebook_oauth_state');

        $this->assertSame(0, ChannelAccount::withoutGlobalScopes()->where('provider', 'facebook_page')->count());
    }
}
