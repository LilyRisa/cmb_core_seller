<?php

namespace Tests\Feature\Customers;

use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Models\CustomerNote;
use CMBcoreSeller\Modules\Orders\Events\OrderUpserted;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerLinkingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Tenant $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Shop A']);
        $this->other = Tenant::create(['name' => 'Shop B']);
    }

    private function makeOrder(Tenant $tenant, string $extId, string $phone, StandardOrderStatus $status, int $total = 100000, ?string $name = 'Nguyễn Văn A', $placedAt = null): Order
    {
        return Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'source' => 'tiktok', 'channel_account_id' => null,
            'external_order_id' => $extId, 'order_number' => $extId, 'status' => $status, 'raw_status' => 'X',
            'buyer_name' => $name, 'shipping_address' => ['phone' => $phone, 'name' => $name, 'city' => 'Hà Nội', 'address' => 'Số 1'],
            'currency' => 'VND', 'grand_total' => $total, 'item_total' => $total, 'placed_at' => $placedAt ?: now(),
            'has_issue' => false, 'tags' => [], 'source_updated_at' => now(),
        ]);
    }

    private function fire(Order $order): void
    {
        OrderUpserted::dispatch($order, true);
    }

    public function test_first_order_creates_a_customer_and_links_it(): void
    {
        $order = $this->makeOrder($this->tenant, 'O1', '0987654321', StandardOrderStatus::Pending, 250000);
        $this->fire($order);

        $customer = Customer::withoutGlobalScope(TenantScope::class)->first();
        $this->assertNotNull($customer);
        $this->assertSame((int) $this->tenant->getKey(), (int) $customer->tenant_id);
        $this->assertSame(hash('sha256', '0987654321'), $customer->phone_hash);
        $this->assertSame('0987654321', $customer->phone);          // decrypted
        $this->assertSame('Nguyễn Văn A', $customer->name);
        $this->assertSame((int) $customer->getKey(), (int) $order->fresh()->customer_id);
        $this->assertSame(1, $customer->lifetime_stats['orders_total']);
        $this->assertSame(0, $customer->lifetime_stats['orders_completed']);
        $this->assertSame(100, $customer->reputation_score);
        $this->assertSame('ok', $customer->reputation_label);
    }

    public function test_orders_with_different_phone_formats_match_the_same_customer(): void
    {
        $this->fire($this->makeOrder($this->tenant, 'O1', '+84987654321', StandardOrderStatus::Completed, 100000));
        $this->fire($this->makeOrder($this->tenant, 'O2', '0987 654 321', StandardOrderStatus::Cancelled, 50000));

        $this->assertSame(1, Customer::withoutGlobalScope(TenantScope::class)->count());
        $c = Customer::withoutGlobalScope(TenantScope::class)->first();
        $this->assertSame(2, $c->lifetime_stats['orders_total']);
        $this->assertSame(1, $c->lifetime_stats['orders_completed']);
        $this->assertSame(1, $c->lifetime_stats['orders_cancelled']);
        $this->assertSame(100000, $c->lifetime_stats['revenue_completed']);
    }

    public function test_recompute_is_idempotent_no_duplicate_auto_notes(): void
    {
        // 2 cancellations → exactly one auto.cancel_streak note, even if the event re-fires.
        $o1 = $this->makeOrder($this->tenant, 'O1', '0900000001', StandardOrderStatus::Cancelled);
        $o2 = $this->makeOrder($this->tenant, 'O2', '0900000001', StandardOrderStatus::Cancelled);
        $this->fire($o1);
        $this->fire($o2);
        $this->fire($o2); // duplicate event

        $c = Customer::withoutGlobalScope(TenantScope::class)->first();
        $this->assertSame(2, $c->lifetime_stats['orders_cancelled']);
        $this->assertSame(70, $c->reputation_score); // 100 - 2*15
        $this->assertSame('watch', $c->reputation_label);
        $notes = CustomerNote::withoutGlobalScope(TenantScope::class)->where('customer_id', $c->getKey())->where('kind', 'auto.cancel_streak')->get();
        $this->assertCount(1, $notes);
        $this->assertSame('warning', $notes->first()->severity);
    }

    public function test_masked_phone_does_not_create_a_customer(): void
    {
        $this->fire($this->makeOrder($this->tenant, 'O1', '(+84) ****21', StandardOrderStatus::Pending));
        $this->assertSame(0, Customer::withoutGlobalScope(TenantScope::class)->count());
    }

    public function test_same_phone_in_two_tenants_are_separate_customers(): void
    {
        $this->fire($this->makeOrder($this->tenant, 'A1', '0987654321', StandardOrderStatus::Completed));
        $this->fire($this->makeOrder($this->other, 'B1', '0987654321', StandardOrderStatus::Completed));
        $this->assertSame(2, Customer::withoutGlobalScope(TenantScope::class)->count());
        $byTenant = Customer::withoutGlobalScope(TenantScope::class)->pluck('tenant_id')->sort()->values()->all();
        $this->assertEqualsCanonicalizing([(int) $this->tenant->getKey(), (int) $this->other->getKey()], array_map('intval', $byTenant));
    }

    public function test_vip_tag_and_note_after_ten_completed(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->fire($this->makeOrder($this->tenant, "O{$i}", '0911111111', StandardOrderStatus::Completed, 100000));
        }
        $c = Customer::withoutGlobalScope(TenantScope::class)->first();
        $this->assertContains('vip', $c->tags);
        $this->assertTrue(CustomerNote::withoutGlobalScope(TenantScope::class)->where('customer_id', $c->getKey())->where('kind', 'auto.vip')->exists());
    }
}
