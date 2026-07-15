<?php

declare(strict_types=1);

namespace Tests\Unit\Integrations\TikTok;

use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;
use CMBcoreSeller\Integrations\Channels\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Channels\TikTok\TikTokListingValidator;
use Tests\TestCase;

class TikTokValidatorTest extends TestCase
{
    public function test_flags_short_title_missing_warehouse_zero_weight_missing_category(): void
    {
        $draft = new ListingDraftDTO(
            title: 'Áo',
            description: 'desc',
            categoryId: '',
            brandId: null,
            attributes: [],
            media: [new MediaRefDTO('uri-1', 'uri')],
            skus: [[
                'seller_sku' => 'S1',
                'price' => 1000,
                'stock' => 1,
                'sale_props' => [],
                'warehouse_id' => null,
            ]],
            logistics: ['package_weight' => 0],
        );

        $errors = (new TikTokListingValidator)->validate($draft);

        $this->assertArrayHasKey('title', $errors);
        $this->assertArrayHasKey('categoryId', $errors);
        $this->assertArrayHasKey('brandId', $errors);
        $this->assertArrayHasKey('logistics.package_weight', $errors);
        $this->assertArrayHasKey('skus.0.warehouse_id', $errors);
    }

    public function test_passes_valid_vn_draft(): void
    {
        $draft = new ListingDraftDTO(
            title: 'Áo thun cotton nam form rộng cao cấp',
            description: 'desc',
            categoryId: '600001',
            brandId: '700001',
            attributes: [],
            media: [new MediaRefDTO('uri-1', 'uri')],
            skus: [[
                'seller_sku' => 'S1',
                'price' => 1000,
                'stock' => 1,
                'sale_props' => [],
                'warehouse_id' => 'WH1',
            ]],
            logistics: ['package_weight' => 0.5],
        );

        $errors = (new TikTokListingValidator)->validate($draft);

        $this->assertSame([], $errors);
    }

    public function test_title_length_reads_from_config(): void
    {
        config(['integrations.listing_limits.tiktok.title_min_length' => 5]);
        config(['integrations.listing_limits.tiktok.title_max_length' => 10]);

        $draft = new ListingDraftDTO(
            title: 'Áo thun', // 7 ký tự — hợp lệ với 5-10, KHÔNG hợp lệ với mặc định 25-255
            description: 'desc',
            categoryId: '600001',
            brandId: '700001',
            attributes: [],
            media: [new MediaRefDTO('uri-1', 'uri')],
            skus: [['seller_sku' => 'S1', 'price' => 1000, 'stock' => 1, 'sale_props' => [], 'warehouse_id' => 'WH1']],
            logistics: ['package_weight' => 0.5],
        );

        $errors = (new TikTokListingValidator)->validate($draft);

        $this->assertArrayNotHasKey('title', $errors);
    }
}
