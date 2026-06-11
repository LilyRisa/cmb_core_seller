<?php

declare(strict_types=1);

namespace Tests\Unit\Integrations\TikTok;

use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;
use CMBcoreSeller\Integrations\Channels\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Channels\TikTok\TikTokProductPayload;
use PHPUnit\Framework\TestCase;

class TikTokPayloadTest extends TestCase
{
    public function test_builds_create_payload(): void
    {
        $draft = new ListingDraftDTO(
            title: 'Áo thun cotton nam form rộng',
            description: 'd',
            categoryId: '600001',
            brandId: null,
            attributes: [],
            media: [new MediaRefDTO('uri-1', 'uri')],
            skus: [[
                'seller_sku' => 'S1',
                'price' => 199000,
                'stock' => 5,
                'sale_props' => ['size' => 'M'],
                'warehouse_id' => 'WH1',
            ]],
            logistics: ['package_weight' => 0.5],
        );

        $body = TikTokProductPayload::toBody($draft, 'LISTING');

        $this->assertSame('600001', $body['category_id']);
        $this->assertSame('v2', $body['category_version']);
        $this->assertSame('LISTING', $body['save_mode']);
        $this->assertSame('uri-1', $body['main_images'][0]['uri']);
        $this->assertTrue(is_string($body['skus'][0]['price']['amount']));
        $this->assertSame('199000', $body['skus'][0]['price']['amount']);
        $this->assertSame('WH1', $body['skus'][0]['inventory'][0]['warehouse_id']);
        $this->assertSame('size', $body['skus'][0]['sales_attributes'][0]['id']);
        $this->assertArrayNotHasKey('brand_id', $body);
    }
}
