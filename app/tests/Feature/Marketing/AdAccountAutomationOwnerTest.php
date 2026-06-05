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

class AdAccountAutomationOwnerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tA;

    private Tenant $tB;

    private AdAccount $a; // owner (connected first)

    private AdAccount $b; // second tenant, same FB account

    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);

        $this->tA = Tenant::create(['name' => 'Shop A']);
        app(CurrentTenant::class)->set($this->tA);
        $this->a = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'TA']);

        $this->tB = Tenant::create(['name' => 'Shop B']);
        app(CurrentTenant::class)->set($this->tB);
        $this->b = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'TB']);
        AdEntity::create(['ad_account_id' => $this->b->id, 'level' => 'campaign', 'external_id' => 'C1', 'name' => 'CD', 'status' => 'ACTIVE', 'daily_budget' => 100000]);
        $this->userB = User::factory()->create(['email_verified_at' => now()]);
        $this->tB->users()->attach($this->userB->getKey(), ['role' => Role::Owner->value]);
    }

    private function hB(): array
    {
        return ['X-Tenant-Id' => (string) $this->tB->id];
    }

    public function test_non_owner_cannot_edit_entity(): void
    {
        // Shop B is not the owner (A connected first) ⇒ 403, no Graph call.
        Http::fake();
        $this->actingAs($this->userB)->withHeaders($this->hB())
            ->patchJson("/api/v1/marketing/ad-accounts/{$this->b->id}/entities/C1", ['level' => 'campaign', 'name' => 'X'])
            ->assertStatus(403);
        Http::assertNothingSent();
    }

    public function test_non_owner_cannot_set_monitor(): void
    {
        $this->actingAs($this->userB)->withHeaders($this->hB())
            ->putJson("/api/v1/marketing/ad-accounts/{$this->b->id}/monitors", [
                'target_level' => 'campaign', 'target_external_id' => 'C1', 'pause_enabled' => true, 'pause_above' => 5000,
            ])
            ->assertStatus(403);
    }

    public function test_claim_makes_b_owner_then_b_can_edit(): void
    {
        Http::fake(['graph.facebook.com/*/C1' => Http::response(['success' => true], 200)]);

        // Take over ownership.
        $this->actingAs($this->userB)->withHeaders($this->hB())
            ->postJson("/api/v1/marketing/ad-accounts/{$this->b->id}/claim-automation")
            ->assertOk()->assertJsonPath('data.is_automation_owner', true);

        $this->assertTrue($this->b->fresh()->isAutomationOwner());
        $this->assertFalse($this->a->fresh()->isAutomationOwner());

        // Now B can edit.
        $this->actingAs($this->userB)->withHeaders($this->hB())
            ->patchJson("/api/v1/marketing/ad-accounts/{$this->b->id}/entities/C1", ['level' => 'campaign', 'name' => 'Đã đổi'])
            ->assertOk();
    }
}
