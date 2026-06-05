<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdEntity;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdEntityUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_connector_posts_name_budget_status(): void
    {
        Http::fake(['graph.facebook.com/*/C1' => Http::response(['success' => true], 200)]);
        $connector = new FacebookAdsConnector(['graph_version' => 'v19.0']);

        $connector->updateEntity('tok', 'campaign', 'C1', ['name' => 'Mới', 'daily_budget_major' => 150000, 'status' => 'PAUSED'], 'VND');

        Http::assertSent(function ($r) {
            $d = $r->data();

            return str_contains($r->url(), '/C1')
                && $d['name'] === 'Mới'
                && $d['daily_budget'] === '150000'   // VND zero-decimal ⇒ unchanged
                && $d['status'] === 'PAUSED';
        });
    }

    public function test_connector_noop_when_no_fields(): void
    {
        Http::fake();
        (new FacebookAdsConnector(['graph_version' => 'v19.0']))->updateEntity('tok', 'campaign', 'C1', []);
        Http::assertNothingSent();
    }

    public function test_endpoint_updates_provider_and_local_row(): void
    {
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);
        Http::fake(['graph.facebook.com/*/C1' => Http::response(['success' => true], 200)]);

        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'TOK']);
        AdEntity::create(['ad_account_id' => $account->id, 'level' => 'campaign', 'external_id' => 'C1', 'name' => 'Cũ', 'daily_budget' => 50000]);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

        $this->actingAs($user)->withHeaders(['X-Tenant-Id' => (string) $tenant->id])
            ->patchJson("/api/v1/marketing/ad-accounts/{$account->id}/entities/C1", [
                'level' => 'campaign', 'name' => 'Tết 2026', 'daily_budget_major' => 200000,
            ])
            ->assertOk()
            ->assertJsonPath('data.updated', true);

        $this->assertDatabaseHas('ad_entities', ['external_id' => 'C1', 'name' => 'Tết 2026', 'daily_budget' => 200000]);
    }

    public function test_endpoint_rejects_invalid_status(): void
    {
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);

        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'TOK']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

        $this->actingAs($user)->withHeaders(['X-Tenant-Id' => (string) $tenant->id])
            ->patchJson("/api/v1/marketing/ad-accounts/{$account->id}/entities/C1", ['level' => 'campaign', 'status' => 'DELETED'])
            ->assertStatus(422);
    }
}
