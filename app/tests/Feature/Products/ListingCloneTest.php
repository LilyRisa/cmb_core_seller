<?php

namespace Tests\Feature\Products;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Products\Models\ListingDraft;
use CMBcoreSeller\Modules\Products\Models\Product;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sao chép một listing nháp/đã đăng sang gian hàng khác (SPEC marketplace-listing-copy
 * phase 2). Cùng nền tảng copy đủ dữ liệu đã validate; khác nền tảng chỉ giữ nội dung
 * dùng chung và để nháp đích sau "edit gate".
 */
class ListingCloneTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Product $product;

    private int $lazadaA;

    private int $lazadaB;

    private int $tiktok;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        app(CurrentTenant::class)->set($this->tenant);

        $this->product = Product::create(['tenant_id' => $this->tenant->getKey(), 'name' => 'Áo thun cotton']);
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

        $this->lazadaA = $this->account('lazada', 'shopA');
        $this->lazadaB = $this->account('lazada', 'shopB');
        $this->tiktok = $this->account('tiktok', 'shopT');
    }

    private function account(string $provider, string $shop): int
    {
        return (int) ChannelAccount::create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => $provider,
            'external_shop_id' => $shop,
            'shop_name' => $shop,
            'shop_region' => 'VN',
            'status' => 'active',
            'access_token' => 'tok',
        ])->getKey();
    }

    /** @param  array<string,mixed>  $body */
    private function putDraft(int $draftId, array $body)
    {
        return $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->putJson("/api/v1/listings/{$draftId}", $body);
    }

    /** Tạo một nháp lazada đã `ready` để làm nguồn sao chép. */
    private function readyLazadaDraft(): int
    {
        $created = $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->postJson("/api/v1/products/{$this->product->getKey()}/listings", [
                'channel_account_id' => $this->lazadaA,
                'provider' => 'lazada',
            ]);
        $created->assertCreated();
        $draftId = (int) $created->json('data.id');

        $this->putDraft($draftId, [
            'category_id' => '3',
            'brand_id' => '40516',
            'description' => 'Áo đẹp',
            'media_refs' => [['ref' => 'https://cdn/x.jpg', 'kind' => 'cdn_url']],
            'skus' => [[
                'seller_sku' => 'S1',
                'price' => 35000,
                'stock' => 3,
                'sale_props' => [],
                'package_weight' => 0.5,
                'package_dims' => ['length' => 10, 'width' => 10, 'height' => 10],
            ]],
        ])->assertOk();

        return $draftId;
    }

    private function cloneTo(int $draftId, int $targetAccountId)
    {
        return $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->postJson("/api/v1/listings/{$draftId}/clone", ['channel_account_id' => $targetAccountId]);
    }

    public function test_same_provider_clone_copies_validated_fields_and_stays_ready(): void
    {
        $source = $this->readyLazadaDraft();

        $res = $this->cloneTo($source, $this->lazadaB);

        $res->assertCreated();
        $this->assertSame('ready', $res->json('data.status'));
        $this->assertSame('3', $res->json('data.category_id'));
        $this->assertSame('40516', $res->json('data.brand_id'));
        $this->assertNotEmpty($res->json('data.skus'));

        // Nháp mới khác nháp nguồn, đúng gian hàng đích, chưa có item id trên sàn.
        $newId = (int) $res->json('data.id');
        $this->assertNotSame($source, $newId);
        $this->assertDatabaseHas('listing_drafts', [
            'id' => $newId,
            'channel_account_id' => $this->lazadaB,
            'provider' => 'lazada',
            'external_item_id' => null,
        ]);
    }

    public function test_cross_provider_clone_keeps_portable_content_but_clears_marketplace_fields(): void
    {
        $source = $this->readyLazadaDraft();

        $res = $this->cloneTo($source, $this->tiktok);

        $res->assertCreated();
        $this->assertSame('tiktok', $res->json('data.provider'));
        $this->assertSame('draft', $res->json('data.status'));    // sau edit gate
        $this->assertNull($res->json('data.category_id'));
        $this->assertNull($res->json('data.brand_id'));
        // Nội dung dùng chung được giữ.
        $this->assertSame('Áo đẹp', $res->json('data.attributes.description'));
        $this->assertNotEmpty($res->json('data.media_refs'));
        $this->assertNotEmpty($res->json('data.skus'));
    }

    public function test_clone_to_same_shop_is_rejected(): void
    {
        $source = $this->readyLazadaDraft();

        $this->cloneTo($source, $this->lazadaA)->assertStatus(422);
    }

    public function test_clone_overwrites_existing_non_live_draft_on_target(): void
    {
        $source = $this->readyLazadaDraft();

        $first = $this->cloneTo($source, $this->lazadaB);
        $first->assertCreated();
        $firstId = (int) $first->json('data.id');

        // Sao chép lại cùng cặp (product, shop) → ghi đè đúng nháp cũ, không tạo nháp thứ 2.
        $second = $this->cloneTo($source, $this->lazadaB);
        $second->assertCreated();
        $this->assertSame($firstId, (int) $second->json('data.id'));

        $this->assertSame(
            1,
            ListingDraft::where('product_id', $this->product->getKey())
                ->where('channel_account_id', $this->lazadaB)
                ->count(),
        );
    }
}
