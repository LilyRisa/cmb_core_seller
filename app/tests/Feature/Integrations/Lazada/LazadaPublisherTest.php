<?php

declare(strict_types=1);

namespace Tests\Feature\Integrations\Lazada;

use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;
use CMBcoreSeller\Integrations\Channels\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Channels\Exceptions\MarketplaceApiException;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaPublisher;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LazadaPublisherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // LazadaClient reads app_key/app_secret at construction time; dev config leaves
        // them null (env commented out) so set them here before the client resolves.
        config([
            'integrations.lazada.app_key' => 'test-key',
            'integrations.lazada.app_secret' => 'test-secret',
            'integrations.lazada.api_base_url' => 'https://api.lazada.vn/rest',
        ]);
    }

    public function test_creates_a_listing_and_returns_item_id_and_sku_map(): void
    {
        Http::fake([
            '*/rest/product/create*' => Http::response([
                'code' => '0',
                'data' => [
                    'item_id' => 3069252927,
                    'sku_list' => [
                        ['shop_sku' => 'X', 'seller_sku' => 'S1', 'sku_id' => 123],
                    ],
                ],
            ]),
        ]);

        $result = app(LazadaPublisher::class)->createListing($this->auth(), $this->validDraft());

        $this->assertSame('3069252927', $result->externalItemId);
        $this->assertSame('123', $result->skuMap['S1']);
        $this->assertSame('PENDING', $result->rawStatus);
    }

    public function test_uploads_video_then_attaches_to_create_payload_when_audit_success(): void
    {
        Http::fake([
            'cdn.example/*' => Http::response('FAKE-VIDEO-BYTES'),
            '*/rest/media/video/block/create*' => Http::response(['code' => '0', 'data' => ['upload_id' => 'u-1']]),
            '*/rest/media/video/block/upload*' => Http::response(['code' => '0', 'data' => ['e_tag' => 'et-0']]),
            '*/rest/media/video/block/commit*' => Http::response(['code' => '0', 'data' => ['video_id' => 'vid-9']]),
            '*/rest/media/video/get*' => Http::response(['code' => '0', 'data' => ['state' => 'AUDIT_SUCCESS']]),
            '*/rest/product/create*' => Http::response(['code' => '0', 'data' => ['item_id' => 999, 'sku_list' => [['seller_sku' => 'S1', 'sku_id' => 1]]]]),
        ]);

        $draft = new ListingDraftDTO(
            title: 'Áo thun cotton', description: 'Mô tả', categoryId: '10000123', brandId: '1234', attributes: [],
            media: [new MediaRefDTO('https://cdn.lazada.vn/img/1.jpg', 'cdn_url')],
            skus: [['seller_sku' => 'S1', 'price' => 199000, 'stock' => 10, 'sale_props' => [], 'package_weight' => 0.3, 'package_dims' => ['length' => 20, 'width' => 15, 'height' => 5]]],
            logistics: [],
            videoRef: 'https://cdn.example/v.mp4',
        );

        $result = app(LazadaPublisher::class)->createListing($this->auth(), $draft);

        $this->assertSame('999', $result->externalItemId);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/product/create')
            && str_contains((string) $r->body(), '<video>vid-9</video>'));
    }

    public function test_video_audit_not_ready_still_creates_product_without_video(): void
    {
        Http::fake([
            'cdn.example/*' => Http::response('FAKE-VIDEO-BYTES'),
            '*/rest/media/video/block/create*' => Http::response(['code' => '0', 'data' => ['upload_id' => 'u-1']]),
            '*/rest/media/video/block/upload*' => Http::response(['code' => '0', 'data' => ['e_tag' => 'et-0']]),
            '*/rest/media/video/block/commit*' => Http::response(['code' => '0', 'data' => ['video_id' => 'vid-9']]),
            '*/rest/media/video/get*' => Http::response(['code' => '0', 'data' => ['state' => 'AUDIT_FAILED']]),
            '*/rest/product/create*' => Http::response(['code' => '0', 'data' => ['item_id' => 999, 'sku_list' => [['seller_sku' => 'S1', 'sku_id' => 1]]]]),
        ]);

        $draft = new ListingDraftDTO(
            title: 'Áo', description: 'Mô tả', categoryId: '10000123', brandId: '1234', attributes: [],
            media: [new MediaRefDTO('https://cdn.lazada.vn/img/1.jpg', 'cdn_url')],
            skus: [['seller_sku' => 'S1', 'price' => 199000, 'stock' => 10, 'sale_props' => [], 'package_weight' => 0.3, 'package_dims' => ['length' => 20, 'width' => 15, 'height' => 5]]],
            logistics: [],
            videoRef: 'https://cdn.example/v.mp4',
        );

        $result = app(LazadaPublisher::class)->createListing($this->auth(), $draft);

        $this->assertSame('999', $result->externalItemId);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/product/create') && ! str_contains((string) $r->body(), '<video>'));
    }

    public function test_throws_marketplace_exception_on_error_code(): void
    {
        Http::fake([
            '*/rest/product/create*' => Http::response([
                'code' => 'IllegalAccessToken',
                'message' => 'token expired',
            ]),
        ]);

        try {
            app(LazadaPublisher::class)->createListing($this->auth(), $this->validDraft());
            $this->fail('Expected MarketplaceApiException was not thrown.');
        } catch (MarketplaceApiException $e) {
            $this->assertSame('lazada', $e->provider);
            $this->assertTrue($e->isRetryable());
        }
    }

    private function auth(): AuthContext
    {
        return new AuthContext(
            channelAccountId: 1,
            provider: 'lazada',
            externalShopId: 'shop-1',
            accessToken: 'tok',
            region: 'VN',
        );
    }

    private function validDraft(): ListingDraftDTO
    {
        return new ListingDraftDTO(
            title: 'Áo thun cotton',
            description: 'Mô tả sản phẩm',
            categoryId: '10000123',
            brandId: '1234',
            attributes: [],
            media: [new MediaRefDTO('https://cdn.lazada.vn/img/1.jpg', 'cdn_url')],
            skus: [[
                'seller_sku' => 'S1',
                'price' => 199000,
                'stock' => 10,
                'sale_props' => [],
                'package_weight' => 0.3,
                'package_dims' => ['length' => 20, 'width' => 15, 'height' => 5],
            ]],
            logistics: [],
        );
    }
}
