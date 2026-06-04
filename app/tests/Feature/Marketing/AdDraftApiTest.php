<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdDraft;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdDraftApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'AdShop']);
    }

    private function user(Role $role): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($u->getKey(), ['role' => $role->value]);

        return $u;
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function account(): AdAccount
    {
        app(CurrentTenant::class)->set($this->tenant);

        return AdAccount::create([
            'provider' => 'facebook', 'external_account_id' => 'act_1',
            'currency' => 'VND', 'status' => 'active', 'access_token' => 'X',
        ]);
    }

    public function test_owner_can_create_then_autosave_then_show(): void
    {
        $acc = $this->account();

        $id = $this->actingAs($this->user(Role::Owner))->withHeaders($this->h())
            ->postJson('/api/v1/marketing/ad-drafts', [
                'ad_account_id' => $acc->id, 'name' => 'Tết', 'objective' => 'messages',
                'payload' => ['budget' => 150000],
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.name', 'Tết')
            ->json('data.id');

        $this->actingAs($this->user(Role::Owner))->withHeaders($this->h())
            ->patchJson("/api/v1/marketing/ad-drafts/{$id}", ['payload' => ['budget' => 200000]])
            ->assertOk()
            ->assertJsonPath('data.payload.budget', 200000);

        $this->actingAs($this->user(Role::Owner))->withHeaders($this->h())
            ->getJson("/api/v1/marketing/ad-drafts/{$id}")
            ->assertOk()
            ->assertJsonPath('data.payload.budget', 200000);
    }

    public function test_index_lists_only_tenant_drafts(): void
    {
        $acc = $this->account();
        AdDraft::create(['ad_account_id' => $acc->id, 'name' => 'mine', 'payload' => []]);

        $this->actingAs($this->user(Role::Owner))->withHeaders($this->h())
            ->getJson('/api/v1/marketing/ad-drafts')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_create_rejects_account_from_other_tenant(): void
    {
        $other = Tenant::create(['name' => 'Other']);
        app(CurrentTenant::class)->set($other);
        $foreign = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_x', 'status' => 'active', 'access_token' => 'X']);

        $this->actingAs($this->user(Role::Owner))->withHeaders($this->h())
            ->postJson('/api/v1/marketing/ad-drafts', ['ad_account_id' => $foreign->id, 'payload' => []])
            ->assertStatus(404);
    }

    public function test_staff_order_forbidden_to_create(): void
    {
        $acc = $this->account();

        $this->actingAs($this->user(Role::StaffOrder))->withHeaders($this->h())
            ->postJson('/api/v1/marketing/ad-drafts', ['ad_account_id' => $acc->id, 'payload' => []])
            ->assertForbidden();
    }

    public function test_owner_can_delete(): void
    {
        $acc = $this->account();
        $draft = AdDraft::create(['ad_account_id' => $acc->id, 'name' => 'x', 'payload' => []]);

        $this->actingAs($this->user(Role::Owner))->withHeaders($this->h())
            ->deleteJson("/api/v1/marketing/ad-drafts/{$draft->id}")
            ->assertOk();
        $this->assertDatabaseMissing('ad_drafts', ['id' => $draft->id]);
    }

    public function test_foreign_draft_show_update_destroy_all_return_404(): void
    {
        // Draft belongs to ANOTHER tenant — the global scope must hide it from every verb.
        $other = Tenant::create(['name' => 'Other']);
        app(CurrentTenant::class)->set($other);
        $foreignAcc = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_y', 'status' => 'active', 'access_token' => 'X']);
        $foreignDraft = AdDraft::create(['ad_account_id' => $foreignAcc->id, 'name' => 'theirs', 'payload' => []]);

        $owner = $this->user(Role::Owner);

        $this->actingAs($owner)->withHeaders($this->h())
            ->getJson("/api/v1/marketing/ad-drafts/{$foreignDraft->id}")->assertStatus(404);
        $this->actingAs($owner)->withHeaders($this->h())
            ->patchJson("/api/v1/marketing/ad-drafts/{$foreignDraft->id}", ['name' => 'hijack'])->assertStatus(404);
        $this->actingAs($owner)->withHeaders($this->h())
            ->deleteJson("/api/v1/marketing/ad-drafts/{$foreignDraft->id}")->assertStatus(404);

        $this->assertDatabaseHas('ad_drafts', ['id' => $foreignDraft->id]); // untouched
    }

    public function test_cannot_delete_draft_mid_publish(): void
    {
        $acc = $this->account();
        $draft = AdDraft::create(['ad_account_id' => $acc->id, 'name' => 'x', 'status' => 'publishing', 'payload' => []]);

        $this->actingAs($this->user(Role::Owner))->withHeaders($this->h())
            ->deleteJson("/api/v1/marketing/ad-drafts/{$draft->id}")
            ->assertStatus(422);
        $this->assertDatabaseHas('ad_drafts', ['id' => $draft->id]);
    }
}
