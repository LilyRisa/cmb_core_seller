<?php

namespace Tests\Feature\Orders;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Accounting\Services\AccountingSetupService;
use CMBcoreSeller\Modules\Customers\Contracts\CustomerWallet;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ManualOrderWalletTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0:User,1:Tenant,2:Sku} */
    private function setUpShop(string $hash): array
    {
        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Shop']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        app(AccountingSetupService::class)->run((int) $tenant->getKey(), 2026);
        $sku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $tenant->getKey(), 'sku_code' => 'S1', 'name' => 'A', 'weight_grams' => 100]);
        app(InventoryLedgerService::class)->adjust((int) $tenant->getKey(), (int) $sku->getKey(), null, 50);

        return [$owner, $tenant, $sku];
    }

    private function customer(Tenant $t, string $hash): Customer
    {
        return Customer::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t->getKey(), 'phone_hash' => str_repeat($hash, 64), 'phone' => '0912345678', 'name' => 'K',
            'lifetime_stats' => ['orders_total' => 0], 'tags' => [], 'addresses_meta' => [], 'first_seen_at' => now(), 'last_seen_at' => now(),
        ]);
    }

    public function test_manual_order_deducts_wallet_and_sets_cod_zero(): void
    {
        Bus::fake([PushStockForSku::class]);
        [$owner, $tenant, $sku] = $this->setUpShop('d');
        $c = $this->customer($tenant, 'd');
        app(CustomerWallet::class)->topup((int) $tenant->getKey(), (int) $c->getKey(), 500000, 'cash', 'HD-1', null, $owner->getKey());
        $h = ['X-Tenant-Id' => (string) $tenant->getKey()];

        $id = $this->actingAs($owner)->withHeaders($h)->postJson('/api/v1/orders', [
            'buyer' => ['name' => 'K', 'phone' => '0912345678', 'address' => 'x', 'province' => 'HN'],
            'items' => [['sku_id' => $sku->getKey(), 'name' => 'A', 'quantity' => 2, 'unit_price' => 150000]],
            'customer_id' => $c->getKey(), 'prepaid_amount' => 300000, 'wallet_amount' => 300000,
        ])->assertCreated()->json('data.id');

        $order = Order::withoutGlobalScope(TenantScope::class)->find($id);
        $this->assertSame(0, (int) $order->cod_amount);
        $this->assertSame(300000, (int) $order->prepaid_amount);
        $this->assertSame(200000, (int) $c->refresh()->prepaid_balance);
    }

    public function test_wallet_amount_exceeding_balance_is_rejected_and_no_order_created(): void
    {
        Bus::fake([PushStockForSku::class]);
        [$owner, $tenant, $sku] = $this->setUpShop('e');
        $c = $this->customer($tenant, 'e'); // ví = 0
        $h = ['X-Tenant-Id' => (string) $tenant->getKey()];

        $this->actingAs($owner)->withHeaders($h)->postJson('/api/v1/orders', [
            'buyer' => ['name' => 'K', 'phone' => '0912345678', 'address' => 'x', 'province' => 'HN'],
            'items' => [['sku_id' => $sku->getKey(), 'name' => 'A', 'quantity' => 1, 'unit_price' => 150000]],
            'customer_id' => $c->getKey(), 'prepaid_amount' => 150000, 'wallet_amount' => 150000,
        ])->assertStatus(422);
        $this->assertSame(0, Order::withoutGlobalScope(TenantScope::class)->count());
    }
}
