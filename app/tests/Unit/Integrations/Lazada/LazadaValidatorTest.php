<?php

namespace Tests\Unit\Integrations\Lazada;

use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;
use CMBcoreSeller\Integrations\Channels\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaListingValidator;
use Tests\TestCase;

class LazadaValidatorTest extends TestCase
{
    public function test_flags_missing_required_fields(): void
    {
        $draft = new ListingDraftDTO(
            title: '',
            description: '',
            categoryId: '',
            brandId: null,
            attributes: [],
            media: [],
            skus: [[
                'seller_sku' => '',
                'price' => 0,
                'stock' => 0,
                'sale_props' => [],
                'package_weight' => null,
                'package_dims' => [],
            ]],
            logistics: [],
        );

        $errors = (new LazadaListingValidator)->validate($draft);

        $this->assertArrayHasKey('title', $errors);
        $this->assertArrayHasKey('categoryId', $errors);
        $this->assertArrayHasKey('brandId', $errors);
        $this->assertArrayHasKey('media', $errors);
        $this->assertArrayHasKey('skus.0.seller_sku', $errors);
        $this->assertArrayHasKey('skus.0.price', $errors);
        $this->assertArrayHasKey('skus.0.package_weight', $errors);
    }

    public function test_passes_a_valid_single_sku_draft(): void
    {
        $draft = new ListingDraftDTO(
            title: 'Áo',
            description: '<p>x</p>',
            categoryId: '3',
            brandId: '40516',
            attributes: ['warranty_type' => 'No Warranty'],
            media: [new MediaRefDTO('https://my-live-02.slatic.net/p/a.jpg', 'cdn_url')],
            skus: [[
                'seller_sku' => 'S1',
                'price' => 35000,
                'stock' => 3,
                'sale_props' => [],
                'package_weight' => 0.5,
                'package_dims' => ['length' => 10, 'width' => 10, 'height' => 10],
            ]],
            logistics: [],
        );

        $errors = (new LazadaListingValidator)->validate($draft);

        $this->assertSame([], $errors);
    }

    public function test_accepts_brand_id_zero_as_no_brand(): void
    {
        $draft = new ListingDraftDTO(
            title: 'Áo',
            description: '<p>x</p>',
            categoryId: '3',
            brandId: '0',
            attributes: ['warranty_type' => 'No Warranty'],
            media: [new MediaRefDTO('https://my-live-02.slatic.net/p/a.jpg', 'cdn_url')],
            skus: [[
                'seller_sku' => 'S1',
                'price' => 35000,
                'stock' => 3,
                'sale_props' => [],
                'package_weight' => 0.5,
                'package_dims' => ['length' => 10, 'width' => 10, 'height' => 10],
            ]],
            logistics: [],
        );

        $errors = (new LazadaListingValidator)->validate($draft);

        $this->assertArrayNotHasKey('brandId', $errors);
    }
}
