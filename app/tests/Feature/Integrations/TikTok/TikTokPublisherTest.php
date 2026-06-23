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

    public function test_builds_product_attributes_from_flat_draft_attributes(): void
    {
        // Get Attributes 202309: 100107 = select (Loại bảo hành), 200 = text (free), 300 = number.
        Http::fake([
            '*/product/202309/categories/*/attributes*' => Http::response(['code' => 0, 'data' => [
                'attributes' => [
                    ['id' => '100107', 'name' => 'Loại bảo hành', 'type' => 'PRODUCT_PROPERTY', 'is_required' => true,
                        'values' => [['id' => '1000055', 'name' => 'Bảo hành nhà sản xuất'], ['id' => '1000056', 'name' => 'Không bảo hành']]],
                    ['id' => '200', 'name' => 'Xuất xứ', 'type' => 'PRODUCT_PROPERTY', 'is_required' => false, 'values' => []],
                    ['id' => '300', 'name' => 'Công suất', 'type' => 'PRODUCT_PROPERTY', 'is_required' => false,
                        'values' => [], 'value_data_format' => 'POSITIVE_INT_OR_DECIMAL'],
                ],
            ]]),
            '*/product/202309/products*' => Http::response(['code' => 0, 'data' => [
                'product_id' => 'p-9', 'skus' => [['id' => 's1', 'seller_sku' => 'S1']],
            ]]),
        ]);

        $draft = new ListingDraftDTO(
            title: 'Áo thun cotton cao cấp form rộng unisex', description: 'Mô tả', categoryId: '913160', brandId: '700001',
            // attributes PHẲNG: id ngành hàng là khóa, lẫn cả khóa meta (description) phải bị bỏ qua.
            attributes: ['100107' => '1000055', '200' => 'Hà Nội', '300' => 85, 'description' => 'ignore me'],
            media: [new MediaRefDTO('tos://img-uri-1', 'uri')],
            skus: [['seller_sku' => 'S1', 'price' => 199000, 'stock' => 10, 'warehouse_id' => 'WH1', 'sale_props' => []]],
            logistics: ['package_weight' => 0.3],
        );

        app(TikTokPublisher::class)->createListing($this->auth(), $draft);

        Http::assertSent(function ($r) {
            if (! str_contains($r->url(), '/product/202309/products')) {
                return false;
            }
            $pa = $r->data()['product_attributes'] ?? null;
            if (! is_array($pa)) {
                return false;
            }
            $byId = [];
            foreach ($pa as $a) {
                $byId[$a['id']] = $a['values'];
            }

            return ($byId['100107'][0]['id'] ?? null) === '1000055'  // select ⇒ value id
                && ($byId['200'][0]['name'] ?? null) === 'Hà Nội'    // text ⇒ name
                && ($byId['300'][0]['name'] ?? null) === '85'        // number ⇒ name (chuỗi)
                && ! array_key_exists('description', $byId);          // khóa meta bị loại
        });
    }

    public function test_attaches_prepared_video_id_to_product(): void
    {
        Http::fake(['*/product/202309/products*' => Http::response(['code' => 0, 'data' => ['product_id' => 'p-1', 'skus' => [['id' => 's1', 'seller_sku' => 'S1']]]])]);

        $draft = new ListingDraftDTO(
            title: 'Áo thun cotton cao cấp form rộng unisex', description: 'Mô tả', categoryId: '600001', brandId: '700001',
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

    public function test_list_promotions_parses_sku_and_product_level_activity_prices(): void
    {
        Http::fake([
            // Liệt kê hoạt động qua POST /activities/search (GET trả 405). Search chạy 2 lần (ONGOING+NOT_START).
            '*/promotion/202309/activities/search*' => Http::response(['code' => 0, 'data' => [
                'activities' => [['id' => 'ACT1', 'status' => 'ONGOING']],
            ]]),
            // Chi tiết hoạt động — giá KM nằm ở activity_price.amount (chuỗi), KHÔNG phải activity_price_amount.
            '*/promotion/202309/activities/*' => Http::response(['code' => 0, 'data' => [
                'activity_id' => 'ACT1', 'status' => 'ONGOING', 'title' => 'Sale 6.6',
                'begin_time' => 1_700_000_000, 'end_time' => 1_700_100_000,
                'products' => [
                    // Cấp VARIATION: có mảng skus.
                    ['id' => 'P1', 'skus' => [['id' => 'SK1', 'activity_price' => ['amount' => '79000', 'currency' => 'VND']]]],
                    // Cấp PRODUCT: KHÔNG có skus ⇒ khóa theo product_id.
                    ['id' => 'P2', 'activity_price' => ['amount' => '50000', 'currency' => 'VND']],
                ],
            ]]),
        ]);

        $promos = app(TikTokPublisher::class)->listPromotions($this->auth());

        $this->assertCount(1, $promos);
        $this->assertSame('ongoing', $promos[0]->status);
        $items = $promos[0]->items;
        $this->assertSame(['external_product_id' => 'P1', 'external_sku_id' => 'SK1', 'sale_price' => 79000], $items[0]);
        $this->assertSame(['external_product_id' => 'P2', 'external_sku_id' => '', 'sale_price' => 50000], $items[1]);
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
            brandId: '700001',
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
