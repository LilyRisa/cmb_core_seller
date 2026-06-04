<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Modules\Marketing\Jobs\SyncAdInsights;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdsScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_ads_insights_poll_dispatches_for_active_accounts(): void
    {
        Queue::fake();
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create([
            'provider' => 'facebook', 'external_account_id' => 'act_1', 'status' => 'active', 'access_token' => 'T',
        ]);

        $event = collect(app(Schedule::class)->events())
            ->first(fn ($e) => $e->description === 'ads-insights-poll');
        $this->assertNotNull($event, 'ads-insights-poll schedule not registered');

        $event->run($this->app);

        Queue::assertPushed(SyncAdInsights::class, fn ($j) => $j->adAccountId === (int) $account->id);
    }
}
