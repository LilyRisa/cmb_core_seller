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
use CMBcoreSeller\Modules\Products\Models\ListingDraft;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sao chép sản phẩm "đã có trên sàn" (ChannelListing) sang nhiều shop: cùng nền tảng
 * giữ ngành hàng/thuộc tính (đẩy được luôn), khác nền tảng chỉ giữ nội dung dùng chung.
 */
class MarketplaceCloneTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private ChannelListing $listing;

    private int $sameLazada;

    private int $crossTiktok;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        app(CurrentTenant::class)->set($this->tenant);

        $source = $this->account('lazada', 'src');
        $this->sameLazada = $this->account('lazada', 'dst-lzd');
        $this->crossTiktok = $this->account('tiktok', 'dst-ttk');

        $this->listing = ChannelListing::create([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $source,
            'external_product_id' => 'LZ-9',
            'external_sku_id' => 'S1',
            'title' => 'Máy cạo râu',
            'currency' => 'VND',
            'sync_status' => ChannelListing::SYNC_OK,
        ]);

        $reg = new PublisherRegistry($this->app);
        $reg->register('lazada', FakeDetailPublisher::class);
        $this->app->instance(FakeDetailPublisher::class, new FakeDetailPublisher);
        $this->app->instance(PublisherRegistry::class, $reg);
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

    public function test_clone_creates_drafts_keeping_category_only_for_same_platform(): void
    {
        $res = $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->postJson("/api/v1/channel-listings/{$this->listing->getKey()}/clone-to-shops", [
                'channel_account_ids' => [$this->sameLazada, $this->crossTiktok],
            ]);

        $res->assertCreated();
        $this->assertCount(2, $res->json('data'));

        // Cùng nền tảng (lazada): giữ ngành hàng từ sàn nguồn.
        $same = ListingDraft::where('channel_account_id', $this->sameLazada)->firstOrFail();
        $this->assertSame('3', $same->category_id);

        // Khác nền tảng (tiktok): KHÔNG có ngành hàng (cần soạn lại) ⇒ nháp.
        $cross = ListingDraft::where('channel_account_id', $this->crossTiktok)->firstOrFail();
        $this->assertNull($cross->category_id);
        $this->assertSame(ListingDraft::STATUS_DRAFT, $cross->status);
    }
}

/** Fake: chỉ getListingDetail có dữ liệu (đủ ngành hàng); còn lại không dùng. */
final class FakeDetailPublisher implements ProductPublishingConnector
{
    public function getListingDetail(AuthContext $auth, string $externalProductId): ListingDetailDTO
    {
        return new ListingDetailDTO(
            externalProductId: $externalProductId,
            title: 'Máy cạo râu',
            description: 'Mô tả',
            images: ['https://cdn/a.jpg'],
            skus: [['external_sku_id' => 'S1', 'seller_sku' => 'S1', 'price' => 249000]],
            categoryId: '3',
            brandId: '40516',
            attributes: ['color' => 'cam'],
        );
    }

    public function getShippingOptions(AuthContext $auth): array
    {
        return [];
    }

    public function createListing(AuthContext $auth, ListingDraftDTO $draft): ListingResultDTO
    {
        throw new \RuntimeException('not used');
    }

    public function updateListing(AuthContext $auth, string $externalProductId, ListingEditDTO $edit): ListingResultDTO
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
}
