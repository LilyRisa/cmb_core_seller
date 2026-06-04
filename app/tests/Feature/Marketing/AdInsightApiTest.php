<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdEntity;
use CMBcoreSeller\Modules\Marketing\Models\AdInsightSnapshot;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdInsightApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'AdShop']);
        app(CurrentTenant::class)->set($this->tenant);
    }

    private function owner(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

        return $user;
    }

    public function test_returns_entity_tree_with_latest_metrics(): void
    {
        $account = AdAccount::create([
            'provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'SECRET',
        ]);
        $camp = AdEntity::create(['ad_account_id' => $account->id, 'level' => 'campaign', 'external_id' => 'C1', 'name' => 'Camp', 'status' => 'ACTIVE']);
        AdInsightSnapshot::create([
            'ad_account_id' => $account->id, 'ad_entity_id' => $camp->id, 'level' => 'campaign', 'external_id' => 'C1',
            'date_start' => now()->toDateString(), 'date_stop' => now()->toDateString(), 'window' => 'today',
            'is_finalizing' => true, 'spend' => 50000, 'impressions' => 1000, 'clicks' => 30, 'reach' => 800,
            'ctr' => 3.0, 'cpc' => 1666, 'cpm' => 50000, 'frequency' => 1.25, 'purchase_roas' => 2.5, 'fetched_at' => now(),
        ]);

        $res = $this->actingAs($this->owner())->withHeaders(['X-Tenant-Id' => (string) $this->tenant->id])
            ->getJson("/api/v1/marketing/ad-accounts/{$account->id}/insights")
            ->assertOk()
            ->assertJsonPath('data.account.currency', 'VND')
            ->assertJsonCount(1, 'data.entities')
            ->assertJsonPath('data.entities.0.external_id', 'C1')
            ->assertJsonPath('data.entities.0.insights.spend', 50000)
            ->assertJsonPath('data.entities.0.insights.is_finalizing', true);

        $this->assertStringNotContainsString('SECRET', $res->getContent());
    }
}
