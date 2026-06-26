<?php

namespace Tests\Feature\Customers;

use CMBcoreSeller\Modules\Accounting\Services\AccountingSetupService;
use CMBcoreSeller\Modules\Customers\Contracts\CustomerWallet;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Orders\Events\OrderStatusChanged;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundWalletOnCancelTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancel_refunds_wallet(): void
    {
        $t = Tenant::create(['name' => 'S']);
        app(AccountingSetupService::class)->run((int) $t->getKey(), 2026);
        $c = Customer::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t->getKey(), 'phone_hash' => str_repeat('f', 64), 'phone' => '0900000000', 'name' => 'K',
            'lifetime_stats' => ['orders_total' => 0], 'tags' => [], 'addresses_meta' => [], 'first_seen_at' => now(), 'last_seen_at' => now(),
        ]);
        $w = app(CustomerWallet::class);
        $w->topup((int) $t->getKey(), (int) $c->getKey(), 300000, 'cash', 'HD-1', null, 1);
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t->getKey(), 'source' => 'manual', 'channel_account_id' => null, 'customer_id' => $c->getKey(),
            'external_order_id' => null, 'order_number' => 'M-1', 'status' => StandardOrderStatus::Processing, 'raw_status' => 'processing',
            'currency' => 'VND', 'grand_total' => 120000, 'item_total' => 120000, 'prepaid_amount' => 120000, 'cod_amount' => 0,
            'placed_at' => now(), 'source_updated_at' => now(), 'tags' => [], 'carrier' => 'manual',
        ]);
        $w->deductForOrder((int) $t->getKey(), (int) $c->getKey(), (int) $order->getKey(), 120000, 1);
        $this->assertSame(180000, (int) $c->refresh()->prepaid_balance);

        event(new OrderStatusChanged($order, StandardOrderStatus::Processing, StandardOrderStatus::Cancelled, 'user'));

        $this->assertSame(300000, (int) $c->refresh()->prepaid_balance);
    }
}
