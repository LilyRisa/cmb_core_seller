<?php

namespace Tests\Feature\Products;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Products\Models\ListingDraft;
use CMBcoreSeller\Modules\Products\Models\Product;
use CMBcoreSeller\Modules\Products\Services\ListingDraftService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingDraftServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Product $product;

    private int $accountId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        app(CurrentTenant::class)->set($this->tenant);

        $this->product = Product::create([
            'tenant_id' => $this->tenant->getKey(),
            'name' => 'Áo thun cotton',
        ]);
        $this->product->skus()->create([
            'tenant_id' => $this->tenant->getKey(),
            'sku_code' => 'SKU-001',
            'name' => 'Áo thun cotton - M',
            'base_unit' => 'cái',
            'cost_price' => 20000,
            'cost_method' => Sku::COST_AVERAGE,
            'ref_sale_price' => 35000,
            'attributes' => ['size' => 'M'],
        ]);

        $account = ChannelAccount::create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'lazada',
            'external_shop_id' => 'shop',
            'shop_name' => 'Shop',
            'shop_region' => 'VN',
            'status' => 'active',
            'access_token' => 'tok',
        ]);
        $this->accountId = (int) $account->getKey();
    }

    public function test_creates_a_draft_listing_from_a_master_product(): void
    {
        $res = $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->postJson("/api/v1/products/{$this->product->getKey()}/listings", [
                'channel_account_id' => $this->accountId,
                'provider' => 'lazada',
            ]);

        $res->assertCreated();
        $this->assertSame('draft', $res->json('data.status'));

        $this->assertDatabaseHas('listing_drafts', [
            'product_id' => $this->product->getKey(),
            'channel_account_id' => $this->accountId,
            'provider' => 'lazada',
            'status' => 'draft',
        ]);

        $draftId = (int) $res->json('data.id');
        $this->assertDatabaseHas('listing_draft_skus', [
            'listing_draft_id' => $draftId,
        ]);
        $this->assertNotEmpty($res->json('data.skus'));
    }

    public function test_creating_a_draft_seeds_images_from_product_data(): void
    {
        $p = Product::create([
            'tenant_id' => $this->tenant->getKey(),
            'name' => 'SP có ảnh',
            'image' => 'https://cdn/main.jpg',
            'meta' => ['image_links' => ['https://cdn/a.jpg', 'https://cdn/b.jpg', 'https://cdn/main.jpg']],
        ]);

        $draft = app(ListingDraftService::class)->createDraft((int) $p->getKey(), $this->accountId, 'lazada');

        // Ảnh đại diện đứng đầu + image_links, khử trùng lặp (main.jpg chỉ 1 lần).
        $this->assertSame(['https://cdn/main.jpg', 'https://cdn/a.jpg', 'https://cdn/b.jpg'], $draft->media_refs);
    }

    public function test_creating_a_draft_seeds_description_from_copied_product_meta(): void
    {
        $p = Product::create([
            'tenant_id' => $this->tenant->getKey(),
            'name' => 'SP copy có mô tả',
            'meta' => ['description' => '<p>Mô tả copy từ sàn nguồn</p>'],
        ]);

        $draft = app(ListingDraftService::class)->createDraft((int) $p->getKey(), $this->accountId, 'lazada');

        $this->assertSame('<p>Mô tả copy từ sàn nguồn</p>', $draft->attributes['description'] ?? null);
    }

    public function test_draft_skus_are_seeded_from_copied_variants_unlinked_with_clean_sale_props(): void
    {
        $p = Product::create([
            'tenant_id' => $this->tenant->getKey(),
            'name' => 'Ốp lưng',
            'meta' => ['variants' => [
                ['name' => 'Trắng Mờ / IP 13', 'price' => 32000, 'stock' => 7, 'sku' => '254160406625'],
                ['name' => 'Hồng Mờ / IP 16', 'price' => 33000, 'stock' => 0, 'sku' => '254160406655'],
            ]],
        ]);

        $draft = app(ListingDraftService::class)->createDraft((int) $p->getKey(), $this->accountId, 'lazada');
        $skus = $draft->skus;

        $this->assertCount(2, $skus);
        $this->assertSame(['Phân loại' => 'Trắng Mờ / IP 13'], $skus[0]->sale_props);
        $this->assertSame('254160406625', $skus[0]->seller_sku);
        $this->assertSame(32000, $skus[0]->price);
        $this->assertSame(7, $skus[0]->stock);
        // Không tự liên kết master SKU (liên kết thủ công sau).
        $this->assertNull($skus[0]->master_variant_id);
    }

    public function test_manual_sku_link_is_persisted_on_update(): void
    {
        $master = $this->product->skus()->first();
        $p = Product::create([
            'tenant_id' => $this->tenant->getKey(),
            'name' => 'SP copy',
            'meta' => ['variants' => [['name' => 'Đỏ', 'price' => 1000, 'stock' => 1, 'sku' => 'V1']]],
        ]);
        $draft = app(ListingDraftService::class)->createDraft((int) $p->getKey(), $this->accountId, 'lazada');
        $skuId = (int) $draft->skus->first()->getKey();

        $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->putJson("/api/v1/listings/{$draft->getKey()}", [
                'skus' => [['id' => $skuId, 'master_variant_id' => $master->getKey()]],
            ])->assertOk();

        $this->assertDatabaseHas('listing_draft_skus', [
            'id' => $skuId,
            'master_variant_id' => $master->getKey(),
        ]);
    }

    public function test_draft_can_be_deleted(): void
    {
        $draft = app(ListingDraftService::class)->createDraft((int) $this->product->getKey(), $this->accountId, 'lazada');
        $id = (int) $draft->getKey();

        $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->deleteJson("/api/v1/listings/{$id}")
            ->assertOk()
            ->assertJsonPath('data.deleted', true);

        $this->assertSoftDeleted('listing_drafts', ['id' => $id]);
    }

    public function test_update_keeps_draft_when_validation_fails_then_ready_when_passes(): void
    {
        $created = $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->postJson("/api/v1/products/{$this->product->getKey()}/listings", [
                'channel_account_id' => $this->accountId,
                'provider' => 'lazada',
            ]);
        $created->assertCreated();
        $draftId = (int) $created->json('data.id');

        $fail = $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->putJson("/api/v1/listings/{$draftId}", [
                'category_id' => '',
            ]);

        $fail->assertOk();
        $this->assertSame('draft', $fail->json('data.status'));
        $this->assertNotEmpty($fail->json('data.validation_errors'));

        $ok = $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->putJson("/api/v1/listings/{$draftId}", [
                'category_id' => '3',
                'brand_id' => '40516',
                'media_refs' => [['ref' => 'https://cdn/x.jpg', 'kind' => 'cdn_url']],
                'skus' => [[
                    'seller_sku' => 'S1',
                    'price' => 35000,
                    'stock' => 3,
                    'sale_props' => [],
                    'package_weight' => 0.5,
                    'package_dims' => ['length' => 10, 'width' => 10, 'height' => 10],
                ]],
            ]);

        $ok->assertOk();
        $this->assertSame('ready', $ok->json('data.status'));
        $this->assertEmpty($ok->json('data.validation_errors'));

        $this->assertDatabaseHas('listing_drafts', [
            'id' => $draftId,
            'status' => ListingDraft::STATUS_READY,
        ]);
    }
}
