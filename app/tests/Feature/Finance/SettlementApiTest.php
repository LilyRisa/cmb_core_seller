<?php

namespace Tests\Feature\Finance;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Finance\Models\Settlement;
use CMBcoreSeller\Modules\Finance\Models\SettlementLine;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Đối soát/Settlement — Phase 6.2 / SPEC 0016. Smoke: list/show + reconcile match line ↔ order + RBAC.
 */
class SettlementApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private ChannelAccount $shop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop F']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->shop = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => '123', 'shop_name' => 'LZD VN', 'status' => 'active',
        ]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function seedSettlement(): Settlement
    {
        $tenantId = (int) $this->tenant->getKey();
        $s = Settlement::query()->create([
            'tenant_id' => $tenantId, 'channel_account_id' => $this->shop->getKey(),
            'external_id' => 'STM-001', 'period_start' => now()->subDays(7), 'period_end' => now(),
            'currency' => 'VND', 'total_payout' => 850000, 'total_revenue' => 1000000,
            'total_fee' => -120000, 'total_shipping_fee' => -30000,
            'status' => Settlement::STATUS_PENDING, 'fetched_at' => now(),
        ]);
        SettlementLine::query()->create([
            'tenant_id' => $tenantId, 'settlement_id' => $s->getKey(),
            'external_order_id' => 'LZD-ORDER-1', 'fee_type' => 'revenue', 'amount' => 1000000,
            'created_at' => now(),
        ]);
        SettlementLine::query()->create([
            'tenant_id' => $tenantId, 'settlement_id' => $s->getKey(),
            'external_order_id' => 'LZD-ORDER-1', 'fee_type' => 'commission', 'amount' => -100000,
            'created_at' => now(),
        ]);
        SettlementLine::query()->create([
            'tenant_id' => $tenantId, 'settlement_id' => $s->getKey(),
            'external_order_id' => 'LZD-ORDER-1', 'fee_type' => 'payment_fee', 'amount' => -20000,
            'created_at' => now(),
        ]);
        SettlementLine::query()->create([
            'tenant_id' => $tenantId, 'settlement_id' => $s->getKey(),
            'external_order_id' => 'LZD-ORDER-1', 'fee_type' => 'shipping_fee', 'amount' => -30000,
            'created_at' => now(),
        ]);

        return $s;
    }

    public function test_index_show_and_reconcile(): void
    {
        $s = $this->seedSettlement();
        // Order khớp với external_order_id
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'lazada', 'channel_account_id' => $this->shop->getKey(),
            'external_order_id' => 'LZD-ORDER-1', 'order_number' => 'LZD-ORDER-1',
            'status' => StandardOrderStatus::Shipped, 'raw_status' => 'shipped', 'source_updated_at' => now(),
            'grand_total' => 1000000, 'item_total' => 1000000, 'shipping_fee' => 30000, 'currency' => 'VND',
        ]);

        // Index thấy 1 settlement, status pending
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/settlements')
            ->assertOk()->assertJsonPath('meta.pagination.total', 1)->assertJsonPath('data.0.status', 'pending');
        // Show có lines
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson("/api/v1/settlements/{$s->getKey()}")
            ->assertOk()->assertJsonPath('data.lines_count', 4)->assertJsonPath('data.total_payout', 850000);

        // Reconcile khớp order_id
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/settlements/{$s->getKey()}/reconcile")
            ->assertOk()->assertJsonPath('data.matched', 4)->assertJsonPath('data.settlement.status', 'reconciled');

        $this->assertSame(0, SettlementLine::withoutGlobalScope(TenantScope::class)
            ->where('settlement_id', $s->getKey())->whereNull('order_id')->count());
        $this->assertSame((int) $order->getKey(), (int) SettlementLine::withoutGlobalScope(TenantScope::class)
            ->where('settlement_id', $s->getKey())->where('fee_type', 'commission')->first()->order_id);
    }

    public function test_rbac_accountant_view_only(): void
    {
        $s = $this->seedSettlement();
        $accountant = User::factory()->create();
        $this->tenant->users()->attach($accountant->getKey(), ['role' => Role::Accountant->value]);
        $viewer = User::factory()->create();
        $this->tenant->users()->attach($viewer->getKey(), ['role' => Role::Viewer->value]);

        $this->actingAs($viewer)->withHeaders($this->h())->getJson('/api/v1/settlements')->assertForbidden();
        $this->actingAs($accountant)->withHeaders($this->h())->getJson('/api/v1/settlements')->assertOk();
        $this->actingAs($accountant)->withHeaders($this->h())->postJson("/api/v1/settlements/{$s->getKey()}/reconcile")->assertOk();
        // accountant không có quyền sửa tenant (chỉ finance.reconcile + view).
    }

    public function test_fetch_for_shop_throws_unsupported_when_provider_not_enabled(): void
    {
        // INTEGRATIONS_LAZADA_FINANCE=false trong test env ⇒ supports('finance.settlements') false ⇒ 422.
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/channel-accounts/{$this->shop->getKey()}/fetch-settlements", ['sync' => true])
            ->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }
}
