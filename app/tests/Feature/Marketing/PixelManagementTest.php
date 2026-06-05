<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PixelManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_connector_lists_pixels_with_details(): void
    {
        Http::fake(['graph.facebook.com/*/act_1/adspixels*' => Http::response(['data' => [
            ['id' => 'PX1', 'name' => 'Pixel chính', 'last_fired_time' => '2026-06-01T00:00:00+0000', 'is_unavailable' => false],
        ]], 200)]);

        $p = (new FacebookAdsConnector(['graph_version' => 'v19.0']))->listPixels('tok', 'act_1')[0];

        $this->assertSame('PX1', $p->id);
        $this->assertSame('2026-06-01T00:00:00+0000', $p->lastFiredTime);
        $this->assertFalse($p->isUnavailable);
    }

    public function test_connector_share_pixel_strips_act_prefix(): void
    {
        Http::fake(['graph.facebook.com/*/PX1/shared_accounts' => Http::response(['success' => true], 200)]);

        (new FacebookAdsConnector(['graph_version' => 'v19.0']))->sharePixel('tok', 'PX1', 'BIZ1', 'act_999');

        Http::assertSent(function ($r) {
            $d = $r->data();

            return str_contains($r->url(), 'PX1/shared_accounts')
                && $d['business'] === 'BIZ1'
                && $d['account_id'] === '999';
        });
    }

    public function test_share_endpoint_requires_business(): void
    {
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);

        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        // account without business_id ⇒ cannot share
        $account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'business_id' => null, 'currency' => 'VND', 'status' => 'active', 'access_token' => 'T']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

        $this->actingAs($user)->withHeaders(['X-Tenant-Id' => (string) $tenant->id])
            ->postJson("/api/v1/marketing/ad-accounts/{$account->id}/pixels/PX1/share", ['target_account_id' => 'act_2'])
            ->assertStatus(422);
    }

    public function test_share_endpoint_calls_connector(): void
    {
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);
        Http::fake(['graph.facebook.com/*/PX1/shared_accounts' => Http::response(['success' => true], 200)]);

        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'business_id' => 'BIZ1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'T']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

        $this->actingAs($user)->withHeaders(['X-Tenant-Id' => (string) $tenant->id])
            ->postJson("/api/v1/marketing/ad-accounts/{$account->id}/pixels/PX1/share", ['target_account_id' => 'act_2'])
            ->assertOk()->assertJsonPath('data.shared', true);

        Http::assertSent(fn ($r) => str_contains($r->url(), 'PX1/shared_accounts'));
    }
}
