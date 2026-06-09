<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\OAuthState;
use CMBcoreSeller\Modules\Marketing\Jobs\SyncAdAccountEntities;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * TikTok Marketing OAuth — callback đổi `auth_code` → token vô hạn, tạo ad_accounts
 * (provider=tiktok) + dispatch đồng bộ. Song song AdsOAuthTest (Facebook).
 */
class TikTokAdsOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'integrations.ads' => ['tiktok'],
            'integrations.ads_tiktok' => ['app_id' => 'A', 'app_secret' => 'S'],
        ]);
        $this->app->forgetInstance(AdsRegistry::class);
    }

    private function fakeTikTok(string $token = 'AT'): void
    {
        Http::fake([
            'business-api.tiktok.com/*oauth2/access_token*' => Http::response([
                'code' => 0, 'data' => ['access_token' => $token, 'advertiser_ids' => ['123']],
            ], 200),
            'business-api.tiktok.com/*oauth2/advertiser/get*' => Http::response([
                'code' => 0, 'data' => ['list' => [['advertiser_id' => '123', 'advertiser_name' => 'Shop']]],
            ], 200),
            'business-api.tiktok.com/*advertiser/info*' => Http::response([
                'code' => 0, 'data' => ['list' => [[
                    'advertiser_id' => '123', 'name' => 'Shop VN', 'currency' => 'VND', 'status' => 'STATUS_ENABLE',
                ]]],
            ], 200),
        ]);
    }

    public function test_callback_connects_tiktok_ad_account(): void
    {
        Queue::fake();
        $this->fakeTikTok();
        $tenant = Tenant::create(['name' => 'T']);
        OAuthState::create([
            'state' => 'st_tt_1', 'provider' => 'tiktok_marketing', 'tenant_id' => $tenant->id,
            'created_by' => null, 'redirect_after' => '/marketing?connected=tiktok_marketing', 'expires_at' => now()->addMinutes(10),
        ]);

        $this->get('/oauth/tiktok_marketing/redirect?auth_code=CODE&state=st_tt_1')
            ->assertOk()->assertViewIs('oauth-callback')
            ->assertViewHas('redirect', '/marketing?connected=tiktok_marketing');

        $this->assertDatabaseHas('ad_accounts', ['tenant_id' => $tenant->id, 'provider' => 'tiktok', 'external_account_id' => '123']);
        $acct = AdAccount::withoutGlobalScopes()->where('external_account_id', '123')->firstOrFail();
        $this->assertSame('AT', $acct->access_token);
        $this->assertSame('VND', $acct->currency);
        $this->assertNull($acct->token_expires_at); // token TikTok không hết hạn

        Queue::assertPushed(SyncAdAccountEntities::class);
        $this->assertDatabaseMissing('oauth_states', ['state' => 'st_tt_1']);
    }

    public function test_callback_uses_auth_code_param_not_code(): void
    {
        Queue::fake();
        $this->fakeTikTok();
        $tenant = Tenant::create(['name' => 'T']);
        OAuthState::create([
            'state' => 'st_tt_2', 'provider' => 'tiktok_marketing', 'tenant_id' => $tenant->id,
            'created_by' => null, 'redirect_after' => '/marketing?connected=tiktok_marketing', 'expires_at' => now()->addMinutes(10),
        ]);

        $this->get('/oauth/tiktok_marketing/redirect?auth_code=THECODE&state=st_tt_2')->assertOk();

        Http::assertSent(fn ($req) => str_contains($req->url(), '/oauth2/access_token/')
            && ($req['auth_code'] ?? null) === 'THECODE');
    }

    public function test_callback_invalid_state_redirects_error(): void
    {
        $this->get('/oauth/tiktok_marketing/redirect?auth_code=CODE&state=nope')
            ->assertOk()->assertViewHas('redirect', '/marketing?error=tiktok_marketing_oauth_state');
    }

    public function test_start_422_when_app_not_configured(): void
    {
        config(['integrations.ads' => ['tiktok'], 'integrations.ads_tiktok' => []]); // no app_id/secret
        $this->app->forgetInstance(AdsRegistry::class);
        $tenant = Tenant::create(['name' => 'T']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

        $this->actingAs($user)->withHeaders(['X-Tenant-Id' => (string) $tenant->id])
            ->postJson('/api/v1/marketing/ads/connect-tiktok')
            ->assertStatus(422);
    }
}
