<?php

namespace Tests\Feature\Orders;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Sku $sku;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->sku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => 'SKU-1', 'name' => 'Áo']);
        app(InventoryLedgerService::class)->adjust((int) $this->tenant->getKey(), (int) $this->sku->getKey(), null, 20);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function level(): InventoryLevel
    {
        return InventoryLevel::withoutGlobalScope(TenantScope::class)->where('sku_id', $this->sku->getKey())->firstOrFail();
    }

    public function test_create_manual_order_reserves_stock_and_links_customer(): void
    {
        $res = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/orders', [
            'buyer' => ['name' => 'Trần B', 'phone' => '0912345678', 'address' => 'Số 5', 'province' => 'Hà Nội'],
            'items' => [['sku_id' => $this->sku->getKey(), 'name' => 'Áo M', 'quantity' => 3, 'unit_price' => 150000]],
            'shipping_fee' => 20000, 'is_cod' => true, 'note' => 'Gọi trước',
        ])->assertCreated();

        $res->assertJsonPath('data.source', 'manual')->assertJsonPath('data.status', 'processing');
        $this->assertSame(450000 + 20000, $res->json('data.grand_total'));
        $orderId = $res->json('data.id');
        $this->assertSame(3, $this->level()->reserved);   // reserved on create

        // customer linked (SPEC 0002 pipeline runs for manual orders too)
        $customer = Customer::withoutGlobalScope(TenantScope::class)->where('tenant_id', $this->tenant->getKey())->first();
        $this->assertNotNull($customer);
        $this->assertSame('0912345678', $customer->phone);
        $this->assertSame((int) $customer->getKey(), (int) Order::withoutGlobalScope(TenantScope::class)->find($orderId)->customer_id);
    }

    public function test_cancel_manual_order_releases_stock(): void
    {
        $orderId = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/orders', [
            'buyer' => ['name' => 'X', 'phone' => '0900000001'],
            'items' => [['sku_id' => $this->sku->getKey(), 'name' => 'Áo', 'quantity' => 2, 'unit_price' => 100000]],
        ])->assertCreated()->json('data.id');
        $this->assertSame(2, $this->level()->reserved);

        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/orders/{$orderId}/cancel", ['reason' => 'Khách đổi ý'])
            ->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertSame(0, $this->level()->reserved);
        $this->assertSame(20, $this->level()->on_hand);
    }

    public function test_cannot_create_without_items(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/orders', ['buyer' => ['name' => 'X'], 'items' => []])->assertStatus(422);
    }

    public function test_create_manual_order_with_ad_hoc_quick_product(): void
    {
        $res = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/orders', [
            'items' => [['name' => 'Quà tặng kèm', 'image' => 'https://cdn.example.com/x.jpg', 'quantity' => 2, 'unit_price' => 50000]],
        ])->assertCreated();

        $res->assertJsonPath('data.items.0.sku_id', null)
            ->assertJsonPath('data.items.0.name', 'Quà tặng kèm')
            ->assertJsonPath('data.items.0.image', 'https://cdn.example.com/x.jpg')
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.has_issue', false);
        // ad-hoc lines are not tracked in inventory
        $this->assertSame(0, $this->level()->reserved);
        $this->assertSame(20, $this->level()->on_hand);
    }

    public function test_sku_line_name_and_image_are_filled_from_the_sku(): void
    {
        Sku::withoutGlobalScope(TenantScope::class)->whereKey($this->sku->getKey())->update(['image_url' => 'https://cdn.example.com/sku.jpg']);
        $res = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/orders', [
            'items' => [['sku_id' => $this->sku->getKey(), 'quantity' => 1, 'unit_price' => 99000]],
        ])->assertCreated();

        $res->assertJsonPath('data.items.0.name', 'Áo')->assertJsonPath('data.items.0.image', 'https://cdn.example.com/sku.jpg');
    }

    public function test_cannot_create_ad_hoc_item_without_name(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/orders', [
            'items' => [['unit_price' => 1000, 'quantity' => 1]],
        ])->assertStatus(422);
    }

    public function test_viewer_cannot_create_order(): void
    {
        $viewer = User::factory()->create();
        $this->tenant->users()->attach($viewer->getKey(), ['role' => Role::Viewer->value]);
        $this->actingAs($viewer)->withHeaders($this->h())->postJson('/api/v1/orders', [
            'items' => [['sku_id' => $this->sku->getKey()]],
        ])->assertForbidden();
    }

    public function test_cannot_edit_a_shipped_manual_order(): void
    {
        $orderId = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/orders', [
            'items' => [['sku_id' => $this->sku->getKey(), 'name' => 'Áo', 'unit_price' => 100000]],
        ])->assertCreated()->json('data.id');
        Order::withoutGlobalScope(TenantScope::class)->whereKey($orderId)->update(['status' => StandardOrderStatus::Shipped->value]);

        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/orders/{$orderId}/cancel")->assertStatus(422);
        $this->actingAs($this->owner)->withHeaders($this->h())->patchJson("/api/v1/orders/{$orderId}", ['note' => 'x'])->assertStatus(422);
    }
}
