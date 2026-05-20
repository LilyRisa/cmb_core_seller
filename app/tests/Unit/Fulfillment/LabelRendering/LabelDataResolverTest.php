<?php

namespace Tests\Unit\Fulfillment\LabelRendering;

use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\LabelDataResolver;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LabelDataResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_assembles_full_context(): void
    {
        $t = Tenant::factory()->create();
        $wh = Warehouse::create(['tenant_id' => $t->id, 'name' => 'Kho A', 'is_default' => true,
            'address' => ['line1' => '1 Lê Lợi', 'ward' => 'Bến Nghé', 'district' => 'Q1', 'province' => 'TP.HCM', 'phone' => '0901', 'contact' => 'Shop A']]);
        $o = Order::create([
            'tenant_id' => $t->id, 'warehouse_id' => $wh->id, 'source' => 'manual',
            'order_number' => 'M-9', 'buyer_name' => 'Nguyễn B',
            'shipping_address' => ['fullName' => 'Nguyễn B', 'phone' => '0911', 'line1' => '34 THD',
                'ward' => 'Hàng Bài', 'district' => 'Hoàn Kiếm', 'province' => 'Hà Nội'],
            'cod_amount' => 250000, 'is_cod' => true, 'grand_total' => 250000,
            'meta' => ['print_note' => 'Cảm ơn'], 'status' => 'processing',
        ]);
        OrderItem::create(['order_id' => $o->id, 'tenant_id' => $t->id, 'external_item_id' => 'ei-1',
            'name' => 'Áo', 'seller_sku' => 'AT01', 'quantity' => 2]);
        Shipment::create(['tenant_id' => $t->id, 'order_id' => $o->id, 'carrier' => 'ghn',
            'tracking_no' => 'AWB-9', 'status' => 'created', 'weight_grams' => 500]);

        $ctx = (new LabelDataResolver)->resolve($o->fresh());

        $this->assertSame('M-9', $ctx->order_number);
        $this->assertSame('AWB-9', $ctx->tracking_no);
        $this->assertSame('ghn', $ctx->carrier);
        $this->assertSame('Shop A', $ctx->sender_name);
        $this->assertSame('0901', $ctx->sender_phone);
        $this->assertStringContainsString('Lê Lợi', $ctx->sender_address);
        $this->assertSame('Nguyễn B', $ctx->recipient_name);
        $this->assertSame('0911', $ctx->recipient_phone);
        $this->assertSame('34 THD', $ctx->recipient_address_detail);
        $this->assertStringContainsString('Hoàn Kiếm', $ctx->recipient_address_admin);
        $this->assertSame(250000, $ctx->cod);
        $this->assertSame(500, $ctx->weight_g);
        $this->assertSame(2, $ctx->total_qty);
        $this->assertSame('Cảm ơn', $ctx->print_note);
        $this->assertCount(1, $ctx->items);
    }

    public function test_resolve_falls_back_to_default_warehouse_when_null(): void
    {
        $t = Tenant::factory()->create();
        $wh = Warehouse::defaultFor($t->id);
        $wh->update(['name' => 'Kho mặc định', 'address' => ['phone' => '0900', 'contact' => 'Default Shop']]);
        $o = Order::create(['tenant_id' => $t->id, 'warehouse_id' => null, 'source' => 'manual',
            'order_number' => 'M-1', 'shipping_address' => [], 'status' => 'pending']);

        $ctx = (new LabelDataResolver)->resolve($o->fresh());

        $this->assertSame('Default Shop', $ctx->sender_name);
    }

    /**
     * Regression for "COD field không hoạt động" — COD orders surfaced "—" on the slip when the
     * cod_amount column was 0 but is_cod=true (legacy/migrated orders). The resolver now falls
     * back to grand_total − prepaid_amount (matching ManualOrderService's persistence math) when
     * the persisted amount is missing but the order is flagged COD via either is_cod OR
     * payment_status='cod'.
     */
    public function test_resolve_cod_uses_persisted_cod_amount_when_present(): void
    {
        $t = Tenant::factory()->create();
        $o = Order::create([
            'tenant_id' => $t->id, 'source' => 'manual', 'order_number' => 'M-1',
            'shipping_address' => [], 'status' => 'pending',
            'cod_amount' => 350000, 'is_cod' => true, 'grand_total' => 500000, 'prepaid_amount' => 150000,
        ]);

        $this->assertSame(350000, (new LabelDataResolver)->resolve($o->fresh())->cod);
    }

    public function test_resolve_cod_falls_back_to_grand_total_minus_prepaid_when_amount_missing(): void
    {
        $t = Tenant::factory()->create();
        $o = Order::create([
            'tenant_id' => $t->id, 'source' => 'manual', 'order_number' => 'M-2',
            'shipping_address' => [], 'status' => 'pending',
            'cod_amount' => 0, 'is_cod' => true, 'grand_total' => 500000, 'prepaid_amount' => 100000,
        ]);

        $this->assertSame(400000, (new LabelDataResolver)->resolve($o->fresh())->cod);
    }

    public function test_resolve_cod_falls_back_via_payment_status_when_is_cod_false(): void
    {
        // Some channel-upsert paths set payment_status='cod' but leave is_cod=false (different
        // platforms encode COD differently). The resolver treats either signal as "wants COD".
        $t = Tenant::factory()->create();
        $o = Order::create([
            'tenant_id' => $t->id, 'source' => 'tiktok', 'order_number' => 'TK-1',
            'shipping_address' => [], 'status' => 'pending',
            'cod_amount' => 0, 'is_cod' => false, 'payment_status' => 'cod',
            'grand_total' => 200000, 'prepaid_amount' => 0,
        ]);

        $this->assertSame(200000, (new LabelDataResolver)->resolve($o->fresh())->cod);
    }

    public function test_resolve_cod_returns_zero_for_non_cod_order(): void
    {
        $t = Tenant::factory()->create();
        $o = Order::create([
            'tenant_id' => $t->id, 'source' => 'manual', 'order_number' => 'M-3',
            'shipping_address' => [], 'status' => 'pending',
            'cod_amount' => 0, 'is_cod' => false, 'payment_status' => 'paid', 'grand_total' => 500000,
        ]);

        $this->assertSame(0, (new LabelDataResolver)->resolve($o->fresh())->cod);
    }

    public function test_resolve_does_not_n_plus_one_on_items(): void
    {
        $t = Tenant::factory()->create();
        $o = Order::create(['tenant_id' => $t->id, 'source' => 'manual',
            'order_number' => 'M-1', 'shipping_address' => [], 'status' => 'pending']);
        foreach (range(1, 5) as $i) {
            OrderItem::create(['order_id' => $o->id, 'tenant_id' => $t->id, 'external_item_id' => "ei-{$i}",
                'name' => "SP $i", 'quantity' => 1]);
        }
        $o = $o->fresh();
        DB::flushQueryLog();
        DB::enableQueryLog();
        (new LabelDataResolver)->resolve($o);
        $this->assertLessThanOrEqual(4, count(DB::getQueryLog()), 'Expected ≤4 queries (warehouse, shipment, items, fallback) — got '.count(DB::getQueryLog()));
    }
}
