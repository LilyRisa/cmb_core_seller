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
