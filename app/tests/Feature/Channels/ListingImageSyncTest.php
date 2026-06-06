<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Contracts\ChannelConnector;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaConnector;
use CMBcoreSeller\Integrations\Channels\TikTok\TikTokConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Đồng bộ listing phải có ẢNH cho cả TikTok và Lazada:
 *  - TikTok: `products/search` KHÔNG trả ảnh ⇒ connector phải gọi GetProduct detail lấy `main_images`.
 *  - Lazada: ảnh có thể ở mức product (`images` dạng chuỗi JSON) ⇒ mapper phải fallback.
 * Regression cho lỗi "SKU sàn TikTok/Lazada không có ảnh".
 */
class ListingImageSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'integrations.lazada.app_key' => 'lk', 'integrations.lazada.app_secret' => 'ls',
            'integrations.tiktok.app_key' => 'tk', 'integrations.tiktok.app_secret' => 'ts',
            'integrations.tiktok.base_url' => 'https://open-api.tiktokglobalshop.com',
            'integrations.tiktok.version.product' => '202309',
        ]);
        $reg = app(ChannelRegistry::class);
        $reg->register('lazada', LazadaConnector::class);
        $reg->register('tiktok', TikTokConnector::class);
    }

    private function connector(string $p): ChannelConnector
    {
        return app(ChannelRegistry::class)->for($p);
    }

    private function auth(string $p): AuthContext
    {
        return new AuthContext(channelAccountId: 1, provider: $p, externalShopId: 'SHOP', accessToken: 'at',
            extra: $p === 'tiktok' ? ['shop_cipher' => 'cipher'] : []);
    }

    public function test_lazada_falls_back_to_product_level_image(): void
    {
        // SKU không có `Images`; ảnh ở mức product dưới dạng chuỗi JSON (đúng kiểu Lazada VN hay trả).
        Http::fake(['*/products/get*' => Http::response(['code' => '0', 'data' => [
            'total_products' => '1',
            'products' => [[
                'item_id' => 'P1',
                'attributes' => ['name' => 'Áo thun'],
                'images' => '[ "https://cdn.lzd.test/p1.jpg", "https://cdn.lzd.test/p2.jpg" ]',
                'skus' => [[
                    'SkuId' => 'sku-a', 'ShopSku' => 'shop-a', 'SellerSku' => 'AO-M',
                    'quantity' => 5, 'price' => '100000.00', 'Status' => 'active',
                ]],
            ]],
        ]])]);

        $page = $this->connector('lazada')->fetchListings($this->auth('lazada'));

        $this->assertCount(1, $page->items);
        $this->assertSame('https://cdn.lzd.test/p1.jpg', $page->items[0]->image);
    }

    public function test_tiktok_fetches_image_from_get_product_detail(): void
    {
        Http::fake([
            '*product/202309/products/search*' => Http::response(['code' => 0, 'data' => [
                'products' => [[
                    'id' => 'PID1', 'title' => 'Vớ', 'status' => 'ACTIVATE',
                    'skus' => [['id' => 'SKU1', 'seller_sku' => 'VO-1', 'price' => ['currency' => 'VND', 'sale_price' => '50000'], 'inventory' => [['quantity' => 9]]]],
                    // KHÔNG có main_images (đúng response thật của search)
                ]],
            ]]),
            '*product/202309/products/PID1*' => Http::response(['code' => 0, 'data' => [
                'id' => 'PID1',
                'main_images' => [['thumb_urls' => ['https://cdn.tt.test/PID1.jpg']]],
            ]]),
        ]);

        $page = $this->connector('tiktok')->fetchListings($this->auth('tiktok'));

        $this->assertCount(1, $page->items);
        $this->assertSame('https://cdn.tt.test/PID1.jpg', $page->items[0]->image);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/products/PID1'));
    }
}
