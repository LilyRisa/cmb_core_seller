<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\OAuthState;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Test Zalo OA connect (start) + OAuth callback (Task 11). Zalo Open API faked —
 * verify state → exchange code → getoa (oa_id + profile) → upsert channel_account
 * (provider=zalo_oa). Mirror {@see MessagingFacebookOAuthTest}.
 */
class ZaloOaOAuthTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'ZaloShop']);
        config()->set('integrations.messaging', ['zalo_oa']);
        config()->set('integrations.messaging_zalo_oa', [
            'app_id' => 'app_123', 'app_secret' => 'sec', 'oa_secret' => 'oa',
            'redirect_uri' => 'https://x.test/oauth/zalo_oa/callback',
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);
        $this->activatePro();
    }

    private function activatePro(): void
    {
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        $now = now();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => $now,
            'current_period_end' => $now->copy()->addMonth(),
        ]);
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($user->getKey(), ['role' => $role->value]);

        return $user;
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_start_returns_authorize_url(): void
    {
        $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->getJson('/api/v1/messaging/zalo/connect')
            ->assertOk()
            ->assertJsonPath('data.authorize_url', fn ($u) => str_contains((string) $u, 'oauth.zaloapp.com/v4/oa/permission')
                && str_contains((string) $u, 'code_challenge=')           // PKCE
                && str_contains((string) $u, 'code_challenge_method=S256'));
    }

    public function test_staff_cs_cannot_start_zalo_connect(): void
    {
        $this->actingAs($this->userWithRole(Role::StaffCs))
            ->withHeaders($this->h())
            ->getJson('/api/v1/messaging/zalo/connect')
            ->assertStatus(403);
    }

    public function test_callback_upserts_channel_account(): void
    {
        $state = OAuthState::issue('zalo_oa', (int) $this->tenant->getKey(), null, '/messaging/channels?connected=zalo_oa');

        Http::fake([
            'oauth.zaloapp.com/v4/oa/access_token' => Http::response(['access_token' => 'AT', 'refresh_token' => 'RT', 'expires_in' => '90000'], 200),
            'openapi.zalo.me/v2.0/oa/getoa' => Http::response(['error' => 0, 'data' => ['oa_id' => 'OA_9', 'name' => 'Shop Zalo', 'avatar' => 'https://zalo.test/a.png']], 200),
        ]);

        $this->get('/oauth/zalo_oa/callback?code=CODE_1&state='.$state->state)
            ->assertOk()
            ->assertViewIs('oauth-callback')
            ->assertViewHas('redirect', '/messaging/channels?connected=zalo_oa');

        $acc = ChannelAccount::withoutGlobalScope(TenantScope::class)->where('provider', 'zalo_oa')->where('external_shop_id', 'OA_9')->first();
        $this->assertNotNull($acc);
        $this->assertSame('Shop Zalo', $acc->shop_name);
        $this->assertSame('AT', $acc->access_token);
        $this->assertSame('RT', $acc->refresh_token);
        $this->assertTrue((bool) $acc->messaging_enabled);

        // state one-time-use → đã xoá
        $this->assertDatabaseMissing('oauth_states', ['state' => $state->state]);
    }

    public function test_callback_rejects_invalid_state(): void
    {
        $this->get('/oauth/zalo_oa/callback?code=CODE&state=bogus')
            ->assertOk()
            ->assertViewIs('oauth-callback')
            ->assertViewHas('redirect', '/messaging/channels?error=zalo_oa_oauth_state');

        $this->assertSame(0, ChannelAccount::withoutGlobalScopes()->where('provider', 'zalo_oa')->count());
    }
}
