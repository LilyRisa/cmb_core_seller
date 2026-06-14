<?php

namespace Tests\Unit\Integrations\Shopee;

use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;
use CMBcoreSeller\Integrations\Channels\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeListingValidator;
use Tests\TestCase;

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

    public function test_flags_too_many_images(): void
    {
        $draft = new ListingDraftDTO(
            title: 'Áo thun', description: 'x', categoryId: '100012', brandId: null,
            attributes: [], media: array_map(fn ($i) => new MediaRefDTO("img-$i", 'image_id'), range(1, 10)),
            skus: [['seller_sku' => 'S1', 'price' => 10000, 'stock' => 5, 'sale_props' => []]],
            logistics: ['channels' => [['logistics_channel_id' => 1, 'enabled' => true, 'fee_type' => 'FIXED_DEFAULT_PRICE']]],
        );

        $errors = (new ShopeeListingValidator)->validate($draft);

        $this->assertArrayHasKey('media', $errors);
    }

    public function test_flags_invalid_pre_order_days(): void
    {
        $errors = (new ShopeeListingValidator)->validate($this->draftWithPreOrder(['is_pre_order' => true, 'days_to_ship' => 3]));

        $this->assertArrayHasKey('logistics.pre_order.days_to_ship', $errors);
    }

    public function test_passes_valid_pre_order_days(): void
    {
        $errors = (new ShopeeListingValidator)->validate($this->draftWithPreOrder(['is_pre_order' => true, 'days_to_ship' => 7]));

        $this->assertArrayNotHasKey('logistics.pre_order.days_to_ship', $errors);
    }

    public function test_ignores_pre_order_days_when_disabled(): void
    {
        $errors = (new ShopeeListingValidator)->validate($this->draftWithPreOrder(['is_pre_order' => false]));

        $this->assertArrayNotHasKey('logistics.pre_order.days_to_ship', $errors);
    }

    /** @param array<string,mixed> $preOrder */
    private function draftWithPreOrder(array $preOrder): ListingDraftDTO
    {
        return new ListingDraftDTO(
            title: 'Áo thun', description: 'x', categoryId: '100012', brandId: null,
            attributes: [], media: [new MediaRefDTO('img-1', 'image_id')],
            skus: [['seller_sku' => 'S1', 'price' => 10000, 'stock' => 5, 'sale_props' => []]],
            logistics: [
                'channels' => [['logistics_channel_id' => 1, 'enabled' => true, 'fee_type' => 'FIXED_DEFAULT_PRICE']],
                'pre_order' => $preOrder,
            ],
        );
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
