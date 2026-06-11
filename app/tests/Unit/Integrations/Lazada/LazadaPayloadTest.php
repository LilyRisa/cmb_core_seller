<?php

declare(strict_types=1);

namespace Tests\Unit\Integrations\Lazada;

use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;
use CMBcoreSeller\Integrations\Channels\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaProductPayload;
use PHPUnit\Framework\TestCase;

class LazadaPayloadTest extends TestCase
{
    private function makeDraft(): ListingDraftDTO
    {
        return new ListingDraftDTO(
            title: 'test',
            description: '<p>desc</p>',
            categoryId: '3',
            brandId: '40516',
            attributes: ['warranty_type' => 'No Warranty'],
            media: [new MediaRefDTO('https://my-live-02.slatic.net/p/a.jpg', 'cdn_url')],
            skus: [[
                'seller_sku' => 'S1',
                'price' => 35,
                'stock' => 3,
                'sale_props' => ['color_family' => 'Green', 'size' => '10'],
                'package_weight' => 0.5,
                'package_dims' => ['length' => 10, 'width' => 10, 'height' => 10],
            ]],
            logistics: [],
            shortDescription: '<ul><li>a</li></ul>',
        );
    }

    public function test_builds_create_product_xml(): void
    {
        $xml = LazadaProductPayload::toXml($this->makeDraft());

        $this->assertStringContainsString('<PrimaryCategory>3</PrimaryCategory>', $xml);
        $this->assertStringContainsString('<brand_id>40516</brand_id>', $xml);
        $this->assertStringContainsString('<SellerSku>S1</SellerSku>', $xml);
        $this->assertStringContainsString('<color_family>Green</color_family>', $xml);
        $this->assertStringContainsString('<package_weight>0.5</package_weight>', $xml);
        $this->assertStringContainsString('<Image>https://my-live-02.slatic.net/p/a.jpg</Image>', $xml);
    }

    public function test_escapes_special_chars(): void
    {
        $draft = new ListingDraftDTO(
            title: 'A & B <x>',
            description: '<p>desc & more</p>',
            categoryId: '3',
            brandId: null,
            attributes: [],
            media: [new MediaRefDTO('https://my-live-02.slatic.net/p/a.jpg', 'cdn_url')],
            skus: [[
                'seller_sku' => 'S2',
                'price' => 10,
                'stock' => 1,
                'sale_props' => [],
                'package_weight' => 0.3,
                'package_dims' => ['length' => 5, 'width' => 5, 'height' => 5],
            ]],
            logistics: [],
        );

        $xml = LazadaProductPayload::toXml($draft);

        $dom = new \DOMDocument;
        $result = $dom->loadXML($xml);

        $this->assertTrue($result !== false, 'XML output must be well-formed');
        $this->assertStringNotContainsString('& B', $xml, 'Raw & must be escaped');
    }
}
