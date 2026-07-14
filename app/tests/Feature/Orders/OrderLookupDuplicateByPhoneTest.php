<?php

namespace Tests\Feature\Orders;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderLookupDuplicateByPhoneTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function makeOrder(array $attrs, ?int $tenantId = null): Order
    {
        return Order::withoutGlobalScope(TenantScope::class)->create(array_merge([
            'tenant_id' => $tenantId ?? $this->tenant->getKey(), 'source' => 'manual', 'customer_id' => null,
            'raw_status' => 'X', 'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000,
            'tags' => [], 'shipping_address' => [],
        ], $attrs));
    }

    public function test_matches_by_buyer_phone_without_customer_link(): void
    {
        // Đơn thủ công CHƯA từng gắn Customer (customer_id null) — vẫn phải tìm được bằng buyer_phone.
        // Tạo theo đúng thứ tự thời gian (created_at là tiêu chí sắp "mới nhất", giống recentByCustomer()).
        $this->makeOrder([
            'order_number' => 'DH-RETURNED', 'status' => StandardOrderStatus::ReturnedRefunded,
            'placed_at' => now()->subDays(5), 'source_updated_at' => now()->subDays(5),
            'buyer_phone' => '0912345678',
        ]);
        $latest = $this->makeOrder([
            'order_number' => 'DH-LATEST', 'status' => StandardOrderStatus::Processing,
            'placed_at' => now()->subDay(), 'source_updated_at' => now()->subDay(),
            'buyer_phone' => '0912345678',
        ]);

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/orders/lookup-duplicate-by-phone?phone=0912345678')
            ->assertOk();

        $res->assertJsonPath('data.latest_order.id', $latest->getKey())
            ->assertJsonPath('data.latest_order.number', 'DH-LATEST')
            ->assertJsonPath('data.latest_returned_order.number', 'DH-RETURNED');
    }

    public function test_matches_by_recipient_phone_when_buyer_phone_blank(): void
    {
        // Đơn cũ chỉ điền "Nhận hàng" (buyer_phone rỗng) — đây chính là ca lỗi gốc: vẫn phải cảnh báo
        // được vì SĐT thật nằm ở shipping_address.phone.
        $order = $this->makeOrder([
            'order_number' => 'DH-RECIPIENT-ONLY', 'status' => StandardOrderStatus::Pending,
            'placed_at' => now()->subDay(), 'source_updated_at' => now()->subDay(),
            'buyer_phone' => null, 'shipping_address' => ['phone' => '0912345678'],
        ]);

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/orders/lookup-duplicate-by-phone?phone=0912345678')
            ->assertOk();

        $res->assertJsonPath('data.latest_order.id', $order->getKey());
    }

    public function test_ignores_marketplace_orders_with_same_phone(): void
    {
        // Đơn sàn (source != manual) trùng SĐT KHÔNG được tính — chỉ đơn thủ công.
        $this->makeOrder([
            'order_number' => 'TT-1', 'status' => StandardOrderStatus::Processing, 'source' => 'tiktok',
            'placed_at' => now(), 'source_updated_at' => now(), 'buyer_phone' => '0912345678',
        ]);

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/orders/lookup-duplicate-by-phone?phone=0912345678')
            ->assertOk();

        $res->assertJsonPath('data.latest_order', null);
    }

    public function test_excludes_given_order_id(): void
    {
        $older = $this->makeOrder([
            'order_number' => 'DH-OLDER', 'status' => StandardOrderStatus::Pending,
            'placed_at' => now()->subDays(2), 'source_updated_at' => now()->subDays(2), 'buyer_phone' => '0912345678',
        ]);
        $editing = $this->makeOrder([
            'order_number' => 'DH-EDITING', 'status' => StandardOrderStatus::Pending,
            'placed_at' => now()->subHour(), 'source_updated_at' => now()->subHour(), 'buyer_phone' => '0912345678',
        ]);

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/orders/lookup-duplicate-by-phone?phone=0912345678&exclude_order_id='.$editing->getKey())
            ->assertOk();

        $res->assertJsonPath('data.latest_order.id', $older->getKey());
    }

    public function test_no_orders_returns_nulls(): void
    {
        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/orders/lookup-duplicate-by-phone?phone=0912345678')
            ->assertOk();

        $res->assertJsonPath('data.latest_order', null)->assertJsonPath('data.latest_returned_order', null);
    }

    public function test_does_not_leak_other_tenant_orders(): void
    {
        $otherTenant = Tenant::create(['name' => 'Other']);
        $this->makeOrder([
            'order_number' => 'OTHER-1', 'status' => StandardOrderStatus::Pending,
            'placed_at' => now(), 'source_updated_at' => now(), 'buyer_phone' => '0912345678',
        ], $otherTenant->getKey());

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/orders/lookup-duplicate-by-phone?phone=0912345678')
            ->assertOk();

        $res->assertJsonPath('data.latest_order', null);
    }
}
