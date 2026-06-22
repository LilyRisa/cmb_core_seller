<?php

declare(strict_types=1);

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
use CMBcoreSeller\Modules\Products\Services\MediaPrepService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MediaPrepServiceTest extends TestCase
{
    /**
     * Build a real PublisherRegistry whose `for()` always resolves the given
     * (counting) fake publisher. PublisherRegistry is final, so we bind the
     * fake into the container as a singleton and register its class.
     */
    private function registryFor(FakeMediaPublisher $pub): PublisherRegistry
    {
        $this->app->instance(FakeMediaPublisher::class, $pub);

        $reg = new PublisherRegistry($this->app);
        $reg->register('tiktok', FakeMediaPublisher::class);
        $reg->register('lazada', FakeMediaPublisher::class);
        $reg->register('shopee', FakeMediaPublisher::class);

        return $reg;
    }

    private function fakePublisher(): FakeMediaPublisher
    {
        return new FakeMediaPublisher;
    }

    private function auth(): AuthContext
    {
        return new AuthContext(1, 'tiktok', 'shop-1', 'token');
    }

    private function tinyPng(): string
    {
        $img = imagecreatetruecolor(10, 10);
        ob_start();
        imagepng($img);
        $bytes = (string) ob_get_clean();
        imagedestroy($img);

        return $bytes;
    }

    public function test_uploads_each_image_and_returns_refs(): void
    {
        Cache::flush();
        Http::fake(['*' => Http::response($this->tinyPng(), 200)]);

        $pub = $this->fakePublisher();
        $svc = new MediaPrepService($this->registryFor($pub));

        $refs = $svc->prepare('tiktok', $this->auth(), ['https://src/a.png', 'https://src/b.png']);

        $this->assertCount(2, $refs);
        $this->assertInstanceOf(MediaRefDTO::class, $refs[0]);
        $this->assertInstanceOf(MediaRefDTO::class, $refs[1]);
    }

    public function test_caches_by_provider_and_url(): void
    {
        Cache::flush();
        Http::fake(['*' => Http::response($this->tinyPng(), 200)]);

        $pub = $this->fakePublisher();
        $svc = new MediaPrepService($this->registryFor($pub));

        $svc->prepare('lazada', $this->auth(), ['https://src/a.png']);
        $svc->prepare('lazada', $this->auth(), ['https://src/a.png']);

        $this->assertSame(1, $pub->uploadCalls);
    }
}

class FakeMediaPublisher implements ProductPublishingConnector
{
    /** @var string[] */
    public array $received = [];

    public int $uploadCalls = 0;

    public function getCategoryTree(AuthContext $auth, ?string $parentId = null): array
    {
        return [];
    }

    public function getCategoryAttributes(AuthContext $auth, string $categoryId): array
    {
        return [];
    }

    public function getBrands(AuthContext $auth, string $categoryId, ?string $keyword = null, int $limit = 50): array
    {
        return [];
    }

    public function uploadMedia(AuthContext $auth, string $imageUrlOrPath, string $useCase = 'main'): MediaRefDTO
    {
        $this->uploadCalls++;
        $this->received[] = $imageUrlOrPath;

        return new MediaRefDTO('ref-'.$this->uploadCalls, 'cdn_url');
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
        return [];
    }
}
