<?php

namespace Tests\Feature\Products;

use CMBcoreSeller\Integrations\Channels\Contracts\ProductPublishingConnector;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\BrandDTO;
use CMBcoreSeller\Integrations\Channels\DTO\CategoryNodeDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingAttributeDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingDetailDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingEditDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingResultDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingStatusDTO;
use CMBcoreSeller\Integrations\Channels\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Channels\PublisherRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Top-level fake publisher used to exercise the taxonomy proxy without a real
 * marketplace API. Counts getCategoryTree calls so we can assert caching.
 */
class FakeTaxPublisher implements ProductPublishingConnector
{
    public static int $treeCalls = 0;

    public function getCategoryTree(AuthContext $auth, ?string $parentId = null): array
    {
        self::$treeCalls++;

        return [
            new CategoryNodeDTO('100', '0', 'Thời trang', false),
            new CategoryNodeDTO('1001', '100', 'Áo', true),
        ];
    }

    public function getCategoryAttributes(AuthContext $auth, string $categoryId): array
    {
        return [
            new ListingAttributeDTO('attr1', 'Chất liệu', true, false, 'text', []),
        ];
    }

    public function getBrands(AuthContext $auth, string $categoryId): array
    {
        return [
            new BrandDTO('b1', 'No Brand', false),
        ];
    }

    public function uploadMedia(AuthContext $auth, string $imageUrlOrPath, string $useCase = 'main'): MediaRefDTO
    {
        throw new \RuntimeException('not used');
    }

    public function createListing(AuthContext $auth, ListingDraftDTO $draft): ListingResultDTO
    {
        throw new \RuntimeException('not used');
    }

    public function getListingStatus(AuthContext $auth, string $externalItemId): ListingStatusDTO
    {
        throw new \RuntimeException('not used');
    }

    public function getListingDetail(AuthContext $auth, string $externalProductId): ListingDetailDTO
    {
        throw new \RuntimeException('not used');
    }

    public function updateListing(AuthContext $auth, string $externalProductId, ListingEditDTO $edit): ListingResultDTO
    {
        throw new \RuntimeException('not used');
    }

    public function getShippingOptions(AuthContext $auth): array
    {
        return ['mode' => 'channels', 'channels' => [['id' => '80101', 'name' => 'SPX Express', 'fee_type' => 'SIZE_INPUT']]];
    }
}

class ListingTaxonomyTest extends TestCase
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

        FakeTaxPublisher::$treeCalls = 0;

        $reg = new PublisherRegistry($this->app);
        $reg->register('lazada', FakeTaxPublisher::class);
        $this->app->instance(PublisherRegistry::class, $reg);
        $this->app->bind(FakeTaxPublisher::class, fn () => new FakeTaxPublisher);
    }

    public function test_root_returns_only_top_level_categories(): void
    {
        Cache::flush();

        $res = $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->getJson("/api/v1/channels/lazada/categories?channel_account_id={$this->accountId}");

        $res->assertOk();
        // Chỉ node gốc (parent '0' → null), KHÔNG đổ cả node con ra cấp gốc.
        $res->assertJsonCount(1, 'data');
        $this->assertSame('100', $res->json('data.0.id'));
        $this->assertFalse($res->json('data.0.is_leaf'));
    }

    public function test_children_are_filtered_by_parent(): void
    {
        Cache::flush();

        $res = $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->getJson("/api/v1/channels/lazada/categories?channel_account_id={$this->accountId}&parent_id=100");

        $res->assertOk();
        $res->assertJsonCount(1, 'data');
        $this->assertSame('1001', $res->json('data.0.id'));
        $this->assertTrue($res->json('data.0.is_leaf'));
    }

    public function test_search_returns_leaf_with_breadcrumb_path(): void
    {
        Cache::flush();

        $res = $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->getJson("/api/v1/channels/lazada/categories/search?channel_account_id={$this->accountId}&q=áo");

        $res->assertOk();
        $res->assertJsonCount(1, 'data');
        $this->assertSame('1001', $res->json('data.0.id'));
        $this->assertSame('Thời trang › Áo', $res->json('data.0.path'));
    }

    public function test_returns_shipping_options_for_a_shop(): void
    {
        Cache::flush();

        $res = $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->getJson("/api/v1/channels/lazada/shipping-options?channel_account_id={$this->accountId}");

        $res->assertOk();
        $this->assertSame('channels', $res->json('data.mode'));
        $this->assertSame('80101', $res->json('data.channels.0.id'));
    }

    public function test_caches_categories(): void
    {
        Cache::flush();

        $url = "/api/v1/channels/lazada/categories?channel_account_id={$this->accountId}";

        $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->getJson($url)->assertOk();

        $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->getJson($url)->assertOk();

        $this->assertSame(1, FakeTaxPublisher::$treeCalls);
    }
}
