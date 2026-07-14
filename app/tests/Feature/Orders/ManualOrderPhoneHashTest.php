<?php

namespace Tests\Feature\Orders;

use CMBcoreSeller\Modules\Customers\Support\CustomerPhoneNormalizer;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Orders\Services\ManualOrderService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualOrderPhoneHashTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Sku $sku;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $warehouse = Warehouse::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'name' => 'Kho chính', 'is_default' => true,
        ]);
        $this->sku = Sku::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'sku_code' => 'SKU-1', 'name' => 'Áo',
            'warehouse_id' => $warehouse->getKey(), 'stock_on_hand' => 10, 'stock_reserved' => 0,
        ]);
    }

    private function baseData(array $overrides = []): array
    {
        return array_merge([
            'buyer' => ['name' => 'Chị A', 'phone' => '0912345678'],
            'recipient' => ['name' => 'Chị A', 'phone' => '0912345678', 'address' => 'HN'],
            'items' => [['sku_id' => $this->sku->getKey(), 'quantity' => 1, 'unit_price' => 100000, 'discount' => 0]],
        ], $overrides);
    }

    public function test_create_sets_buyer_and_recipient_phone_hash(): void
    {
        $order = app(ManualOrderService::class)->create($this->tenant->getKey(), null, $this->baseData());

        $expected = CustomerPhoneNormalizer::normalizeAndHash('0912345678');
        $this->assertSame($expected, $order->buyer_phone_hash);
        $this->assertSame($expected, $order->recipient_phone_hash);
    }

    public function test_create_with_recipient_only_sets_recipient_hash_and_null_buyer_hash(): void
    {
        // Ca lỗi gốc (SPEC 2026-07-13): chỉ điền "Nhận hàng" ⇒ buyer_phone rỗng.
        $order = app(ManualOrderService::class)->create($this->tenant->getKey(), null, $this->baseData([
            'buyer' => ['name' => 'Chị A'],
        ]));

        $this->assertNull($order->buyer_phone_hash);
        $this->assertSame(CustomerPhoneNormalizer::normalizeAndHash('0912345678'), $order->recipient_phone_hash);
    }

    public function test_update_recomputes_hash_when_phone_changes(): void
    {
        $order = app(ManualOrderService::class)->create($this->tenant->getKey(), null, $this->baseData());

        $updated = app(ManualOrderService::class)->update($order, [
            'buyer' => ['phone' => '0987654321'],
        ]);

        $this->assertSame(CustomerPhoneNormalizer::normalizeAndHash('0987654321'), $updated->buyer_phone_hash);
    }

    public function test_update_without_phone_change_keeps_existing_hash(): void
    {
        $order = app(ManualOrderService::class)->create($this->tenant->getKey(), null, $this->baseData());
        $originalHash = $order->buyer_phone_hash;

        $updated = app(ManualOrderService::class)->update($order, ['note' => 'ghi chú mới']);

        $this->assertSame($originalHash, $updated->buyer_phone_hash);
    }
}
