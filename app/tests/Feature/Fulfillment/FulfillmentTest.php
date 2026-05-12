<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Carriers\Ghn\GhnConnector;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Fulfillment\Models\CarrierAccount;
use CMBcoreSeller\Modules\Fulfillment\Models\PrintJob;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;
use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Inventory\Models\InventoryMovement;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FulfillmentTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Sku $sku;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([PushStockForSku::class]);   // ship effect → InventoryChanged → push job; don't hit TikTok
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->sku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => 'SKU-1', 'name' => 'Áo', 'weight_grams' => 300]);
        app(InventoryLedgerService::class)->adjust((int) $this->tenant->getKey(), (int) $this->sku->getKey(), null, 20);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function createOrder(array $overrides = []): int
    {
        return $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/orders', array_merge([
            'buyer' => ['name' => 'Trần B', 'phone' => '0912345678', 'address' => 'Số 5', 'province' => 'Hà Nội'],
            'items' => [['sku_id' => $this->sku->getKey(), 'name' => 'Áo M', 'quantity' => 2, 'unit_price' => 150000]],
            'shipping_fee' => 20000,
        ], $overrides))->assertCreated()->json('data.id');
    }

    private function level(): InventoryLevel
    {
        return InventoryLevel::withoutGlobalScope(TenantScope::class)->where('sku_id', $this->sku->getKey())->firstOrFail();
    }

    // -------------------------------------------------------------------------

    public function test_carrier_accounts_crud_rbac_and_no_credential_leak(): void
    {
        // viewer can't list / create
        $viewer = User::factory()->create();
        $this->tenant->users()->attach($viewer->getKey(), ['role' => Role::Viewer->value]);
        $this->actingAs($viewer)->withHeaders($this->h())->getJson('/api/v1/carrier-accounts')->assertForbidden();

        // warehouse staff can view but not configure (fulfillment.carriers is owner/admin only)
        $wh = User::factory()->create();
        $this->tenant->users()->attach($wh->getKey(), ['role' => Role::StaffWarehouse->value]);
        $this->actingAs($wh)->withHeaders($this->h())->getJson('/api/v1/carrier-accounts')->assertOk();
        $this->actingAs($wh)->withHeaders($this->h())->postJson('/api/v1/carrier-accounts', ['carrier' => 'manual', 'name' => 'X'])->assertForbidden();

        // owner creates a manual account + a GHN one
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/carrier-accounts', ['carrier' => 'manual', 'name' => 'Tự ship', 'is_default' => true])->assertCreated();
        $unsupported = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/carrier-accounts', ['carrier' => 'nope', 'name' => 'X']);
        $unsupported->assertStatus(422);

        // GHN needs the connector registered (env-gated) — register for the test
        app(CarrierRegistry::class)->register('ghn', GhnConnector::class);
        $res = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/carrier-accounts', [
            'carrier' => 'ghn', 'name' => 'GHN HN', 'credentials' => ['token' => 'SECRET-TOKEN', 'shop_id' => 999],
        ])->assertCreated();
        $res->assertJsonPath('data.credential_keys', ['token', 'shop_id']);
        $this->assertStringNotContainsString('SECRET-TOKEN', $res->getContent());

        // listing again still doesn't leak; only one default
        $list = $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/carrier-accounts')->assertOk();
        $this->assertStringNotContainsString('SECRET-TOKEN', $list->getContent());

        // tenant isolation
        $other = Tenant::create(['name' => 'B']);
        $otherAcc = CarrierAccount::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $other->getKey(), 'carrier' => 'manual', 'name' => 'O']);
        $this->actingAs($this->owner)->withHeaders($this->h())->patchJson("/api/v1/carrier-accounts/{$otherAcc->getKey()}", ['name' => 'hack'])->assertNotFound();
    }

    public function test_carriers_endpoint_lists_manual(): void
    {
        $res = $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/carriers')->assertOk();
        $codes = collect($res->json('data'))->pluck('code')->all();
        $this->assertContains('manual', $codes);
        $manual = collect($res->json('data'))->firstWhere('code', 'manual');
        $this->assertFalse($manual['needs_credentials']);
    }

    public function test_processing_flow_scan_pack_then_handover_then_cancel(): void
    {
        $orderId = $this->createOrder();
        $this->assertSame(2, $this->level()->reserved);

        // appears in the "ready"/prepare stage (no shipment yet)
        $this->assertTrue(collect($this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/fulfillment/ready')->json('data'))->contains('id', $orderId));

        // create shipment (manual carrier — no label, so it goes straight to the "pack" stage)
        $ship = $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/orders/{$orderId}/ship", ['tracking_no' => 'TN-001'])->assertCreated();
        $shipmentId = $ship->json('data.id');
        $ship->assertJsonPath('data.carrier', 'manual')->assertJsonPath('data.status', 'created')->assertJsonPath('data.print_count', 0);
        $this->assertSame('ready_to_ship', Order::withoutGlobalScope(TenantScope::class)->find($orderId)->status->value);
        // out of prepare, into pack (manual = no print step)
        $this->assertFalse(collect($this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/fulfillment/processing?stage=prepare')->json('data'))->contains('id', $orderId));
        $this->assertTrue(collect($this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/fulfillment/processing?stage=pack')->json('data'))->contains('id', $orderId));

        // ship again returns the same shipment (1 order = 1 active shipment)
        $this->assertSame($shipmentId, $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/orders/{$orderId}/ship", [])->assertCreated()->json('data.id'));
        $this->assertSame(1, Shipment::withoutGlobalScope(TenantScope::class)->where('order_id', $orderId)->count());

        // scan-pack → đóng gói (created → packed). Order STAYS ready_to_ship, NO stock movement yet.
        $scan = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/scan-pack', ['code' => 'TN-001'])->assertOk();
        $scan->assertJsonPath('data.action', 'pack')->assertJsonPath('data.shipment.status', 'packed')->assertJsonPath('data.order.status', 'ready_to_ship');
        $this->assertFalse(InventoryMovement::withoutGlobalScope(TenantScope::class)->where('sku_id', $this->sku->getKey())->where('type', 'order_ship')->exists());
        $this->assertSame(20, $this->level()->on_hand);
        // moved from pack → handover
        $this->assertTrue(collect($this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/fulfillment/processing?stage=handover')->json('data'))->contains('id', $orderId));
        // scan-pack again → 409 (đã đóng gói)
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/scan-pack', ['code' => 'TN-001'])->assertStatus(409);

        // scan-handover (the app endpoint) → bàn giao ĐVVC: shipment picked_up, order shipped, stock leaves
        $hand = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/scan-handover', ['code' => 'TN-001'])->assertOk();
        $hand->assertJsonPath('data.action', 'handover')->assertJsonPath('data.shipment.status', 'picked_up')->assertJsonPath('data.order.status', 'shipped');
        $this->assertTrue(InventoryMovement::withoutGlobalScope(TenantScope::class)->where('sku_id', $this->sku->getKey())->where('type', 'order_ship')->exists());
        $this->assertSame(18, $this->level()->on_hand);
        $this->assertSame(0, $this->level()->reserved);
        // scan-handover again → 409
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/scan-handover', ['code' => 'TN-001'])->assertStatus(409);
        // unknown code → 404
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/scan-pack', ['code' => 'NOPE-XYZ'])->assertStatus(404);

        // bulk pack + bulk handover on a fresh order
        $o2 = $this->createOrder();
        $s2 = $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/orders/{$o2}/ship", [])->assertCreated()->json('data.id');
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/shipments/pack', ['shipment_ids' => [$s2]])->assertOk()->assertJsonPath('data.packed', 1);
        $this->assertSame('packed', Shipment::withoutGlobalScope(TenantScope::class)->find($s2)->status);
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/shipments/handover', ['shipment_ids' => [$s2]])->assertOk()->assertJsonPath('data.handed_over', 1);
        $this->assertSame('shipped', Order::withoutGlobalScope(TenantScope::class)->find($o2)->status->value);

        // cancel a created shipment → order back to processing
        $o3 = $this->createOrder();
        $s3 = $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/orders/{$o3}/ship", [])->assertCreated()->json('data.id');
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/shipments/{$s3}/cancel")->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertSame('processing', Order::withoutGlobalScope(TenantScope::class)->find($o3)->status->value);
    }

    public function test_bulk_create_reports_per_order_errors(): void
    {
        $ok1 = $this->createOrder();
        $ok2 = $this->createOrder();
        // cancel ok2 so it can't be shipped → becomes a per-order error
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/orders/{$ok2}/cancel")->assertOk();

        $res = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/shipments/bulk-create', ['order_ids' => [$ok1, $ok2, 999999]])->assertOk();
        $this->assertCount(1, $res->json('data.created'));
        $errorIds = collect($res->json('data.errors'))->pluck('order_id')->all();
        $this->assertContains($ok2, $errorIds);
        $this->assertContains(999999, $errorIds);
    }

    public function test_ghn_connector_create_label_and_track_via_http_fake(): void
    {
        Storage::fake('public');
        app(CarrierRegistry::class)->register('ghn', GhnConnector::class);
        Http::fake([
            '*shipping-order/create*' => Http::response(['code' => 200, 'data' => ['order_code' => 'GHN-ABC', 'total_fee' => 31000, 'expected_delivery_time' => null]]),
            '*a5/gen-token*' => Http::response(['code' => 200, 'data' => ['token' => 'PRINT-TK']]),
            '*printA6*' => Http::response('%PDF-1.4 fake-ghn-label', 200, ['Content-Type' => 'application/pdf']),
            '*shipping-order/detail*' => Http::response(['code' => 200, 'data' => ['status' => 'delivered', 'log' => [['status' => 'picked', 'updated_date' => '2026-05-17T08:00:00Z'], ['status' => 'delivered', 'updated_date' => '2026-05-18T15:00:00Z']]]]),
        ]);
        $acc = CarrierAccount::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'carrier' => 'ghn', 'name' => 'GHN', 'credentials' => ['token' => 'T', 'shop_id' => 5], 'is_default' => true]);

        $orderId = $this->createOrder();
        $ship = $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/orders/{$orderId}/ship", ['carrier_account_id' => $acc->getKey()])->assertCreated();
        $ship->assertJsonPath('data.carrier', 'ghn')->assertJsonPath('data.tracking_no', 'GHN-ABC')->assertJsonPath('data.fee', 31000)->assertJsonPath('data.has_label', true);
        $shipmentId = $ship->json('data.id');
        Storage::disk('public')->assertExists("tenants/{$this->tenant->getKey()}/labels/{$shipmentId}.pdf");
        // label endpoint redirects to the stored url
        $this->actingAs($this->owner)->withHeaders($this->h())->get("/api/v1/shipments/{$shipmentId}/label")->assertRedirect();

        // track → events recorded, status synced to delivered, order delivered
        $track = $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/shipments/{$shipmentId}/track")->assertOk();
        $track->assertJsonPath('data.status', 'delivered');
        $this->assertGreaterThanOrEqual(2, count($track->json('data.events')));
        $this->assertSame('delivered', Order::withoutGlobalScope(TenantScope::class)->find($orderId)->status->value);
    }

    public function test_print_jobs_label_bundle_and_picking_list(): void
    {
        Storage::fake('public');
        Http::fake(['*/forms/chromium/convert/html' => Http::response('%PDF-1.4 fake-picking', 200, ['Content-Type' => 'application/pdf'])]);

        // a shipment with a stored label
        $orderId = $this->createOrder();
        $shipment = Shipment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'order_id' => $orderId, 'carrier' => 'manual', 'tracking_no' => 'T1',
            'status' => Shipment::STATUS_CREATED, 'label_path' => "tenants/{$this->tenant->getKey()}/labels/x.pdf", 'label_url' => 'http://x/x.pdf',
        ]);
        Storage::disk('public')->put($shipment->label_path, '%PDF-1.4 one-label');

        $job = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/print-jobs', ['type' => 'label', 'shipment_ids' => [$shipment->getKey()]])->assertCreated();
        $jobId = $job->json('data.id');
        // sync queue → RenderPrintJob already ran
        $done = $this->actingAs($this->owner)->withHeaders($this->h())->getJson("/api/v1/print-jobs/{$jobId}")->assertOk();
        $done->assertJsonPath('data.status', 'done');
        $this->assertNotNull($done->json('data.file_url'));
        $this->actingAs($this->owner)->withHeaders($this->h())->get("/api/v1/print-jobs/{$jobId}/download")->assertRedirect();
        // the print run is counted on the shipment ("đã in N lần")
        $this->assertSame(1, Shipment::withoutGlobalScope(TenantScope::class)->find($shipment->getKey())->print_count);
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/print-jobs', ['type' => 'label', 'shipment_ids' => [$shipment->getKey()]])->assertCreated();   // re-print allowed
        $this->assertSame(2, Shipment::withoutGlobalScope(TenantScope::class)->find($shipment->getKey())->print_count);

        // a label bundle across two carriers is rejected (different formats / pickup batches)
        $o2 = $this->createOrder();
        $s2 = Shipment::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'order_id' => $o2, 'carrier' => 'ghn', 'tracking_no' => 'GHN-1', 'status' => Shipment::STATUS_CREATED, 'label_path' => "tenants/{$this->tenant->getKey()}/labels/y.pdf", 'label_url' => 'http://x/y.pdf']);
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/print-jobs', ['type' => 'label', 'shipment_ids' => [$shipment->getKey(), $s2->getKey()]])->assertStatus(422);

        // picking list for the order — grouped by SKU with the right qty
        $pj = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/print-jobs', ['type' => 'picking', 'order_ids' => [$orderId]])->assertCreated()->json('data.id');
        $pjDone = $this->actingAs($this->owner)->withHeaders($this->h())->getJson("/api/v1/print-jobs/{$pj}")->assertOk();
        $pjDone->assertJsonPath('data.status', 'done')->assertJsonPath('data.meta.lines', 1)->assertJsonPath('data.meta.orders', 1);

        // missing-everything → 422
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/print-jobs', ['type' => 'packing'])->assertStatus(422);
        // viewer can't print
        $viewer = User::factory()->create();
        $this->tenant->users()->attach($viewer->getKey(), ['role' => Role::Viewer->value]);
        $this->actingAs($viewer)->withHeaders($this->h())->postJson('/api/v1/print-jobs', ['type' => 'picking', 'order_ids' => [$orderId]])->assertForbidden();

        // print job is also a PrintJob row in the DB
        $this->assertTrue(PrintJob::withoutGlobalScope(TenantScope::class)->where('id', $jobId)->where('status', 'done')->exists());
    }
}
