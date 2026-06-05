<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdEntity;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SavedReportTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private AdAccount $account;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);
        $this->tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($this->tenant);
        $this->account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'TOK']);
        AdEntity::create(['ad_account_id' => $this->account->id, 'level' => 'campaign', 'external_id' => 'C1', 'name' => 'CD1']);
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($this->user->getKey(), ['role' => Role::Owner->value]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->id];
    }

    public function test_save_capture_list_and_view_snapshot(): void
    {
        Http::fake(['graph.facebook.com/*/insights*' => Http::response(['data' => [
            ['campaign_id' => 'C1', 'spend' => '12345', 'impressions' => '1000', 'clicks' => '20'],
        ]], 200)]);

        $id = $this->actingAs($this->user)->withHeaders($this->h())
            ->postJson("/api/v1/marketing/ad-accounts/{$this->account->id}/saved-reports", [
                'name' => 'Tuần 1', 'level' => 'campaign', 'since' => '2026-06-01', 'until' => '2026-06-07', 'filters' => [],
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Tuần 1')
            ->json('data.id');

        $this->actingAs($this->user)->withHeaders($this->h())
            ->getJson("/api/v1/marketing/ad-accounts/{$this->account->id}/saved-reports")
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Tuần 1')
            ->assertJsonPath('data.0.since', '2026-06-01');

        $this->actingAs($this->user)->withHeaders($this->h())
            ->getJson("/api/v1/marketing/saved-reports/{$id}")
            ->assertOk()
            ->assertJsonPath('data.rows.0.external_id', 'C1')
            ->assertJsonPath('data.rows.0.insights.spend', 12345);

        $this->actingAs($this->user)->withHeaders($this->h())
            ->deleteJson("/api/v1/marketing/saved-reports/{$id}")->assertNoContent();
    }

    public function test_other_tenant_cannot_view(): void
    {
        Http::fake(['graph.facebook.com/*/insights*' => Http::response(['data' => []], 200)]);
        $id = $this->actingAs($this->user)->withHeaders($this->h())
            ->postJson("/api/v1/marketing/ad-accounts/{$this->account->id}/saved-reports", [
                'level' => 'campaign', 'since' => '2026-06-01', 'until' => '2026-06-07',
            ])->json('data.id');

        $other = Tenant::create(['name' => 'O']);
        $u2 = User::factory()->create(['email_verified_at' => now()]);
        $other->users()->attach($u2->getKey(), ['role' => Role::Owner->value]);
        $this->actingAs($u2)->withHeaders(['X-Tenant-Id' => (string) $other->id])
            ->getJson("/api/v1/marketing/saved-reports/{$id}")->assertStatus(404);
    }
}
