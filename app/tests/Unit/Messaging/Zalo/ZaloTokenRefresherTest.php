<?php

namespace Tests\Unit\Messaging\Zalo;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Services\ZaloTokenRefresher;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZaloTokenRefresherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('integrations.messaging', ['zalo_oa']);
        config()->set('integrations.messaging_zalo_oa', ['app_id' => 'app_123', 'app_secret' => 'sec', 'oa_secret' => 'oa', 'redirect_uri' => 'https://x.test/cb']);
    }

    public function test_refresh_rotates_and_persists(): void
    {
        Http::fake(['oauth.zaloapp.com/v4/oa/access_token' => Http::response(['access_token' => 'AT2', 'refresh_token' => 'RT2', 'expires_in' => '90000'], 200)]);

        $tenant = Tenant::factory()->create();
        $acc = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->id, 'provider' => 'zalo_oa', 'external_shop_id' => 'OA_9',
            'access_token' => 'AT1', 'refresh_token' => 'RT1', 'status' => ChannelAccount::STATUS_ACTIVE,
            'token_expires_at' => now()->addMinutes(10), 'messaging_enabled' => true,
        ]);

        $ok = app(ZaloTokenRefresher::class)->refresh($acc->fresh());

        $this->assertTrue($ok);
        $fresh = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($acc->id);
        $this->assertSame('AT2', $fresh->access_token);
        $this->assertSame('RT2', $fresh->refresh_token);
        $this->assertSame(ChannelAccount::STATUS_ACTIVE, $fresh->status);
    }
}
