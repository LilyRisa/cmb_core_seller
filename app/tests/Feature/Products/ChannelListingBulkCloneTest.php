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

class ChannelListingBulkCloneTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private int $lazadaSource;

    private int $shopeeSource; // provider KHÔNG đăng ký publisher ⇒ clone sẽ lỗi cho sản phẩm nguồn này

    private int $target;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        app(CurrentTenant::class)->set($this->tenant);

        $this->lazadaSource = $this->account('lazada', 'src-lzd');
        $this->shopeeSource = $this->account('shopee', 'src-shp');
        $this->target = $this->account('lazada', 'dst-lzd');

        $reg = new PublisherRegistry($this->app);
        $reg->register('lazada', FakeBulkDetailPublisher::class);
        $this->app->instance(FakeBulkDetailPublisher::class, new FakeBulkDetailPublisher);
        $this->app->instance(PublisherRegistry::class, $reg);
    }

    private function account(string $provider, string $shop): int
    {
        return (int) ChannelAccount::create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => $provider,
            'external_shop_id' => $shop, 'shop_name' => $shop, 'shop_region' => 'VN',
            'status' => 'active', 'access_token' => 'tok',
        ])->getKey();
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_bulk_clone_processes_each_product_independently_isolating_failures(): void
    {
        $okListing = ChannelListing::create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $this->lazadaSource,
            'external_product_id' => 'OK-1', 'external_sku_id' => 'S1', 'title' => 'SP OK',
            'currency' => 'VND', 'sync_status' => ChannelListing::SYNC_OK,
        ]);
        $failListing = ChannelListing::create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $this->shopeeSource,
            'external_product_id' => 'FAIL-1', 'external_sku_id' => 'S2', 'title' => 'SP lỗi',
            'currency' => 'VND', 'sync_status' => ChannelListing::SYNC_OK,
        ]);

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/channel-listings/bulk-clone-to-shops', [
                'channel_listing_ids' => [$okListing->getKey(), $failListing->getKey()],
                'channel_account_ids' => [$this->target],
            ]);

        $res->assertCreated();
        $rows = $res->json('data');
        $this->assertCount(2, $rows);

        $ok = collect($rows)->firstWhere('channel_listing_id', $okListing->getKey());
        $this->assertTrue($ok['ok']);
        $this->assertArrayHasKey('results', $ok);

        $fail = collect($rows)->firstWhere('channel_listing_id', $failListing->getKey());
        $this->assertFalse($fail['ok']);
        $this->assertArrayHasKey('error', $fail);

        $this->assertDatabaseHas('listing_drafts', ['channel_account_id' => $this->target]);
    }
}

/** Fake: chỉ getListingDetail có dữ liệu (đủ để tạo draft); còn lại không dùng. */
final class FakeBulkDetailPublisher implements ProductPublishingConnector
{
    public function getListingDetail(AuthContext $auth, string $externalProductId): ListingDetailDTO
    {
        return new ListingDetailDTO(
            externalProductId: $externalProductId,
            title: 'SP OK',
            description: 'Mô tả',
            images: ['https://cdn/a.jpg'],
            skus: [['external_sku_id' => 'S1', 'seller_sku' => 'S1', 'price' => 100000]],
            categoryId: '3',
            brandId: '40516',
            attributes: [],
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
