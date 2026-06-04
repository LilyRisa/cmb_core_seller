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

class AdsOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'integrations.ads' => ['facebook'],
            'integrations.ads_facebook' => ['app_id' => 'A', 'app_secret' => 'S', 'graph_version' => 'v19.0'],
        ]);
        $this->app->forgetInstance(AdsRegistry::class);
    }

    public function test_callback_connects_ad_accounts(): void
    {
        Queue::fake();
        Http::fake([
            'graph.facebook.com/*oauth/access_token*' => Http::response(['access_token' => 'AT', 'expires_in' => 5184000], 200),
            'graph.facebook.com/*me/adaccounts*' => Http::response(['data' => [['id' => 'act_123', 'name' => 'Shop', 'currency' => 'VND', 'account_status' => 1]]], 200),
        ]);
        $tenant = Tenant::create(['name' => 'T']);
        OAuthState::create([
            'state' => 'st_ads_1', 'provider' => 'facebook_ads', 'tenant_id' => $tenant->id,
            'created_by' => null, 'redirect_after' => '/marketing?connected=facebook_ads', 'expires_at' => now()->addMinutes(10),
        ]);

        $this->get('/oauth/facebook_ads/callback?code=CODE&state=st_ads_1')
            ->assertOk()->assertViewIs('oauth-callback')
            ->assertViewHas('redirect', '/marketing?connected=facebook_ads');

        $this->assertDatabaseHas('ad_accounts', ['tenant_id' => $tenant->id, 'provider' => 'facebook', 'external_account_id' => 'act_123']);
        $acct = AdAccount::withoutGlobalScopes()->where('external_account_id', 'act_123')->firstOrFail();
        $this->assertSame('AT', $acct->access_token);
        $this->assertSame('VND', $acct->currency);

        Queue::assertPushed(SyncAdAccountEntities::class);
        $this->assertDatabaseMissing('oauth_states', ['state' => 'st_ads_1']);
    }

    public function test_connect_start_422_when_dedicated_ads_app_not_configured(): void
    {
        config(['integrations.ads' => ['facebook'], 'integrations.ads_facebook' => []]); // no app_id/secret
        $this->app->forgetInstance(AdsRegistry::class);
        $tenant = Tenant::create(['name' => 'T']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

        $this->actingAs($user)->withHeaders(['X-Tenant-Id' => (string) $tenant->id])
            ->postJson('/api/v1/marketing/ads/connect')
            ->assertStatus(422);
    }

    public function test_callback_rejects_invalid_state(): void
    {
        $this->get('/oauth/facebook_ads/callback?code=CODE&state=bogus')
            ->assertOk()
            ->assertViewHas('redirect', '/marketing?error=facebook_ads_oauth_state');

        $this->assertSame(0, AdAccount::withoutGlobalScopes()->count());
    }
}
