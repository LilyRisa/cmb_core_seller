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
use Tests\TestCase;

class AdAuthoringTargetingApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);
        $this->tenant = Tenant::create(['name' => 'AdShop']);
    }

    private function owner(): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($u->getKey(), ['role' => Role::Owner->value]);

        return $u;
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function account(): AdAccount
    {
        app(CurrentTenant::class)->set($this->tenant);

        return AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'TOK']);
    }

    public function test_targeting_search_returns_options(): void
    {
        Http::fake(['graph.facebook.com/*/search*' => Http::response(['data' => [['id' => '6003', 'name' => 'Thời trang', 'audience_size_lower_bound' => 5000000]]], 200)]);
        $acc = $this->account();

        $this->actingAs($this->owner())->withHeaders($this->h())
            ->getJson("/api/v1/marketing/ad-accounts/{$acc->id}/targeting-search?q=thời+trang")
            ->assertOk()
            ->assertJsonPath('data.0.id', '6003')
            ->assertJsonPath('data.0.name', 'Thời trang')
            ->assertJsonPath('data.0.audience_size', 5000000);
    }

    public function test_audience_estimate_returns_bounds(): void
    {
        Http::fake(['graph.facebook.com/*/delivery_estimate*' => Http::response(['data' => [['estimate_mau_lower_bound' => 1000000, 'estimate_mau_upper_bound' => 2100000]]], 200)]);
        $acc = $this->account();

        $this->actingAs($this->owner())->withHeaders($this->h())
            ->postJson("/api/v1/marketing/ad-accounts/{$acc->id}/audience-estimate", [
                'targeting' => ['geo_locations' => ['countries' => ['VN']]],
                'optimization_goal' => 'REACH',
            ])
            ->assertOk()
            ->assertJsonPath('data.lower_bound', 1000000)
            ->assertJsonPath('data.upper_bound', 2100000);
    }
}
