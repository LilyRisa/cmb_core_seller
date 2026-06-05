<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RefreshAdAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_syncs_entities_and_health_synchronously(): void
    {
        Queue::fake(); // the snapshot insights job stays async
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);

        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'TOK', 'fb_account_status' => 1]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

        Http::fake([
            'graph.facebook.com/*/act_1/campaigns*' => Http::response(['data' => [['id' => 'C1', 'name' => 'CD mới', 'status' => 'ACTIVE', 'effective_status' => 'ACTIVE', 'objective' => 'OUTCOME_SALES']]], 200),
            'graph.facebook.com/*/act_1/adsets*' => Http::response(['data' => []], 200),
            'graph.facebook.com/*/act_1/ads*' => Http::response(['data' => []], 200),
            // fetchAccountStatus: GET /act_1?fields=account_status,disable_reason
            'graph.facebook.com/*/act_1?*' => Http::response(['account_status' => 2, 'disable_reason' => 1], 200),
            'graph.facebook.com/*/act_1' => Http::response(['account_status' => 2, 'disable_reason' => 1], 200),
        ]);

        $this->actingAs($user)->withHeaders(['X-Tenant-Id' => (string) $tenant->id])
            ->postJson("/api/v1/marketing/ad-accounts/{$account->id}/refresh")
            ->assertOk()
            ->assertJsonPath('data.synced', true);

        // Entity tree synced inline (campaign now in DB).
        $this->assertDatabaseHas('ad_entities', ['ad_account_id' => $account->id, 'external_id' => 'C1', 'name' => 'CD mới']);
        // Health refreshed inline.
        $this->assertDatabaseHas('ad_accounts', ['id' => $account->id, 'fb_account_status' => 2, 'disable_reason' => 1]);
    }
}
