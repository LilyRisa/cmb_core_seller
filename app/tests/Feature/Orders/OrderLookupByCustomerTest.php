<?php

namespace Tests\Feature\Orders;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderLookupByCustomerTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->customer = Customer::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'name' => 'Trần B',
            'phone' => '0912345678', 'phone_hash' => hash('sha256', '0912345678'),
            'lifetime_stats' => [], 'tags' => [], 'first_seen_at' => now(), 'last_seen_at' => now(),
        ]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function makeOrder(string $number, StandardOrderStatus $status, $placedAt, ?int $customerId, ?int $tenantId = null): Order
    {
        return Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenantId ?? $this->tenant->getKey(), 'source' => 'manual', 'customer_id' => $customerId,
            'order_number' => $number, 'status' => $status, 'raw_status' => 'X',
            'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000, 'placed_at' => $placedAt,
            'tags' => [], 'source_updated_at' => $placedAt,
        ]);
    }

    public function test_returns_latest_order_and_latest_returned_order(): void
    {
        $this->makeOrder('DH-OLD', StandardOrderStatus::Completed, now()->subDays(10), $this->customer->getKey());
        $this->makeOrder('DH-RETURNED', StandardOrderStatus::ReturnedRefunded, now()->subDays(5), $this->customer->getKey());
        $latest = $this->makeOrder('DH-LATEST', StandardOrderStatus::Processing, now()->subDay(), $this->customer->getKey());

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/orders/lookup-by-customer?customer_id='.$this->customer->getKey())
            ->assertOk();

        $res->assertJsonPath('data.latest_order.id', $latest->getKey())
            ->assertJsonPath('data.latest_order.number', 'DH-LATEST')
            ->assertJsonPath('data.latest_returned_order.number', 'DH-RETURNED');
    }

    public function test_excludes_given_order_id(): void
    {
        $older = $this->makeOrder('DH-OLDER', StandardOrderStatus::Pending, now()->subDays(2), $this->customer->getKey());
        $editing = $this->makeOrder('DH-EDITING', StandardOrderStatus::Pending, now()->subHour(), $this->customer->getKey());

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/orders/lookup-by-customer?customer_id='.$this->customer->getKey().'&exclude_order_id='.$editing->getKey())
            ->assertOk();

        $res->assertJsonPath('data.latest_order.id', $older->getKey());
    }

    public function test_no_orders_returns_nulls(): void
    {
        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/orders/lookup-by-customer?customer_id='.$this->customer->getKey())
            ->assertOk();

        $res->assertJsonPath('data.latest_order', null)->assertJsonPath('data.latest_returned_order', null);
    }

    public function test_does_not_leak_other_tenant_orders(): void
    {
        $otherTenant = Tenant::create(['name' => 'Other']);
        $otherCustomer = Customer::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $otherTenant->getKey(), 'name' => 'X',
            'phone' => '0900000000', 'phone_hash' => hash('sha256', '0900000000'),
            'lifetime_stats' => [], 'tags' => [], 'first_seen_at' => now(), 'last_seen_at' => now(),
        ]);
        $this->makeOrder('OTHER-1', StandardOrderStatus::Pending, now(), $otherCustomer->getKey(), $otherTenant->getKey());

        // customer_id thuộc tenant KHÁC — filter tenant_id trong OrderLookupService phải chặn, không chỉ dựa customer_id.
        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/orders/lookup-by-customer?customer_id='.$otherCustomer->getKey())
            ->assertOk();

        $res->assertJsonPath('data.latest_order', null);
    }
}
