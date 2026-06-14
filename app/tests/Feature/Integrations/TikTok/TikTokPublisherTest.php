<?php

declare(strict_types=1);

namespace Tests\Feature\Integrations\TikTok;

use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;
use CMBcoreSeller\Integrations\Channels\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Channels\Exceptions\MarketplaceApiException;
use CMBcoreSeller\Integrations\Channels\TikTok\TikTokPublisher;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TikTokPublisherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // TikTokClient reads app_key/app_secret at construction time (via system_setting
        // falling back to config); dev config leaves them null, so set them here.
        config([
            'integrations.tiktok.app_key' => 'test-app-key',
            'integrations.tiktok.app_secret' => 'test-app-secret',
            'integrations.tiktok.base_url' => 'https://open-api.tiktokglobalshop.com',
            // Avoid the per-shop rate limiter doing any sleeping in tests.
            'integrations.throttle.tiktok' => 0,
        ]);
    }

    public function test_creates_a_tiktok_product(): void
    {
        Http::fake([
            '*/product/202309/products*' => Http::response([
                'code' => 0,
                'data' => [
                    'product_id' => '17xx',
                    'skus' => [
                        ['id' => 'sku-9', 'seller_sku' => 'S1'],
                    ],
                ],
            ]),
        ]);

        $result = app(TikTokPublisher::class)->createListing($this->auth(), $this->validDraft());

        $this->assertSame('17xx', $result->externalItemId);
        $this->assertSame('sku-9', $result->skuMap['S1']);
        $this->assertSame('PENDING', $result->rawStatus);
    }

    public function test_attaches_prepared_video_id_to_product(): void
    {
        Http::fake(['*/product/202309/products*' => Http::response(['code' => 0, 'data' => ['product_id' => 'p-1', 'skus' => [['id' => 's1', 'seller_sku' => 'S1']]]])]);

        $draft = new ListingDraftDTO(
            title: 'Áo thun cotton cao cấp form rộng unisex', description: 'Mô tả', categoryId: '600001', brandId: null,
            attributes: [], media: [new MediaRefDTO('tos://img-uri-1', 'uri')],
            skus: [['seller_sku' => 'S1', 'price' => 199000, 'stock' => 10, 'warehouse_id' => 'WH1', 'sale_props' => []]],
            logistics: ['package_weight' => 0.3],
            videoExternalId: 'v-77',
        );

        $result = app(TikTokPublisher::class)->createListing($this->auth(), $draft);

        $this->assertSame('p-1', $result->externalItemId);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/product/202309/products')
            && (($r->data()['video']['id'] ?? null) === 'v-77'));
    }

    public function test_start_video_upload_returns_file_id_and_status_ready(): void
    {
        Http::fake([
            'cdn.example/*' => Http::response('FAKE-VIDEO-BYTES'),
            '*/product/202309/files/upload*' => Http::response(['code' => 0, 'data' => ['id' => 'v-77', 'url' => 'https://tt/v']]),
        ]);

        $draft = new ListingDraftDTO(
            title: 'Áo', description: 'Mô tả', categoryId: '600001', brandId: null, attributes: [],
            media: [new MediaRefDTO('tos://img-uri-1', 'uri')],
            skus: [['seller_sku' => 'S1', 'price' => 1, 'stock' => 1, 'warehouse_id' => 'WH1', 'sale_props' => []]],
            logistics: ['package_weight' => 0.3], videoRef: 'https://cdn.example/v.mp4',
        );

        $publisher = app(TikTokPublisher::class);
        $this->assertSame('v-77', $publisher->startVideoUpload($this->auth(), $draft));
        $this->assertSame('ready', $publisher->videoUploadStatus($this->auth(), 'v-77'));
    }

    public function test_throws_on_non_zero_code(): void
    {
        Http::fake([
            '*/product/202309/products*' => Http::response([
                'code' => 12019022,
                'message' => 'SKU must contain a valid warehouse',
            ]),
        ]);

        try {
            app(TikTokPublisher::class)->createListing($this->auth(), $this->validDraft());
            $this->fail('Expected MarketplaceApiException was not thrown.');
        } catch (MarketplaceApiException $e) {
            $this->assertSame('tiktok', $e->provider);
        }
    }

    private function auth(): AuthContext
    {
        return new AuthContext(
            channelAccountId: 1,
            provider: 'tiktok',
            externalShopId: 'shop-1',
            accessToken: 'tok',
            region: 'VN',
            extra: ['shop_cipher' => 'CIPHER'],
        );
    }

    private function validDraft(): ListingDraftDTO
    {
        return new ListingDraftDTO(
            title: 'Áo thun cotton cao cấp form rộng unisex',
            description: 'Mô tả sản phẩm áo thun cotton',
            categoryId: '600001',
            brandId: null,
            attributes: [],
            media: [new MediaRefDTO('tos://img-uri-1', 'uri')],
            skus: [[
                'seller_sku' => 'S1',
                'price' => 199000,
                'stock' => 10,
                'warehouse_id' => 'WH1',
                'sale_props' => [],
            ]],
            logistics: ['package_weight' => 0.3],
        );
    }
}
