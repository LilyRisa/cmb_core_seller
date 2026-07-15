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

class BulkListingDraftTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private int $accountId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        app(CurrentTenant::class)->set($this->tenant);

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

    private function makeDraft(string $name): ListingDraft
    {
        // 'image' bắt buộc để LazadaListingValidator không báo thiếu ảnh — xem
        // ListingDraftServiceTest::test_creating_a_draft_seeds_images_from_product_data.
        $product = Product::create(['tenant_id' => $this->tenant->getKey(), 'name' => $name, 'image' => 'https://cdn/'.$name.'.jpg']);
        $product->skus()->create([
            'tenant_id' => $this->tenant->getKey(),
            'sku_code' => 'SKU-'.$name,
            'name' => $name,
            'base_unit' => 'cái',
            'cost_price' => 20000,
            'cost_method' => Sku::COST_AVERAGE,
            'ref_sale_price' => 35000,
        ]);

        return app(ListingDraftService::class)->createDraft((int) $product->getKey(), $this->accountId, 'lazada');
    }

    public function test_bulk_update_saves_each_item_independently(): void
    {
        $draft1 = $this->makeDraft('Áo 1');
        $draft2 = $this->makeDraft('Áo 2');

        $items = [
            [
                'id' => $draft1->getKey(),
                'category_id' => '3',
                'brand_id' => '40516',
                'skus' => [[
                    'seller_sku' => 'S1', 'price' => 35000, 'stock' => 3, 'sale_props' => [],
                    'package_weight' => 0.5, 'package_dims' => ['length' => 10, 'width' => 10, 'height' => 10],
                ]],
            ],
            [
                // draft2 KHÔNG gửi category_id ⇒ vẫn thiếu ⇒ phải ở lại 'draft' kèm lỗi.
                'id' => $draft2->getKey(),
                'skus' => [[
                    'seller_sku' => 'S2', 'price' => 35000, 'stock' => 3, 'sale_props' => [],
                ]],
            ],
        ];

        $results = app(ListingDraftService::class)->bulkUpdate($items);

        $this->assertCount(2, $results);

        $r1 = collect($results)->firstWhere('id', $draft1->getKey());
        $this->assertSame('ready', $r1['status']);
        $this->assertNull($r1['validation_errors']);

        $r2 = collect($results)->firstWhere('id', $draft2->getKey());
        $this->assertSame('draft', $r2['status']);
        $this->assertArrayHasKey('categoryId', $r2['validation_errors']);
    }

    public function test_bulk_update_one_item_missing_does_not_abort_others(): void
    {
        $draft1 = $this->makeDraft('Áo 1');

        $items = [
            ['id' => 999999, 'category_id' => '3'],
            ['id' => $draft1->getKey(), 'category_id' => '3', 'brand_id' => '40516', 'skus' => [[
                'seller_sku' => 'S1', 'price' => 35000, 'stock' => 3, 'sale_props' => [],
                'package_weight' => 0.5, 'package_dims' => ['length' => 10, 'width' => 10, 'height' => 10],
            ]]],
        ];

        $results = app(ListingDraftService::class)->bulkUpdate($items);

        $this->assertCount(2, $results);
        $failed = collect($results)->firstWhere('id', 999999);
        $this->assertSame('error', $failed['status']);
        $ok = collect($results)->firstWhere('id', $draft1->getKey());
        $this->assertSame('ready', $ok['status']);
    }

    public function test_get_bulk_returns_full_drafts_for_selected_ids(): void
    {
        $draft1 = $this->makeDraft('Áo 1');
        $draft2 = $this->makeDraft('Áo 2');

        $res = $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->getJson('/api/v1/listings/bulk?ids='.$draft1->getKey().','.$draft2->getKey());

        $res->assertOk();
        $this->assertCount(2, $res->json('data'));
        $this->assertNotEmpty($res->json('data.0.skus'));
    }

    public function test_get_bulk_rejects_mixed_providers(): void
    {
        $draft1 = $this->makeDraft('Áo 1');

        $tiktokAccount = ChannelAccount::create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'tiktok',
            'external_shop_id' => 'shop-tt',
            'shop_name' => 'Shop TikTok',
            'shop_region' => 'VN',
            'status' => 'active',
            'access_token' => 'tok-tt',
        ]);
        $product2 = Product::create(['tenant_id' => $this->tenant->getKey(), 'name' => 'Áo 2']);
        $draft2 = app(ListingDraftService::class)->createDraft((int) $product2->getKey(), (int) $tiktokAccount->getKey(), 'tiktok');

        $res = $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->getJson('/api/v1/listings/bulk?ids='.$draft1->getKey().','.$draft2->getKey());

        $res->assertStatus(422);
    }

    public function test_put_bulk_saves_each_item_and_returns_status(): void
    {
        $draft1 = $this->makeDraft('Áo 1');

        $res = $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->putJson('/api/v1/listings/bulk', [
                'items' => [[
                    'id' => $draft1->getKey(),
                    'category_id' => '3',
                    'brand_id' => '40516',
                    'skus' => [[
                        'seller_sku' => 'S1', 'price' => 35000, 'stock' => 3, 'sale_props' => [],
                        'package_weight' => 0.5, 'package_dims' => ['length' => 10, 'width' => 10, 'height' => 10],
                    ]],
                ]],
            ]);

        $res->assertOk();
        $this->assertSame('ready', $res->json('data.0.status'));
    }
}
