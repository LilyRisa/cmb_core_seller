<?php

namespace Tests\Feature\Customers;

use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Models\CustomerWalletTransaction;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_has_prepaid_balance_and_wallet_transactions_table(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);
        $c = Customer::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'phone_hash' => str_repeat('a', 64), 'phone' => '0900000000',
            'name' => 'Khách', 'lifetime_stats' => ['orders_total' => 0], 'tags' => [], 'addresses_meta' => [],
            'first_seen_at' => now(), 'last_seen_at' => now(),
        ]);
        $this->assertSame(0, (int) $c->prepaid_balance);

        CustomerWalletTransaction::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'customer_id' => $c->getKey(),
            'type' => CustomerWalletTransaction::TYPE_TOPUP, 'amount' => 100000, 'balance_after' => 100000,
            'created_at' => now(),
        ]);
        $this->assertDatabaseHas('customer_wallet_transactions', ['customer_id' => $c->getKey(), 'amount' => 100000]);
    }
}
