<?php

namespace Tests\Feature\Products;

use CMBcoreSeller\Integrations\Channels\Contracts\ProductPublishingConnector;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\ListingDetailDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingEditDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingResultDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingStatusDTO;
use CMBcoreSeller\Integrations\Channels\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Channels\PublisherRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sửa "Sản phẩm đã có trên sàn" (ChannelListing): đọc chi tiết từ sàn + đẩy
 * tiêu đề/mô tả/ảnh/giá lên sàn, rồi mirror local. Tồn KHÔNG nằm trong luồng này.
 */
class MarketplaceListingEditTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private ChannelListing $listing;

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

        $this->listing = ChannelListing::create([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $account->getKey(),
            'external_product_id' => 'LZ-1',
            'external_sku_id' => 'SKU-1',
            'seller_sku' => 'S1',
            'title' => 'Tên cũ',
            'price' => 35000,
            'channel_stock' => 7,
            'currency' => 'VND',
            'sync_status' => ChannelListing::SYNC_OK,
        ]);
    }

    private function bindPublisher(ProductPublishingConnector $pub): void
    {
        $reg = new PublisherRegistry($this->app);
        $cls = get_class($pub);
        $reg->register('lazada', $cls);
        $this->app->instance($cls, $pub);
        $this->app->instance(PublisherRegistry::class, $reg);
    }

    private function fakeDetail(): ListingDetailDTO
    {
        return new ListingDetailDTO('LZ-1', 'Tên cũ', 'Mô tả cũ', ['https://cdn/a.jpg'], [
            ['external_sku_id' => 'SKU-1', 'seller_sku' => 'S1', 'price' => 35000],
        ]);
    }

    public function test_detail_returns_marketplace_content(): void
    {
        $this->bindPublisher(new FakeEditPublisher($this->fakeDetail()));

        $res = $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->getJson("/api/v1/channel-listings/{$this->listing->getKey()}/marketplace-detail");

        $res->assertOk();
        $this->assertSame('LZ-1', $res->json('data.external_product_id'));
        $this->assertSame('Tên cũ', $res->json('data.title'));
        $this->assertSame('SKU-1', $res->json('data.skus.0.external_sku_id'));
    }

    public function test_update_pushes_changed_fields_and_mirrors_locally(): void
    {
        $pub = new FakeEditPublisher($this->fakeDetail());
        $this->bindPublisher($pub);

        $res = $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->putJson("/api/v1/channel-listings/{$this->listing->getKey()}/marketplace", [
                'title' => 'Tên mới',
                'description' => 'Mô tả mới',
                'images' => ['https://cdn/b.jpg'],
                'prices' => [['external_sku_id' => 'SKU-1', 'price' => 49000]],
            ]);

        $res->assertOk();

        // Đúng payload đẩy lên sàn.
        $this->assertNotNull($pub->lastEdit);
        $this->assertSame('Tên mới', $pub->lastEdit->title);
        $this->assertSame('Mô tả mới', $pub->lastEdit->description);
        $this->assertSame(['https://cdn/b.jpg'], $pub->lastEdit->images);
        $this->assertSame(49000, $pub->lastEdit->prices[0]['price']);

        // Mirror local: tiêu đề + giá + ảnh cập nhật; tồn giữ nguyên.
        $fresh = $this->listing->fresh();
        $this->assertSame('Tên mới', $fresh->title);
        $this->assertSame(49000, (int) $fresh->price);
        $this->assertSame('https://cdn/b.jpg', $fresh->image);
        $this->assertSame(7, (int) $fresh->channel_stock);
    }

    public function test_listing_without_external_product_id_cannot_be_edited(): void
    {
        $this->bindPublisher(new FakeEditPublisher($this->fakeDetail()));
        $this->listing->update(['external_product_id' => null]);

        $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->getJson("/api/v1/channel-listings/{$this->listing->getKey()}/marketplace-detail")
            ->assertStatus(422);
    }
}

/** Fake publisher: ghi lại lần updateListing cuối + trả chi tiết cố định. */
final class FakeEditPublisher implements ProductPublishingConnector
{
    public ?ListingEditDTO $lastEdit = null;

    public function __construct(private ListingDetailDTO $detail) {}

    public function getListingDetail(AuthContext $auth, string $externalProductId): ListingDetailDTO
    {
        return $this->detail;
    }

    public function updateListing(AuthContext $auth, string $externalProductId, ListingEditDTO $edit): ListingResultDTO
    {
        $this->lastEdit = $edit;

        return new ListingResultDTO($externalProductId, [], 'live');
    }

    public function createListing(AuthContext $auth, ListingDraftDTO $draft): ListingResultDTO
    {
        throw new \RuntimeException('not used');
    }

    public function getListingStatus(AuthContext $auth, string $externalItemId): ListingStatusDTO
    {
        throw new \RuntimeException('not used');
    }

    public function getCategoryTree(AuthContext $auth, ?string $parentId = null): array
    {
        throw new \RuntimeException('not used');
    }

    public function getCategoryAttributes(AuthContext $auth, string $categoryId): array
    {
        throw new \RuntimeException('not used');
    }

    public function getBrands(AuthContext $auth, string $categoryId, ?string $keyword = null, int $limit = 50): array
    {
        throw new \RuntimeException('not used');
    }

    public function uploadMedia(AuthContext $auth, string $imageUrlOrPath, string $useCase = 'main'): MediaRefDTO
    {
        return new MediaRefDTO($imageUrlOrPath, 'cdn_url');
    }

    public function getShippingOptions(AuthContext $auth): array
    {
        return [];
    }
}
