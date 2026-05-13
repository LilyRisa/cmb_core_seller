<?php

namespace Tests\Feature\Inventory;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\SkuMapping;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InventoryApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Tenant $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop A']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->other = Tenant::create(['name' => 'Shop B']);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_product_and_sku_crud(): void
    {
        $pid = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/products', ['name' => 'Áo thun'])
            ->assertCreated()->json('data.id');

        $res = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/skus', ['sku_code' => 'AO-M', 'name' => 'Áo M', 'product_id' => $pid, 'cost_price' => 45000, 'cost_method' => 'latest'])
            ->assertCreated();
        $skuId = $res->json('data.id');
        $this->assertSame('AO-M', $res->json('data.sku_code'));
        // cost_method (bình quân | lô gần nhất) — chưa có lần nhập kho nào ⇒ effective_cost rơi về cost_price (SPEC 0012)
        $this->assertSame('latest', $res->json('data.cost_method'));
        $this->assertSame(45000, $res->json('data.effective_cost'));

        // duplicate code rejected
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/skus', ['sku_code' => 'AO-M', 'name' => 'dup'])->assertStatus(422);

        // adjust stock, then it shows in show + levels
        app(InventoryLedgerService::class)->adjust((int) $this->tenant->getKey(), (int) $skuId, null, 30);
        $show = $this->actingAs($this->owner)->withHeaders($this->h())->getJson("/api/v1/skus/{$skuId}")->assertOk();
        $this->assertSame(30, $show->json('data.available_total'));
        $this->assertNotEmpty($show->json('data.movements'));

        $this->actingAs($this->owner)->withHeaders($this->h())->getJson("/api/v1/inventory/levels?sku_id={$skuId}")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.on_hand', 30);

        // can't delete a SKU with stock
        $this->actingAs($this->owner)->withHeaders($this->h())->deleteJson("/api/v1/skus/{$skuId}")->assertStatus(409);
    }

    public function test_adjust_endpoint_records_movement(): void
    {
        $sku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => 'X1', 'name' => 'X1']);
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/inventory/adjust', ['sku_id' => $sku->getKey(), 'qty_change' => 12, 'note' => 'Nhập'])
            ->assertCreated()->assertJsonPath('data.qty_change', 12)->assertJsonPath('data.balance_after', 12);
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson("/api/v1/inventory/movements?sku_id={$sku->getKey()}")->assertOk()->assertJsonCount(1, 'data');
        // zero change rejected
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/inventory/adjust', ['sku_id' => $sku->getKey(), 'qty_change' => 0])->assertStatus(422);
    }

    public function test_sku_mapping_link_relink_unlink_and_auto_match(): void
    {
        $shop = ChannelAccount::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'provider' => 'tiktok', 'external_shop_id' => 's', 'shop_name' => 'S', 'status' => 'active']);
        $a = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => 'A', 'name' => 'A']);
        $b = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => 'B', 'name' => 'B']);
        $l1 = ChannelListing::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $shop->getKey(), 'external_sku_id' => 'e1', 'seller_sku' => 'A', 'title' => 'L1', 'currency' => 'VND']);
        $l2 = ChannelListing::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $shop->getKey(), 'external_sku_id' => 'e2', 'seller_sku' => 'COMBO', 'title' => 'L2', 'currency' => 'VND']);

        // ghép l2 → a (quan hệ 1-1, không có "số lượng")
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/sku-mappings', ['channel_listing_id' => $l2->getKey(), 'sku_id' => $a->getKey()])->assertCreated()->assertJsonCount(1, 'data');
        $this->assertSame(1, SkuMapping::withoutGlobalScope(TenantScope::class)->where('channel_listing_id', $l2->getKey())->count());
        // đổi liên kết l2 → b (mỗi SKU sàn chỉ thuộc 1 SKU app — gọi lại = thay)
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/sku-mappings', ['channel_listing_id' => $l2->getKey(), 'sku_id' => $b->getKey()])->assertCreated();
        $this->assertSame(1, SkuMapping::withoutGlobalScope(TenantScope::class)->where('channel_listing_id', $l2->getKey())->count());
        $this->assertTrue(SkuMapping::withoutGlobalScope(TenantScope::class)->where('channel_listing_id', $l2->getKey())->where('sku_id', $b->getKey())->exists());
        // 1 SKU app → nhiều SKU sàn: ghép l1 cũng → b
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/sku-mappings', ['channel_listing_id' => $l1->getKey(), 'sku_id' => $b->getKey()])->assertCreated();
        $this->assertSame(2, SkuMapping::withoutGlobalScope(TenantScope::class)->where('sku_id', $b->getKey())->count());
        // bỏ liên kết l1 (sku_id = null)
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/sku-mappings', ['channel_listing_id' => $l1->getKey(), 'sku_id' => null])->assertOk()->assertJsonCount(0, 'data');
        $this->assertSame(0, SkuMapping::withoutGlobalScope(TenantScope::class)->where('channel_listing_id', $l1->getKey())->count());

        // auto-match: l1.seller_sku 'A' == sku 'A'
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/sku-mappings/auto-match')->assertOk()->assertJsonPath('data.matched', 1);
        $this->assertTrue(SkuMapping::withoutGlobalScope(TenantScope::class)->where('channel_listing_id', $l1->getKey())->where('sku_id', $a->getKey())->exists());

        // listing list shows mapped flag
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/channel-listings?mapped=1')->assertOk()->assertJsonCount(2, 'data');
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/channel-listings?mapped=0')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_create_sku_with_catalogue_fields_mappings_and_opening_stock(): void
    {
        Bus::fake([PushStockForSku::class]);
        $shop = ChannelAccount::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'provider' => 'tiktok', 'external_shop_id' => 's1', 'shop_name' => 'Shop 1', 'status' => 'active']);
        $wh = Warehouse::defaultFor((int) $this->tenant->getKey());

        $res = $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/skus', [
            'sku_code' => 'AOM-001', 'name' => 'Áo thun M',
            'spu_code' => 'AOM', 'category' => 'Áo', 'gtins' => ['8930001112223'], 'base_unit' => 'PCS',
            'cost_price' => 40000, 'ref_sale_price' => 99000, 'sale_start_date' => '2026-05-20',
            'note' => 'Hàng mới', 'weight_grams' => 250, 'length_cm' => 30, 'width_cm' => 20, 'height_cm' => 2,
            'mappings' => [['channel_account_id' => $shop->getKey(), 'external_sku_id' => 'EXT-1', 'seller_sku' => 'SHOP-AOM', 'quantity' => 1]],
            'levels' => [['warehouse_id' => $wh->getKey(), 'on_hand' => 15, 'cost_price' => 41000]],
        ])->assertCreated();

        $skuId = (int) $res->json('data.id');
        $this->assertSame('AOM', $res->json('data.spu_code'));
        $this->assertSame(99000, $res->json('data.ref_sale_price'));
        $this->assertSame(59000, $res->json('data.ref_profit_per_unit'));
        $this->assertSame(['8930001112223'], $res->json('data.gtins'));

        // opening stock landed in the warehouse with the per-warehouse cost
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson("/api/v1/skus/{$skuId}")
            ->assertOk()->assertJsonPath('data.available_total', 15)
            ->assertJsonPath('data.levels.0.cost_price', 41000)
            ->assertJsonPath('data.mappings.0.channel_listing.external_sku_id', 'EXT-1');

        // listing was created and linked
        $this->assertTrue(ChannelListing::withoutGlobalScope(TenantScope::class)->where('channel_account_id', $shop->getKey())->where('external_sku_id', 'EXT-1')->exists());

        // bad warehouse / bad shop → 422
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/skus', ['sku_code' => 'X', 'name' => 'x', 'levels' => [['warehouse_id' => 999999, 'on_hand' => 1]]])->assertStatus(422);
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/skus', ['sku_code' => 'Y', 'name' => 'y', 'mappings' => [['channel_account_id' => 999999, 'external_sku_id' => 'Z']]])->assertStatus(422);
    }

    public function test_update_sku_edits_fields_and_replaces_mappings(): void
    {
        Bus::fake([PushStockForSku::class]);
        $shop = ChannelAccount::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'provider' => 'tiktok', 'external_shop_id' => 's', 'shop_name' => 'S', 'status' => 'active']);
        $sku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => 'EDIT-1', 'name' => 'Cũ', 'cost_price' => 1000]);

        // edit catalogue fields + create a mapping to listing L1 (firstOrCreate)
        $this->actingAs($this->owner)->withHeaders($this->h())->patchJson("/api/v1/skus/{$sku->getKey()}", [
            'name' => 'Mới', 'cost_price' => 5000, 'ref_sale_price' => 12000, 'base_unit' => 'Cái', 'spu_code' => 'GRP', 'weight_grams' => 100,
            'mappings' => [['channel_account_id' => $shop->getKey(), 'external_sku_id' => 'L1', 'quantity' => 1]],
        ])->assertOk()->assertJsonPath('data.name', 'Mới')->assertJsonPath('data.cost_price', 5000)->assertJsonPath('data.spu_code', 'GRP')->assertJsonPath('data.base_unit', 'Cái');
        $l1 = ChannelListing::withoutGlobalScope(TenantScope::class)->where('channel_account_id', $shop->getKey())->where('external_sku_id', 'L1')->firstOrFail();
        $this->assertTrue(SkuMapping::withoutGlobalScope(TenantScope::class)->where('channel_listing_id', $l1->getKey())->where('sku_id', $sku->getKey())->exists());

        // sku_code is NOT changed even if sent? FE locks it; backend still allows — but our FE never sends it.
        // Replace the mapping set with L2 only → L1 link dropped, L2 created.
        $this->actingAs($this->owner)->withHeaders($this->h())->patchJson("/api/v1/skus/{$sku->getKey()}", [
            'mappings' => [['channel_account_id' => $shop->getKey(), 'external_sku_id' => 'L2', 'seller_sku' => 's2']],
        ])->assertOk();
        $l2 = ChannelListing::withoutGlobalScope(TenantScope::class)->where('channel_account_id', $shop->getKey())->where('external_sku_id', 'L2')->firstOrFail();
        $this->assertFalse(SkuMapping::withoutGlobalScope(TenantScope::class)->where('channel_listing_id', $l1->getKey())->where('sku_id', $sku->getKey())->exists());
        $this->assertTrue(SkuMapping::withoutGlobalScope(TenantScope::class)->where('channel_listing_id', $l2->getKey())->where('sku_id', $sku->getKey())->where('quantity', 1)->exists());

        // empty mappings array → all links dropped
        $this->actingAs($this->owner)->withHeaders($this->h())->patchJson("/api/v1/skus/{$sku->getKey()}", ['mappings' => []])->assertOk();
        $this->assertSame(0, SkuMapping::withoutGlobalScope(TenantScope::class)->where('sku_id', $sku->getKey())->count());

        // not sending `mappings` leaves links untouched (re-add one, then PATCH only a field)
        $this->actingAs($this->owner)->withHeaders($this->h())->patchJson("/api/v1/skus/{$sku->getKey()}", ['mappings' => [['channel_account_id' => $shop->getKey(), 'external_sku_id' => 'L2', 'quantity' => 1]]])->assertOk();
        $this->actingAs($this->owner)->withHeaders($this->h())->patchJson("/api/v1/skus/{$sku->getKey()}", ['note' => 'chỉ đổi ghi chú'])->assertOk();
        $this->assertSame(1, SkuMapping::withoutGlobalScope(TenantScope::class)->where('sku_id', $sku->getKey())->count());

        // bad shop in mappings → 422
        $this->actingAs($this->owner)->withHeaders($this->h())->patchJson("/api/v1/skus/{$sku->getKey()}", ['mappings' => [['channel_account_id' => 999999, 'external_sku_id' => 'Z']]])->assertStatus(422);

        // viewer can't edit
        $viewer = User::factory()->create();
        $this->tenant->users()->attach($viewer->getKey(), ['role' => Role::Viewer->value]);
        $this->actingAs($viewer)->withHeaders($this->h())->patchJson("/api/v1/skus/{$sku->getKey()}", ['name' => 'x'])->assertForbidden();
    }

    public function test_upload_and_delete_sku_image(): void
    {
        Storage::fake('public');   // MEDIA_DISK falls back to "public" outside production
        $sku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => 'IMG1', 'name' => 'img']);

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/skus/{$sku->getKey()}/image", ['image' => UploadedFile::fake()->image('a.jpg', 200, 200)])
            ->assertOk();
        $this->assertNotNull($res->json('data.image_url'));
        $sku->refresh();
        $this->assertNotNull($sku->image_path);
        Storage::disk('public')->assertExists($sku->image_path);

        // non-image rejected
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/skus/{$sku->getKey()}/image", ['image' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])->assertStatus(422);

        $path = (string) $sku->image_path;
        $this->actingAs($this->owner)->withHeaders($this->h())->deleteJson("/api/v1/skus/{$sku->getKey()}/image")->assertOk();
        $sku->refresh();
        $this->assertNull($sku->image_url);
        Storage::disk('public')->assertMissing($path);

        // viewer can't upload
        $viewer = User::factory()->create();
        $this->tenant->users()->attach($viewer->getKey(), ['role' => Role::Viewer->value]);
        $this->actingAs($viewer)->withHeaders($this->h())->postJson("/api/v1/skus/{$sku->getKey()}/image", ['image' => UploadedFile::fake()->image('b.png')])->assertForbidden();
    }

    public function test_tenant_isolation_on_skus(): void
    {
        $otherSku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->other->getKey(), 'sku_code' => 'OTH', 'name' => 'oth']);
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson("/api/v1/skus/{$otherSku->getKey()}")->assertNotFound();
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/skus')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_warehouse_default_is_auto_created(): void
    {
        $res = $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/warehouses')->assertOk();
        $this->assertGreaterThanOrEqual(1, count($res->json('data')));
        $this->assertTrue(collect($res->json('data'))->contains('is_default', true));
    }

    public function test_viewer_cannot_adjust_or_create_sku(): void
    {
        $viewer = User::factory()->create();
        $this->tenant->users()->attach($viewer->getKey(), ['role' => Role::Viewer->value]);
        $sku = Sku::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'sku_code' => 'V1', 'name' => 'v']);
        $this->actingAs($viewer)->withHeaders($this->h())->getJson('/api/v1/skus')->assertOk();    // can view
        $this->actingAs($viewer)->withHeaders($this->h())->postJson('/api/v1/inventory/adjust', ['sku_id' => $sku->getKey(), 'qty_change' => 1])->assertForbidden();
        $this->actingAs($viewer)->withHeaders($this->h())->postJson('/api/v1/skus', ['sku_code' => 'Z', 'name' => 'z'])->assertForbidden();
    }
}
