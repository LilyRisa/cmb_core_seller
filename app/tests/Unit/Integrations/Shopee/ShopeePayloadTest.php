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
        $this->assertSame(10000, $body['original_price']);
        $this->assertSame(5, $body['normal_stock']);
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
    }
}
