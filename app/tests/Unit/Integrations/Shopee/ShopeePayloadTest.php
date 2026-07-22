<?php

declare(strict_types=1);

namespace Tests\Unit\Integrations\Shopee;

use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;
use CMBcoreSeller\Integrations\Channels\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeProductPayload;
use PHPUnit\Framework\TestCase;

class ShopeePayloadTest extends TestCase
{
    private function makeDraft(array $skus): ListingDraftDTO
    {
        return new ListingDraftDTO(
            title: 'Áo',
            description: 'x',
            categoryId: '100012',
            brandId: null,
            attributes: [],
            media: [new MediaRefDTO('img-1', 'image_id')],
            skus: $skus,
            logistics: [
                'channels' => [
                    [
                        'logistics_channel_id' => 1,
                        'enabled' => true,
                        'fee_type' => 'FIXED_DEFAULT_PRICE',
                    ],
                ],
            ],
        );
    }

    public function test_builds_add_item_body_single_sku(): void
    {
        $draft = $this->makeDraft([
            ['seller_sku' => 'S1', 'price' => 10000, 'stock' => 5, 'sale_props' => []],
        ]);

        $body = ShopeeProductPayload::addItem($draft);

        $this->assertSame(100012, $body['category_id']);
        $this->assertSame('img-1', $body['image']['image_id_list'][0]);
        $this->assertTrue($body['logistic_info'][0]['enabled']);
        // add_item dùng `logistic_id` (không phải logistics_channel_id) + is_free.
        $this->assertSame(1, $body['logistic_info'][0]['logistic_id']);
        $this->assertFalse($body['logistic_info'][0]['is_free']);
        $this->assertSame('NEW', $body['condition']);
        $this->assertSame(10000, $body['original_price']);
        // Tồn kho theo mô hình chính chủ hiện hành: seller_stock: [{stock}].
        $this->assertSame(5, $body['seller_stock'][0]['stock']);
        $this->assertArrayNotHasKey('normal_stock', $body);
    }

    public function test_casts_string_logistics_channel_id_to_int(): void
    {
        // FE lưu id kênh dạng string (Checkbox.Group) — phải ép về int trước khi gửi Shopee,
        // nếu không Go backend từ chối "cannot unmarshal string into ... logistic_id (uint64)".
        $draft = new ListingDraftDTO(
            title: 'Áo', description: 'x', categoryId: '100012', brandId: null,
            attributes: [], media: [new MediaRefDTO('img-1', 'image_id')],
            skus: [['seller_sku' => 'S1', 'price' => 10000, 'stock' => 5, 'sale_props' => []]],
            logistics: [
                'channels' => [['logistics_channel_id' => '5012', 'enabled' => true, 'fee_type' => 'FIXED_DEFAULT_PRICE']],
            ],
        );

        $body = ShopeeProductPayload::addItem($draft);

        $this->assertSame(5012, $body['logistic_info'][0]['logistic_id']);
        $this->assertIsInt($body['logistic_info'][0]['logistic_id']);
    }

    public function test_omits_pre_order_when_not_enabled(): void
    {
        $body = ShopeeProductPayload::addItem($this->makeDraft([
            ['seller_sku' => 'S1', 'price' => 10000, 'stock' => 5, 'sale_props' => []],
        ]));

        $this->assertArrayNotHasKey('pre_order', $body);
    }

    public function test_includes_pre_order_when_enabled(): void
    {
        $draft = new ListingDraftDTO(
            title: 'Áo', description: 'x', categoryId: '100012', brandId: null,
            attributes: [], media: [new MediaRefDTO('img-1', 'image_id')],
            skus: [['seller_sku' => 'S1', 'price' => 10000, 'stock' => 5, 'sale_props' => []]],
            logistics: [
                'channels' => [['logistics_channel_id' => 1, 'enabled' => true, 'fee_type' => 'FIXED_DEFAULT_PRICE']],
                'pre_order' => ['is_pre_order' => true, 'days_to_ship' => 12],
            ],
        );

        $body = ShopeeProductPayload::addItem($draft);

        $this->assertTrue($body['pre_order']['is_pre_order']);
        $this->assertSame(12, $body['pre_order']['days_to_ship']);
    }

    public function test_builds_tier_variation_two_skus(): void
    {
        $draft = $this->makeDraft([
            ['seller_sku' => 'S1', 'price' => 10000, 'stock' => 5, 'sale_props' => ['size' => 'S']],
            ['seller_sku' => 'S2', 'price' => 11000, 'stock' => 3, 'sale_props' => ['size' => 'M']],
        ]);

        $result = ShopeeProductPayload::tierVariation(123, $draft);

        $this->assertSame(123, $result['item_id']);
        $this->assertCount(1, $result['tier_variation']);
        $this->assertCount(2, $result['model']);
        $this->assertSame('size', $result['tier_variation'][0]['name']);
        $this->assertSame([0], $result['model'][0]['tier_index']);
        $this->assertSame([1], $result['model'][1]['tier_index']);
        // Model tồn kho cũng theo seller_stock (chính chủ init_tier_variation).
        $this->assertSame(5, $result['model'][0]['seller_stock'][0]['stock']);
        $this->assertArrayNotHasKey('normal_stock', $result['model'][0]);
    }

    public function test_tier_variation_attaches_first_tier_images_when_all_options_have_image(): void
    {
        $draft = $this->makeDraft([
            ['seller_sku' => 'S1', 'price' => 10000, 'stock' => 5, 'sale_props' => ['size' => 'S'], 'image' => 'img-S'],
            ['seller_sku' => 'S2', 'price' => 11000, 'stock' => 3, 'sale_props' => ['size' => 'M'], 'image' => 'img-M'],
        ]);

        $result = ShopeeProductPayload::tierVariation(123, $draft);

        $opts = $result['tier_variation'][0]['option_list'];
        $this->assertSame('img-S', $opts[0]['image']['image_id']);
        $this->assertSame('img-M', $opts[1]['image']['image_id']);
    }

    public function test_tier_variation_omits_images_when_any_first_tier_option_missing_image(): void
    {
        // Shopee yêu cầu MỌI option tier đầu có ảnh — thiếu 1 ⇒ bỏ hết.
        $draft = $this->makeDraft([
            ['seller_sku' => 'S1', 'price' => 10000, 'stock' => 5, 'sale_props' => ['size' => 'S'], 'image' => 'img-S'],
            ['seller_sku' => 'S2', 'price' => 11000, 'stock' => 3, 'sale_props' => ['size' => 'M']], // không ảnh
        ]);

        $result = ShopeeProductPayload::tierVariation(123, $draft);

        foreach ($result['tier_variation'][0]['option_list'] as $opt) {
            $this->assertArrayNotHasKey('image', $opt);
        }
    }
}
