<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Modules\Marketing\Contracts\MarketingAnalysisClient;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdForecast;
use CMBcoreSeller\Modules\Marketing\Models\AdInsightSnapshot;
use CMBcoreSeller\Modules\Marketing\Services\AdsForecastService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Counting fake — proves AI is called only when allowed (quota savings). */
class CountingAnalysisClient implements MarketingAnalysisClient
{
    public int $calls = 0;

    public function analyze(array $data, string $instruction): array
    {
        $this->calls++;

        return ['payload' => ['forecast' => ['next_7d' => ['orders' => 7]], 'strategy' => []], 'provider_code' => 'fake', 'model' => 'fake-1'];
    }
}

class AdsForecastServiceTest extends TestCase
{
    use RefreshDatabase;

    private function seedAccount(): AdAccount
    {
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'S']);
        $today = now()->toDateString();
        AdInsightSnapshot::create(['ad_account_id' => $account->id, 'level' => 'account', 'external_id' => 'act_1', 'date_start' => $today, 'date_stop' => $today, 'window' => 'today', 'spend' => 60000, 'messaging_conversations' => 12, 'leads' => 5, 'fetched_at' => now()]);
        Order::create(['tenant_id' => $tenant->id, 'source' => 'manual', 'order_number' => 'O1', 'status' => 'pending', 'grand_total' => 200000]);

        return $account;
    }

    public function test_generate_creates_cached_forecast_and_cooldown_skips_ai(): void
    {
        $fake = new CountingAnalysisClient;
        $this->app->instance(MarketingAnalysisClient::class, $fake);
        config(['marketing.forecast_cooldown_minutes' => 360]);

        $account = $this->seedAccount();
        $svc = app(AdsForecastService::class);

        $first = $svc->generate($account);
        $this->assertSame(7, $first->payload['forecast']['next_7d']['orders']);
        $this->assertSame(1, $fake->calls);
        $this->assertDatabaseCount('ad_forecasts', 1);

        // Within cooldown → returns cache, NO new AI call (quota saved).
        $second = $svc->generate($account);
        $this->assertSame(1, $fake->calls, 'AI must not be called again within cooldown');
        $this->assertSame($first->id, $second->id);

        // force=true bypasses cooldown.
        $svc->generate($account, force: true);
        $this->assertSame(2, $fake->calls);
        $this->assertDatabaseCount('ad_forecasts', 1); // still one row (upsert per account)
    }
}
