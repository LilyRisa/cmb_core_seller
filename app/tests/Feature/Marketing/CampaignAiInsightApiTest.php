<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\TestUnlimitedPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Marketing\Jobs\GenerateCampaignAiInsight;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\CampaignAiInsight;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CampaignAiInsightApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private AdAccount $account;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($this->tenant);
        $this->account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'S']);
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($this->user->getKey(), ['role' => Role::Owner->value]);

        // SPEC 0032 — AI insight cần gói có AI. Gói TEST không giới hạn ⇒ consume no-op.
        $this->seed(TestUnlimitedPlanSeeder::class);
        Subscription::create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => Plan::query()->where('code', 'test_unlimited')->value('id'),
            'status' => 'active', 'billing_cycle' => 'monthly',
            'current_period_start' => now(), 'current_period_end' => now()->addYears(50),
        ]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->id];
    }

    public function test_generate_dispatches_job_with_normalized_params(): void
    {
        Queue::fake();

        $this->actingAs($this->user)->withHeaders($this->h())
            ->postJson("/api/v1/marketing/ad-accounts/{$this->account->id}/campaigns/c_1/ai-insight", [
                'days' => 30, 'metrics' => ['spend', 'ctr'], 'include_engagement' => true,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'generating')
            ->assertJsonPath('queued', true);

        Queue::assertPushed(GenerateCampaignAiInsight::class, fn ($j) => $j->adAccountId === (int) $this->account->id
            && $j->campaignExternalId === 'c_1'
            && $j->params['days'] === 30
            && $j->params['metrics'] === ['spend', 'ctr']);
    }

    public function test_invalid_metric_is_rejected(): void
    {
        Queue::fake();

        $this->actingAs($this->user)->withHeaders($this->h())
            ->postJson("/api/v1/marketing/ad-accounts/{$this->account->id}/campaigns/c_1/ai-insight", [
                'metrics' => ['not_a_metric'],
            ])
            ->assertStatus(422);

        Queue::assertNothingPushed();
    }

    public function test_post_within_cooldown_same_params_returns_cache_no_dispatch(): void
    {
        Queue::fake();

        CampaignAiInsight::create([
            'ad_account_id' => $this->account->id,
            'campaign_external_id' => 'c_1',
            'payload' => ['summary' => 'ok'],
            'params' => ['days' => 14, 'metrics' => ['spend', 'impressions', 'clicks', 'ctr', 'cpc', 'purchase_roas'], 'include_engagement' => true, 'include_landing' => true],
            'provider_code' => 'openai', 'model' => 'gpt-4o', 'generated_at' => now(),
        ]);

        // No params posted ⇒ normalizes to the same defaults as the cached row.
        $this->actingAs($this->user)->withHeaders($this->h())
            ->postJson("/api/v1/marketing/ad-accounts/{$this->account->id}/campaigns/c_1/ai-insight")
            ->assertOk()
            ->assertJsonPath('queued', false)
            ->assertJsonPath('status', 'cached');

        Queue::assertNothingPushed();
    }

    public function test_post_with_changed_params_bypasses_cooldown_and_dispatches(): void
    {
        Queue::fake();

        CampaignAiInsight::create([
            'ad_account_id' => $this->account->id,
            'campaign_external_id' => 'c_1',
            'payload' => ['summary' => 'ok'],
            'params' => ['days' => 14, 'metrics' => ['spend'], 'include_engagement' => true],
            'provider_code' => 'openai', 'model' => 'gpt-4o', 'generated_at' => now(),
        ]);

        $this->actingAs($this->user)->withHeaders($this->h())
            ->postJson("/api/v1/marketing/ad-accounts/{$this->account->id}/campaigns/c_1/ai-insight", [
                'days' => 7, 'metrics' => ['spend', 'cpm'],
            ])
            ->assertOk()
            ->assertJsonPath('queued', true);

        Queue::assertPushed(GenerateCampaignAiInsight::class);
    }

    public function test_get_returns_cached_insight(): void
    {
        CampaignAiInsight::create([
            'ad_account_id' => $this->account->id,
            'campaign_external_id' => 'c_9',
            'payload' => ['summary' => 'hi'],
            'params' => ['days' => 7, 'metrics' => ['spend'], 'include_engagement' => false],
            'provider_code' => 'openai', 'model' => 'gpt-4o', 'generated_at' => now(),
        ]);

        $this->actingAs($this->user)->withHeaders($this->h())
            ->getJson("/api/v1/marketing/ad-accounts/{$this->account->id}/campaigns/c_9/ai-insight")
            ->assertOk()
            ->assertJsonPath('data.payload.summary', 'hi')
            ->assertJsonPath('data.params.days', 7);
    }

    public function test_history_lists_past_analyses_and_can_delete(): void
    {
        foreach (['a', 'b', 'c'] as $s) {
            CampaignAiInsight::create([
                'ad_account_id' => $this->account->id, 'campaign_external_id' => 'c_h',
                'payload' => ['summary' => $s], 'params' => ['days' => 7, 'metrics' => ['spend'], 'include_engagement' => false],
                'provider_code' => 'openai', 'model' => 'gpt-4o', 'generated_at' => now(),
            ]);
        }

        $res = $this->actingAs($this->user)->withHeaders($this->h())
            ->getJson("/api/v1/marketing/ad-accounts/{$this->account->id}/campaigns/c_h/ai-insight/history")
            ->assertOk()->assertJsonCount(3, 'data');
        $id = $res->json('data.0.id'); // latest first

        $this->actingAs($this->user)->withHeaders($this->h())
            ->deleteJson("/api/v1/marketing/campaign-insights/{$id}")->assertNoContent();

        $this->actingAs($this->user)->withHeaders($this->h())
            ->getJson("/api/v1/marketing/ad-accounts/{$this->account->id}/campaigns/c_h/ai-insight/history")
            ->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_other_tenant_cannot_see_insight(): void
    {
        CampaignAiInsight::create([
            'ad_account_id' => $this->account->id,
            'campaign_external_id' => 'c_9',
            'payload' => ['summary' => 'secret'],
            'params' => ['days' => 7, 'metrics' => ['spend'], 'include_engagement' => false],
            'provider_code' => 'openai', 'model' => 'gpt-4o', 'generated_at' => now(),
        ]);

        $other = Tenant::create(['name' => 'Other']);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        $other->users()->attach($otherUser->getKey(), ['role' => Role::Owner->value]);

        // The account belongs to tenant T ⇒ findOrFail is tenant-scoped ⇒ 404 for the other tenant.
        $this->actingAs($otherUser)->withHeaders(['X-Tenant-Id' => (string) $other->id])
            ->getJson("/api/v1/marketing/ad-accounts/{$this->account->id}/campaigns/c_9/ai-insight")
            ->assertStatus(404);
    }
}
