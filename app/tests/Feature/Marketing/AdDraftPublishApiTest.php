<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Jobs\PublishAdDraft;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdDraft;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdDraftPublishApiTest extends TestCase
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

    private function makeDraft(): AdDraft
    {
        app(CurrentTenant::class)->set($this->tenant);
        AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'X']);

        return AdDraft::create(['ad_account_id' => AdAccount::query()->value('id'), 'name' => 'T', 'objective' => 'messages', 'payload' => []]);
    }

    public function test_owner_publish_queues_job_and_sets_publishing(): void
    {
        Queue::fake();
        $draft = $this->makeDraft();

        $this->actingAs($this->user(Role::Owner))->withHeaders($this->h())
            ->postJson("/api/v1/marketing/ad-drafts/{$draft->id}/publish")
            ->assertOk()
            ->assertJsonPath('data.queued', true)
            ->assertJsonPath('data.status', 'publishing');

        Queue::assertPushed(PublishAdDraft::class, fn ($j) => $j->draftId === (int) $draft->id);
        $this->assertSame('publishing', $draft->fresh()->status);
    }

    public function test_staff_forbidden_to_publish(): void
    {
        Queue::fake();
        $draft = $this->makeDraft();

        $this->actingAs($this->user(Role::StaffOrder))->withHeaders($this->h())
            ->postJson("/api/v1/marketing/ad-drafts/{$draft->id}/publish")
            ->assertForbidden();
        Queue::assertNothingPushed();
    }

    public function test_publish_422_when_ads_create_not_supported(): void
    {
        Queue::fake();
        config(['integrations.ads' => []]);
        $this->app->forgetInstance(AdsRegistry::class);
        $draft = $this->makeDraft();

        $this->actingAs($this->user(Role::Owner))->withHeaders($this->h())
            ->postJson("/api/v1/marketing/ad-drafts/{$draft->id}/publish")
            ->assertStatus(422);
        Queue::assertNothingPushed();
    }
}
