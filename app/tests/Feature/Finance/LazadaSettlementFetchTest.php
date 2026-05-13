<?php

namespace Tests\Feature\Finance;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaConnector;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Finance\Models\Settlement;
use CMBcoreSeller\Modules\Finance\Models\SettlementLine;
use CMBcoreSeller\Modules\Finance\Services\SettlementService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Contract test cho `LazadaConnector::fetchSettlements` + `SettlementService::fetchForShop`. SPEC 0016.
 * `Http::fake` `/finance/transaction/details/get` → kiểm settlement + lines + reconcile + profit hook.
 */
class LazadaSettlementFetchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'integrations.lazada.app_key' => 'k', 'integrations.lazada.app_secret' => 's',
            'integrations.lazada.finance_enabled' => true,
        ]);
        // Đảm bảo registry có lazada (test env có thể không bật connector qua INTEGRATIONS_CHANNELS).
        app(ChannelRegistry::class)->register('lazada', LazadaConnector::class);
    }

    public function test_fetch_settlement_from_lazada_and_reconcile_links_orders(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Shop LZD']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        $shop = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 'LZD-VN', 'shop_name' => 'LZD', 'status' => 'active',
            'access_token' => 'AT', 'refresh_token' => 'RT',
        ]);

        // Order có sẵn để reconcile khớp
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'source' => 'lazada', 'channel_account_id' => $shop->getKey(),
            'external_order_id' => 'L-12345', 'order_number' => 'L-12345',
            'status' => StandardOrderStatus::Shipped, 'raw_status' => 'shipped', 'source_updated_at' => now(),
            'grand_total' => 220000, 'item_total' => 220000, 'shipping_fee' => 25000, 'currency' => 'VND',
        ]);

        // Lazada trả 2 trang: vẫn dùng offset; sample data có lines
        Http::fake([
            '*/finance/transaction/details/get*' => Http::sequence()
                ->push(['code' => '0', 'data' => [
                    ['transaction_type' => 'Item Price Credit', 'amount' => '220000.00', 'order_no' => 'L-12345', 'lazada_id' => 'tx1', 'transaction_date' => '2026-05-10', 'currency' => 'VND', 'statement' => 'STM-202605-1'],
                    ['transaction_type' => 'Commission', 'amount' => '-22000.00', 'order_no' => 'L-12345', 'lazada_id' => 'tx2', 'transaction_date' => '2026-05-10', 'currency' => 'VND'],
                    ['transaction_type' => 'Payment Fee', 'amount' => '-4000.00', 'order_no' => 'L-12345', 'lazada_id' => 'tx3', 'transaction_date' => '2026-05-10', 'currency' => 'VND'],
                    ['transaction_type' => 'Shipping Fee Paid by Customer', 'amount' => '25000.00', 'order_no' => 'L-12345', 'lazada_id' => 'tx4', 'transaction_date' => '2026-05-10', 'currency' => 'VND'],
                ]])
                ->push(['code' => '0', 'data' => []]),
        ]);

        $service = app(SettlementService::class);
        $r = $service->fetchForShop($shop, CarbonImmutable::parse('2026-05-01'), CarbonImmutable::parse('2026-05-31'));
        $this->assertSame(1, $r['fetched']);
        $this->assertSame(4, $r['lines']);

        $settlement = Settlement::withoutGlobalScope(TenantScope::class)->where('channel_account_id', $shop->getKey())->firstOrFail();
        $this->assertSame('STM-202605-1', $settlement->external_id);
        $this->assertSame('reconciled', $settlement->status);
        $this->assertSame(220000 - 22000 - 4000 + 25000, (int) $settlement->total_payout);

        // line.order_id đã match
        $lines = SettlementLine::withoutGlobalScope(TenantScope::class)->where('settlement_id', $settlement->getKey())->get();
        $this->assertCount(4, $lines);
        foreach ($lines as $l) {
            $this->assertSame((int) $order->getKey(), (int) $l->order_id);
        }

        // OrderResource.profit dùng fee_source=settlement khi đã reconcile
        $detail = $this->actingAs($owner)->withHeaders(['X-Tenant-Id' => (string) $tenant->getKey()])->getJson("/api/v1/orders/{$order->getKey()}")->assertOk()->json('data.profit');
        $this->assertSame('settlement', $detail['fee_source']);
        $this->assertSame(22000 + 4000, (int) $detail['platform_fee']);
        $this->assertSame(25000, (int) $detail['shipping_fee']);
    }

    public function test_fetch_settlement_idempotent_on_replay(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Shop LZD2']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        $shop = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 'X', 'shop_name' => 'X', 'status' => 'active', 'access_token' => 'AT',
        ]);

        Http::fake([
            '*/finance/transaction/details/get*' => Http::response(['code' => '0', 'data' => [
                ['transaction_type' => 'Item Price Credit', 'amount' => '100000.00', 'order_no' => 'X-1', 'lazada_id' => 't1', 'transaction_date' => '2026-05-01', 'currency' => 'VND', 'statement' => 'S-1'],
            ]]),
        ]);

        $svc = app(SettlementService::class);
        $svc->fetchForShop($shop, CarbonImmutable::parse('2026-05-01'), CarbonImmutable::parse('2026-05-31'));
        $svc->fetchForShop($shop, CarbonImmutable::parse('2026-05-01'), CarbonImmutable::parse('2026-05-31'));

        $this->assertSame(1, Settlement::withoutGlobalScope(TenantScope::class)->where('channel_account_id', $shop->getKey())->count());
        $this->assertSame(1, SettlementLine::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->getKey())->count());
    }
}
