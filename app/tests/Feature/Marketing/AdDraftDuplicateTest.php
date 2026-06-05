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

class AdDraftDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_copies_payload_and_resets_external_ids(): void
    {
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'T']);
        $draft = AdDraft::create([
            'ad_account_id' => $account->id, 'name' => 'Tết', 'objective' => 'messages',
            'status' => AdDraft::STATUS_DRAFT,
            'payload' => ['adsets' => [[
                'name' => 'Nhóm 1', 'external_id' => 'AS_LIVE',
                'ads' => [['name' => 'QC 1', 'external_id' => 'AD_LIVE', 'creative' => ['page_id' => '1']]],
            ]]],
        ]);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

        $res = $this->actingAs($user)->withHeaders(['X-Tenant-Id' => (string) $tenant->id])
            ->postJson("/api/v1/marketing/ad-drafts/{$draft->id}/duplicate")
            ->assertCreated()
            ->assertJsonPath('data.name', 'Tết (sao chép)')
            ->assertJsonPath('data.status', 'draft');

        $newId = $res->json('data.id');
        $this->assertNotSame($draft->id, $newId);

        $copy = AdDraft::query()->findOrFail($newId);
        $this->assertNull($copy->payload['adsets'][0]['external_id']);
        $this->assertNull($copy->payload['adsets'][0]['ads'][0]['external_id']);
        $this->assertSame('Nhóm 1', $copy->payload['adsets'][0]['name']);
    }
}
