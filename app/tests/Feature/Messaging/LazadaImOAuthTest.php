<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\OAuthState;
use CMBcoreSeller\Modules\Messaging\Jobs\SyncConversationsForShop;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Lazada IM Chat OAuth callback (app "IM ERP" RIÊNG, tách khỏi orders). Verify
 * state → exchange code (auth.lazada.com/rest/auth/token/create) → upsert
 * channel_accounts(provider=lazada_im) + token riêng → queue poll.
 */
class LazadaImOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['integrations.messaging' => ['lazada_chat']]);
        config([
            'integrations.messaging_lazada_im.app_key' => 'LK_IM',
            'integrations.messaging_lazada_im.app_secret' => 'SEC_IM',
            'integrations.messaging_lazada_im.auth_base_url' => 'https://auth.lazada.com/rest',
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);
    }

    public function test_callback_connects_lazada_im_account(): void
    {
        Queue::fake();
        Http::fake(['*/auth/token/create*' => Http::response([
            'code' => '0',
            'access_token' => 'IM_ACCESS',
            'refresh_token' => 'IM_REFRESH',
            'expires_in' => 2592000,
            'account' => 'seller@shop.vn',
            'country_user_info_list' => [['country' => 'vn', 'seller_id' => '200758896491']],
        ], 200)]);

        $tenant = Tenant::create(['name' => 'LzShop']);
        OAuthState::create([
            'state' => 'st_lz_im_1', 'provider' => 'lazada_im', 'tenant_id' => $tenant->getKey(),
            'created_by' => null, 'redirect_after' => '/messaging/channels?connected=lazada_im',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->get('/oauth/lazada_im/callback?code=CODE_X&state=st_lz_im_1')
            ->assertOk()
            ->assertViewIs('oauth-callback')
            ->assertViewHas('redirect', '/messaging/channels?connected=lazada_im');

        $this->assertDatabaseHas('channel_accounts', [
            'tenant_id' => $tenant->getKey(),
            'provider' => 'lazada_im',
            'external_shop_id' => '200758896491',
            'messaging_enabled' => true,
        ]);

        $account = ChannelAccount::withoutGlobalScopes()
            ->where('provider', 'lazada_im')->where('external_shop_id', '200758896491')->firstOrFail();
        $this->assertSame('IM_ACCESS', $account->access_token);
        $this->assertSame('IM_REFRESH', $account->refresh_token);

        $meta = MessagingAccountMeta::withoutGlobalScopes()->where('channel_account_id', $account->id)->firstOrFail();
        $this->assertSame(MessagingAccountMeta::SYNC_QUEUED, $meta->sync_status);

        Queue::assertPushed(SyncConversationsForShop::class);
        $this->assertDatabaseMissing('oauth_states', ['state' => 'st_lz_im_1']);
    }

    public function test_callback_rejects_invalid_state(): void
    {
        $this->get('/oauth/lazada_im/callback?code=CODE&state=bogus')
            ->assertOk()
            ->assertViewHas('redirect', '/messaging/channels?error=lazada_im_oauth_state');

        $this->assertSame(0, ChannelAccount::withoutGlobalScopes()->where('provider', 'lazada_im')->count());
    }
}
