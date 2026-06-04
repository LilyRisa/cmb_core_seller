<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdInsightSnapshot;
use CMBcoreSeller\Modules\Marketing\Services\AdReconciliationService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdReconciliationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_reconciles_ad_metrics_against_manual_orders_per_day(): void
    {
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create([
            'provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'T',
        ]);
        $today = now()->toDateString();

        AdInsightSnapshot::create([
            'ad_account_id' => $account->id, 'level' => 'account', 'external_id' => 'act_1',
            'date_start' => $today, 'date_stop' => $today, 'window' => 'today',
            'spend' => 60000, 'impressions' => 2000, 'clicks' => 40, 'reach' => 1500,
            'messaging_conversations' => 12, 'leads' => 5, 'fetched_at' => now(),
        ]);

        // 2 manual orders today (counted) + 1 marketplace order (excluded).
        $this->order($tenant->id, 'manual', 200000);
        $this->order($tenant->id, 'manual', 200000);
        $this->order($tenant->id, 'tiktok', 999000);

        $rows = app(AdReconciliationService::class)->reconcile($account, 7);
        $todayRow = collect($rows)->firstWhere('date', $today);

        $this->assertNotNull($todayRow);
        $this->assertSame(60000, $todayRow['spend']);
        $this->assertSame(12, $todayRow['conversations']);
        $this->assertSame(5, $todayRow['leads']);
        $this->assertSame(2, $todayRow['manual_orders']);
        $this->assertSame(400000, $todayRow['manual_revenue']);
        $this->assertSame(30000, $todayRow['cost_per_order']);            // 60000/2
        $this->assertEqualsWithDelta(16.67, $todayRow['conv_to_order_pct'], 0.1); // 2/12*100
    }

    private function order(int $tenantId, string $source, int $total): void
    {
        Order::create([
            'tenant_id' => $tenantId, 'source' => $source, 'order_number' => 'O'.uniqid(),
            'status' => 'pending', 'grand_total' => $total,
        ]);
    }
}
