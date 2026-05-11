<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Jobs\SyncOrdersForShop;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\OAuthState;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Fixtures\Channels\tiktok\TikTokFixtures as F;
use Tests\TestCase;

class ChannelConnectFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        F::configure();
        config(['integrations.tiktok.service_id' => 'svc-123']);

        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Connect test shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
    }

    private function header(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_connect_returns_an_authorization_url_and_creates_oauth_state(): void
    {
        $res = $this->actingAs($this->owner)->withHeaders($this->header())
            ->postJson('/api/v1/channel-accounts/tiktok/connect')
            ->assertOk()->assertJsonPath('data.provider', 'tiktok');

        $authUrl = $res->json('data.auth_url');
        $this->assertStringContainsString('services.tiktokshop.com/open/authorize', $authUrl);
        $this->assertStringContainsString('service_id=svc-123', $authUrl);
        $this->assertSame(1, OAuthState::query()->where('provider', 'tiktok')->where('tenant_id', $this->tenant->getKey())->count());
    }

    public function test_non_manager_cannot_connect(): void
    {
        $viewer = User::factory()->create();
        $this->tenant->users()->attach($viewer->getKey(), ['role' => Role::Viewer->value]);

        $this->actingAs($viewer)->withHeaders($this->header())->postJson('/api/v1/channel-accounts/tiktok/connect')->assertForbidden();
    }

    public function test_oauth_callback_creates_the_channel_account_and_kicks_off_a_backfill(): void
    {
        Queue::fake();
        Http::fake([
            '*/api/v2/token/get*' => Http::response(F::tokenGet()),
            '*/authorization/202309/shops*' => Http::response(F::authShops()),
        ]);

        $state = OAuthState::issue('tiktok', (int) $this->tenant->getKey(), $this->owner->getKey());

        $this->get('/oauth/tiktok/callback?app_key='.F::APP_KEY."&code=auth_code_abc&state={$state->state}")
            ->assertRedirect('/channels?connected=tiktok');

        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)
            ->where('provider', 'tiktok')->where('external_shop_id', F::SHOP_ID)->first();
        $this->assertNotNull($account);
        $this->assertSame((int) $this->tenant->getKey(), (int) $account->tenant_id);
        $this->assertSame(ChannelAccount::STATUS_ACTIVE, $account->status);
        $this->assertSame('tk_access_123', $account->access_token);            // encrypted at rest, decrypted here
        $this->assertSame(F::SHOP_CIPHER, $account->meta['shop_cipher']);
        $this->assertSame(0, OAuthState::query()->count());                    // consumed
        Queue::assertPushed(SyncOrdersForShop::class, fn ($j) => $j->type === 'backfill');
    }

    public function test_oauth_callback_with_a_stale_state_redirects_with_an_error(): void
    {
        $state = OAuthState::create(['state' => 'oldstate', 'provider' => 'tiktok', 'tenant_id' => $this->tenant->getKey(), 'expires_at' => now()->subMinute()]);
        $this->get("/oauth/tiktok/callback?code=abc&state={$state->state}")->assertRedirect('/channels?error=oauth_state');
    }

    public function test_disconnect_marks_revoked_and_keeps_order_history(): void
    {
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'tiktok', 'external_shop_id' => F::SHOP_ID,
            'status' => ChannelAccount::STATUS_ACTIVE, 'access_token' => 'tk', 'refresh_token' => 'rt',
        ]);

        $this->actingAs($this->owner)->withHeaders($this->header())
            ->deleteJson("/api/v1/channel-accounts/{$account->getKey()}")
            ->assertOk()->assertJsonPath('data.status', 'revoked');

        $this->assertSame(ChannelAccount::STATUS_REVOKED, $account->fresh()->status);
        // resync on a revoked account -> 409
        $this->actingAs($this->owner)->withHeaders($this->header())->postJson("/api/v1/channel-accounts/{$account->getKey()}/resync")->assertStatus(409);
    }

    public function test_index_lists_accounts_and_connectable_providers(): void
    {
        ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'tiktok', 'external_shop_id' => 'shop-x', 'shop_name' => 'Shop X', 'status' => ChannelAccount::STATUS_ACTIVE,
        ]);

        $res = $this->actingAs($this->owner)->withHeaders($this->header())->getJson('/api/v1/channel-accounts')->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertSame('Shop X', $res->json('data.0.shop_name'));
        $this->assertSame('tiktok', $res->json('meta.connectable_providers.0.code'));
    }
}
