<?php

namespace Tests\Unit\Integrations\Shopee;

use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;
use CMBcoreSeller\Integrations\Channels\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeListingValidator;
use PHPUnit\Framework\TestCase;

class ShopeeValidatorTest extends TestCase
{
    public function test_flags_missing_category_image_and_size_weight(): void
    {
        $draft = new ListingDraftDTO(
            title: 'Áo',
            description: 'x',
            categoryId: '',
            brandId: null,
            attributes: [],
            media: [],
            skus: [[
                'seller_sku' => 'S1',
                'price' => 10000,
                'stock' => 1,
                'sale_props' => [],
            ]],
            logistics: [
                'channels' => [
                    [
                        'logistics_channel_id' => 1,
                        'enabled' => true,
                        'fee_type' => 'SIZE_INPUT',
                    ],
                ],
                'weight' => null,
            ],
        );

        $errors = (new ShopeeListingValidator)->validate($draft);

        $this->assertArrayHasKey('categoryId', $errors);
        $this->assertArrayHasKey('media', $errors);
        $this->assertArrayHasKey('logistics.weight', $errors);
    }

    public function test_passes_valid_draft(): void
    {
        $draft = new ListingDraftDTO(
            title: 'Áo thun',
            description: 'x',
            categoryId: '100012',
            brandId: null,
            attributes: [],
            media: [new MediaRefDTO('img-1', 'image_id')],
            skus: [[
                'seller_sku' => 'S1',
                'price' => 10000,
                'stock' => 5,
                'sale_props' => [],
            ]],
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

        $errors = (new ShopeeListingValidator)->validate($draft);

        $this->assertSame([], $errors);
    }
}
