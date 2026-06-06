<?php

namespace Tests\Feature\Finance;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Finance\Models\Settlement;
use CMBcoreSeller\Modules\Finance\Services\SettlementService;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Tổng hợp đối soát thực cho "Báo cáo tổng thể" — SettlementService::summary. */
class SettlementSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_aggregates_period_and_status(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Shop A']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        $tid = (int) $tenant->getKey();

        $shop = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tid, 'provider' => 'lazada', 'external_shop_id' => 's1', 'shop_name' => 'S1', 'status' => 'active',
        ]);

        $mk = function (array $attrs) use ($tid, $shop) {
            return Settlement::withoutGlobalScope(TenantScope::class)->create(array_merge([
                'tenant_id' => $tid, 'channel_account_id' => $shop->getKey(), 'currency' => 'VND',
            ], $attrs));
        };

        // Trong kỳ (10 ngày gần nhất)
        $mk(['external_id' => 'A', 'period_start' => CarbonImmutable::now()->subDays(5), 'period_end' => CarbonImmutable::now()->subDays(2),
            'total_payout' => 800000, 'total_revenue' => 1000000, 'total_fee' => -150000, 'total_shipping_fee' => -50000, 'status' => 'reconciled']);
        $mk(['external_id' => 'B', 'period_start' => CarbonImmutable::now()->subDays(4), 'period_end' => CarbonImmutable::now()->subDays(1),
            'total_payout' => 400000, 'total_revenue' => 500000, 'total_fee' => -80000, 'total_shipping_fee' => -20000, 'status' => 'pending']);
        // Ngoài kỳ — KHÔNG được tính.
        $mk(['external_id' => 'C', 'period_start' => CarbonImmutable::now()->subDays(45), 'period_end' => CarbonImmutable::now()->subDays(40),
            'total_payout' => 999999, 'total_revenue' => 999999, 'total_fee' => -1, 'total_shipping_fee' => -1, 'status' => 'reconciled']);

        $res = app(SettlementService::class)->summary($tid, CarbonImmutable::now()->subDays(10), CarbonImmutable::now());

        $this->assertSame(2, $res['totals']['settlements']);
        $this->assertSame(1, $res['totals']['reconciled']);
        $this->assertSame(1, $res['totals']['pending']);
        $this->assertSame(1_200_000, $res['totals']['payout']);
        $this->assertSame(1_500_000, $res['totals']['revenue']);
        $this->assertSame(-230_000, $res['totals']['fee']);
        $this->assertSame(-70_000, $res['totals']['shipping']);

        $this->assertCount(1, $res['by_channel']);
        $this->assertSame((int) $shop->getKey(), $res['by_channel'][0]['channel_account_id']);
        $this->assertSame(1_200_000, $res['by_channel'][0]['payout']);
    }
}
