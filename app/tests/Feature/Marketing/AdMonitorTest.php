<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdEntity;
use CMBcoreSeller\Modules\Marketing\Models\AdMonitor;
use CMBcoreSeller\Modules\Marketing\Models\AdMonitorAction;
use CMBcoreSeller\Modules\Marketing\Notifications\AdMonitorActionNotification;
use CMBcoreSeller\Modules\Marketing\Services\AdMonitorEvaluator;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdMonitorTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private AdAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);
        $this->tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($this->tenant);
        $this->account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'TOK']);
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
    }

    private function fakeInsights(array $campaignRow, array $adsetRows = []): void
    {
        Http::fake([
            'graph.facebook.com/*/insights*' => Http::sequence()
                ->push(['data' => [$campaignRow]], 200)   // campaign level
                ->push(['data' => $adsetRows], 200),      // adset level
        ]);
    }

    public function test_pauses_when_cost_per_result_too_high(): void
    {
        Notification::fake();
        AdEntity::create(['ad_account_id' => $this->account->id, 'level' => 'campaign', 'external_id' => 'C1', 'name' => 'CD', 'objective' => 'MESSAGES', 'status' => 'ACTIVE', 'daily_budget' => 100000]);
        AdMonitor::create(['tenant_id' => $this->tenant->id, 'ad_account_id' => $this->account->id, 'target_level' => 'campaign', 'target_external_id' => 'C1', 'enabled' => true, 'pause_enabled' => true, 'pause_above' => 50000, 'min_results' => 1]);
        Http::fake(['graph.facebook.com/*/C1' => Http::response(['success' => true], 200)]);

        // spend 200000, 2 messages ⇒ CPR 100000 > 50000 ⇒ pause
        $this->fakeInsights(['campaign_id' => 'C1', 'spend' => '200000', 'actions' => [['action_type' => 'onsite_conversion.messaging_conversation_started_7d', 'value' => '2']]]);

        $actions = app(AdMonitorEvaluator::class)->evaluateAccount($this->account->refresh());

        $this->assertCount(1, $actions);
        $this->assertSame('pause', $actions[0]['type']);
        $this->assertSame('PAUSED', AdEntity::where('external_id', 'C1')->value('status'));
        Notification::assertSentTimes(AdMonitorActionNotification::class, 1);
        // History row logged (viewable + deletable).
        $this->assertDatabaseHas('ad_monitor_actions', ['ad_account_id' => $this->account->id, 'target_external_id' => 'C1', 'type' => 'pause']);
    }

    public function test_monitor_action_history_endpoint_and_delete(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);
        $h = ['X-Tenant-Id' => (string) $this->tenant->id];
        AdMonitorAction::create([
            'tenant_id' => $this->tenant->id, 'ad_account_id' => $this->account->id, 'target_level' => 'campaign',
            'target_external_id' => 'C1', 'target_name' => 'CD', 'type' => 'increase', 'from_budget' => 100000, 'to_budget' => 150000,
        ]);

        $id = $this->actingAs($user)->withHeaders($h)
            ->getJson("/api/v1/marketing/ad-accounts/{$this->account->id}/monitor-actions")
            ->assertOk()->assertJsonPath('data.0.type', 'increase')->json('data.0.id');

        $this->actingAs($user)->withHeaders($h)->deleteJson("/api/v1/marketing/monitor-actions/{$id}")->assertNoContent();
        $this->actingAs($user)->withHeaders($h)
            ->getJson("/api/v1/marketing/ad-accounts/{$this->account->id}/monitor-actions")->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_pauses_when_zero_results_but_spend_reached_threshold(): void
    {
        Notification::fake();
        // Spent the full 100k with ZERO results, pause threshold 100k ⇒ must pause.
        AdEntity::create(['ad_account_id' => $this->account->id, 'level' => 'campaign', 'external_id' => 'C1', 'name' => 'CD', 'objective' => 'OUTCOME_SALES', 'status' => 'ACTIVE', 'daily_budget' => 100000]);
        AdMonitor::create(['tenant_id' => $this->tenant->id, 'ad_account_id' => $this->account->id, 'target_level' => 'campaign', 'target_external_id' => 'C1', 'enabled' => true, 'pause_enabled' => true, 'pause_above' => 100000, 'min_results' => 1]);
        Http::fake(['graph.facebook.com/*/C1' => Http::response(['success' => true], 200)]);

        // spend 100000, no actions ⇒ results 0; spend >= pause_above ⇒ pause
        $this->fakeInsights(['campaign_id' => 'C1', 'spend' => '100000']);

        $actions = app(AdMonitorEvaluator::class)->evaluateAccount($this->account->refresh());

        $this->assertCount(1, $actions);
        $this->assertSame('pause', $actions[0]['type']);
        $this->assertNull($actions[0]['cpr']);
        $this->assertSame('PAUSED', AdEntity::where('external_id', 'C1')->value('status'));
    }

    public function test_does_not_pause_zero_results_below_spend_threshold(): void
    {
        Notification::fake();
        // 0 results but only spent 30k < 100k threshold ⇒ keep running (not yet wasteful).
        AdEntity::create(['ad_account_id' => $this->account->id, 'level' => 'campaign', 'external_id' => 'C1', 'name' => 'CD', 'objective' => 'OUTCOME_SALES', 'status' => 'ACTIVE', 'daily_budget' => 100000]);
        AdMonitor::create(['tenant_id' => $this->tenant->id, 'ad_account_id' => $this->account->id, 'target_level' => 'campaign', 'target_external_id' => 'C1', 'enabled' => true, 'pause_enabled' => true, 'pause_above' => 100000, 'min_results' => 1]);

        $this->fakeInsights(['campaign_id' => 'C1', 'spend' => '30000']);

        $actions = app(AdMonitorEvaluator::class)->evaluateAccount($this->account->refresh());

        $this->assertCount(0, $actions);
        $this->assertSame('ACTIVE', AdEntity::where('external_id', 'C1')->value('status'));
    }

    public function test_raises_budget_when_cost_per_result_cheap(): void
    {
        Notification::fake();
        AdEntity::create(['ad_account_id' => $this->account->id, 'level' => 'campaign', 'external_id' => 'C1', 'name' => 'CD', 'objective' => 'MESSAGES', 'status' => 'ACTIVE', 'daily_budget' => 100000]);
        AdMonitor::create(['tenant_id' => $this->tenant->id, 'ad_account_id' => $this->account->id, 'target_level' => 'campaign', 'target_external_id' => 'C1', 'enabled' => true, 'increase_enabled' => true, 'increase_below' => 20000, 'increase_step_pct' => 50, 'min_results' => 1]);
        Http::fake(['graph.facebook.com/*/C1' => Http::response(['success' => true], 200)]);

        // spend 100000, 10 messages ⇒ CPR 10000 < 20000 ⇒ raise budget 100000 → 150000
        $this->fakeInsights(['campaign_id' => 'C1', 'spend' => '100000', 'actions' => [['action_type' => 'onsite_conversion.messaging_conversation_started_7d', 'value' => '10']]]);

        $actions = app(AdMonitorEvaluator::class)->evaluateAccount($this->account->refresh());

        $this->assertSame('increase', $actions[0]['type']);
        $this->assertSame(150000, $actions[0]['to']);
        $this->assertSame(150000, (int) AdEntity::where('external_id', 'C1')->value('daily_budget'));
    }

    public function test_campaign_monitor_overrides_adset_monitor(): void
    {
        Notification::fake();
        AdEntity::create(['ad_account_id' => $this->account->id, 'level' => 'campaign', 'external_id' => 'C1', 'name' => 'CD', 'objective' => 'MESSAGES', 'status' => 'ACTIVE', 'daily_budget' => 100000]);
        AdEntity::create(['ad_account_id' => $this->account->id, 'level' => 'adset', 'external_id' => 'AS1', 'parent_external_id' => 'C1', 'name' => 'Nhóm', 'status' => 'ACTIVE', 'daily_budget' => 50000]);
        AdMonitor::create(['tenant_id' => $this->tenant->id, 'ad_account_id' => $this->account->id, 'target_level' => 'campaign', 'target_external_id' => 'C1', 'enabled' => true, 'pause_enabled' => true, 'pause_above' => 999999, 'min_results' => 1]);
        $adsetMon = AdMonitor::create(['tenant_id' => $this->tenant->id, 'ad_account_id' => $this->account->id, 'target_level' => 'adset', 'target_external_id' => 'AS1', 'enabled' => true, 'pause_enabled' => true, 'pause_above' => 1, 'min_results' => 1]);

        // adset would pause (CPR high) but its campaign is monitored ⇒ skipped
        $this->fakeInsights(
            ['campaign_id' => 'C1', 'spend' => '1000', 'actions' => [['action_type' => 'onsite_conversion.messaging_conversation_started_7d', 'value' => '5']]],
            [['adset_id' => 'AS1', 'spend' => '100000', 'actions' => [['action_type' => 'onsite_conversion.messaging_conversation_started_7d', 'value' => '1']]]],
        );

        app(AdMonitorEvaluator::class)->evaluateAccount($this->account->refresh());

        $this->assertSame('ACTIVE', AdEntity::where('external_id', 'AS1')->value('status'), 'adset must NOT be paused (campaign monitor overrides)');
        $this->assertNotNull($adsetMon->refresh()->last_evaluated_at);
    }

    public function test_upsert_and_list_via_api(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);
        $h = ['X-Tenant-Id' => (string) $this->tenant->id];

        $this->actingAs($user)->withHeaders($h)
            ->putJson("/api/v1/marketing/ad-accounts/{$this->account->id}/monitors", [
                'target_level' => 'campaign', 'target_external_id' => 'C1',
                'pause_enabled' => true, 'pause_above' => 50000,
            ])
            ->assertCreated()
            ->assertJsonPath('data.pause_above', 50000);

        $this->actingAs($user)->withHeaders($h)
            ->getJson("/api/v1/marketing/ad-accounts/{$this->account->id}/monitors")
            ->assertOk()
            ->assertJsonPath('data.0.target_external_id', 'C1');
    }
}
