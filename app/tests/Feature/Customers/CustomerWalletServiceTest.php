<?php

namespace Tests\Feature\Customers;

use CMBcoreSeller\Modules\Accounting\Services\AccountingSetupService;
use CMBcoreSeller\Modules\Customers\Contracts\CustomerWallet;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Models\CustomerWalletTransaction;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerWalletServiceTest extends TestCase
{
    use RefreshDatabase;

    private function seedAccounting(int $tenantId): void
    {
        app(AccountingSetupService::class)->run($tenantId, 2026);
    }

    private function customer(Tenant $t, string $hashChar = 'b'): Customer
    {
        return Customer::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t->getKey(), 'phone_hash' => str_repeat($hashChar, 64), 'phone' => '0900000000',
            'name' => 'K', 'lifetime_stats' => ['orders_total' => 0], 'tags' => [], 'addresses_meta' => [],
            'first_seen_at' => now(), 'last_seen_at' => now(),
        ]);
    }

    public function test_topup_then_deduct_then_refund(): void
    {
        $t = Tenant::create(['name' => 'S']);
        $this->seedAccounting((int) $t->getKey());
        $c = $this->customer($t);
        $w = app(CustomerWallet::class);

        $tx = $w->topup((int) $t->getKey(), (int) $c->getKey(), 300000, 'cash', 'HD-001', 'Nạp', 1);
        $this->assertSame(300000, (int) $c->refresh()->prepaid_balance);
        $this->assertSame('HD-001', $tx->invoice_ref);

        $w->deductForOrder((int) $t->getKey(), (int) $c->getKey(), 777, 120000, 1);
        $this->assertSame(180000, (int) $c->refresh()->prepaid_balance);

        $w->refundForOrder((int) $t->getKey(), (int) $c->getKey(), 777, 1);
        $this->assertSame(300000, (int) $c->refresh()->prepaid_balance);
    }

    public function test_deduct_more_than_balance_throws(): void
    {
        $t = Tenant::create(['name' => 'S']);
        $this->seedAccounting((int) $t->getKey());
        $c = $this->customer($t, 'c');
        $this->expectException(\RuntimeException::class);
        app(CustomerWallet::class)->deductForOrder((int) $t->getKey(), (int) $c->getKey(), 1, 50000, 1);
    }

    public function test_deduct_is_idempotent_per_order(): void
    {
        $t = Tenant::create(['name' => 'S']);
        $this->seedAccounting((int) $t->getKey());
        $c = $this->customer($t, 'd');
        $w = app(CustomerWallet::class);
        $w->topup((int) $t->getKey(), (int) $c->getKey(), 300000, 'cash', 'HD-002', null, 1);
        $w->deductForOrder((int) $t->getKey(), (int) $c->getKey(), 9, 100000, 1);
        $w->deductForOrder((int) $t->getKey(), (int) $c->getKey(), 9, 100000, 1); // gọi lại — không trừ lần 2
        $this->assertSame(200000, (int) $c->refresh()->prepaid_balance);
        $this->assertSame(1, CustomerWalletTransaction::withoutGlobalScope(TenantScope::class)
            ->where('order_id', 9)->where('type', 'order_payment')->count());
    }
}
