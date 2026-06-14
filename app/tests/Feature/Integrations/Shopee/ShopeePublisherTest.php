<?php

namespace Tests\Feature\Integrations\Shopee;

use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;
use CMBcoreSeller\Integrations\Channels\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Channels\Exceptions\MarketplaceApiException;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeePublisher;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * Feature test cho ShopeePublisher (ProductPublishingConnector) — Http::fake + Sleep::fake,
 * không gọi mạng thật. Khẳng định luồng 2 bước add_item → init_tier_variation (multi-SKU)
 * và việc raise MarketplaceApiException provider-agnostic khi envelope có `error`.
 */
class ShopeePublisherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'integrations.shopee.partner_id' => 123,
            'integrations.shopee.partner_key' => 'sk',
            'integrations.shopee.base_url' => 'https://partner.shopeemobile.com',
        ]);
    }

    private function auth(): AuthContext
    {
        return new AuthContext(
            channelAccountId: 1,
            provider: 'shopee',
            externalShopId: '123',
            accessToken: 'tok',
        );
    }

    /** @param array<int,array<string,mixed>> $skus */
    private function draft(array $skus): ListingDraftDTO
    {
        return new ListingDraftDTO(
            title: 'Áo thun',
            description: 'Mô tả áo thun chất lượng cao',
            categoryId: '100182',
            brandId: null,
            attributes: [],
            media: [new MediaRefDTO('img-1', 'image_id')],
            skus: $skus,
            logistics: [
                'weight' => 0.3,
                'channels' => [
                    ['logistics_channel_id' => 88001, 'enabled' => true, 'fee_type' => 'FIXED_DEFAULT_PRICE'],
                ],
            ],
        );
    }

    public function test_creates_item_then_inits_variation_for_multi_sku(): void
    {
        Sleep::fake();
        Http::fake([
            '*/product/add_item*' => Http::response(['item_id' => 555, 'response' => ['item_id' => 555]]),
            '*/product/init_tier_variation*' => Http::response(['response' => ['model' => [
                ['model_id' => 1, 'tier_index' => [0]],
                ['model_id' => 2, 'tier_index' => [1]],
            ]]]),
        ]);

        $draft = $this->draft([
            ['price' => 100000, 'stock' => 10, 'seller_sku' => 'SKU-S', 'sale_props' => ['size' => 'S']],
            ['price' => 100000, 'stock' => 8, 'seller_sku' => 'SKU-M', 'sale_props' => ['size' => 'M']],
        ]);

        $result = app(ShopeePublisher::class)->createListing($this->auth(), $draft);

        $this->assertSame('555', $result->externalItemId);
        $this->assertArrayHasKey('tier', $result->raw);
    }

    public function test_uploads_video_then_attaches_video_upload_id_to_add_item(): void
    {
        Sleep::fake();
        Http::fake([
            'cdn.example/*' => Http::response('FAKE-VIDEO-BYTES'),
            '*/media_space/init_video_upload*' => Http::response(['response' => ['video_upload_id' => 'vid-1']]),
            '*/media_space/upload_video_part*' => Http::response(['response' => []]),
            '*/media_space/complete_video_upload*' => Http::response(['response' => []]),
            '*/media_space/get_video_upload_result*' => Http::response(['response' => ['status' => 'SUCCEEDED']]),
            '*/product/add_item*' => Http::response(['item_id' => 777, 'response' => ['item_id' => 777]]),
        ]);

        $draft = new ListingDraftDTO(
            title: 'Áo', description: 'Mô tả dài đủ chuẩn', categoryId: '100182', brandId: null, attributes: [],
            media: [new MediaRefDTO('img-1', 'image_id')],
            skus: [['price' => 100000, 'stock' => 5, 'seller_sku' => 'S1', 'sale_props' => []]],
            logistics: ['weight' => 0.3, 'channels' => [['logistics_channel_id' => 88001, 'enabled' => true, 'fee_type' => 'FIXED_DEFAULT_PRICE']]],
            videoRef: 'https://cdn.example/video.mp4',
        );

        $result = app(ShopeePublisher::class)->createListing($this->auth(), $draft);

        $this->assertSame('777', $result->externalItemId);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/product/add_item')
            && (($req->data()['video_upload_id'][0] ?? null) === 'vid-1'));
    }

    public function test_throws_on_error_envelope(): void
    {
        Http::fake([
            '*/product/add_item*' => Http::response(['error' => 'error_param', 'message' => 'Invalid category id']),
        ]);

        $draft = $this->draft([
            ['price' => 100000, 'stock' => 10, 'seller_sku' => 'SKU-S', 'sale_props' => ['size' => 'S']],
        ]);

        try {
            app(ShopeePublisher::class)->createListing($this->auth(), $draft);
            $this->fail('Expected MarketplaceApiException');
        } catch (MarketplaceApiException $e) {
            $this->assertSame('shopee', $e->provider);
        }
    }
}
